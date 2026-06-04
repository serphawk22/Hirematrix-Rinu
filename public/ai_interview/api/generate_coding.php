<?php
error_reporting(E_ALL); ini_set('display_errors',0); ob_start();
session_start();
require_once dirname(__DIR__).'/config.php';
ob_end_clean(); header('Content-Type: application/json');

if (empty($_SESSION['candidate'])) { echo json_encode(['success'=>false,'error'=>'Session expired']); exit; }
if (!empty($_SESSION['coding_problems'])) { echo json_encode(['success'=>true,'cached'=>true]); exit; }

$cand     = $_SESSION['candidate'];
$position = $cand['position'];
$exp      = $cand['experience'];
$resume   = substr($cand['resume'],0,1500);

$prompt = <<<PROMPT
You are a senior software engineering interviewer. Generate exactly 2 coding interview problems for a "{$position}" candidate ({$exp} level), tailored to their background: "{$resume}".

Each problem must be original, well-structured, and similar in quality to LeetCode/HackerRank problems. Difficulty: Problem 1 = Medium, Problem 2 = Medium-Hard.

Return ONLY valid JSON:
{
  "problems": [
    {
      "id": 1,
      "title": "Two Sum",
      "difficulty": "Medium",
      "topic": "Arrays & Hashing",
      "description": "Full problem description with clear explanation...",
      "input_format": "First line: integer n. Second line: n space-separated integers. Third line: target integer.",
      "output_format": "Two space-separated indices (0-indexed) that add up to target.",
      "constraints": ["2 <= n <= 10^4", "-10^9 <= nums[i] <= 10^9", "Exactly one solution exists"],
      "examples": [
        {
          "input": "4\n2 7 11 15\n9",
          "output": "0 1",
          "explanation": "nums[0] + nums[1] = 2 + 7 = 9"
        },
        {
          "input": "3\n3 2 4\n6",
          "output": "1 2",
          "explanation": "nums[1] + nums[2] = 2 + 4 = 6"
        }
      ],
      "test_cases": [
        {"input": "4\n2 7 11 15\n9", "output": "0 1"},
        {"input": "3\n3 2 4\n6", "output": "1 2"},
        {"input": "2\n3 3\n6", "output": "0 1"},
        {"input": "5\n1 5 3 7 2\n8", "output": "1 4"},
        {"input": "6\n-1 -2 -3 -4 -5 -6\n-5", "output": "0 3"}
      ],
      "starter_code": {
        "python": "import sys\ninput = sys.stdin.read().split()\nn = int(input[0])\nnums = list(map(int, input[1:n+1]))\ntarget = int(input[n+1])\n\n# Write your solution here\n",
        "javascript": "const lines = require('fs').readFileSync('/dev/stdin','utf8').trim().split('\\n');\nconst n = parseInt(lines[0]);\nconst nums = lines[1].split(' ').map(Number);\nconst target = parseInt(lines[2]);\n\n// Write your solution here\n",
        "java": "import java.util.*;\npublic class Solution {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int n = sc.nextInt();\n        int[] nums = new int[n];\n        for(int i=0;i<n;i++) nums[i]=sc.nextInt();\n        int target = sc.nextInt();\n        // Write your solution here\n    }\n}",
        "cpp": "#include <bits/stdc++.h>\nusing namespace std;\nint main() {\n    int n; cin >> n;\n    vector<int> nums(n);\n    for(int i=0;i<n;i++) cin >> nums[i];\n    int target; cin >> target;\n    // Write your solution here\n    return 0;\n}",
        "c": "#include <stdio.h>\nint main() {\n    int n; scanf(\"%d\",&n);\n    int nums[10001];\n    for(int i=0;i<n;i++) scanf(\"%d\",&nums[i]);\n    int target; scanf(\"%d\",&target);\n    // Write your solution here\n    return 0;\n}"
      }
    }
  ]
}

Rules:
- starter_code must read from stdin matching your input_format
- test_cases inputs must match input_format exactly
- test_cases outputs must have no trailing spaces/newlines issues — be precise
- Make problems genuinely challenging and relevant to the position
- starter_code must have at least: python, javascript, java, cpp, c
PROMPT;

$data = openai_chat($prompt, 90);
if (!$data || empty($data['problems'])) {
    echo json_encode(['success'=>false,'error'=>'Could not generate problems. Please retry.']);
    exit;
}
$_SESSION['coding_problems'] = $data['problems'];
echo json_encode(['success'=>true,'count'=>count($data['problems'])]);
