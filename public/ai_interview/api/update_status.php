<?php
session_start();
require_once __DIR__ . '/../config.php';
$conn = db_connect();

if ($conn->connect_error) {

    die(
        "Database Connection Failed: " .
        $conn->connect_error
    );
}

$data = json_decode(file_get_contents("php://input"), true);

$candidate_id = $data['candidate_id'] ?? 0;
$job_id       = $data['job_id'] ?? 0;

if ($candidate_id > 0 && $job_id > 0) {

    $stmt = $conn->prepare("
        UPDATE applications 
        SET status = 'ai_interview_completed' 
        WHERE candidate_id = ? AND job_id = ?
    ");

    $stmt->bind_param("ii", $candidate_id, $job_id); // ✅ both conditions
    $stmt->execute();

    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["success" => false]);