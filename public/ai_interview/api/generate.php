<?php
// Prevent PHP errors from polluting JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

session_start();

// Reliable path regardless of how PHP is invoked
require_once dirname(__DIR__) . '/config.php';

// Discard any stray output so far, then set JSON header
ob_end_clean();
header('Content-Type: application/json');

if (empty($_SESSION['candidate'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please restart.']);
    exit;
}

$round    = (int)($_GET['round'] ?? 1);
$cand     = $_SESSION['candidate'];
$position = $cand['position'];
$resume   = substr($cand['resume'], 0, 2000);
$exp      = $cand['experience'];

// ── ROUND 1: APTITUDE (30 MCQ) ───────────────────────────────────────────────
if ($round === 1) {
    if (!empty($_SESSION['round1_questions'])) {
        echo json_encode(['success' => true, 'cached' => true]);
        exit;
    }

    $dbQuestions = load_questions_from_db('aptitude', 30);
    if (count($dbQuestions) === 30) {
        $_SESSION['round1_questions'] = $dbQuestions;
        echo json_encode(['success' => true, 'source' => 'db', 'count' => 30]);
        exit;
    }

    $prompt = <<<PROMPT
You are an expert psychometric test designer. Generate exactly 30 aptitude MCQ questions for a candidate applying for "{$position}" ({$exp} level).

Distribute as:
- Questions 1–10: Verbal Ability (reading comprehension, vocabulary, grammar, sentence correction, para jumbles)
- Questions 11–20: Logical Reasoning (series, syllogisms, blood relations, direction sense, coding-decoding, analogies)
- Questions 21–30: Quantitative Aptitude (percentages, profit/loss, time-speed-distance, probability, permutations-combinations, number series)

Difficulty: medium to advanced. No trivial questions. All questions must be completely original.

Return ONLY valid JSON:
{
  "questions": [
    {
      "id": 1,
      "category": "Verbal Ability",
      "question": "...",
      "options": ["option A text", "option B text", "option C text", "option D text"],
      "correct": 0,
      "explanation": "Concise explanation of the correct answer"
    }
  ]
}
Rules: correct is 0-indexed (0=A,1=B,2=C,3=D). Exactly 4 options per question. Return all 30 questions.
PROMPT;

    $data = openai_chat($prompt, 120);
    if (!$data || empty($data['questions']) || count($data['questions']) < 10) {
        echo json_encode(['success' => false, 'error' => 'Could not generate aptitude questions. Try again.']);
        exit;
    }
    $_SESSION['round1_questions'] = array_values($data['questions']);
    echo json_encode(['success' => true, 'count' => count($data['questions'])]);
    exit;
}

// ── ROUND 2: TECHNICAL (30 MIXED) ────────────────────────────────────────────
if ($round === 2) {
    if (!empty($_SESSION['round2_questions'])) {
        echo json_encode(['success' => true, 'cached' => true]);
        exit;
    }

    $dbQuestions = load_questions_from_db('technical', 30);
    if (count($dbQuestions) === 30) {
        $_SESSION['round2_questions'] = $dbQuestions;
        echo json_encode(['success' => true, 'source' => 'db', 'count' => 30]);
        exit;
    }

    $prompt = <<<PROMPT
You are an expert technical interviewer. Generate exactly 30 technical questions for a "{$position}" candidate ({$exp} level).
Candidate resume excerpt: "{$resume}"

Include exactly these 4 types (7–8 each, interleaved):
1. type "mcq" — conceptual/technical multiple choice
2. type "drag_drop" — arrange 5–7 shuffled code lines into a working function
3. type "fill_blank" — code snippet with ___ placeholder; pick the correct fill from 4 options
4. type "debug" — find the 0-indexed line number containing the bug

Difficulty: medium to advanced. Use {$position}-relevant code examples.

Return ONLY valid JSON:
{
  "questions": [
    {
      "id": 31, "type": "mcq", "category": "{$position}",
      "question": "...",
      "options": ["A","B","C","D"],
      "correct": 2,
      "explanation": "..."
    },
    {
      "id": 32, "type": "drag_drop", "category": "{$position}",
      "question": "Arrange the lines to build a working merge sort function",
      "code_lines": ["    return sorted_arr", "def merge_sort(arr):", "    if len(arr) <= 1:", "        return arr", "    mid = len(arr) // 2"],
      "correct_order": [1, 2, 3, 4, 0],
      "explanation": "..."
    },
    {
      "id": 33, "type": "fill_blank", "category": "{$position}",
      "question": "Complete the function to reverse a string",
      "code_template": "def reverse(s):\n    return s[___]",
      "options": ["::-1", ":-1", "0:-1", "reverse()"],
      "correct": 0,
      "explanation": "..."
    },
    {
      "id": 34, "type": "debug", "category": "{$position}",
      "question": "Find and click the line containing the bug",
      "code_lines": ["def factorial(n):", "    if n == 0:", "        return 1", "    return n * factorial(n)"],
      "correct": 3,
      "explanation": "Line 4 should call factorial(n-1) not factorial(n) — causes infinite recursion"
    }
  ]
}
Rules:
- drag_drop correct_order is an array of indices giving the correct sequence of code_lines
- fill_blank uses ___ as placeholder; correct is 0-indexed option
- debug correct is 0-indexed line number with the bug
- Return all 30 questions. Vary types — do not group all of one type together.
PROMPT;

    $data = openai_chat($prompt, 150);
    if (!$data || empty($data['questions']) || count($data['questions']) < 10) {
        echo json_encode(['success' => false, 'error' => 'Could not generate technical questions. Try again.']);
        exit;
    }
    $_SESSION['round2_questions'] = array_values($data['questions']);
    echo json_encode(['success' => true, 'count' => count($data['questions'])]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid round.']);
