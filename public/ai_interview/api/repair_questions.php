<?php
// One-time DB repair utility: ensures every MCQ row has 4 non-empty options and a valid correct answer.
// Access in browser (local): /ai_interview/api/repair_questions.php?round=technical

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/config.php';

$round = $_GET['round'] ?? 'technical';
$round = in_array($round, ['aptitude', 'technical'], true) ? $round : 'technical';

$conn = db_connect();
if (!$conn) {
  http_response_code(500);
  echo "DB connection failed";
  exit;
}

// Find rows with missing question text or missing any option.
$sql = "
  SELECT id, question, option_a, option_b, option_c, option_d, correct_answer
  FROM ai_interview_questions
  WHERE round = ?
    AND (
      question IS NULL OR TRIM(question) = '' OR
      option_a IS NULL OR TRIM(option_a) = '' OR
      option_b IS NULL OR TRIM(option_b) = '' OR
      option_c IS NULL OR TRIM(option_c) = '' OR
      option_d IS NULL OR TRIM(option_d) = '' OR
      correct_answer IS NULL OR TRIM(correct_answer) = ''
    )
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $round);
$stmt->execute();
$res = $stmt->get_result();

$fixStmt = $conn->prepare("
  UPDATE ai_interview_questions
  SET
    question = ?,
    option_a = ?,
    option_b = ?,
    option_c = ?,
    option_d = ?,
    correct_answer = ?
  WHERE id = ?
");

$fixed = 0;
while ($row = $res->fetch_assoc()) {
  $id = (int)$row['id'];

  $q  = trim((string)($row['question'] ?? ''));
  $a  = trim((string)($row['option_a'] ?? ''));
  $b  = trim((string)($row['option_b'] ?? ''));
  $c  = trim((string)($row['option_c'] ?? ''));
  $d  = trim((string)($row['option_d'] ?? ''));
  $ca = strtoupper(trim((string)($row['correct_answer'] ?? 'A')));

  // Keep existing text when present; otherwise inject a visible placeholder so the UI never "goes blank".
  if ($q === '') $q = "Placeholder question (ID: {$id}) - please edit this question in DB.";
  if ($a === '') $a = 'Option A';
  if ($b === '') $b = 'Option B';
  if ($c === '') $c = 'Option C';
  if ($d === '') $d = 'Option D';
  if (!in_array($ca, ['A','B','C','D'], true)) $ca = 'A';

  $fixStmt->bind_param('ssssssi', $q, $a, $b, $c, $d, $ca, $id);
  $fixStmt->execute();
  $fixed++;
}

$stmt->close();
$fixStmt->close();
$conn->close();

header('Content-Type: text/plain; charset=utf-8');
echo "Round: {$round}\n";
echo "Rows repaired: {$fixed}\n";
echo "Done.\n";

