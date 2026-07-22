<?php
/*
|--------------------------------------------------------------------------
| PERSISTENT FULLSCREEN SHELL
|--------------------------------------------------------------------------
| This is the ONLY page that should ever be opened as a real top-level
| browser navigation for the interview flow (link it here instead of
| index.php from the job board / "Start Interview" button).
|
| Every other page in the flow (index -> start -> exam -> results ->
| start -> coding -> coding_results -> start -> interview ->
| interview_results) keeps navigating exactly as it always has - full
| PHP pages, full <form> POSTs, plain <a href> links, window.location.href
| redirects, nothing about that logic changed. The only difference is
| that all of that now happens inside an <iframe> that fills this page.
|
| Fullscreen is requested on THIS document (the one thing that never
| reloads), so no matter how many times the inner flow navigates, the
| top-level document - and therefore fullscreen - is never touched.
| That's what makes the "click once, stay fullscreen for the whole
| interview" behaviour actually work, without rewriting eight pages of
| PHP into an AJAX/JSON API.
|
| index.php (and every other flow page) will auto-redirect INTO this
| shell if someone opens them directly/top-level - see the small guard
| script added to the top of each of those files - so old bookmarks or
| direct links still get the full-flow fullscreen behaviour.
*/

session_start();

$allowedPages = [
    'index.php',
    'guidelines.php',
    'start.php',
    'exam.php',
    'results.php',
    'coding.php',
    'coding_results.php',
    'interview.php',
    'interview_results.php',
];

$page = $_GET['p'] ?? 'index.php';
if (!in_array($page, $allowedPages, true)) {
    $page = 'index.php';
}

$query = $_GET;
unset($query['p']);

$initialSrc = $page . ($query ? ('?' . http_build_query($query)) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>HireMatrix AI Interview</title>
<link rel="icon" type="image/png" href="../jobboard/images/Serp Hwak Logo.png">
<style>
    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: #0b0f10;
    }
    #ai-interview-frame {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
        background: #F8FCFB;
    }
</style>
</head>
<body>
<script>
    // The shell itself has no direct PHP session context (it never talks
    // to the DB) - the embedded page broadcasts candidate_id/job_id/etc.
    // to us via postMessage the moment it loads, see the listener below
    // and the "broadcastSessionToShell" bit added to fullscreen-lock.js.
    window.candidate_id   = 0;
    window.job_id         = 0;
    window.candidate_name = null;
    window.jobrole        = null;

    window.FullscreenLockConfig = {
        maxViolations: 0,        // 0 = unlimited, never auto end the session
        showStartOverlay: true,  // this is the only real page load - show the "Begin" click here
        requestMediaPermissionsOnStart: true // ask for camera/mic now, before fullscreen,
                                              // so proctoring's later getUserMedia() call
                                              // doesn't pop a permission prompt mid-interview
                                              // and force fullscreen to exit
    };
</script>
<script src="js/fullscreen-lock.js"></script>

<iframe
    id="ai-interview-frame"
    name="ai-interview-frame"
    src="<?= htmlspecialchars($initialSrc, ENT_QUOTES, 'UTF-8') ?>"
    allow="fullscreen; camera; microphone; clipboard-read; clipboard-write; autoplay"
    allowfullscreen
    referrerpolicy="same-origin"
></iframe>

<script>
(function () {
    "use strict";

    // Keep the shell's fullscreen-lock instance (which owns the exit-confirm
    // modal and the endInterview() redirect) in sync with whichever
    // candidate/job is currently loaded inside the iframe.
    window.addEventListener("message", function (event) {

        if (event.origin !== window.location.origin) return;

        const data = event.data || {};
        if (data.type !== "ai-interview:session") return;

        if (data.candidate_id)   window.candidate_id   = data.candidate_id;
        if (data.job_id)         window.job_id         = data.job_id;
        if (data.candidate_name) window.candidate_name = data.candidate_name;
        if (data.jobrole)        window.jobrole        = data.jobrole;
    });
})();
</script>
</body>
</html>
