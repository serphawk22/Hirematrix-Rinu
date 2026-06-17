<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.php';
 
// Redirect if already in exam
//if (!empty($_SESSION['exam_ready'])) { header('Location: exam.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$candidateId = $_POST['candidate_id'] ?? '';
$_SESSION['candidateId'] = $candidateId;  
//echo $_SESSION['candidateId'];
$jobid = $_POST['jobid'] ?? '';
$experienceInput = $_POST['experience'] ?? '';
$experience = '';

if (is_numeric($experienceInput)) {

    $years = (float)$experienceInput;

    if ($years >= 0 && $years <= 1) {
        $experience = 'fresher';
    } elseif ($years > 1 && $years <= 3) {
        $experience = 'junior';
    } elseif ($years > 3 && $years <= 6) {
        $experience = 'mid';
    } else {
        $experience = 'senior';
    }

} else {

    // if already sending string values like fresher/junior/mid/senior
    $allowed = ['fresher', 'junior', 'mid', 'senior'];

    if (in_array($experienceInput, $allowed)) {
        $experience = $experienceInput;
    }
}
$_SESSION['jobid'] = $jobid;
$sessionCandidateName = $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['candidate_name']
    ?? $_SESSION['candidateName']
    ?? '';
$candidateName = trim($_POST['candidate_name'] ?? $sessionCandidateName);

if ($candidateId > 0) {
    $conn = db_connect();
    if ($conn) {
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $candidateId);
            $stmt->execute();
            $stmt->bind_result($candidateName);
            $stmt->fetch();
            $stmt->close();
        }

        $conn->close();
    }
}

if ($candidateName === '') {
    $candidateName = $sessionCandidateName;
}
 
$selectedJobTitle = $_POST['job_title'] ?? '';
$highlightSkills = $_POST['highlight_skills'] ?? '';
$experience = $_POST['experience'] ?? '';
$_SESSION['jobrole'] = $selectedJobTitle;
$_SESSION['candidateName'] = $candidateName;
$_SESSION['highlight_skills'] = $highlightSkills;
$_SESSION['experience'] = $experience;
}
else{
$candidateName = $_SESSION['candidateName']
    ?? $_SESSION['name']
    ?? $_SESSION['user_name']
    ?? $_SESSION['candidate_name']
    ?? '';
$selectedJobTitle = $_SESSION['position'] ?? '';
$highlightSkills = $_SESSION['highlight_skills'] ?? '';
$experience = $_SESSION['experience'] ?? '';
//echo $selectedJobTitle;
}
//echo $_SESSION['highlight_skills'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>HireMatrix AI Interview</title>
<meta name="description" content="AI-powered technical interview platform with aptitude, reasoning, and role-specific assessment."/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../jobboard/css/fontawesome-all.min.css"/>
<link rel="stylesheet" href="css/style.css"/>

</head>
<body class="hirematrix-app candidate-app ai-interview-candidate-page">
  <!-- RULES POPUP -->
<div id="rulesPopup" class="rules-overlay" >
  <div class="rules-box">
    <h2>⚠️ AI Interview Instructions</h2>

    <div class="rules-content">
      <p><strong>The interview contains 3 rounds:</strong></p>
      <ul>
        <li>MCQ (Aptitude + Technical)</li>
        <li>Coding Round</li>
        <li>AI Interview</li>
      </ul>

      <p><strong>Minimum 70% score is required in each round to proceed further.</strong></p>

      <p><strong>Strictly prohibited:</strong></p>
      <ul>
        <li>Copy/Paste/Cut</li>
        <li>Screenshots/Screen Recording</li>
        <li>Developer Tools</li>
        <li>Tab Switching</li>
        <li>Right Click / Context Menu</li>
        <li>External Help or AI Tools</li>
      </ul>

      <p><strong>AI Proctoring is active during the interview.</strong></p>

      <p><strong>The system monitors:</strong></p>
      <ul>
        <li>No Face / Multiple Faces</li>
        <li>Background Noise</li>
        <li>Suspicious Movements</li>
        <li>Tab Changes</li>
        <li>Screenshot Attempts</li>
        <li>Developer Tools Usage</li>
      </ul>

      <p>Warnings will be issued for suspicious activities.</p>
      <p>All detections are visible to recruiters/admins.</p>
      <p><strong>Multiple violations may lead to automatic interview termination and disqualification.</strong></p>

      <p>👉 Please attend the interview honestly.</p>
      <p><strong>All the best!</strong></p>
    </div>
<div style="text-align:center; margin-top:15px;">
  <label style="font-size:13px;">
    <input type="checkbox" id="agreeCheck"> I have read and agree to the rules
  </label>
</div>
    <div class="rules-actions">
      <button id="acceptRules">✅ Accept & Continue</button>
    </div>
  </div>
</div>
<?php include 'theme-toggle.php'; ?>
<canvas id="particleCanvas"></canvas>
<div id="app">
<div id="screen-container">
<div class="reg-screen ai-interview-jobboard animate-in">
  <div class="reg-wrap">

    <div class="brand-header page-board-header page-board-header-tight">
      <div class="page-board-copy">
        <span class="page-board-kicker">AI assessment</span>
        <h1 class="brand-title page-board-title"><span class="text-gradient">HireMatrix</span> AI Interview</h1>
        <p class="brand-sub page-board-subtitle">AI-powered assessment platform. 60 questions across aptitude & technical skills. Medium to advanced level.

</p>
      </div>
      <div class="page-board-actions">
        <a href="guidelines.php" class="btn btn-secondary btn-sm">Guidelines</a>
      </div>
    </div> 
    <!-- Form -->
    <div class="glass-card reg-card">
      <?php if (!empty($_GET['error'])): ?>
      <div class="notification error" style="position:static;margin-bottom:18px;animation:none">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
      <?php endif; ?>

      <form action="start.php" method="POST" id="regForm" enctype="multipart/form-data">
        <input type="hidden" name="mode" value="mcq">

        <div class="form-grid2">
          <div class="form-group">
            <label class="form-label" for="candidate_name">Full Name</label>
            <input class="form-input" type="text" id="candidate_name" name="candidate_name"
                   placeholder="e.g. Arjun Sharma" required autocomplete="off" value="<?php echo $candidateName ?? $_SESSION['candidateName']; ?>" readonly />
          </div>
        <div class="form-group">
    <label class="form-label" for="position">Position Applied For</label>
     <input class="form-input" type="text" id="position" name="position"
                   placeholder="Job Role" required autocomplete="off" value="<?php echo $selectedJobTitle ?? $_SESSION['position'] ?? $_SESSION['jobrole']; ?>" readonly />
     
</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="resume">Paste Your Resume / Key Skills</label>
          <textarea class="form-textarea" id="resume" name="resume" rows="6"
                    placeholder="Paste your resume text here — skills, experience, technologies you've worked with. The AI uses this to tailor your technical round questions..."  required><?php echo $highlightSkills ?? $_SESSION['highlight_skills']; ?></textarea>
          <p class="hint">💡 The more detail you provide, the more personalised your questions will be.</p>
        </div>

        <div class="form-group">
          <label class="form-label" for="experience">Experience Level</label>
          <input type="hidden" name="experience" value="<?= htmlspecialchars($experience, ENT_QUOTES, 'UTF-8') ?>">
          <select class="form-select" id="experience" disabled>
            <option value="">— Select Level —</option>
            <option value="fresher" <?= ($experience === 'fresher') ? 'selected' : '' ?>>Fresher (0–1 yr)</option>
            <option value="junior" <?= ($experience === 'junior') ? 'selected' : '' ?>>Junior (1–3 yrs)</option>
            <option value="mid" <?= ($experience === 'mid') ? 'selected' : '' ?>>Mid-Level (3–6 yrs)</option>
            <option value="senior" <?= ($experience === 'senior') ? 'selected' : '' ?>>Senior (6+ yrs)</option>
          </select>
        </div>

        <div class="mode-select">
          <p class="mode-label">Choose Interview Mode</p>
          <div class="mode-cards" style="grid-template-columns:1fr 1fr 1fr">
            <button class="mode-card" type="submit" name="mode" value="mcq" id="modeMCQ">
              <span class="mode-icon">📋</span>
              <div class="mode-name">MCQ Round</div>
              <div class="mode-desc">60 questions — aptitude, logical reasoning &amp; technical MCQs, debug challenges</div>
              <div class="mode-meta">30+30 Questions · 50 min</div>
            </button>
            <button class="mode-card" type="submit" name="mode" value="coding" id="modeCoding">
              <span class="mode-icon">💻</span>
              <div class="mode-name">Coding Round</div>
              <div class="mode-desc">2 real coding problems with Monaco editor, test cases &amp; multi-language execution</div>
              <div class="mode-meta">2 Problems · 45 min · All Languages</div>
            </button>
            <button class="mode-card" type="submit" name="mode" value="interview" id="modeInterview">
              <span class="mode-icon">🎤</span>
              <div class="mode-name">AI Interview</div>
              <div class="mode-desc">One-on-one with AI interviewer Maya — resume-based questions, follow-ups &amp; deep analysis</div>
              <div class="mode-meta">Conversational · 20 min · Full Report</div>
            </button>
          </div>
          <div id="pdfUploadRow" style="margin-top:14px;display:none">
            <label class="form-label">📄 Upload Resume PDF <span style="color:var(--t3);font-weight:400">(optional — AI will use it for questions)</span></label>
            <input type="file" name="resume_pdf" id="resumePdf" accept=".pdf" class="form-input" style="padding:10px;cursor:pointer">
          </div>
        </div>

        <div class="assessment-start-row">
          <button class="btn btn-primary assessment-start-btn" type="submit">
            <i class="fas fa-play" aria-hidden="true"></i>
            Start Assessment
          </button>
        </div>

      </form>
    </div>

    <!-- Info strip -->
    <div class="info-strip">
      <div class="info-item"><div class="info-icon">⏱️</div><span>90s per question</span></div>
      <div class="info-item"><div class="info-icon">📋</div><span>60 total questions</span></div>
      <div class="info-item"><div class="info-icon">🤖</div><span>GPT-4o powered</span></div>
      <div class="info-item"><div class="info-icon">📊</div><span>Detailed analytics</span></div>
    </div>

  </div>
</div>
</div>
</div>

<script src="js/particles.js"></script>
<script>
document.querySelectorAll('.mode-card').forEach(btn => {
  btn.type = 'button';
  btn.tabIndex = -1;
  btn.setAttribute('aria-disabled', 'true');
  btn.removeAttribute('name');
  btn.removeAttribute('value');
});

// Show PDF upload row when AI Interview card is hovered/clicked
const interviewBtn = document.getElementById('modeInterview');
const pdfRow = document.getElementById('pdfUploadRow');
interviewBtn.addEventListener('mouseenter', () => pdfRow.style.display = 'block');
interviewBtn.addEventListener('mouseleave', () => {
  if (!interviewBtn.classList.contains('selected')) pdfRow.style.display = 'none';
});
interviewBtn.addEventListener('click', () => pdfRow.style.display = 'block');
// Highlight selected card
document.querySelectorAll('.mode-card').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.mode-card').forEach(b => b.style.borderColor = '');
    btn.style.borderColor = 'var(--accent)';
    btn.style.background  = '#f0fdfa';
  });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {

    /* =========================
       THEME
    ========================= */
    applySavedTheme();

    /* =========================
       RULES POPUP
    ========================= */
    const popup = document.getElementById("rulesPopup");
    const btn = document.getElementById("acceptRules");
    const checkbox = document.getElementById("agreeCheck");

    if (!sessionStorage.getItem("rulesAccepted")) {
        popup.style.display = "flex";
    } else {
        popup.style.display = "none";
    }

    btn.disabled = true;
    btn.style.opacity = "0.6";

    checkbox.addEventListener("change", () => {
        btn.disabled = !checkbox.checked;
        btn.style.opacity = checkbox.checked ? "1" : "0.6";
    });

    btn.onclick = function () {
        sessionStorage.setItem("rulesAccepted", "true");
        popup.style.display = "none";
    };
});
</script>
 <script src="js/theme.js"></script>
</body>
</html>
