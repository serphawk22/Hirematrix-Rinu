/*
|--------------------------------------------------------------------------
| FULLSCREEN LOCK (standalone, self-contained build)
|--------------------------------------------------------------------------
|
| Drop this ONE file into every interview page (interview.php, exam.php,
| coding.php, results.php, etc). It is fully self-contained - all its
| helper functions and styles live inside its own closure, so it will
| NOT collide with anything of the same name in proctoring.js or any
| other script also loaded on the page. Include it everywhere you want
| the fullscreen lock behaviour, independent of whether that page also
| has the camera/proctoring script.
|
| WHAT IT DOES
| ------------
|   1. Shows a small "Click to begin interview" start overlay on load.
|      Clicking it (or the first click/keypress/tap anywhere, as a
|      fallback) is the real user gesture the browser requires before
|      it will allow going fullscreen - so this is the *earliest*
|      possible moment fullscreen can be entered, not an arbitrary delay.
|   2. If fullscreen is exited (Esc, F11, swipe, etc.), OR the window
|      loses focus (Alt+Tab, Cmd+Tab, clicking the taskbar/another
|      window), OR the tab/window is hidden (minimized, laptop lid
|      closed) - it logs a violation and shows a VISIBLE modal that
|      blocks the whole page until the candidate picks one of two
|      options:
|         - "Resume Interview" -> re-requests fullscreen (reliable,
|           because clicking this button is a genuine user gesture).
|         - "End Interview"    -> posts to update_status.php with
|           candidate_id/job_id, then redirects away from the interview.
|   3. Reports every violation to report_violation.php - reusing
|      window.showWarning()/window.reportViolation() if proctoring.js
|      (or anything else) has already defined them on the page, so you
|      don't get two different-looking warning toasts. Falls back to
|      its own fetch() call if those aren't present.
|   4. Exposes window.FullscreenLock.endInterview() - a single shared
|      "end the interview" action (same fetch + redirect used by the
|      violation modal's End Interview button) that any other button
|      on the page (e.g. a normal "End Interview" button in your own
|      toolbar) can call too, so the logic only lives in one place.
|
| IMPORTANT BROWSER LIMITATION - PLEASE ACTUALLY READ THIS:
| No script, on any site, can stop Esc / Alt+Tab / Cmd+Tab / clicking the
| taskbar / closing the laptop lid from doing what they do, and no script
| can force fullscreen without a user gesture. Browsers deliberately
| prevent a page from trapping a user like that or auto-fullscreening
| itself - this is a security policy enforced identically in every
| browser, not a bug in this file. The only thing achievable in web JS
| is: make the required gesture happen as early/obviously as possible,
| and the instant an exit is detectable, log it and make the page
| unusable until the candidate makes a choice - which is exactly what
| this file does. A hard, unbypassable lock needs a native kiosk-mode
| client outside the browser, not web JS.
|
| REQUIRED GLOBALS (set these BEFORE including this script, on every page)
| --------------------------------------------------------------------
|   <script>
|       window.candidate_id   = <?= (int) ($_SESSION['candidateId'] ?? 0); ?>;
|       window.job_id         = <?= (int) ($_SESSION['jobid'] ?? 0); ?>;
|       window.candidate_name = <?= json_encode($_SESSION['candidateName'] ?? null); ?>;
|       window.jobrole        = <?= json_encode($_SESSION['position'] ?? null); ?>;
|   </script>
|   <script src="js/fullscreen-lock.js"></script>
|
| OPTIONAL CONFIG (set BEFORE the script tag above)
| --------------------------------------------------------------------
|   <script>
|       window.FullscreenLockConfig = {
|           maxViolations: 5,             // 0 = unlimited, no auto-terminate
|           debug: false,                 // true = verbose console timing logs
|           onMaxViolationsExceeded: function () {
|               window.location.href = "results.php";
|           }
|       };
|   </script>
|
| RECOMMENDED: only show the "Begin Interview" overlay on the FIRST page
| of the flow (index.php), where the candidate hasn't clicked anything
| yet. Leave it off on every later page so fullscreen just silently
| re-enters on whatever the candidate clicks first there - no extra
| click added:
|   <script>
|       window.FullscreenLockConfig = {
|           showStartOverlay: true   // only set this on index.php
|       };
|   </script>
|
| USING THE SHARED "END INTERVIEW" ACTION FROM YOUR OWN BUTTON
| --------------------------------------------------------------------
|   <button id="endInterviewBtn">End Interview</button>
|   <script>
|       document.getElementById("endInterviewBtn").addEventListener("click", function () {
|           window.FullscreenLock.endInterview(); // same fetch+redirect the modal uses
|       });
|   </script>
|
| When the interview finishes/submits normally through your own flow
| (not via the button above), call window.FullscreenLock.stop() so
| leaving fullscreen after submission isn't logged as a violation and
| the modal doesn't pop up.
|
*/

(function () {

    "use strict";

    /*
    |----------------------------------------------------------------------
    | SHELL-AWARE MODE (added for the persistent-fullscreen shell)
    |----------------------------------------------------------------------
    | When the whole flow (index -> start -> exam -> results -> ... )
    | is loaded inside shell.php's iframe, only the TOP window (shell.php)
    | should ever call requestFullscreen()/own the start overlay/listen
    | for fullscreenchange-blur-visibility. Every child page still includes
    | this exact same file (no per-page changes needed), but detects that
    | it is running inside that iframe and quietly steps aside, letting
    | the shell do all fullscreen enforcement. This is what makes fullscreen
    | survive every normal page navigation in the flow: the frame that
    | actually holds fullscreen (the shell) never reloads.
    |
    | If a page is ever opened standalone (direct URL, bookmark, testing),
    | window.top === window.self and this file behaves exactly as it did
    | before - fully self-sufficient, own start overlay included.
    */
    let isTopWindow = true;
    try {
        isTopWindow = (window.top === window.self);
    } catch (e) {
        isTopWindow = false; // cross-origin frame access blocked -> treat as child
    }

    const APP_BASE_PATH = (() => {
        const marker = "/ai_interview/";
        const pathname = window.location.pathname;
        const markerIndex = pathname.indexOf(marker);
        return markerIndex >= 0
            ? pathname.slice(0, markerIndex).replace(/\/+$/, "")
            : "";
    })();

    function appUrl(path = "") {
        const cleanPath = String(path).replace(/^\/+/, "");
        return window.location.origin + APP_BASE_PATH + "/" + cleanPath;
    }

    function aiInterviewUrl(path = "") {
        return appUrl("ai_interview/" + String(path).replace(/^\/+/, ""));
    }

    function aiInterviewApiUrl(path = "") {
        return aiInterviewUrl("api/" + String(path).replace(/^\/+/, ""));
    }

    const INTERVIEW_EXIT_URL = appUrl("candidate/applications");

    const DEFAULTS = {

        exitViolationMessage: "You exited fullscreen mode.",
        blurViolationMessage: "You switched away from the interview window.",
        tabHiddenViolationMessage: "You switched away from the interview tab.",

        maxViolations: 0,

        onMaxViolationsExceeded: function () {

            alert(
                "You have exceeded the allowed number of fullscreen/focus " +
                "violations. This interview session will now be flagged."
            );
        },

        violationDebounceMs: 1200,

        showStartOverlay: false,
        startOverlayMessage: "Click anywhere to begin your interview in fullscreen mode.",

        // When true, the "Begin Interview" click requests camera+mic
        // permission FIRST (and releases the tracks immediately), then
        // enters fullscreen. Only relevant on the shell (shell.php) -
        // proctoring.js will re-request the same already-granted
        // permission later without the browser showing a prompt again,
        // so fullscreen never gets force-exited mid-interview by a
        // permission dialog. Leave false on every other page.
        requestMediaPermissionsOnStart: false,

        debug: false
    };

    const CONFIG = Object.assign(
        {},
        DEFAULTS,
        window.FullscreenLockConfig || {}
    );

    const T0 = Date.now();

    function log() {

        if (!CONFIG.debug) return;
        const args = Array.prototype.slice.call(arguments);
        console.log("[fullscreen-lock +" + (Date.now() - T0) + "ms]", ...args);
    }

    let active = true;
    let violationCount = 0;
    let lastViolationAt = 0;
    let pendingArmed = false;
    let endInterviewInFlight = false;

    function isFullscreen() {

        return !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement
        );
    }

    function requestFullscreen() {

        const el = document.documentElement;

        const request =
            el.requestFullscreen ||
            el.webkitRequestFullscreen ||
            el.mozRequestFullScreen ||
            el.msRequestFullscreen;

        if (!request) {

            console.warn("Fullscreen API not supported in this browser.");
            return Promise.reject(new Error("Fullscreen API not supported"));
        }

        log("requestFullscreen() called");

        return Promise.resolve()
            .then(() => request.call(el))
            .then(() => {
                log("requestFullscreen() resolved, isFullscreen =", isFullscreen());
            })
            .catch((err) => {
                log("requestFullscreen() rejected:", err && err.message);
                console.warn("Fullscreen request failed:", err);
            });
    }

    window.enableFullscreen = requestFullscreen;

    /*
    |----------------------------------------------------------------------
    | NON-NATIVE CONFIRM / ALERT
    |----------------------------------------------------------------------
    | Native window.confirm()/alert()/prompt() are blocking browser-chrome
    | dialogs - showing ANY of them forces the whole tab out of fullscreen
    | (same underlying browser security rule that stops a page
    | auto-fullscreening itself; a getUserMedia() permission prompt does
    | the exact same thing). Since the shell's fullscreen is what the
    | whole "no re-click needed" flow depends on, any page in the flow
    | that calls a native dialog was making the shell think the candidate
    | had genuinely exited fullscreen.
    |
    | window.AiInterviewDialog.confirm(message) / .alert(message) are
    | drop-in async replacements built from plain DOM - visually similar
    | to the exit-confirm modal - that never touch fullscreen at all.
    | Use them instead of confirm()/alert()/prompt() anywhere in the flow.
    */
    function beginFullscreenFlow() {

        if (
            !CONFIG.requestMediaPermissionsOnStart ||
            !navigator.mediaDevices ||
            !navigator.mediaDevices.getUserMedia
        ) {
            return requestFullscreen();
        }

        log("Requesting camera/mic permission before entering fullscreen");

        return navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then((stream) => {
                // We don't need to keep this stream open - proctoring.js
                // opens its own later. This call's only job is to make
                // the browser show (and resolve) the permission prompt
                // NOW, while we're not fullscreen yet, instead of later
                // mid-interview where showing that prompt would force
                // fullscreen to exit.
                stream.getTracks().forEach((track) => track.stop());
            })
            .catch((err) => {
                log("Camera/mic permission not granted:", err && err.message);
                console.warn(
                    "Camera/microphone permission was not granted before " +
                    "starting - proctoring may prompt again during the interview.",
                    err
                );
            })
            .then(() => requestFullscreen());
    }

    function ensureDialogStyle() {

        if (document.getElementById("ai-dialog-style")) return;

        const style = document.createElement("style");
        style.id = "ai-dialog-style";
        style.innerHTML = `

            #ai-dialog-overlay {
                position: fixed;
                inset: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 2147483647;
                background: rgba(248, 252, 251, 0.96);
                backdrop-filter: blur(12px);
            }

            body.dark #ai-dialog-overlay {
                background: rgba(17, 17, 17, 0.96);
            }

            #ai-dialog-box {
                width: 420px;
                max-width: 92%;
                padding: 34px;
                border-radius: 22px;
                text-align: center;
                font-family: 'Inter', sans-serif;
                background: #FFFFFF;
                border: 1px solid #D9ECE5;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            }

            body.dark #ai-dialog-box {
                background: #162327;
                border: 1px solid #23343A;
                box-shadow: 0 20px 60px rgba(0,0,0,0.55);
            }

            #ai-dialog-message {
                font-size: 16px;
                line-height: 1.7;
                color: #16212B;
                margin-bottom: 22px;
                white-space: pre-line;
            }

            body.dark #ai-dialog-message {
                color: #F8FAFC;
            }

            #ai-dialog-actions {
                display: flex;
                gap: 12px;
            }

            #ai-dialog-actions button {
                flex: 1;
                padding: 12px 18px;
                border-radius: 12px;
                border: none;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
            }

            #ai-dialog-ok {
                background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
                color: #ffffff;
            }

            #ai-dialog-cancel {
                background: transparent;
                border: 1px solid rgba(100, 116, 139, 0.4) !important;
                color: #64748B;
            }
        `;

        document.head.appendChild(style);
    }

    function showDialog(message, options) {

        const opts = Object.assign({
            showCancel: false,
            okText: "OK",
            cancelText: "Cancel"
        }, options || {});

        return new Promise((resolve) => {

            ensureDialogStyle();

            const overlay = document.createElement("div");
            overlay.id = "ai-dialog-overlay";

            overlay.innerHTML = `
                <div id="ai-dialog-box">
                    <p id="ai-dialog-message"></p>
                    <div id="ai-dialog-actions">
                        ${opts.showCancel ? '<button id="ai-dialog-cancel"></button>' : ""}
                        <button id="ai-dialog-ok"></button>
                    </div>
                </div>
            `;

            overlay.querySelector("#ai-dialog-message").textContent = message;
            overlay.querySelector("#ai-dialog-ok").textContent = opts.okText;

            if (opts.showCancel) {
                overlay.querySelector("#ai-dialog-cancel").textContent = opts.cancelText;
            }

            document.documentElement.appendChild(overlay);

            function close(result) {
                overlay.remove();
                resolve(result);
            }

            overlay.querySelector("#ai-dialog-ok").addEventListener("click", () => close(true));

            if (opts.showCancel) {
                overlay.querySelector("#ai-dialog-cancel").addEventListener("click", () => close(false));
            }
        });
    }

    window.AiInterviewDialog = {

        alert: function (message, okText) {
            return showDialog(message, { showCancel: false, okText: okText || "OK" });
        },

        confirm: function (message, okText, cancelText) {
            return showDialog(message, {
                showCancel: true,
                okText: okText || "OK",
                cancelText: cancelText || "Cancel"
            });
        }
    };

    function fallbackReport(message) {

        try {

            fetch(aiInterviewApiUrl("report_violation.php"), {

                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({

                    candidate_id: window.candidate_id || null,
                    candidate_name: window.candidate_name || null,
                    jobrole: window.jobrole || null,
                    message: message
                })

            }).catch((err) => console.error(err));

        } catch (err) {

            console.error("fullscreen-lock: failed to report violation", err);
        }
    }

    function logViolation(message) {

        if (!active) return;

        const now = Date.now();

        if (now - lastViolationAt < CONFIG.violationDebounceMs) {

            log("Duplicate violation suppressed (debounced):", message);
            return;
        }

        lastViolationAt = now;
        violationCount++;

        log("VIOLATION #" + violationCount + ":", message);

        if (typeof window.showWarning === "function") {

            window.showWarning(message);
        }

        if (typeof window.reportViolation === "function") {

            window.reportViolation(message);

        } else {

            fallbackReport(message);
        }

        if (
            CONFIG.maxViolations > 0 &&
            violationCount >= CONFIG.maxViolations
        ) {

            active = false;
            CONFIG.onMaxViolationsExceeded();
        }
    }

    function endInterview(triggerButton) {

        if (endInterviewInFlight) return Promise.resolve();
        endInterviewInFlight = true;

        if (triggerButton) {
            triggerButton.disabled = true;
            triggerButton.textContent = "Ending interview...";
        }

        return fetch(aiInterviewApiUrl("update_status.php"), {

            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                candidate_id: window.candidate_id || null,
                job_id: window.job_id || 0
            })

        }).catch((err) => {

            console.error("Status update failed", err);

        }).finally(() => {

            active = false;
            (window.top || window).location.href = INTERVIEW_EXIT_URL;
        });
    }

    let modalEl = null;

    function injectSharedStyle() {

        if (document.getElementById("exit-confirm-style")) return;

        const style = document.createElement("style");
        style.id = "exit-confirm-style";
        style.innerHTML = `

            #exit-confirm-overlay,
            #fs-start-overlay {
                position: fixed;
                inset: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 2147483647;
                background: rgba(248, 252, 251, 0.96);
                backdrop-filter: blur(12px);
            }

            body.dark #exit-confirm-overlay,
            body.dark #fs-start-overlay {
                background: rgba(17, 17, 17, 0.96);
            }

            #exit-confirm-box,
            #fs-start-box {
                width: 460px;
                max-width: 92%;
                padding: 38px;
                border-radius: 24px;
                text-align: center;
                font-family: 'Inter', sans-serif;
                background: #FFFFFF;
                border: 1px solid #D9ECE5;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            }

            body.dark #exit-confirm-box,
            body.dark #fs-start-box {
                background: #162327;
                border: 1px solid #23343A;
                box-shadow: 0 20px 60px rgba(0,0,0,0.55);
            }

            #exit-confirm-icon,
            #fs-start-icon {
                width: 85px;
                height: 85px;
                margin: 0 auto 22px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 42px;
            }

            #exit-confirm-icon {
                background: rgba(224, 82, 79, 0.16);
                border: 1px solid rgba(224, 82, 79, 0.35);
                box-shadow: 0 0 25px rgba(224, 82, 79, 0.35);
            }

            body.dark #exit-confirm-icon {
                background: rgba(224, 82, 79, 0.2);
                border: 1px solid rgba(224, 82, 79, 0.4);
                box-shadow: 0 0 25px rgba(224, 82, 79, 0.4);
            }

            #fs-start-icon {
                background: rgba(31, 183, 181, 0.14);
                border: 1px solid rgba(31, 183, 181, 0.35);
                box-shadow: 0 0 25px rgba(31, 183, 181, 0.3);
            }

            #exit-confirm-box h2,
            #fs-start-box h2 {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 14px;
                color: #16212B;
            }

            body.dark #exit-confirm-box h2,
            body.dark #fs-start-box h2 {
                color: #F8FAFC;
            }

            #exit-confirm-message,
            #fs-start-message {
                font-size: 16px;
                line-height: 1.9;
                color: #64748B;
            }

            body.dark #exit-confirm-message,
            body.dark #fs-start-message {
                color: #94A3B8;
            }

            #exit-confirm-actions {
                display: flex;
                gap: 14px;
                margin-top: 28px;
            }

            #exit-confirm-actions button,
            #fs-start-btn {
                padding: 14px 20px;
                border: none;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            #exit-confirm-actions button {
                flex: 1;
            }

            #exit-confirm-actions button:disabled,
            #fs-start-btn:disabled {
                opacity: 0.6;
                cursor: default;
            }

            #exit-confirm-resume-btn,
            #fs-start-btn {
                background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
                color: #ffffff;
                box-shadow: 0 10px 25px rgba(31, 183, 181, 0.35);
            }

            #fs-start-btn {
                margin-top: 26px;
                width: 100%;
            }

            #exit-confirm-end-btn {
                background: transparent;
                color: #E0524F;
                border: 1px solid rgba(224, 82, 79, 0.4) !important;
            }

            body.dark #exit-confirm-end-btn {
                color: #E0524F;
                border: 1px solid rgba(224, 82, 79, 0.5) !important;
            }
        `;

        document.head.appendChild(style);
    }

    function showExitConfirmModal(reasonMessage) {

        if (modalEl) return;

        log("Showing exit-confirm modal:", reasonMessage);

        injectSharedStyle();

        modalEl = document.createElement("div");
        modalEl.id = "exit-confirm-overlay";

        modalEl.innerHTML = `
            <div id="exit-confirm-box">
                <div id="exit-confirm-icon">⚠️</div>
                <h2>You left the interview</h2>
                <p id="exit-confirm-message">${reasonMessage} Do you want to continue this interview, or end it now?</p>
                <div id="exit-confirm-actions">
                    <button id="exit-confirm-resume-btn">Cancel &amp; Resume</button>
                    <button id="exit-confirm-end-btn">End Interview</button>
                </div>
            </div>
        `;

        document.documentElement.appendChild(modalEl);

        modalEl.querySelector("#exit-confirm-resume-btn").addEventListener("click", () => {

            log("Resume clicked, re-requesting fullscreen");

            requestFullscreen().then(() => {

                hideExitConfirmModal();
            });
        });

        modalEl.querySelector("#exit-confirm-end-btn").addEventListener("click", function () {

            log("End Interview clicked");
            endInterview(this);
        });
    }

    function hideExitConfirmModal() {

        if (modalEl) {

            log("Hiding exit-confirm modal");
            modalEl.remove();
            modalEl = null;
        }
    }

    let startOverlayEl = null;

    function showStartOverlay() {

        injectSharedStyle();

        startOverlayEl = document.createElement("div");
        startOverlayEl.id = "fs-start-overlay";

        startOverlayEl.innerHTML = `
            <div id="fs-start-box">
                <div id="fs-start-icon">🖥️</div>
                <h2>Ready to begin?</h2>
                <p id="fs-start-message">${CONFIG.startOverlayMessage}</p>
                <button id="fs-start-btn">Begin Interview</button>
            </div>
        `;

        document.documentElement.appendChild(startOverlayEl);

        startOverlayEl.querySelector("#fs-start-btn").addEventListener("click", () => {

            log("Start overlay button clicked, requesting fullscreen");
            beginFullscreenFlow().then(() => hideStartOverlay());
        });
    }

    function hideStartOverlay() {

        if (startOverlayEl) {

            startOverlayEl.remove();
            startOverlayEl = null;
        }
    }

    function armSilentEntry() {

        if (pendingArmed) return;

        pendingArmed = true;

        log("Armed: waiting for first user gesture to trigger fullscreen");

        const events = ["click", "mousedown", "keydown", "touchstart", "pointerdown"];

        const handler = (evt) => {

            log("First gesture detected:", evt.type);

            events.forEach((e) =>
                document.removeEventListener(e, handler, true)
            );

            pendingArmed = false;

            if (!isFullscreen() && active) {

                beginFullscreenFlow().then(() => hideStartOverlay());
            }
        };

        events.forEach((evt) =>
            document.addEventListener(evt, handler, true)
        );
    }

    if (isTopWindow) {
        document.addEventListener("fullscreenchange", handleFullscreenChange);
        document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
        document.addEventListener("mozfullscreenchange", handleFullscreenChange);
        document.addEventListener("MSFullscreenChange", handleFullscreenChange);
    }

    function handleFullscreenChange() {

        if (!active) return;

        log("fullscreenchange event fired, isFullscreen =", isFullscreen());

        if (isFullscreen()) {

            hideExitConfirmModal();
            hideStartOverlay();
            return;
        }

        logViolation(CONFIG.exitViolationMessage);
        showExitConfirmModal(CONFIG.exitViolationMessage);
    }

    if (isTopWindow) {

        window.addEventListener("blur", () => {

            if (!active) return;

            // Clicking/typing anywhere inside the shell's own iframe also
            // fires a native "blur" event on this (parent) window - that's
            // normal interaction, not the candidate leaving the tab.
            // document.hasFocus() alone is NOT reliable for telling these
            // apart across browsers, so the real signal we check first is:
            // did focus land on our own iframe element? Give focus a tick
            // to land first (activeElement can lag the blur event by a
            // frame in some browsers).
            setTimeout(() => {

                const frame = document.getElementById("ai-interview-frame");
                const focusIsOurFrame = !!frame && document.activeElement === frame;

                if (focusIsOurFrame || document.hasFocus()) {

                    log("Blur ignored - focus is still within this window/iframe");
                    return;
                }

                logViolation(CONFIG.blurViolationMessage);
                showExitConfirmModal(CONFIG.blurViolationMessage);

            }, 0);
        });

        document.addEventListener("visibilitychange", () => {

            if (!active) return;

            if (document.hidden) {

                logViolation(CONFIG.tabHiddenViolationMessage);
                showExitConfirmModal(CONFIG.tabHiddenViolationMessage);
            }
        });
    }

    function broadcastSessionToShell() {

        // Lets the shell (which owns fullscreen but has no PHP session of
        // its own) know which candidate/job is currently active, so its
        // violation modal + endInterview() call can report/redirect with
        // the right context. Harmless no-op if there's no parent shell.
        try {

            (window.top || window).postMessage({
                type: "ai-interview:session",
                candidate_id: window.candidate_id || null,
                job_id: window.job_id || null,
                candidate_name: window.candidate_name || null,
                jobrole: window.jobrole || null
            }, window.location.origin);

        } catch (err) {

            log("broadcastSessionToShell failed:", err && err.message);
        }
    }

    function init() {

        log(
            "init() called, document.readyState =", document.readyState,
            ", isTopWindow =", isTopWindow
        );

        if (!isTopWindow) {

            // Running inside the persistent shell iframe: the shell
            // already owns fullscreen and all violation detection.
            // Just hand it our session context and step aside entirely -
            // no start overlay, no requestFullscreen attempts here.
            broadcastSessionToShell();
            return;
        }

        if (isFullscreen()) {

            log("Already fullscreen, nothing to do");
            return;
        }

        armSilentEntry();

        if (CONFIG.showStartOverlay) {

            showStartOverlay();
        }
    }

    if (document.readyState === "loading") {

        document.addEventListener("DOMContentLoaded", init);

    } else {

        init();
    }

    window.FullscreenLock = {

        stop: function () {

            active = false;
            hideExitConfirmModal();
            hideStartOverlay();
        },

        start: function () {

            active = true;
        },

        endInterview: function (triggerButton) {

            return endInterview(triggerButton);
        },

        getViolationCount: function () {

            return violationCount;
        },

        isFullscreen: isFullscreen,
        requestFullscreen: requestFullscreen
    };

})();
