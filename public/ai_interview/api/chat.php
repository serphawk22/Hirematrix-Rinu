<?php
error_reporting(E_ALL); ini_set('display_errors',0);
ob_start(); session_start();
require_once dirname(__DIR__).'/config.php';
ob_end_clean();
header('Content-Type: application/json');

if (empty($_SESSION['candidate'])) { echo json_encode(['error'=>'Session expired']); exit; }

$body    = json_decode(file_get_contents('php://input'), true);
$userMsg = trim($body['message'] ?? '');
if (!$userMsg) { echo json_encode(['error'=>'Empty message']); exit; }

$cand    = $_SESSION['candidate'];
$history = $_SESSION['interview_messages'] ?? [];
$count   = count($history);

$systemPrompt = "You are Maya, a senior technical interviewer at NexusAI with 12 years of experience. You are conducting a professional 20-minute one-on-one interview.

Candidate: {$cand['name']}
Position Applied: {$cand['position']}
Experience Level: {$cand['experience']}
Resume/Background: {$cand['resume']}

Interview Guidelines:
- If this is the very first message, warmly greet the candidate by name, introduce yourself as Maya, and ask them to start with a brief self-introduction.
- Ask exactly ONE clear question per response — never ask multiple questions at once.
- Build naturally on the candidate's previous answers — if they mention a project, probe deeper.
- Progressively cover: self-introduction → work experience → technical skills (from resume) → project deep-dives → problem-solving approach → behavioral situations → career goals.
- If an answer is vague or too short, politely ask for more detail.
- Be warm, encouraging, and professional. Make the candidate feel at ease.
- Keep YOUR responses SHORT (2-4 sentences max) — acknowledge briefly, then ask the next question.
- Do NOT give any feedback or scoring during the interview — save that for the report.
- After message exchange " . ($count+1) . " of about 24, if near the end, wrap up graciously.
- Never repeat a question already asked.";

// Append user's message
$history[] = ['role' => 'user', 'content' => $userMsg];

// Build messages for OpenAI
$messages = array_merge(
    [['role' => 'system', 'content' => $systemPrompt]],
    $history
);

$reply = openai_chat_messages($messages, 45);
if (!$reply) { echo json_encode(['error' => 'AI unavailable, please retry.']); exit; }

// Store AI reply in history
$history[] = ['role' => 'assistant', 'content' => $reply];
$_SESSION['interview_messages'] = $history;

$totalExchanges = intdiv(count($history), 2); // each exchange = 1 user + 1 AI
$shouldEnd      = $totalExchanges >= 12;       // ~12 exchanges = ~20 min

echo json_encode([
    'reply'      => $reply,
    'exchanges'  => $totalExchanges,
    'shouldEnd'  => $shouldEnd,
]);
