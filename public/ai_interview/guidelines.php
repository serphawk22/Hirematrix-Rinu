<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Interview Guidelines</title>
<link rel="stylesheet" href="css/style.css">

<style>
 /* PAGE BACKGROUND */
body {
  margin: 0;
  padding: 0;
  font-family: Inter, sans-serif;
  background: radial-gradient(circle at top, #0f172a, #020617);
  color: #e5e7eb;

  /* spacing top & bottom */
  padding: 60px 20px;
  display: flex;
  justify-content: center;
}

/* MAIN CONTAINER */
.container {
  width: 100%;
  max-width: 950px;

  /* spacing feel */
  padding: 35px 40px;

  /* modern glass card */
  background: rgba(17, 24, 39, 0.65);
  backdrop-filter: blur(14px);

  border-radius: 18px;
  border: 1px solid rgba(255,255,255,0.08);

  box-shadow:
    0 20px 60px rgba(0,0,0,0.6),
    0 0 0 1px rgba(99,102,241,0.15);

  /* scrollable */
  max-height: 85vh;
  overflow-y: auto;
}

/* SCROLLBAR */
.container::-webkit-scrollbar {
  width: 6px;
}
.container::-webkit-scrollbar-thumb {
  background: rgba(99,102,241,0.5);
  border-radius: 10px;
}
.container::-webkit-scrollbar-track {
  background: transparent;
}

/* TITLE */
h1 {
  color: #a5b4fc;
  text-align: center;
  margin-bottom: 25px;
  font-size: 26px;
  font-weight: 600;
}

/* SECTION HEADINGS */
h2 {
  margin-top: 28px;
  color: #818cf8;
  font-size: 18px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  padding-bottom: 6px;
}

/* TEXT */
p {
  margin-bottom: 12px;
  color: #cbd5f5;
}

/* LISTS */
ul {
  padding-left: 20px;
  margin-bottom: 12px;
}

li {
  margin-bottom: 6px;
}

/* NUMBERED LIST */
ol {
  padding-left: 18px;
}

/* BUTTON */
.back-btn {
  display: inline-block;
  margin-top: 30px;
  padding: 12px 20px;

  background: linear-gradient(135deg, #6366f1, #4f46e5);
  color: white;
  text-decoration: none;
  border-radius: 10px;

  transition: all 0.25s ease;
  box-shadow: 0 8px 25px rgba(99,102,241,0.35);
}

/* BUTTON HOVER */
.back-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(99,102,241,0.5);
}
</style>
</head>

<body>
<?php include 'theme-toggle.php'; ?>
<div class="container">

<h1>📘 AI Interview Rules & Guidelines</h1>

<p><strong>Welcome to the AI-Based Interview Process.</strong><br>
Please read all instructions carefully before starting the interview.</p>

<h2>Interview Structure</h2>

<p>The interview consists of 3 mandatory rounds:</p>

<ol>
<li><strong>MCQ Round</strong>
  <ul>
    <li>Aptitude Assessment</li>
    <li>Technical Assessment</li>
  </ul>
</li>

<li><strong>Coding Round</strong>
  <p>Candidates must solve coding problems within the given time limit.</p>
</li>

<li><strong>AI Interview Round</strong>
  <p>An AI-based live interview will evaluate communication, technical understanding, confidence, and behavior.</p>
</li>
</ol>

<h2>Qualification Criteria</h2>

<p>
Candidates must score <strong>70% or above in each round</strong> to proceed to the next round.<br>
Failing to achieve the minimum score in any round will automatically disqualify the candidate from further rounds.
</p>

<h2>Strict Proctoring Rules</h2>

<p>To ensure a fair interview process, advanced AI proctoring and monitoring systems are active throughout the interview.</p>

<p><strong>The following activities are strictly prohibited:</strong></p>

<ul>
<li>Copying or pasting content</li>
<li>Using keyboard shortcuts for copy/paste</li>
<li>Opening Developer Tools</li>
<li>Taking screenshots or screen recordings</li>
<li>Switching browser tabs or applications</li>
<li>Leaving the interview window</li>
<li>Right-clicking or opening the context menu</li>
<li>Using external devices, AI tools, or unauthorized assistance</li>
</ul>

<p>
Any suspicious activity may lead to warnings, score reduction, or immediate termination of the interview.
</p>

<h2>AI Monitoring & Detection</h2>

<p>During the interview, the system continuously monitors:</p>

<ul>
<li>Face visibility</li>
<li>Multiple face detection</li>
<li>No face detection</li>
<li>Background noise levels</li>
<li>Unusual movements or suspicious behavior</li>
<li>Tab switching activity</li>
<li>Screenshot attempts</li>
<li>Developer tools access</li>
<li>Window focus changes</li>
</ul>

<h2>Warning System</h2>

<p>
Candidates will receive warnings if suspicious or prohibited activities are detected.<br>
All warnings and detection logs are visible to the recruiter/admin panel.
</p>

<p><strong>Repeated violations may result in:</strong></p>

<ul>
<li>Automatic interview termination</li>
<li>Candidate disqualification</li>
<li>Permanent rejection from the hiring process</li>
</ul>

<h2>Important Instructions</h2>

<ul>
<li>Sit in a quiet and well-lit environment</li>
<li>Ensure your face remains clearly visible at all times</li>
<li>Maintain stable internet connectivity</li>
<li>Do not leave your seat during the interview</li>
<li>Use only one monitor and one device</li>
<li>Close all unnecessary applications before starting</li>
</ul>

<h2>Candidate Declaration</h2>

<p>By starting the interview, you agree that:</p>

<ul>
<li>Your interview session may be monitored and recorded</li>
<li>AI-based proctoring systems will track interview activity</li>
<li>Any malpractice or unfair behavior may result in disqualification</li>
</ul>

<h2>Final Note</h2>

<p>
Please attempt the interview honestly and professionally.<br>
Your skills, behavior, and integrity are all part of the evaluation process.
</p>

<p><strong>We wish you all the best for your interview.</strong></p>

<a href="index.php" class="back-btn">⬅ Back to Interview</a>

</div>
<script src="js/theme.js"></script>
</body>
</html>