<?php
function ai_interview_load_env(): void {
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $loaded = true;
    $GLOBALS['ai_interview_env_path'] = null;

    $envCandidates = [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ai-job-portal' . DIRECTORY_SEPARATOR . '.env',
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ai-job-portal' . DIRECTORY_SEPARATOR . '.env',
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env',
        dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env',
        __DIR__ . DIRECTORY_SEPARATOR . '.env',
    ];

    $envPath = null;
    foreach ($envCandidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            $envPath = $candidate;
            break;
        }
    }

    if ($envPath === null) {
        return;
    }

    $GLOBALS['ai_interview_env_path'] = $envPath;

    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function ai_env(string $key, ?string $default = null): ?string {
    ai_interview_load_env();

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function ai_mask_debug_value(?string $value): array {
    $value = (string) ($value ?? '');
    $length = strlen($value);

    if ($length === 0) {
        return [
            'present' => false,
            'length' => 0,
            'preview' => '',
        ];
    }

    if ($length <= 6) {
        $preview = str_repeat('*', $length);
    } else {
        $preview = substr($value, 0, 3) . str_repeat('*', max($length - 6, 0)) . substr($value, -3);
    }

    return [
        'present' => true,
        'length' => $length,
        'preview' => $preview,
    ];
}

function ai_interview_debug_snapshot(): array {
    ai_interview_load_env();

    return [
        'env_path' => $GLOBALS['ai_interview_env_path'] ?? null,
        'resolved' => [
            'db_host' => DB_HOST,
            'db_name' => DB_NAME,
            'db_user' => ai_mask_debug_value(DB_USER),
            'db_pass' => ai_mask_debug_value(DB_PASS),
            'openai_key' => ai_mask_debug_value(OPENAI_KEY),
            'openai_model' => OPENAI_MODEL,
        ],
        'raw_env_presence' => [
            'AI_INTERVIEW_DB_HOST' => ai_mask_debug_value(ai_env('AI_INTERVIEW_DB_HOST', '')),
            'AI_INTERVIEW_DB_NAME' => ai_mask_debug_value(ai_env('AI_INTERVIEW_DB_NAME', '')),
            'AI_INTERVIEW_DB_USER' => ai_mask_debug_value(ai_env('AI_INTERVIEW_DB_USER', '')),
            'AI_INTERVIEW_DB_PASS' => ai_mask_debug_value(ai_env('AI_INTERVIEW_DB_PASS', '')),
            'AI_INTERVIEW_OPENAI_API_KEY' => ai_mask_debug_value(ai_env('AI_INTERVIEW_OPENAI_API_KEY', '')),
            'database.default.hostname' => ai_mask_debug_value(ai_env('database.default.hostname', '')),
            'database.default.database' => ai_mask_debug_value(ai_env('database.default.database', '')),
            'database.default.username' => ai_mask_debug_value(ai_env('database.default.username', '')),
            'database.default.password' => ai_mask_debug_value(ai_env('database.default.password', '')),
            'OPENAI_API_KEY' => ai_mask_debug_value(ai_env('OPENAI_API_KEY', '')),
        ],
    ];
}

define('OPENAI_KEY',   ai_env('AI_INTERVIEW_OPENAI_API_KEY', ai_env('OPENAI_API_KEY', '')));
define('OPENAI_MODEL', ai_env('OPENAI_MODEL', 'gpt-4o'));
define('APP_NAME',     'NexusAI Interview');
define('Q_TIMEOUT',    90);

define('DB_HOST', ai_env('AI_INTERVIEW_DB_HOST', ai_env('database.default.hostname', ai_env('DB_HOST', ''))));
define('DB_USER', ai_env('AI_INTERVIEW_DB_USER', ai_env('database.default.username', ai_env('DB_USER', ''))));
define('DB_PASS', ai_env('AI_INTERVIEW_DB_PASS', ai_env('database.default.password', ai_env('DB_PASS', ''))));
define('DB_NAME', ai_env('AI_INTERVIEW_DB_NAME', ai_env('database.default.database', ai_env('DB_NAME', ''))));
 
function db_connect(): ?mysqli {
    if (DB_HOST === '' || DB_USER === '' || DB_NAME === '') {
        return null;
    }

    $previousReportMode = mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (mysqli_sql_exception $e) {
        mysqli_report($previousReportMode);
        return null;
    }

    mysqli_report($previousReportMode);
    if ($conn->connect_error) {
        return null;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function load_questions_from_db(string $round, int $limit = 30): array {
    $conn = db_connect();
    if (!$conn) {
        return [];
    }

    // Fetch more than needed so we can skip corrupt/incomplete rows and still hit $limit.
    $fetchLimit = max($limit * 5, $limit);
    $stmt = $conn->prepare(
        "SELECT id, round, skill, question, option_a, option_b, option_c, option_d, correct_answer 
         FROM ai_interview_questions 
         WHERE round = ? 
         ORDER BY RAND() 
         LIMIT ?"
    );
    if (!$stmt) {
        $conn->close();
        return [];
    }

    $stmt->bind_param('si', $round, $fetchLimit);
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = [];

    while ($row = $result->fetch_assoc()) {
        $options = array_values(array_filter([
            $row['option_a'] ?? null,
            $row['option_b'] ?? null,
            $row['option_c'] ?? null,
            $row['option_d'] ?? null,
        ], fn($option) => $option !== null && $option !== ''));

        // Skip broken rows (these cause "invisible options" in the UI).
        if (empty($row['question']) || count($options) < 4) {
            continue;
        }

        $correct = strtoupper(trim($row['correct_answer'] ?? ''));
        $correctIndex = match($correct) {
            'A' => 0,
            'B' => 1,
            'C' => 2,
            'D' => 3,
            default => 0,
        };

        $questions[] = [
            'id' => (int)$row['id'],
            'type' => 'mcq',
            'category' => $row['skill'] ? $row['skill'] : ucfirst($row['round']),
            'question' => $row['question'] ?? '',
            'options' => array_slice($options, 0, 4),
            'correct' => $correctIndex,
            'explanation' => '',
        ];

        if (count($questions) >= $limit) {
            break;
        }
    }

    $stmt->close();
    $conn->close();

    shuffle($questions);
    return $questions;
}

/* Returns parsed JSON — used for question generation */
function openai_chat(string $prompt, int $timeout = 120): ?array {
    if (OPENAI_KEY === '') {
        return null;
    }

    $payload = json_encode([
        'model'           => OPENAI_MODEL,
        'messages'        => [['role' => 'user', 'content' => $prompt]],
        'temperature'     => 0.7,
        'response_format' => ['type' => 'json_object'],
        'max_tokens'      => 8000,
    ]);
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json','Authorization: Bearer '.OPENAI_KEY],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $res = curl_exec($ch); $err = curl_error($ch);
    if ($err || !$res) return null;
    $data = json_decode($res, true);
    if (empty($data['choices'][0]['message']['content'])) return null;
    return json_decode($data['choices'][0]['message']['content'], true);
}

/* Returns plain text — used for conversational interview chat */
function openai_chat_messages(array $messages, int $timeout = 60): ?string {
    if (OPENAI_KEY === '') {
        return null;
    }

    $payload = json_encode([
        'model'       => OPENAI_MODEL,
        'messages'    => $messages,
        'temperature' => 0.75,
        'max_tokens'  => 600,
    ]);
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json','Authorization: Bearer '.OPENAI_KEY],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $res = curl_exec($ch); $err = curl_error($ch);
    if ($err || !$res) return null;
    $data = json_decode($res, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

/* Extract readable text from a PDF binary (no pdftotext needed) */
function extractPdfText(string $path): string {
    $content = @file_get_contents($path);
    if (!$content) return '';
    // Extract readable ASCII strings (>4 chars) from PDF binary
    preg_match_all('/[^\x00-\x1F\x7F-\xFF]{4,}/', $content, $m);
    $lines = array_filter($m[0], fn($l) => preg_match('/[a-zA-Z]{3,}/', $l));
    $text  = implode(' ', array_slice($lines, 0, 500));
    // Clean up PDF artefacts
    $text  = preg_replace('/\s+/', ' ', $text);
    return trim(substr($text, 0, 4000));
}
