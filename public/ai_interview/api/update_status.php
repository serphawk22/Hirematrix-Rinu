<?php
session_start();
require_once __DIR__ . '/../config.php';
$conn = db_connect();

if ($conn->connect_error) {
    die(
        'Database Connection Failed: ' .
        $conn->connect_error
    );
}

$data = json_decode(file_get_contents('php://input'), true);

$candidate_id = $data['candidate_id'] ?? 0;
$job_id = $data['job_id'] ?? 0;

if ($candidate_id > 0 && $job_id > 0) {
    $stmt = $conn->prepare("
        UPDATE applications
        SET status = 'ai_interview_completed'
        WHERE candidate_id = ? AND job_id = ?
          AND status IN ('applied', 'pending', 'ai_interview_started', 'ai_evaluated', 'ai_interview_completed')
    ");

    $stmt->bind_param('ii', $candidate_id, $job_id);
    $success = $stmt->execute();

    if ($success) {
        $verify = $conn->prepare("
            SELECT status
            FROM applications
            WHERE candidate_id = ? AND job_id = ?
            LIMIT 1
        ");
        $verify->bind_param('ii', $candidate_id, $job_id);
        $verify->execute();
        $result = $verify->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        echo json_encode([
            'success' => !empty($row) && ($row['status'] ?? '') === 'ai_interview_completed',
        ]);
        exit;
    }

    echo json_encode(['success' => false]);
    exit;
}

echo json_encode(['success' => false]);
