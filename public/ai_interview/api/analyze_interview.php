<?php
error_reporting(E_ALL); ini_set('display_errors',0);
ob_start(); session_start();
require_once dirname(__DIR__).'/config.php';
ob_end_clean();
header('Content-Type: application/json');

if (empty($_SESSION['candidate']) || empty($_SESSION['interview_messages'])) {
    echo json_encode(['error'=>'No interview data']); exit;
}

$cand    = $_SESSION['candidate'];
$history = $_SESSION['interview_messages'];

// Build full transcript
$transcript = '';
foreach ($history as $m) {
    $role = $m['role'] === 'user' ? $cand['name'] : 'Maya (AI Interviewer)';
    $transcript .= "$role: {$m['content']}\n\n";
}

$prompt = "You are an expert HR analyst. Analyze the following job interview transcript for a {$cand['position']} position (Experience: {$cand['experience']}) and provide a comprehensive performance report.

INTERVIEW TRANSCRIPT:
$transcript

Return ONLY valid JSON in this exact structure:
{
  \"overall_score\": 78,
  \"recommendation\": \"Strong Hire | Hire | Maybe | No Hire\",
  \"summary\": \"2-3 sentence overall assessment\",
  \"scores\": {
    \"communication\": { \"score\": 82, \"label\": \"Communication Skills\", \"feedback\": \"Specific feedback...\" },
    \"technical\": { \"score\": 75, \"label\": \"Technical Knowledge\", \"feedback\": \"Specific feedback...\" },
    \"problem_solving\": { \"score\": 70, \"label\": \"Problem Solving\", \"feedback\": \"Specific feedback...\" },
    \"experience_depth\": { \"score\": 80, \"label\": \"Experience Depth\", \"feedback\": \"Specific feedback...\" },
    \"confidence\": { \"score\": 85, \"label\": \"Confidence & Clarity\", \"feedback\": \"Specific feedback...\" },
    \"resume_alignment\": { \"score\": 78, \"label\": \"Resume Alignment\", \"feedback\": \"Specific feedback...\" }
  },
  \"strengths\": [\"Strength 1\", \"Strength 2\", \"Strength 3\"],
  \"weaknesses\": [\"Area 1\", \"Area 2\"],
  \"improvements\": [\"Specific actionable improvement 1\", \"Specific actionable improvement 2\", \"Specific actionable improvement 3\"],
  \"key_moments\": [
    { \"type\": \"positive\", \"text\": \"What candidate said that impressed\" },
    { \"type\": \"negative\", \"text\": \"What candidate said that was concerning\" }
  ],
  \"hiring_notes\": \"Detailed notes an HR manager would write\"
}";

$result = openai_chat($prompt, 90);
if (!$result) { echo json_encode(['error'=>'Analysis failed']); exit; }

$_SESSION['interview_report'] = $result;
echo json_encode(['success' => true, 'report' => $result]);
