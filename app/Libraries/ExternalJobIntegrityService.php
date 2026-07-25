<?php

namespace App\Libraries;

class ExternalJobIntegrityService
{
    private const FAILURE_LIMIT = 2;

    /**
     * Deactivate duplicates/expired rows and check a bounded number of URLs.
     *
     * @return array<string, int>
     */
    public function maintain(int $checkLimit = 100): array
    {
        $db = \Config\Database::connect();
        $stats = ['duplicates' => 0, 'expired' => 0, 'checked' => 0, 'unreachable' => 0];
        $checkLimit = max(0, min(1000, $checkLimit));

        if ($db->tableExists('mnc_external_jobs')) {
            $rows = $db->table('mnc_external_jobs')->where('is_active', 1)->orderBy('last_sync_at', 'DESC')->get()->getResultArray();
            $stats = $this->maintainRows($rows, 'mnc_external_jobs', 'is_active', 0, 'apply_url', 'company_name', 'posted_at_raw', 'last_sync_at', $checkLimit, $stats);
        }

        if ($db->tableExists('jobs') && $db->fieldExists('is_external', 'jobs')) {
            $rows = $db->table('jobs')->where('is_external', 1)->where('status', 'open')->orderBy('created_at', 'DESC')->get()->getResultArray();
            $remaining = max(0, $checkLimit - $stats['checked']);
            $stats = $this->maintainRows($rows, 'jobs', 'status', 'closed', 'external_apply_url', 'company', null, 'created_at', $remaining, $stats);
        }

        return $stats;
    }

    private function maintainRows(
        array $rows,
        string $table,
        string $activeField,
        $inactiveValue,
        string $urlField,
        string $companyField,
        ?string $postedField,
        string $freshnessField,
        int $checkLimit,
        array $stats
    ): array {
        $db = \Config\Database::connect();
        $seen = [];
        $checkedHere = 0;
        $fields = $db->getFieldNames($table);
        $hasIntegrityFields = in_array('external_url_hash', $fields, true);

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $url = trim((string) ($row[$urlField] ?? ''));
            $hash = ExternalJobUrl::hash($url);
            $companyKey = preg_replace('/[^a-z0-9]+/', '', strtolower((string) ($row[$companyField] ?? ''))) ?? '';
            $duplicateKey = $companyKey . ':' . $hash;

            $isExpired = $postedField !== null && ExternalJobUrl::postedAtIsExpired($row[$postedField] ?? null);
            if ($table === 'mnc_external_jobs') {
                $syncedAt = strtotime((string) ($row[$freshnessField] ?? ''));
                $isExpired = $isExpired || ($syncedAt !== false && $syncedAt < strtotime('-30 days'));
            } elseif (!empty($row['application_deadline'])) {
                $isExpired = strtotime((string) $row['application_deadline']) < strtotime('today');
            }

            if ($hash === '' || $isExpired) {
                $this->deactivate($table, $id, $activeField, $inactiveValue, $hasIntegrityFields ? 'expired' : null);
                $stats['expired']++;
                continue;
            }

            if (isset($seen[$duplicateKey])) {
                $this->deactivate($table, $id, $activeField, $inactiveValue, $hasIntegrityFields ? 'duplicate' : null);
                $stats['duplicates']++;
                continue;
            }
            $seen[$duplicateKey] = $id;

            if ($hasIntegrityFields) {
                $db->table($table)->where('id', $id)->update(['external_url_hash' => $hash]);
            }

            if (!$hasIntegrityFields || $checkedHere >= $checkLimit || !$this->needsCheck($row)) {
                continue;
            }

            $checkedHere++;
            $stats['checked']++;
            $result = $this->checkUrl($url);
            $failures = $result['reachable']
                ? 0
                : ($result['count_failure']
                    ? ((int) ($row['external_failure_count'] ?? 0) + 1)
                    : (int) ($row['external_failure_count'] ?? 0));
            $update = [
                'external_last_checked_at' => date('Y-m-d H:i:s'),
                'external_failure_count' => $failures,
                'external_validation_status' => $result['status'],
            ];
            $db->table($table)->where('id', $id)->update($update);

            if (
                !$result['reachable']
                && ($result['definitive'] || ($result['count_failure'] && $failures >= self::FAILURE_LIMIT))
            ) {
                $this->deactivate($table, $id, $activeField, $inactiveValue, 'unreachable');
                $stats['unreachable']++;
            }
        }

        return $stats;
    }

    private function needsCheck(array $row): bool
    {
        $lastChecked = strtotime((string) ($row['external_last_checked_at'] ?? ''));
        return $lastChecked === false || $lastChecked < strtotime('-24 hours');
    }

    /** @return array{reachable:bool, definitive:bool, count_failure:bool, status:string} */
    private function checkUrl(string $url): array
    {
        if (ExternalJobUrl::canonicalize($url) === '') {
            return ['reachable' => false, 'definitive' => true, 'count_failure' => true, 'status' => 'invalid_url'];
        }

        $request = static function (bool $headOnly) use ($url): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => $headOnly,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'HireMatrix Job Link Validator/1.0',
            CURLOPT_SSL_VERIFYPEER => ENVIRONMENT !== 'development',
            ]);
            if (!$headOnly) {
                curl_setopt($ch, CURLOPT_RANGE, '0-1023');
            }
            curl_exec($ch);
            $result = [
                'code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
                'error' => curl_errno($ch),
            ];
            curl_close($ch);
            return $result;
        };

        $result = $request(true);
        $code = $result['code'];
        $error = $result['error'];
        $reachable = ($code >= 200 && $code < 400) || in_array($code, [401, 403, 405, 429], true);
        if ($reachable) {
            return ['reachable' => true, 'definitive' => false, 'count_failure' => false, 'status' => 'reachable'];
        }

        // Some ATS sites do not implement HEAD correctly. Confirm an apparent
        // dead link with a tiny ranged GET before changing job visibility.
        if ($error === 0 && in_array($code, [404, 410], true)) {
            $result = $request(false);
            $code = $result['code'];
            $error = $result['error'];
            $reachable = ($code >= 200 && $code < 400) || in_array($code, [401, 403, 405, 429], true);
            if ($reachable) {
                return ['reachable' => true, 'definitive' => false, 'count_failure' => false, 'status' => 'reachable'];
            }
        }

        return [
            'reachable' => false,
            'definitive' => in_array($code, [404, 410], true),
            // DNS, connection, and timeout errors indicate validator/network
            // health, not job health. Record them, but never penalize the job.
            'count_failure' => $error === 0 && $code > 0,
            'status' => $error !== 0 ? 'check_failed' : 'http_' . ($code ?: 0),
        ];
    }

    private function deactivate(string $table, int $id, string $activeField, $inactiveValue, ?string $reason): void
    {
        $data = [$activeField => $inactiveValue];
        if ($reason !== null) {
            $data['external_validation_status'] = $reason;
        }
        \Config\Database::connect()->table($table)->where('id', $id)->update($data);
    }
}
