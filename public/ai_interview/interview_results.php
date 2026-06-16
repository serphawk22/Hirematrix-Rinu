<?php
session_start();
echo $_SESSION['candidateId'];
if (empty($_SESSION['candidate']) || empty($_SESSION['interview_report'])) { header('Location: index.php'); exit; }
$cand   = $_SESSION['candidate'];
$report = $_SESSION['interview_report'];
//echo $_SESSION['position'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Interview Report - HireMatrix AI</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/style.css"/>
<style>
.report-page{max-width:900px;margin:0 auto;padding:40px 24px}
.report-header{text-align:center;margin-bottom:40px}
.report-title{font-size:2rem;font-weight:800;margin-bottom:4px}
.overall-card{background:rgba(255,255,255,.04);border:1px solid var(--border-sub);border-radius:20px;padding:32px;display:grid;grid-template-columns:auto 1fr;gap:32px;align-items:center;margin-bottom:32px}
.score-ring{width:120px;height:120px;flex-shrink:0}
.score-svg{width:100%;height:100%}
.recommend-badge{display:inline-block;padding:6px 18px;border-radius:var(--r-full);font-weight:700;font-size:.85rem;margin-bottom:12px}
.recommend-hire{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.recommend-maybe{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid rgba(245,158,11,.3)}
.recommend-no{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.3)}
.scores-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px}
.score-card{background:rgba(255,255,255,.03);border:1px solid var(--border-sub);border-radius:14px;padding:16px}
.sc-label{font-size:.72rem;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px}
.sc-score{font-size:1.6rem;font-weight:800;margin-bottom:6px}
.sc-bar{height:5px;border-radius:var(--r-full);background:rgba(255,255,255,.07);margin-bottom:8px;overflow:hidden}
.sc-fill{height:100%;border-radius:var(--r-full)}
.sc-feedback{font-size:.78rem;color:var(--t2);line-height:1.5}
.section-card{background:rgba(255,255,255,.03);border:1px solid var(--border-sub);border-radius:16px;padding:22px;margin-bottom:20px}
.section-title{font-size:.8rem;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.07em;margin-bottom:14px}
.pill-list{display:flex;flex-wrap:wrap;gap:8px}
.pill{padding:5px 14px;border-radius:var(--r-full);font-size:.8rem;font-weight:500}
.pill.green{background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.25)}
.pill.red{background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.25)}
.pill.blue{background:rgba(99,102,241,.1);color:var(--accent-l);border:1px solid rgba(99,102,241,.25)}
.moment-item{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-sub)}
.moment-item:last-child{border:none}
.moment-icon{font-size:1rem;flex-shrink:0;margin-top:2px}
.moment-text{font-size:.85rem;color:var(--t2);line-height:1.5}
.hiring-notes{font-size:.88rem;color:var(--t2);line-height:1.7;font-style:italic;padding:16px;background:rgba(255,255,255,.02);border-left:3px solid var(--accent);border-radius:0 8px 8px 0}
</style>
<link rel="stylesheet" href="css/style.css?v=candidate-ui"/>
</head>
<body>
  <?php include 'theme-toggle.php'; ?>
<canvas id="particleCanvas"></canvas>
<div id="app" style="position:relative;z-index:1;min-height:100vh;overflow-y:auto">
<div class="report-page">

  <div class="report-header">
    <div class="badge badge-accent" style="margin-bottom:16px;font-size:.75rem">🎤 Interview Complete</div>
    <h1 class="report-title text-gradient">Interview Performance Report</h1>
    <p style="color:var(--t2);font-size:.95rem"><?= htmlspecialchars($cand['name']) ?> · <?= htmlspecialchars($cand['position']) ?> · <?= date('d M Y') ?></p>
  </div>

  <?php
  $overall = $report['overall_score'] ?? 70;
  $rec     = $report['recommendation'] ?? 'Maybe';
  $summary = $report['summary'] ?? '';
  $scores  = $report['scores'] ?? [];
  $strengths = $report['strengths'] ?? [];
  $weaknesses = $report['weaknesses'] ?? [];
  $improvements = $report['improvements'] ?? [];
  $moments   = $report['key_moments'] ?? [];
  $notes     = $report['hiring_notes'] ?? '';

  $recClass = str_contains(strtolower($rec),'no') ? 'recommend-no' : (str_contains(strtolower($rec),'strong') || str_contains(strtolower($rec),'hire') ? 'recommend-hire' : 'recommend-maybe');

  // SVG ring color
  $ringColor = $overall >= 75 ? '#4ade80' : ($overall >= 55 ? '#fcd34d' : '#fca5a5');
  $dash = round($overall * 2.83); // circumference ~283 for r=45

require_once 'config.php';
$conn = db_connect();

if ($conn->connect_error) {
    die('DB Error: ' . $conn->connect_error);
}

$candidateId   = $_SESSION['candidateId'] ?? 0;
$candidateName = $_SESSION['candidateName'] ?? 'unknown';
$jobrole = $_SESSION['position'] ?? 'Unknown';
// Function to insert (avoids duplicate)
function insertRound($conn, $candidateId, $candidateName, $roundName, $jobrole, $totalQ, $score) {
    
    $pct = ($totalQ > 0) ? round(($score / $totalQ) * 100) : 0;

    // Check duplicate
    $checkStmt = $conn->prepare("
        SELECT id FROM interview_results 
        WHERE candidate_id = ? AND round_name = ?
    ");
    $checkStmt->bind_param("is", $candidateId, $roundName);
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
insertRound(
    $conn,
    $candidateId,
    $candidateName,
    'AI Interview',
    $jobrole,
    6,   // total categories (6)
   $overall,
    $overall    
);
  ?>

  <!-- Overall -->
  <div class="overall-card glass-card" style="margin-bottom:28px">
    <div class="score-ring">
      <svg class="score-svg" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,.07)" stroke-width="8"/>
        <circle cx="50" cy="50" r="45" fill="none" stroke="<?= $ringColor ?>" stroke-width="8"
          stroke-dasharray="<?= $dash ?> 283" stroke-dashoffset="70" stroke-linecap="round"
          transform="rotate(-90 50 50)" style="transition:stroke-dasharray 1.5s ease"/>
        <text x="50" y="54" text-anchor="middle" font-size="22" font-weight="800" fill="#6d0df3" font-family="Inter"><?= $overall ?></text>
        <text x="50" y="68" text-anchor="middle" font-size="9" fill="#94a3b8" font-family="Inter">/100</text>
      </svg>
    </div>
    <div>
      <span class="recommend-badge <?= $recClass ?>"><?= htmlspecialchars($rec) ?></span>
      <p style="color:var(--t1);line-height:1.7;font-size:.92rem"><?= htmlspecialchars($summary) ?></p>
    </div>
  </div>

  <!-- Score breakdown -->
  <?php if ($scores): ?>
  <h3 style="font-size:.8rem;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px">Score Breakdown</h3>
  <div class="scores-grid">
    <?php foreach ($scores as $s):
      $sc  = $s['score'] ?? 70;
      $col = $sc >= 75 ? '#4ade80' : ($sc >= 55 ? '#fcd34d' : '#fca5a5');
    ?>
    <div class="score-card">
      <div class="sc-label"><?= htmlspecialchars($s['label'] ?? '') ?></div>
      <div class="sc-score" style="color:<?= $col ?>"><?= $sc ?></div>
      <div class="sc-bar"><div class="sc-fill" style="width:<?= $sc ?>%;background:<?= $col ?>"></div></div>
      <div class="sc-feedback"><?= htmlspecialchars($s['feedback'] ?? '') ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Strengths & Weaknesses -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
    <?php if ($strengths): ?>
    <div class="section-card">
      <div class="section-title">✅ Strengths</div>
      <div class="pill-list"><?php foreach($strengths as $s): ?><span class="pill green"><?= htmlspecialchars($s) ?></span><?php endforeach; ?></div>
    </div>
    <?php endif; ?>
    <?php if ($weaknesses): ?>
    <div class="section-card">
      <div class="section-title">⚠️ Areas to Improve</div>
      <div class="pill-list"><?php foreach($weaknesses as $s): ?><span class="pill red"><?= htmlspecialchars($s) ?></span><?php endforeach; ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Improvements -->
  <?php if ($improvements): ?>
  <div class="section-card" style="margin-bottom:20px">
    <div class="section-title">🎯 Action Plan</div>
    <div class="pill-list"><?php foreach($improvements as $s): ?><span class="pill blue"><?= htmlspecialchars($s) ?></span><?php endforeach; ?></div>
  </div>
  <?php endif; ?>

  <!-- Key moments -->
  <?php if ($moments): ?>
  <div class="section-card" style="margin-bottom:20px">
    <div class="section-title">💬 Key Moments</div>
    <?php foreach($moments as $m): ?>
    <div class="moment-item">
      <span class="moment-icon"><?= ($m['type']??'') === 'positive' ? '✅' : '⚠️' ?></span>
      <span class="moment-text"><?= htmlspecialchars($m['text'] ?? '') ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- HR Notes -->
  <?php if ($notes): ?>
  <div class="section-card" style="margin-bottom:32px">
    <div class="section-title">📋 Interviewer's Notes</div>
    <div class="hiring-notes"><?= htmlspecialchars($notes) ?></div>
  </div>
  <?php endif; ?>

  <div style="text-align:center;margin-bottom:40px">
    <button id="endInterviewBtn" class="btn btn-primary">🔄 End Interview</button>
  </div>
</div>
</div>
<script src="js/particles.js"></script>
 <script src="js/prevent-back.js"></script> 
<script src="js/theme.js"></script>
<script>
document.getElementById("endInterviewBtn").addEventListener("click", async function () {
    const candidate_id = <?= $_SESSION['candidateId']; ?>;
    const job_id = <?= intval($_SESSION['jobid'] ?? 0) ?>;
alert(candidate_id);
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

    window.location.href = "https://hirematrix.serphawk.in/candidate/applications";
});
    </script>
</body>
</html>
