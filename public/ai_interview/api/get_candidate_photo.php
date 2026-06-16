<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

$db_host = "localhost";
$db_name = "ai_job_portal1";   // 👈 Change this
$db_user = "root";   // 👈 Change this
$db_pass = ""; // 👈 Change this

/*
|--------------------------------------------------------------------------
| PDO CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDATE CANDIDATE ID
|--------------------------------------------------------------------------
*/
$candidate_id = isset($_GET['candidate_id']) ? intval($_GET['candidate_id']) : 0;

if ($candidate_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid candidate_id'
    ]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT profile_photo FROM candidate_profiles WHERE user_id = ?"
);
$stmt->execute([$candidate_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['profile_photo'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No profile photo on file'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| RESOLVE FILESYSTEM PATH
|--------------------------------------------------------------------------
| profile_photo is stored as a relative web path (e.g. "uploads/profiles/123.jpg").
| Adjust BASE_PATH to wherever your uploads actually live on disk.
*/
/*
|--------------------------------------------------------------------------
| RESOLVE FILESYSTEM PATH
|--------------------------------------------------------------------------
*/

$relativePath = trim($row['profile_photo']);
$relativePath = str_replace('\\', '/', $relativePath);

$filePath = "C:/xampp/htdocs/ai-job-portal/public/" . ltrim($relativePath, '/');

if (!file_exists($filePath)) {

    echo json_encode([
        'success' => false,
        'message' => 'Profile photo file not found',
        'path' => $filePath
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| READ IMAGE
|--------------------------------------------------------------------------
*/

$mime = mime_content_type($filePath);

$allowedMimes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

if (!in_array($mime, $allowedMimes, true)) {

    echo json_encode([
        'success' => false,
        'message' => 'Unsupported image type',
        'mime' => $mime
    ]);

    exit;
}

$data = base64_encode(file_get_contents($filePath));

echo json_encode([
    'success' => true,
    'image' => "data:$mime;base64,$data"
]);

exit;