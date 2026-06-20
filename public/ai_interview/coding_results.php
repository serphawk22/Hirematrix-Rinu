<?php
session_start();

if (empty($_SESSION['coding_results'])) {
    header("Location: index.php");
    exit;
}

$results = $_SESSION['coding_results'];

/* ───────── CALCULATIONS ───────── */
$totalProblems = count($results);
$passedProblems = 0;
$attemptedProblems = 0;

$totalTests = 0;
$passedTests = 0;

foreach ($results as $problem) {

    $hasAttempt = !empty($problem['results']) && !empty($problem['code']);

    if ($hasAttempt) {
        $attemptedProblems++;
    }

    $allPass = true;

    if (!$hasAttempt) {
        $allPass = false;
    } else {
        foreach ($problem['results'] as $r) {
            $totalTests++;

            if (!empty($r['pass'])) {
                $passedTests++;
            } else {
                $allPass = false;
            }
        }
    }

    if ($allPass) {
        $passedProblems++;
    }
}

/* ───────── SCORE ───────── */
$score = $totalTests > 0 
    ? round(($passedTests / $totalTests) * 100)
    : 0;
$_SESSION['coding_score'] = $score;
/* ───────── TEST SCORE ───────── */
$testScore = $totalTests > 0 
    ? round(($passedTests / $totalTests) * 100)
    : 0;

/* ───────── RANK ───────── */
if ($score >= 90) $rank = "🏆 Expert";
elseif ($score >= 75) $rank = "🥇 Advanced";
elseif ($score >= 60) $rank = "🥈 Intermediate";
elseif ($score >= 40) $rank = "🥉 Beginner";
else $rank = "❌ Needs Improvement";

require_once 'config.php';
$conn = db_connect();
if (!$conn) {
    die('DB Error: Could not connect to database.');
}

$candidateId   = $_SESSION['candidateId'] ?? 0;
$candidateName = $_SESSION['candidateName'] ?? 'unknown';
$jobrole = $_SESSION['position'];

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
    'coding',
    $jobrole,
    $totalTests,
    $passedTests,
    $score  // score = passed tests
);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Results — NexusAI Interview</title>
<link rel="icon" type="image/png" href="../jobboard/images/Serp Hwak Logo.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/style.css"/>

<style> 
body {
    background: #0f172a;
    color: #e2e8f0;
    font-family: 'Inter', sans-serif;
    margin: 0;
}

/* MAIN CONTAINER */
.container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 30px;
    background: rgba(17,24,39,0.75);
    border: 1px solid #1f2937;
    border-radius: 18px;
    backdrop-filter: blur(14px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

/* HEADER */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.score-box {
    font-size: 32px;
    font-weight: 800;
}

.rank {
    font-size: 16px;
    color: #60a5fa;
}

/* PROGRESS */
.progress {
    width: 100%;
    height: 14px;
    background: #1e293b;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0 25px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg,#6366f1,#22c55e);
}

/* CARD */
.card {
    background: #020617;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #1f2937;
    transition: 0.25s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}

/* TEST */
.test {
    padding: 10px;
    margin: 8px 0;
    border-radius: 8px;
    font-size: 14px;
}

.pass { background: rgba(34,197,94,0.15); }
.fail { background: rgba(239,68,68,0.15); }
.unattempt { background: rgba(148,163,184,0.15); }

/* BUTTON */
.btn {
    padding: 12px 22px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 20px;
    font-weight: 600;
    display: inline-block;
    text-decoration: none;
}

.success { background: #22c55e; color: white; }
.failbtn { background: #ef4444; color: white; }
.primary { background: #6366f1; color: white; }

details {
    margin-top: 10px;
}

/* SECTION TITLES */
.section-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: #94a3b8;
}
</style>
<link rel="stylesheet" href="css/style.css?v=candidate-ui"/>

</head>

<body>
<div class="container">
    <?php include 'theme-toggle.php'; ?>
<!-- HEADER -->
<div class="header">
    <div>
        <div class="score-box">Score: <?= $score ?>%</div>
        <div class="rank"><?= $rank ?></div>
    </div>

    <div>
        Attempted: <?= $attemptedProblems ?> / <?= $totalProblems ?><br>
Passed: <?= $passedTests ?> / <?= $totalTests ?>
    </div>
</div>

<!-- PROBLEM SCORE -->
<div>Problem Score</div>
<div class="progress">
    <div class="progress-bar" style="width: <?= $score ?>%"></div>
</div>

<!-- TEST SCORE -->
<div>Test Case Score: <?= $passedTests ?> / <?= $totalTests ?></div>
<div class="progress">
    <div class="progress-bar" style="width: <?= $testScore ?>%"></div>
</div>

<hr style="margin:30px 0;border-color:#1f2937">

<!-- PROBLEMS -->
<?php foreach ($results as $problem): ?>

<div class="card">
    <h3><?= htmlspecialchars($problem['title']) ?></h3>

    <?php
    $hasAttempt = !empty($problem['results']) && !empty($problem['code']);
    $pTotal = count($problem['results'] ?? []);
    $pPass = 0;

    if ($hasAttempt) {
        foreach ($problem['results'] as $r) {
            if (!empty($r['pass'])) $pPass++;
        }
    }

    $pPercent = ($pTotal > 0) ? round(($pPass / $pTotal) * 100) : 0;
    ?>

    <?php if (!$hasAttempt): ?>
        <div class="test unattempt">⚠️ Not Attempted</div>
    <?php else: ?>
        <div>Passed: <?= $pPass ?> / <?= $pTotal ?></div>

        <div class="progress">
            <div class="progress-bar" style="width: <?= $pPercent ?>%"></div>
        </div>

        <details>
            <summary>View Test Cases</summary>

            <?php foreach ($problem['results'] as $i => $r): ?>
                <div class="test <?= $r['pass'] ? 'pass' : 'fail' ?>">
                    <strong>Case <?= $i+1 ?>:</strong>
                    <?= $r['pass'] ? '✅ Passed' : '❌ Failed' ?>

                    <?php if (!$r['pass']): ?>
                        <br>Expected: <?= htmlspecialchars($r['expected']) ?>
                        <br>Got: <?= htmlspecialchars($r['actual']) ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        </details>
    <?php endif; ?>

</div>

<?php endforeach; ?>

<!-- ACTION -->
<?php if ($score >= 70): ?>
     <a href="#" onclick="submitCodingRound()" class="btn btn-secondary btn-lg">
    🎤 AI Interview
</a>
  <form id="codingForm" method="POST" action="start.php" style="display: none;">
    <input type="hidden" name="candidate_name" value="<?= $_SESSION['candidateName']; ?>">
    <input type="hidden" name="position" value="<?= $_SESSION['position']; ?>">
    <input type="hidden" name="resume" value="<?= $_SESSION['resume']; ?>">
    <input type="hidden" name="experience" value="<?= $_SESSION['experience']; ?>">
    <input type="hidden" name="mode" value="interview">
</form>

<?php else: ?>
   <button id="endInterviewBtn" class="btn btn-primary">🔄 End Interview</button>
 
<?php endif; ?>
</div> 
<script src="js/theme.js"></script>
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
<script>
function submitCodingRound() {
    document.getElementById("codingForm").submit();
}
</script> 
</body>
</html>
