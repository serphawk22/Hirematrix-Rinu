<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['candidate']) || empty($_SESSION['round1_questions'])) {
    header('Location: index.php'); exit;
}
//echo $_SESSION['candidateId'];
$cand   = $_SESSION['candidate'];
$r1q    = $_SESSION['round1_questions'] ?? [];
$r2q    = $_SESSION['round2_questions'] ?? [];
$r1a    = $_SESSION['round1_answers']   ?? [];
$r2a    = $_SESSION['round2_answers']   ?? [];

// ── Score calculation ───────────────────────────────────────────────────────
function calcCategoryScores(array $questions, array $answers): array {
    $cats = [];
    foreach ($questions as $i => $q) {
        $cat = $q['category'] ?? 'General';
        if (!isset($cats[$cat])) $cats[$cat] = ['correct'=>0,'total'=>0,'wrong'=>[]];
        $cats[$cat]['total']++;
        $correct = $answers[$i]['correct'] ?? false;
        if ($correct) $cats[$cat]['correct']++;
        else          $cats[$cat]['wrong'][] = $q['question'] ?? '';
    }
    return $cats;
}

$r1Cats   = calcCategoryScores($r1q, $r1a);
$r2Cats   = calcCategoryScores($r2q, $r2a);
$r1Score  = array_sum(array_column($r1a, 'correct'));
$r2Score  = array_sum(array_column($r2a, 'correct'));
$totalScore = $r1Score + $r2Score;
$_SESSION['totalScore'] = $totalScore;
$totalQ     = count($r1q) + count($r2q);
$pct        = $totalQ > 0 ? round(($totalScore / $totalQ) * 100) : 0;

// Grade
$grade = match(true) {
    $pct >= 90 => ['A+','Outstanding','#22c55e'],
    $pct >= 80 => ['A','Excellent','#4ade80'],
    $pct >= 70 => ['B+','Good','#6366f1'],
    $pct >= 60 => ['B','Above Average','#818cf8'],
    $pct >= 50 => ['C','Average','#f59e0b'],
    $pct >= 40 => ['D','Below Average','#f97316'],
    default    => ['F','Needs Improvement','#ef4444'],
};

// ── AI Feedback ────────────────────────────────────────────────────────────
$weakAreas = [];
foreach (array_merge($r1Cats, $r2Cats) as $cat => $data) {
    $catPct = $data['total'] > 0 ? ($data['correct'] / $data['total']) * 100 : 0;
    if ($catPct < 60) $weakAreas[$cat] = round($catPct);
}

$wrongSample = array_slice(
    array_merge(
        array_column(array_filter($r1a, fn($a) => !($a['correct'] ?? false)), 'idx'),
        array_map(fn($a) => ($a['idx'] ?? 0) + count($r1q), array_filter($r2a, fn($a) => !($a['correct'] ?? false)))
    ), 0, 8
);

$feedbackText = 'AI feedback unavailable.';
$prompt = <<<PROMPT
Candidate: {$cand['name']}, applying for {$cand['position']} ({$cand['experience']} level).
Interview score: {$totalScore}/{$totalQ} ({$pct}%).
Round 1 (Aptitude): {$r1Score}/30
Round 2 (Technical): {$r2Score}/30

Weak categories (below 60%):
PROMPT;
foreach ($weakAreas as $cat => $p) $prompt .= "\n- {$cat}: {$p}%";

$prompt .= "\n\nProvide a professional, encouraging, and specific improvement report (3-5 paragraphs). Cover:\n1. Overall performance assessment\n2. Specific weak areas and how to improve them\n3. Recommended resources/topics to study\n4. Career readiness verdict for the {$cand['position']} role\n\nReturn JSON: {\"feedback\": \"...\"}";

$fbData = openai_chat($prompt, 60);
if (!empty($fbData['feedback'])) $feedbackText = $fbData['feedback'];

// ── Per-question review ────────────────────────────────────────────────────
$allQ   = array_merge($r1q, $r2q);
$allA   = array_merge(
    array_values($r1a),
    array_values($r2a)
);
// ── STORE RESULT IN DATABASE ───────────────────────────────
$conn = db_connect();

if ($conn->connect_error) {
    die('DB Error: ' . $conn->connect_error);
}

$candidateId = $_SESSION['candidateId'] ?? 0;
$candidateName = $cand['name'] ?? 'Unknown';
$jobrole = $_SESSION['position'] ?? 'Unknown';
// Function to insert (avoids duplicate)
function insertRound($conn, $candidateId, $candidateName, $roundName, $jobrole, $totalQ, $score) {
    
    $pct = ($totalQ > 0) ? round(($score / $totalQ) * 100) : 0;

    // Check duplicate
    $checkStmt = $conn->prepare("
        SELECT id FROM interview_results 
        WHERE candidate_id = ? AND round_name = ? AND jobrole = ?
    ");
    $checkStmt->bind_param("iss", $candidateId, $roundName, $jobrole);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows == 0) {

        $stmt = $conn->prepare("
            INSERT INTO interview_results 
            (candidate_id, candidate_name, round_name, jobrole, total_questions, score, percentage) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssiii",
            $candidateId,
            $candidateName,
            $roundName,
            $jobrole,
            $totalQ,
            $score,
            $pct
        );

        $stmt->execute();
        $stmt->close();
    }

    $checkStmt->close();
}


// ✅ Insert Aptitude (Round 1)
insertRound($conn, $candidateId, $candidateName, 'Aptitude', $jobrole, count($r1q), $r1Score);

// ✅ Insert Technical (Round 2)
insertRound($conn, $candidateId, $candidateName, 'Technical', $jobrole, count($r2q), $r2Score);
insertRound($conn, $candidateId, $candidateName, 'MCQ', $jobrole, $totalQ, $totalScore);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Results - HireMatrix AI Interview</title>
<link rel="icon" type="image/png" href="../jobboard/images/Serp Hwak Logo.png"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../jobboard/css/fontawesome-all.min.css"/>
<link rel="stylesheet" href="css/style.css"/>
</head>
<body class="hirematrix-app candidate-app ai-results-page">
  <?php include 'theme-toggle.php'; ?>
<canvas id="particleCanvas"></canvas>
<div id="app" style="position:relative;z-index:1">
<div class="results-screen">
<div class="results-wrap">

  <!-- Header -->
  <div class="results-header animate-in">
    <span class="badge badge-success" style="margin-bottom:16px"><i class="fas fa-check-circle" aria-hidden="true"></i> Interview Complete</span>
    <h1>Your Results, <span class="text-gradient"><?= $cand['name'] ?></span></h1>
    <p class="text-muted" style="margin-top:10px"><?= $cand['position'] ?> · <?= ucfirst($cand['experience']) ?> Level</p>
  </div>

  <div class="results-grid">

    <!-- Radial Score -->
    <div class="glass-card result-card animate-in">
      <h3 style="margin-bottom:20px">Overall Score</h3>
      <div class="radial-wrap">
        <canvas id="radialCanvas" width="200" height="200"></canvas>
        <div class="radial-label">
          <div style="font-size:3rem;font-weight:900;color:<?= $grade[2] ?>"><?= $pct ?>%</div>
          <div style="font-size:1.1rem;font-weight:700;margin-top:4px"><?= $totalScore ?> / <?= $totalQ ?> correct</div>
          <div style="margin-top:8px"><span class="badge" style="background:<?= $grade[2] ?>22;color:<?= $grade[2] ?>;border:1px solid <?= $grade[2] ?>44;font-size:.9rem;padding:6px 18px">Grade <?= $grade[0] ?> — <?= $grade[1] ?></span></div>
        </div>
      </div>
    </div>

    <!-- Category Breakdown -->
    <div class="glass-card result-card animate-in">
      <h3 style="margin-bottom:20px">Category Breakdown</h3>
      <div class="category-list">
        <?php foreach (array_merge($r1Cats, $r2Cats) as $cat => $data):
          $cp  = $data['total'] > 0 ? round(($data['correct']/$data['total'])*100) : 0;
          $col = $cp >= 70 ? '#22c55e' : ($cp >= 50 ? '#f59e0b' : '#ef4444');
        ?>
        <div class="cat-row">
          <div class="cat-row-header">
            <span class="cat-name"><?= htmlspecialchars($cat) ?></span>
            <span class="cat-score"><?= $data['correct'] ?>/<?= $data['total'] ?> (<?= $cp ?>%)</span>
          </div>
          <div class="cat-bar-bg">
            <div class="cat-bar-fill" style="width:<?= $cp ?>%;background:<?= $col ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Round Scores -->
    <div class="glass-card result-card animate-in" style="grid-column:1/-1">
      <h3 style="margin-bottom:20px">Round Summary</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <?php
        $rounds = [
            ['Round 1', 'Aptitude', $r1Score, 30, 'fa-graduation-cap', '#1FB7B5'],
            ['Round 2', 'Technical', $r2Score, 30, 'fa-laptop', '#1FB7B5'],
        ];
        foreach($rounds as [$rl,$rn,$rs,$rt,$ri,$rc]):
          $rp = round(($rs/$rt)*100);
        ?>
        <div class="result-round-card">
          <div class="result-round-icon"><i class="fas <?= $ri ?>" aria-hidden="true"></i></div>
          <div style="font-size:.78rem;color:var(--t3);text-transform:uppercase;letter-spacing:.06em"><?= $rl ?> — <?= $rn ?></div>
          <div style="font-size:2.5rem;font-weight:900;color:<?= $rc ?>;margin:8px 0"><?= $rs ?><span style="font-size:1rem;color:var(--t2)"> / <?= $rt ?></span></div>
          <div style="font-size:.85rem;color:var(--t2)"><?= $rp ?>% accuracy</div>
          <div style="margin-top:10px;height:6px;background:rgba(255,255,255,.06);border-radius:3px"><div style="height:100%;width:<?= $rp ?>%;background:<?= $rc ?>;border-radius:3px;transition:width 1s ease"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- AI Feedback -->
    <div class="glass-card feedback-card animate-in">
      <div class="feedback-title">
        <i class="fas fa-chart-line" aria-hidden="true"></i>
        AI-Powered Improvement Report
      </div>
      <div class="feedback-text"><?= nl2br(htmlspecialchars($feedbackText)) ?></div>
    </div>

    <?php if (!empty($weakAreas)): ?>
    <!-- Weak Areas Heatmap -->
    <div class="glass-card result-card animate-in">
      <h3 style="margin-bottom:16px"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Areas to Improve</h3>
      <div class="weak-area-list">
        <?php foreach($weakAreas as $cat => $p):
          $col = $p < 30 ? '#ef4444' : '#f59e0b';
        ?>
        <div class="weak-area-row">
          <span class="weak-area-icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
          <div style="flex:1">
            <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($cat) ?></div>
            <div style="font-size:.78rem;color:var(--t3)">Only <?= $p ?>% accuracy</div>
          </div>
          <span class="weak-area-score"><?= $p ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Question Review -->
    <div class="glass-card q-review-card animate-in">
      <h3 style="margin-bottom:16px"><i class="fas fa-clipboard" aria-hidden="true"></i> Question Review</h3>
      <div class="review-list">
        <?php foreach($allQ as $i => $q):
          $ans     = $allA[$i] ?? null;
          $correct = $ans['correct'] ?? false;
          $skipped = $ans === null || $ans['answer'] === null;
        ?>
        <div class="review-item">
          <div class="review-icon"><i class="fas <?= $skipped ? 'fa-step-forward' : ($correct ? 'fa-check' : 'fa-times') ?>" aria-hidden="true"></i></div>
          <div class="review-q">
            <strong>Q<?= $i+1 ?>. <?= htmlspecialchars(substr($q['question']??'',0,100)) ?>…</strong>
            <span style="color:var(--t3);font-size:.78rem;margin-left:8px"><?= $q['category'] ?? '' ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /results-grid -->

  <!-- Actions -->
  <div class="results-actions animate-in">
 
    <a href="#" onclick="submitCodingRound()" class="btn btn-secondary btn-lg">
    <i class="fas fa-laptop" aria-hidden="true"></i> Coding Round
</a>
  <form id="codingForm" method="POST" action="start.php" style="display: none;">
    <input type="hidden" name="candidate_name" value="<?= $_SESSION['candidateName']; ?>">
    <input type="hidden" name="position" value="<?= $_SESSION['position']; ?>">
    <input type="hidden" name="resume" value="<?= $_SESSION['resume']; ?>">
    <input type="hidden" name="experience" value="<?= $_SESSION['experience']; ?>">
    <input type="hidden" name="mode" value="coding">
</form> 
<button id="endInterviewBtn" class="btn btn-primary"><i class="fas fa-sync-alt" aria-hidden="true"></i> End Interview</button> 
    <button class="btn btn-secondary btn-lg" id="copyReportLinkBtn">Copy Share Link</button>
    <button class="btn btn-secondary btn-lg" id="downloadHtmlBtn">Download HTML Report</button>
    <button class="btn btn-secondary btn-lg" onclick="window.print()"><i class="fas fa-print" aria-hidden="true"></i> Print Report</button>
  </div>

</div><!-- /results-wrap -->
</div><!-- /results-screen -->
</div>

<script src="js/theme.js"></script>
<script>
// Animated radial score gauge
(function(){
  const canvas = document.getElementById('radialCanvas');
  if(!canvas) return;
  const ctx = canvas.getContext('2d');
  const cx=100, cy=100, r=80, pct=<?= $pct ?>/100;
  const color='<?= $grade[2] ?>';
  let current=0;
  function draw(){
    ctx.clearRect(0,0,200,200);
    // Track
    ctx.beginPath(); ctx.arc(cx,cy,r,-Math.PI*.75,Math.PI*.75);
    ctx.strokeStyle=document.body.classList.contains('dark') ? '#26383e' : '#e8eef6'; ctx.lineWidth=14; ctx.lineCap='round'; ctx.stroke();
    // Fill
    const end = -Math.PI*.75 + (Math.PI*1.5)*current;
    ctx.beginPath(); ctx.arc(cx,cy,r,-Math.PI*.75,end);
    ctx.strokeStyle='#1FB7B5'; ctx.lineWidth=14; ctx.lineCap='round'; ctx.stroke();
    // Glow
    ctx.beginPath(); ctx.arc(cx,cy,r,-Math.PI*.75,end);
    ctx.strokeStyle='rgba(31,183,181,.22)'; ctx.lineWidth=22; ctx.lineCap='round'; ctx.stroke();
  }
  function animate(){
    if(current < pct){ current = Math.min(current+0.015, pct); draw(); requestAnimationFrame(animate); }
    else draw();
  }
  animate();
})();

// Animate bar fills
window.addEventListener('load', () => {
  document.querySelectorAll('.cat-bar-fill').forEach(el => {
    const w = el.style.width; el.style.width='0';
    setTimeout(()=>{ el.style.transition='width 1.2s ease'; el.style.width=w; },200);
  });
});
</script>
<script>
(function(){
  const linkBtn = document.getElementById('copyReportLinkBtn');
  if (linkBtn) {
    linkBtn.addEventListener('click', async () => {
      const url = window.location.href;
      try {
        await navigator.clipboard.writeText(url);
        const prev = linkBtn.textContent;
        linkBtn.textContent = 'Link Copied';
        setTimeout(() => (linkBtn.textContent = prev), 1400);
      } catch (e) {
        window.prompt('Copy this link:', url);
      }
    });
  }

  const dlBtn = document.getElementById('downloadHtmlBtn');
  if (dlBtn) {
    dlBtn.addEventListener('click', () => {
      const name = <?= json_encode($cand['name'] ?? 'candidate') ?>;
      const safe = String(name).replace(/[^a-z0-9_-]+/gi, '_').slice(0, 40) || 'report';
      const stamp = new Date().toISOString().slice(0, 10);
      const filename = `hirematrix-ai-report-${safe}-${stamp}.html`;

      const html = '<!doctype html>\\n' + document.documentElement.outerHTML;
      const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      setTimeout(() => {
        URL.revokeObjectURL(a.href);
        a.remove();
      }, 0);
    });
  }
})();
</script>
 <script>
function submitCodingRound() {
    document.getElementById("codingForm").submit();
}
</script>
<script>
function appUrl(path) {
    const marker = '/ai_interview/';
    const markerIndex = window.location.pathname.indexOf(marker);
    const appBasePath = markerIndex >= 0 ? window.location.pathname.slice(0, markerIndex) : '';
    return window.location.origin + appBasePath + '/' + String(path).replace(/^\/+/, '');
}

document.getElementById("endInterviewBtn").addEventListener("click", async function () {
    const candidate_id = <?= $_SESSION['candidateId']; ?>;
    const job_id = <?= intval($_SESSION['jobid'] ?? 0) ?>;

    try {
        await fetch("api/update_status.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                candidate_id: candidate_id,
                job_id: job_id // ✅ send both
            })
        });

    } catch (e) {
        console.error("Status update failed", e);
    }

    window.location.href = appUrl("candidate/applications");
});
    </script> 
</body>
</html>
