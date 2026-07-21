<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Interview Guidelines</title>
<link rel="icon" type="image/png" href="../jobboard/images/Serp Hwak Logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../jobboard/css/fontawesome-all.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/style.css?v=candidate-ui">
<style>
body.ai-guidelines-page {
  align-items: stretch !important;
  background: #f4f7fb !important;
  color: #0f172a !important;
  display: block !important;
  font-family: Inter, sans-serif !important;
  min-height: 100vh !important;
  padding: 16px !important;
}

.guidelines-shell {
  display: grid;
  gap: 16px;
  margin: 0 auto;
  max-width: 1180px;
  width: 100%;
}

.guidelines-hero,
.guidelines-content {
  background: #ffffff !important;
  border: 1px solid #d7e4ef !important;
  border-radius: 12px !important;
  box-shadow: none !important;
}

.guidelines-hero {
  align-items: center;
  display: flex;
  gap: 18px;
  justify-content: space-between;
  padding: 20px 22px;
}

.guidelines-kicker {
  color: #0d8a90;
  display: block;
  font-size: .76rem;
  font-weight: 800;
  letter-spacing: .06em;
  margin-bottom: 6px;
  text-transform: uppercase;
}

.guidelines-title {
  color: #0f172a !important;
  font-size: clamp(1.55rem, 2vw, 2rem) !important;
  font-weight: 800 !important;
  letter-spacing: 0 !important;
  line-height: 1.2 !important;
  margin: 0 !important;
  text-align: left !important;
}

.guidelines-subtitle {
  color: #52677a;
  font-size: .95rem;
  line-height: 1.55;
  margin: 8px 0 0;
}

.guidelines-content {
  max-height: none !important;
  max-width: none !important;
  overflow: visible !important;
  padding: 22px !important;
  width: 100% !important;
}

.guidelines-note {
  background: #edf8f5;
  border: 1px solid rgba(31, 183, 181, .24);
  border-radius: 10px;
  color: #0d8a90;
  margin: 0 0 18px;
  padding: 14px 16px;
}

.guidelines-content h2 {
  align-items: center;
  border-bottom: 1px solid #e7eef5;
  color: #0f172a !important;
  display: flex;
  font-size: 1.05rem !important;
  font-weight: 800 !important;
  gap: 10px;
  letter-spacing: 0 !important;
  margin: 24px 0 12px;
  padding-bottom: 10px;
}

.guidelines-content h2:first-of-type {
  margin-top: 0;
}

.guidelines-content h2 i,
.guidelines-title i {
  color: #0d8a90;
}

.guidelines-content p,
.guidelines-content li {
  color: #52677a;
  font-size: .94rem;
  line-height: 1.55;
}

.guidelines-content strong {
  color: #24384a;
  font-weight: 800;
}

.guidelines-content ol,
.guidelines-content ul {
  margin: 0 0 14px;
  padding-left: 22px;
}

.guidelines-content li {
  margin-bottom: 7px;
}

.guidelines-page-btn {
  align-items: center;
  background: transparent !important;
  border: 1.5px solid #1fb7b5 !important;
  border-radius: 6px !important;
  box-shadow: none !important;
  color: #1fb7b5 !important;
  display: inline-flex;
  font-weight: 800;
  gap: 8px;
  justify-content: center;
  padding: 10px 16px;
  text-decoration: none !important;
}

.guidelines-page-btn:hover,
.guidelines-page-btn:focus {
  background: #1fb7b5 !important;
  color: #ffffff !important;
  transform: translateY(-1px);
}

.guidelines-bottom-action {
  margin-top: 18px;
}

body.ai-guidelines-page .theme-toggle-btn {
  right: 18px !important;
  top: 18px !important;
}

body.dark.ai-guidelines-page {
  background: #0d181b !important;
  color: #f1fbfa !important;
}

body.dark.ai-guidelines-page .guidelines-hero,
body.dark.ai-guidelines-page .guidelines-content {
  background: #142427 !important;
  border-color: #2e4a50 !important;
}

body.dark.ai-guidelines-page .guidelines-title,
body.dark.ai-guidelines-page .guidelines-content h2,
body.dark.ai-guidelines-page .guidelines-content strong {
  color: #f1fbfa !important;
}

body.dark.ai-guidelines-page .guidelines-subtitle,
body.dark.ai-guidelines-page .guidelines-content p,
body.dark.ai-guidelines-page .guidelines-content li {
  color: #b8cdd2 !important;
}

body.dark.ai-guidelines-page .guidelines-content h2 {
  border-color: #2e4a50;
}

body.dark.ai-guidelines-page .guidelines-note {
  background: rgba(31, 183, 181, .16);
  border-color: rgba(120, 227, 221, .3);
}

@media (max-width: 760px) {
  body.ai-guidelines-page {
    padding: 12px !important;
  }

  .guidelines-hero {
    align-items: flex-start;
    flex-direction: column;
    padding: 18px;
  }

  .guidelines-page-btn,
  .guidelines-actions {
    width: 100%;
  }

  .guidelines-content {
    padding: 18px !important;
  }
}
</style>
</head>
<body class="hirematrix-app candidate-app ai-guidelines-page">
<?php include 'theme-toggle.php'; ?>

<main class="guidelines-shell">
  <section class="guidelines-hero">
    <div>
      <span class="guidelines-kicker">AI assessment</span>
      <h1 class="guidelines-title"><i class="fas fa-book-open" aria-hidden="true"></i> Interview Rules & Guidelines</h1>
      <p class="guidelines-subtitle">Review the interview structure, scoring rules, and proctoring expectations before starting your assessment.</p>
    </div>
    <div class="guidelines-actions">
      <a href="index.php" class="guidelines-page-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Interview</a>
    </div>
  </section>

  <section class="guidelines-content">
    <p class="guidelines-note"><strong>Welcome to the AI-Based Interview Process.</strong><br>Please read all instructions carefully before starting the interview.</p>

    <h2><i class="fas fa-layer-group" aria-hidden="true"></i> Interview Structure</h2>
    <p>The interview consists of 3 mandatory rounds:</p>
    <ol>
      <li><strong>MCQ Round</strong>
        <ul>
          <li>Aptitude Assessment</li>
          <li>Technical Assessment</li>
        </ul>
      </li>
      <li><strong>Coding Round</strong><br>Candidates must solve coding problems within the given time limit.</li>
      <li><strong>AI Interview Round</strong><br>An AI-based live interview will evaluate communication, technical understanding, confidence, and behavior.</li>
    </ol>

    <h2><i class="fas fa-arrow-right" aria-hidden="true"></i> Question Navigation Rules</h2>
    <p>Once a candidate submits an answer and clicks <strong>Next</strong>, that question is locked and cannot be revisited.</p>
    <ul>
      <li>There is no option to go back to a previous question once you have moved forward</li>
      <li>Review your answer carefully before clicking Next, as it cannot be changed afterward</li>
      <li>Each question must be answered in the order it is presented</li>
      <li>Skipped or unanswered questions cannot be returned to later</li>
    </ul>

    <h2><i class="fas fa-shield-alt" aria-hidden="true"></i> Strict Proctoring Rules</h2>
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
    <p>Any suspicious activity may lead to warnings, score reduction, or immediate termination of the interview.</p>

    <h2><i class="fas fa-video" aria-hidden="true"></i> AI Monitoring & Detection</h2>
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

    <h2><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Warning System</h2>
    <p>Candidates will receive warnings if suspicious or prohibited activities are detected.<br>All warnings and detection logs are visible to the recruiter/admin panel.</p>
    <p><strong>Repeated violations may result in:</strong></p>
    <ul>
      <li>Automatic interview termination</li>
      <li>Candidate disqualification</li>
      <li>Permanent rejection from the hiring process</li>
    </ul>

    <h2><i class="fas fa-clipboard-list" aria-hidden="true"></i> Important Instructions</h2>
    <ul>
      <li>Sit in a quiet and well-lit environment</li>
      <li>Ensure your face remains clearly visible at all times</li>
      <li>Maintain stable internet connectivity</li>
      <li>Do not leave your seat during the interview</li>
      <li>Use only one monitor and one device</li>
      <li>Close all unnecessary applications before starting</li>
    </ul>

    <h2><i class="fas fa-file-signature" aria-hidden="true"></i> Candidate Declaration</h2>
    <p>By starting the interview, you agree that:</p>
    <ul>
      <li>Your interview session may be monitored and recorded</li>
      <li>AI-based proctoring systems will track interview activity</li>
      <li>Any malpractice or unfair behavior may result in disqualification</li>
    </ul>

    <h2><i class="fas fa-flag-checkered" aria-hidden="true"></i> Final Note</h2>
    <p>Please attempt the interview honestly and professionally.<br>Your skills, behavior, and integrity are all part of the evaluation process.</p>
    <p><strong>We wish you all the best for your interview.</strong></p>

    <div class="guidelines-bottom-action">
      <a href="index.php" class="guidelines-page-btn"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Interview</a>
    </div>
  </section>
</main>

<script src="js/theme.js"></script>
</body>
</html>