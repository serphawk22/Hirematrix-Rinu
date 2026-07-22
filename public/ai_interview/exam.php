<?php
require_once 'config.php';
session_start();
if (empty($_SESSION['candidate'])) { header('Location: index.php'); exit; }
if (empty($_SESSION['round1_questions']) || empty($_SESSION['round2_questions'])) {
    header('Location: start.php'); exit;
}
//echo $_SESSION['candidateId'];
$cand = $_SESSION['candidate'];
// Use JSON_HEX_* flags to safely escape characters that can break inline <script> tags
$r1   = json_encode($_SESSION['round1_questions'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$r2   = json_encode($_SESSION['round2_questions'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$timeout = Q_TIMEOUT;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<script>
(function () {
    "use strict";
    // If this page is ever opened as a real top-level browser navigation
    // (direct URL, bookmark, old link) instead of inside the persistent
    // fullscreen shell, bounce into the shell so fullscreen still survives
    // the rest of the flow. No-op when already embedded in shell.php's
    // iframe (window.top !== window.self in that case).
    try {
        if (window.top === window.self) {
            var here = window.location.pathname.split('/').pop() || 'index.php';
            var qs = window.location.search ? window.location.search.slice(1) : '';
            var target = 'shell.php?p=' + encodeURIComponent(here) + (qs ? '&' + qs : '');
            window.location.replace(target);
        }
    } catch (e) {}
})();
</script>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Interview in Progress - HireMatrix AI</title>
<link rel="icon" type="image/png" href="../jobboard/images/Serp Hwak Logo.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css"/>
<link rel="stylesheet" href="../jobboard/css/fontawesome-all.min.css"/>
<link rel="stylesheet" href="css/style.css"/>

<style>
#proctoring-container {

    position: fixed;

    top: 50%;
    right: 20px;

    transform: translateY(-50%);

    width: 250px;
    height: 180px;

    z-index: 9999;

    cursor: move;
}

#video {

    width: 100%;
    height: 100%;

    border-radius: 12px;

    border: 3px solid #6c63ff;

    background: black;

    object-fit: cover;
}
    </style>
</head>
<body>
<script>
    window.candidate_id   = <?= (int) ($_SESSION['candidateId'] ?? 0); ?>;
    window.job_id         = <?= (int) ($_SESSION['jobid'] ?? 0); ?>;
    window.candidate_name = <?= json_encode($_SESSION['candidateName'] ?? null); ?>;
    window.jobrole        = <?= json_encode($_SESSION['position'] ?? null); ?>;
    window.FullscreenLockConfig = {
        maxViolations: 0,
        showStartOverlay: false
    };
</script>
<script src="js/fullscreen-lock.js"></script>
   
<canvas id="particleCanvas"></canvas>
<div id="app">
  <div id="screen-container">
    <!-- JS renders everything here -->
    
  </div>
</div>
<div id="notification" class="notification hidden"></div>
<!-- ===================================================== -->
<!-- AI PROCTORING -->
<!-- ===================================================== -->
  <?php include 'theme-toggle.php'; ?>
<div id="proctoring-container">

    <video
        id="video"
        autoplay
        muted
        playsinline
        >
    </video>

    <canvas
        id="canvas"
        style="display:none;">
    </canvas>

</div> 

<!-- FACE API -->
<script src="js/face-api.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd"></script>
<!-- PROCTORING -->
<script src="js/proctoring.js?v=2"></script>
<script>

const proctoringBox =
    document.getElementById(
        "proctoring-container"
    );

let isDragging = false;

let offsetX = 0;
let offsetY = 0;

/*
|--------------------------------------------------------------------------
| START DRAG
|--------------------------------------------------------------------------
*/

proctoringBox.addEventListener(
    "mousedown",
    e => {

        isDragging = true;

        offsetX =
            e.clientX -
            proctoringBox.offsetLeft;

        offsetY =
            e.clientY -
            proctoringBox.offsetTop;
    }
);

/*
|--------------------------------------------------------------------------
| DRAGGING
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "mousemove",
    e => {

        if (!isDragging) return;

        proctoringBox.style.left =
            (
                e.clientX - offsetX
            ) + "px";

        proctoringBox.style.top =
            (
                e.clientY - offsetY
            ) + "px";

        proctoringBox.style.right =
            "auto";

        proctoringBox.style.bottom =
            "auto";
    }
);

/*
|--------------------------------------------------------------------------
| STOP DRAG
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "mouseup",
    () => {

        isDragging = false;
    }
);

</script>
<script>
   
const CANDIDATE  = <?= json_encode($cand) ?>;
const R1_QUESTIONS = <?= $r1 ?>;
const R2_QUESTIONS = <?= $r2 ?>;
const Q_TIMEOUT  = <?= $timeout ?>;
const candidate_id = <?= $_SESSION['candidateId']; ?>;
const candidate_name = <?= json_encode($_SESSION['candidateName'] ?? ($cand['name'] ?? '')) ?>;
const jobrole = <?= json_encode($_SESSION['position'] ?? '') ?>;
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-java.min.js"></script>
<script src="js/particles.js"></script>
<script src="js/timer.js"></script>
<script src="js/questions.js"></script>
<script src="js/exam.js?v=5"></script> 
<script src="js/prevent-back.js"></script> 
<script src="js/theme.js"></script>
<script>
// Surface JS errors instead of "blank screen" failures.
window.addEventListener('error', (e) => {
  console.error('[GlobalError]', e.message, e.filename, e.lineno, e.colno, e.error);
});
window.addEventListener('unhandledrejection', (e) => {
  console.error('[UnhandledRejection]', e.reason);
});
</script>
</body>
</html>
