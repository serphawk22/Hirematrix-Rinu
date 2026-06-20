<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
//echo $_SESSION['candidateId'];
$name       = trim($_POST['candidate_name'] ?? '');
$position   = trim($_POST['position'] ?? '');
$resume     = trim($_POST['resume'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$_SESSION['position'] = $position;
$_SESSION['resume'] = $resume ;
$_SESSION['experience'] = $experience;
$mode = in_array($_POST['mode'] ?? '', ['mcq','coding','interview']) ? $_POST['mode'] : 'mcq';

// Handle PDF upload for interview mode
$resumeText = trim($_POST['resume'] ?? '');
if ($mode === 'interview' && !empty($_FILES['resume_pdf']['tmp_name'])) {
    require_once 'config.php';
    $pdfText = extractPdfText($_FILES['resume_pdf']['tmp_name']);
    if (strlen($pdfText) > 100) $resumeText = $pdfText . "\n\n" . $resumeText;
}


if (!$name || !$position || !$resume || !$experience) {
    header('Location: index.php?error=' . urlencode('Please fill all fields.'));
    exit;
}

$_SESSION['candidate'] = [
    'name'       => htmlspecialchars($name),
    'position'   => htmlspecialchars($position),
    'resume'     => substr($resumeText ?: $resume, 0, 3000),
    'experience' => htmlspecialchars($experience),
    'mode'       => $mode,
];
$_SESSION['round1_questions'] = null;
$_SESSION['round2_questions'] = null;
$_SESSION['coding_problems']  = null;
$_SESSION['round1_answers']   = [];
$_SESSION['round2_answers']   = [];
$_SESSION['coding_results']   = [];
$_SESSION['exam_ready']       = false;
$_SESSION['started_at']       = time();
//$_SESSION['candidateId']      = 0;
$_SESSION['candidateName']    = htmlspecialchars($name);

$isCoding   = ($mode === 'coding');
$isInterview= ($mode === 'interview');
$redirect   = $isCoding ? 'coding.php' : ($isInterview ? 'interview.php' : 'exam.php');

// Preload MCQ rounds on the server where possible to avoid client-side fetch
// failures (proctoring scripts or permission prompts may abort in-browser XHR).
if ($mode === 'mcq') {
  require_once 'config.php';
  $r1 = load_questions_from_db('aptitude', 30);
  $r2 = load_questions_from_db('technical', 30);
  if (count($r1) === 30) $_SESSION['round1_questions'] = $r1;
  if (count($r2) === 30) $_SESSION['round2_questions'] = $r2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Preparing - HireMatrix AI</title>
<link rel="icon" type="image/png" href="../jobboard/images/Serp Hwak Logo.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/style.css"/>
</head>
<body>
  <?php include 'theme-toggle.php'; ?>
<canvas id="particleCanvas"></canvas>
<div id="app"><div id="screen-container">
<div class="loading-screen animate-in">
  <div class="loading-orb" id="loadingOrb"><?= $isCoding ? '💻' : ($isInterview ? '🎤' : '🧠') ?></div>
  <h2 class="loading-title">Crafting Your <span class="text-gradient"><?= $isCoding ? 'Coding Challenge' : ($isInterview ? 'AI Interview' : 'Personalised Interview') ?></span></h2>
  <p class="loading-sub"><?= $isCoding
    ? 'GPT-4o is generating 2 original coding problems tailored to your profile…'
    : ($isInterview ? 'Setting up your personalised AI interview session with Maya…' : 'GPT-4o is generating 60 medium-to-advanced questions. This takes about 20–40 seconds.') ?></p>

  <div class="loading-steps">
    <div class="loading-step active" id="step1"><span class="step-dot"></span> Analysing your profile &amp; resume</div>
    <?php if ($isCoding): ?>
    <div class="loading-step" id="step2"><span class="step-dot"></span> Generating 2 coding problems &amp; test cases</div>
    <div class="loading-step" id="step3"><span class="step-dot"></span> Preparing editor environment</div>
    <?php elseif ($isInterview): ?>
    <div class="loading-step" id="step2"><span class="step-dot"></span> Setting up Maya — your AI interviewer</div>
    <div class="loading-step" id="step3"><span class="step-dot"></span> Preparing your interview session</div>
    <?php else: ?>
    <div class="loading-step" id="step2"><span class="step-dot"></span> Generating Round 1 — Aptitude (30 Qs)</div>
    <div class="loading-step" id="step3"><span class="step-dot"></span> Generating Round 2 — Technical (30 Qs)</div>
    <div class="loading-step" id="step4"><span class="step-dot"></span> Finalising exam environment</div>
    <?php endif; ?>
  </div>
  <p id="errorMsg" style="color:#fca5a5;display:none;font-size:.9rem;max-width:400px;text-align:center"></p>
</div>
</div></div>

<script src="js/particles.js"></script>
<script>
const IS_CODING   = <?= $isCoding   ? 'true' : 'false' ?>;
const IS_INTERVIEW= <?= $isInterview? 'true' : 'false' ?>;
const REDIRECT    = '<?= $redirect ?>';
const MAX_STEPS   = (IS_CODING || IS_INTERVIEW) ? 3 : 4;
const SERVER_ROUNDS_AVAILABLE = <?= (!empty($_SESSION['round1_questions']) && !empty($_SESSION['round2_questions'])) ? 'true' : 'false' ?>;

function setStep(n) {
  for (let i = 1; i <= MAX_STEPS; i++) {
    const el = document.getElementById('step' + i);
    if (!el) continue;
    el.classList.remove('active', 'done');
    if (i < n) el.classList.add('done');
    if (i === n) el.classList.add('active');
  }
}

async function generate() {
  try {
    if (SERVER_ROUNDS_AVAILABLE) {
      // Server already prepared rounds — skip client fetches and redirect.
      window.location.href = REDIRECT;
      return;
    }
    if (IS_CODING) {
      setStep(2);
      const r = await fetch('api/generate_coding.php');
      const d = await r.json();
      if (!d.success) throw new Error(d.error || 'Problem generation failed');
      setStep(3);
      await new Promise(r => setTimeout(r, 600));
    } else if (IS_INTERVIEW) {
      setStep(2);
      await new Promise(r => setTimeout(r, 800));
      setStep(3);
      await new Promise(r => setTimeout(r, 600));
    } else {
      setStep(2);
      const r1 = await fetch('api/generate.php?round=1');
      const d1 = await r1.json();
      if (!d1.success) throw new Error(d1.error || 'Round 1 failed');
      setStep(3);
      const r2 = await fetch('api/generate.php?round=2');
      const d2 = await r2.json();
      if (!d2.success) throw new Error(d2.error || 'Round 2 failed');
      setStep(4);
      await new Promise(r => setTimeout(r, 600));
    }
    window.location.href = REDIRECT;
  } catch(e) {
    document.getElementById('errorMsg').textContent = '⚠️ ' + e.message + ' — Please reload and try again.';
    document.getElementById('errorMsg').style.display = 'block';
    document.getElementById('loadingOrb').textContent = '❌';
    document.getElementById('loadingOrb').style.background = 'rgba(239,68,68,.3)';
  }
}

setTimeout(() => { setStep(1); setTimeout(generate, 700); }, 400);
</script> 
<script src="js/theme.js"></script> 
<script src="js/prevent-back.js"></script> 
</body>
</html>
