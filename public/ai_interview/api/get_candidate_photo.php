<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once dirname(__DIR__) . '/config.php';

try {
    if (DB_HOST === '' || DB_USER === '' || DB_NAME === '') {
        throw new PDOException("Database configuration is missing.");
    }

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage(),
    ]);
    exit;
}

$candidate_id = isset($_GET['candidate_id']) ? intval($_GET['candidate_id']) : 0;

if ($candidate_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid candidate_id',
    ]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT profile_photo FROM candidate_profiles WHERE user_id = ?"
);
$stmt->execute([$candidate_id]);
$row = $stmt->fetch();

if (!$row || empty($row['profile_photo'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No profile photo on file',
    ]);
    exit;
}

$relativePath = trim($row['profile_photo']);
$relativePath = str_replace('\\', '/', $relativePath);

$readImageFromUrl = static function (string $url): ?array {
    $imageData = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $imageData = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($imageData === false || $httpCode >= 400) {
            $imageData = null;
        }
    }

    if ($imageData === null) {
        $imageData = @file_get_contents($url);
        if ($imageData === false) {
            return null;
        }
    }

    $imageInfo = @getimagesizefromstring($imageData);
    $mime = $imageInfo['mime'] ?? null;
    if (!$mime) {
        return null;
    }

    return [
        'mime' => $mime,
        'data' => $imageData,
    ];
};

if (preg_match('/^https?:\/\//i', $relativePath)) {
    $remoteImage = $readImageFromUrl($relativePath);
    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    if ($remoteImage === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Profile photo URL could not be read',
            'path' => $relativePath,
        ]);
        exit;
    }

    $mime = $remoteImage['mime'];
    if (!in_array($mime, $allowedMimes, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Unsupported image type',
            'mime' => $mime,
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'image' => "data:$mime;base64," . base64_encode($remoteImage['data']),
    ]);
    exit;
}

$relativeFilePath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
$envPath = $GLOBALS['ai_interview_env_path'] ?? null;
$fileCandidates = [];

if (is_string($envPath) && $envPath !== '') {
    $fileCandidates[] = dirname($envPath) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativeFilePath;
}

$fileCandidates = array_merge($fileCandidates, [
    dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativeFilePath,
    dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativeFilePath,
    dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ai-job-portal' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativeFilePath,
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ai-job-portal' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativeFilePath,
    dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'ai-job-portal' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativeFilePath,
]);

$fileCandidates = array_values(array_unique($fileCandidates));

$filePath = null;
foreach ($fileCandidates as $candidatePath) {
    if (is_file($candidatePath)) {
        $filePath = $candidatePath;
        break;
    }
}

if ($filePath === null) {
    $baseUrl = rtrim((string) ai_env('app.baseURL', ''), '/');
    $urlCandidates = [];

    if ($baseUrl !== '') {
        $urlCandidates[] = $baseUrl . '/' . ltrim($relativePath, '/');
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $urlCandidates[] = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/ai-job-portal/public/' . ltrim($relativePath, '/');
    }

    $urlCandidates = array_values(array_unique($urlCandidates));
    foreach ($urlCandidates as $candidateUrl) {
        $remoteImage = $readImageFromUrl($candidateUrl);
        if ($remoteImage !== null) {
            $mime = $remoteImage['mime'];
            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ];

            if (!in_array($mime, $allowedMimes, true)) {
                continue;
            }

            echo json_encode([
                'success' => true,
                'image' => "data:$mime;base64," . base64_encode($remoteImage['data']),
            ]);
            exit;
        }
    }

    echo json_encode([
        'success' => false,
        'message' => 'Profile photo file not found',
        'path' => $relativePath,
        'checked_paths' => $fileCandidates,
        'checked_urls' => $urlCandidates,
    ]);
    exit;
}

$mime = mime_content_type($filePath);
$allowedMimes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
];

if (!in_array($mime, $allowedMimes, true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported image type',
        'mime' => $mime,
    ]);
    exit;
}

$data = base64_encode(file_get_contents($filePath));

echo json_encode([
    'success' => true,
    'image' => "data:$mime;base64,$data",
]);

exit;
