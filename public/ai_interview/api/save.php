<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();
session_start();
ob_end_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }

$body    = json_decode(file_get_contents('php://input'), true);
$round   = $body['round']   ?? 0;
$answers = $body['answers'] ?? [];

if (!$round || !is_array($answers)) { echo json_encode(['success'=>false,'error'=>'Bad request']); exit; }

if ($round === 'coding') {
    $_SESSION['coding_results'] = $answers;
    $_SESSION['exam_ready']     = true;
} elseif ((int)$round === 1) {
    $_SESSION['round1_answers'] = $answers;
} elseif ((int)$round === 2) {
    $_SESSION['round2_answers'] = $answers;
    $_SESSION['exam_ready']     = true;
}

echo json_encode(['success' => true]);
