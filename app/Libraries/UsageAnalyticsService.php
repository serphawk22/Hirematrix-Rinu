<?php

namespace App\Libraries;

class UsageAnalyticsService
{
    private static ?array $adminApiColumns = null;

    public function captureFirstPageAfterLogin(string $path): void
    {
        try {
            $session = session();
            if (!$session->get('logged_in') || (int) $session->get('login_perf_pending') !== 1) {
                return;
            }

            $loginStartedAtMs = (int) $session->get('login_started_at_ms');
            if ($loginStartedAtMs <= 0) {
                $this->clearLoginPerfSession($session);
                return;
            }

            $nowMs = (int) round(microtime(true) * 1000);
            $durationMs = max(0, $nowMs - $loginStartedAtMs);
            $now = date('Y-m-d H:i:s');
            $loginAt = (string) ($session->get('login_at') ?: $now);

            $db = \Config\Database::connect();
            if (!$db->tableExists('user_login_performance_logs')) {
                $this->clearLoginPerfSession($session);
                return;
            }

            $db->table('user_login_performance_logs')->insert([
                'user_id' => (int) ($session->get('user_id') ?: 0) ?: null,
                'user_email' => (string) ($session->get('user_email') ?: ''),
                'user_role' => (string) ($session->get('role') ?: ''),
                'login_at' => $loginAt,
                'first_page_path' => $path,
                'first_page_loaded_at' => $now,
                'duration_ms' => $durationMs,
            ]);

            $this->clearLoginPerfSession($session);
        } catch (\Throwable $e) {
            log_message('error', 'UsageAnalyticsService login perf capture failed: ' . $e->getMessage());
        }
    }

    public function logOpenAiUsage(array $payload, string $endpoint, ?string $modelOverride = null): void
    {
        try {
            $usage = (array) ($payload['usage'] ?? []);
            $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
            $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
            $totalTokens = (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens));
            if ($totalTokens <= 0) {
                return;
            }

            $model = trim((string) ($modelOverride ?: ($payload['model'] ?? '')));
            $estimatedCost = $this->estimateOpenAiCost($model, $promptTokens, $completionTokens);
            $this->insertApiUsage([
                'provider' => 'openai',
                'endpoint' => $endpoint,
                'model' => $model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'usage_units' => $totalTokens,
                'estimated_cost_usd' => $estimatedCost,
                'is_success' => 1,
                'http_status_code' => 200,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'UsageAnalyticsService API usage capture failed: ' . $e->getMessage());
        }
    }

    public function logExternalApiUsage(
        string $provider,
        string $endpoint,
        string $operationKey,
        ?int $httpStatusCode = null,
        ?int $latencyMs = null,
        int $usageUnits = 1,
        ?bool $isSuccess = null
    ): void {
        try {
            $provider = strtolower(trim($provider));
            $endpoint = trim($endpoint);
            $operationKey = trim($operationKey);
            $usageUnits = max(1, $usageUnits);
            $success = $isSuccess ?? ($httpStatusCode !== null ? ($httpStatusCode >= 200 && $httpStatusCode < 400) : false);

            $estimatedCost = $this->estimateExternalCost($provider, $operationKey, $usageUnits);

            $this->insertApiUsage([
                'provider' => $provider !== '' ? $provider : 'external',
                'endpoint' => $endpoint,
                'model' => $operationKey,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'usage_units' => $usageUnits,
                'http_status_code' => $httpStatusCode,
                'latency_ms' => $latencyMs,
                'is_success' => $success ? 1 : 0,
                'estimated_cost_usd' => $estimatedCost,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'UsageAnalyticsService external API usage capture failed: ' . $e->getMessage());
        }
    }

    private function insertApiUsage(array $payload): void
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('admin_api_usage_logs')) {
            return;
        }

        $columns = $this->getAdminApiUsageColumns($db);
        $row = [
            'user_id' => null,
            'user_email' => null,
            'user_role' => null,
            'provider' => (string) ($payload['provider'] ?? 'external'),
            'endpoint' => (string) ($payload['endpoint'] ?? ''),
            'model' => (string) ($payload['model'] ?? ''),
            'prompt_tokens' => (int) ($payload['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($payload['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($payload['total_tokens'] ?? 0),
            'estimated_cost_usd' => (float) ($payload['estimated_cost_usd'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $session = session();
            $uid = (int) ($session->get('user_id') ?: 0);
            $row['user_id'] = $uid > 0 ? $uid : null;
            $row['user_email'] = (string) ($session->get('user_email') ?: '');
            $row['user_role'] = (string) ($session->get('role') ?: '');
        } catch (\Throwable $e) {
            // Ignore session context errors.
        }

        if (isset($columns['usage_units'])) {
            $row['usage_units'] = max(1, (int) ($payload['usage_units'] ?? 1));
        }
        if (isset($columns['http_status_code'])) {
            $row['http_status_code'] = isset($payload['http_status_code']) ? (int) $payload['http_status_code'] : null;
        }
        if (isset($columns['latency_ms'])) {
            $row['latency_ms'] = isset($payload['latency_ms']) ? max(0, (int) $payload['latency_ms']) : null;
        }
        if (isset($columns['is_success'])) {
            $row['is_success'] = (int) ($payload['is_success'] ?? 1) === 1 ? 1 : 0;
        }

        $db->table('admin_api_usage_logs')->insert($row);
    }

    private function getAdminApiUsageColumns($db): array
    {
        if (self::$adminApiColumns !== null) {
            return self::$adminApiColumns;
        }

        $fields = $db->getFieldData('admin_api_usage_logs');
        $columns = [];
        foreach ($fields as $field) {
            $columns[strtolower((string) $field->name)] = true;
        }

        self::$adminApiColumns = $columns;
        return $columns;
    }

    private function clearLoginPerfSession($session): void
    {
        $session->remove(['login_perf_pending', 'login_started_at_ms', 'login_at']);
    }

    private function estimateOpenAiCost(string $model, int $promptTokens, int $completionTokens): float
    {
        $modelKey = strtolower(trim($model));
        $defaultInput = (float) (env('analytics.openaiDefaultInputCostPer1k') ?? 0.00015);
        $defaultOutput = (float) (env('analytics.openaiDefaultOutputCostPer1k') ?? 0.00060);

        $inputRate = $defaultInput;
        $outputRate = $defaultOutput;

        if (str_contains($modelKey, 'gpt-4o-mini')) {
            $inputRate = (float) (env('analytics.openaiGpt4oMiniInputCostPer1k') ?? $defaultInput);
            $outputRate = (float) (env('analytics.openaiGpt4oMiniOutputCostPer1k') ?? $defaultOutput);
        }

        $promptCost = ($promptTokens / 1000) * max(0, $inputRate);
        $completionCost = ($completionTokens / 1000) * max(0, $outputRate);

        return round($promptCost + $completionCost, 6);
    }

    private function estimateExternalCost(string $provider, string $operationKey, int $usageUnits): float
    {
        $provider = strtolower(trim($provider));
        $operationKey = strtolower(trim($operationKey));
        $usageUnits = max(1, $usageUnits);

        $specificRate = env('analytics.apiCost.' . $provider . '.' . $operationKey);
        $providerDefaultRate = env('analytics.apiCost.' . $provider . '.default');
        $globalDefaultRate = env('analytics.apiCost.default');

        $rate = $specificRate !== null
            ? (float) $specificRate
            : ($providerDefaultRate !== null ? (float) $providerDefaultRate : ($globalDefaultRate !== null ? (float) $globalDefaultRate : 0.0));

        return round(max(0, $rate) * $usageUnits, 6);
    }
}
