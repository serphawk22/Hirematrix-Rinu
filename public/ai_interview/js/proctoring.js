/*
|--------------------------------------------------------------------------
| ELEMENTS
|--------------------------------------------------------------------------
*/

const video = document.getElementById("video");

const canvas = document.getElementById("canvas");

const ctx = canvas.getContext("2d");

/*
|--------------------------------------------------------------------------
| GLOBAL VARIABLES
|--------------------------------------------------------------------------
*/

let previousFrame = null;

let modelsLoaded = false;

// NEW: global flag + custom event so other scripts on the page (e.g. the
// interview boot sequence) can know exactly when face verification has
// actually succeeded, instead of guessing based on timers.
window.faceVerificationComplete = false;

const FACE_MATCH_THRESHOLD = 0.5; // lower = stricter. 0.45 - 0.6 is a reasonable range
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
const CANDIDATE_PROFILE_URL = appUrl("candidate/profile");
const MODEL_URL = aiInterviewUrl("models");

/*
|--------------------------------------------------------------------------
| THEME VARIABLES (LIGHT + DARK)
|--------------------------------------------------------------------------
*/

function injectThemeVariables() {

    if (
        document.getElementById(
            "theme-vars-style"
        )
    ) {
        return;
    }

    const themeStyle =
        document.createElement("style");

    themeStyle.id =
        "theme-vars-style";

    themeStyle.innerHTML = `

        /*
        |--------------------------------------------------------------------------
        | PERMISSION POPUP
        |--------------------------------------------------------------------------
        */

        #camera-permission-popup {
            background: rgba(248, 252, 251, 0.88);
        }

        body.dark #camera-permission-popup {
            background: rgba(17, 17, 17, 0.88);
        }

        #permission-box {
            background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%);
            border: 1px solid #D9ECE5;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        body.dark #permission-box {
            background: linear-gradient(135deg, #162327 0%, #1B2A2F 100%);
            border: 1px solid #23343A;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        #permission-icon {
            background: rgba(31, 183, 181, 0.16);
            border: 1px solid rgba(31, 183, 181, 0.35);
            box-shadow: 0 0 25px rgba(31, 183, 181, 0.35);
        }

        body.dark #permission-icon {
            background: rgba(31, 183, 181, 0.2);
            border: 1px solid rgba(31, 183, 181, 0.4);
            box-shadow: 0 0 25px rgba(31, 183, 181, 0.4);
        }

        #permission-box h2 {
            color: #16212B;
        }

        body.dark #permission-box h2 {
            color: #F8FAFC;
        }

        #permission-box p {
            color: #64748B;
        }

        body.dark #permission-box p {
            color: #94A3B8;
        }

        #grant-permission-btn {
            background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(31, 183, 181, 0.35);
        }

        body.dark #grant-permission-btn {
            background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(31, 183, 181, 0.45);
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFICATION OVERLAY
        |--------------------------------------------------------------------------
        */

        #face-verify-overlay {
            background: rgba(248, 252, 251, 0.92);
        }

        body.dark #face-verify-overlay {
            background: rgba(17, 17, 17, 0.92);
        }

        #face-verify-box {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        body.dark #face-verify-box {
            background: #161B22;
            border: 1px solid #30363D;
            box-shadow: 0 16px 34px rgba(0, 0, 0, 0.34);
        }

        #face-verify-icon {
            background: #E8F7F7;
            border: 1px solid rgba(31, 183, 181, 0.28);
            box-shadow: none;
        }

        body.dark #face-verify-icon {
            background: rgba(31, 183, 181, 0.14);
            border: 1px solid rgba(120, 227, 221, 0.28);
            box-shadow: none;
        }

        #face-verify-box h2 {
            color: #16212B;
        }

        body.dark #face-verify-box h2 {
            color: #F8FAFC;
        }

        #face-verify-message {
            color: #64748B;
        }

        body.dark #face-verify-message {
            color: #94A3B8;
        }

        #face-verify-message.is-error {
            color: #E0524F;
        }

        body.dark #face-verify-message.is-error {
            color: #E0524F;
        }

        #face-verify-preview {
            border: 1px solid #D9ECE5;
        }

        body.dark #face-verify-preview {
            border: 1px solid #23343A;
        }

        #face-verify-retry {
            background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(31, 183, 181, 0.35);
        }

        body.dark #face-verify-retry {
            background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(31, 183, 181, 0.45);
        }

        /*
        |--------------------------------------------------------------------------
        | TERMINATION OVERLAY
        |--------------------------------------------------------------------------
        */

        #face-terminate-overlay {
            background: rgba(248, 252, 251, 0.96);
        }

        body.dark #face-terminate-overlay {
            background: rgba(17, 17, 17, 0.96);
        }

        #face-terminate-box {
            background: #FFFFFF;
            border: 1px solid rgba(224, 82, 79, 0.35);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        body.dark #face-terminate-box {
            background: #162327;
            border: 1px solid rgba(224, 82, 79, 0.4);
            box-shadow: 0 20px 60px rgba(0,0,0,0.55);
        }

        #face-terminate-icon {
            background: rgba(224, 82, 79, 0.16);
            border: 1px solid rgba(224, 82, 79, 0.35);
            box-shadow: 0 0 25px rgba(224, 82, 79, 0.35);
        }

        body.dark #face-terminate-icon {
            background: rgba(224, 82, 79, 0.2);
            border: 1px solid rgba(224, 82, 79, 0.4);
            box-shadow: 0 0 25px rgba(224, 82, 79, 0.4);
        }

        #face-terminate-box h2 {
            color: #16212B;
        }

        body.dark #face-terminate-box h2 {
            color: #F8FAFC;
        }

        #face-terminate-message {
            color: #E0524F;
        }

        body.dark #face-terminate-message {
            color: #E0524F;
        }

        #face-terminate-countdown {
            color: #94A3B8;
        }

        body.dark #face-terminate-countdown {
            color: #7A8B96;
        }

        /*
        |--------------------------------------------------------------------------
        | ANTI-SCREENSHOT OVERLAY (NEW)
        |--------------------------------------------------------------------------
        */

        #anti-screenshot-overlay {
            background: #000000;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            text-align: center;
            padding: 24px;
        }

    `;

    document.head.appendChild(
        themeStyle
    );
}

/*
|--------------------------------------------------------------------------
| ANTI-SCREENSHOT OVERLAY (NEW)
|--------------------------------------------------------------------------
| Creates (once) and returns the full-screen blackout overlay used when a
| PrintScreen / screenshot-shortcut attempt is detected. Previously this
| element was referenced in the keydown handler but never created, so the
| handler threw instead of running. Note: this is a deterrent + logging
| mechanism, not real prevention - the OS captures the screen before this
| JS can run, so nothing in-browser can stop the initial capture itself.
|--------------------------------------------------------------------------
*/

function ensureAntiScreenshotOverlay() {

    injectThemeVariables();

    let overlay =
        document.getElementById(
            "anti-screenshot-overlay"
        );

    if (overlay) return overlay;

    overlay =
        document.createElement("div");

    overlay.id =
        "anti-screenshot-overlay";

    overlay.textContent =
        "Screenshots are disabled during this interview";

    document.body.appendChild(
        overlay
    );

    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";

    overlay.style.width = "100%";
    overlay.style.height = "100%";

    overlay.style.display = "none";

    overlay.style.justifyContent =
        "center";

    overlay.style.alignItems =
        "center";

    overlay.style.zIndex =
        "9999999999999";

    return overlay;
}

function triggerScreenshotBlackout(reason) {

    const overlay =
        ensureAntiScreenshotOverlay();

    overlay.style.display = "flex";

    document.body.style.filter =
        "blur(25px)";

    // Best-effort: overwrite whatever may have just been copied
    if (
        navigator.clipboard &&
        navigator.clipboard.writeText
    ) {

        navigator.clipboard
            .writeText(
                "Screenshots are disabled during interview"
            )
            .catch(() => {});
    }

    showWarning(reason);
    reportViolation(reason);

    setTimeout(() => {

        overlay.style.display = "none";

        document.body.style.filter =
            "blur(0px)";

    }, 3000);
}

/*
|--------------------------------------------------------------------------
| WARNING SYSTEM
|--------------------------------------------------------------------------
*/

function showWarning(message) {

    injectThemeVariables();

    /*
    |--------------------------------------------------------------------------
    | CREATE STYLE ONCE
    |--------------------------------------------------------------------------
    */

    if (
        !document.getElementById(
            "custom-warning-style"
        )
    ) {

        const style =
            document.createElement("style");

        style.id =
            "custom-warning-style";

        style.innerHTML = `

            #warning-container {

                position: fixed;
                top: 20px;
                right: 20px;
                width: 350px;
                z-index: 999999;
            }

            .custom-warning {

                background: #FFFFFF;
                color: #16212B;
                border: 1px solid #D9ECE5;
                border-left: 5px solid #B5D84E;

                padding: 15px 20px;

                margin-bottom: 15px;

                border-radius: 10px;

                box-shadow:
                    0 4px 12px rgba(0,0,0,0.15);

                font-family: Arial, sans-serif;

                animation:
                    slideIn 0.3s ease;

                position: relative;
            }

            body.dark .custom-warning {

                background: #162327;
                color: #F8FAFC;
                border: 1px solid #23343A;
                border-left: 5px solid #B5D84E;
            }

            .custom-warning-title {

                font-weight: bold;
                margin-bottom: 5px;
                font-size: 16px;
                color: #0D8A90;
            }

            body.dark .custom-warning-title {

                color: #1FB7B5;
            }

            .custom-warning-close {

                position: absolute;
                top: 10px;
                right: 12px;

                cursor: pointer;

                font-size: 18px;

                color: #64748B;
            }

            body.dark .custom-warning-close {

                color: #94A3B8;
            }

            @keyframes slideIn {

                from {

                    opacity: 0;
                    transform:
                        translateX(100%);
                }

                to {

                    opacity: 1;
                    transform:
                        translateX(0);
                }
            }

        `;

        document.head.appendChild(
            style
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE CONTAINER
    |--------------------------------------------------------------------------
    */

    let container =
        document.getElementById(
            "warning-container"
        );

    if (!container) {

        container =
            document.createElement("div");

        container.id =
            "warning-container";

        document.body.appendChild(
            container
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE WARNING
    |--------------------------------------------------------------------------
    */

    const warning =
        document.createElement("div");

    warning.className =
        "custom-warning";

    warning.innerHTML = `

        <div class="custom-warning-title">
            Warning
        </div>

        <div>
            ${message}
        </div>

        <span class="custom-warning-close">
            &times;
        </span>

    `;

    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTON
    |--------------------------------------------------------------------------
    */

    warning.querySelector(
        ".custom-warning-close"
    ).onclick = () => {

        warning.remove();
    };

    /*
    |--------------------------------------------------------------------------
    | APPEND WARNING
    |--------------------------------------------------------------------------
    */

    container.appendChild(
        warning
    );

    /*
    |--------------------------------------------------------------------------
    | AUTO REMOVE
    |--------------------------------------------------------------------------
    */

    setTimeout(() => {

        if (
            document.body.contains(
                warning
            )
        ) {

            warning.remove();
        }

    }, 5000);

    /*
    |--------------------------------------------------------------------------
    | SAVE VIOLATION
    |--------------------------------------------------------------------------
    */

    reportViolation(message);
}

/*
|--------------------------------------------------------------------------
| LOAD FACE-API MODELS (ONCE)
|--------------------------------------------------------------------------
*/

async function loadModels() {

    if (modelsLoaded) return;
  try {
    await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
    console.log("SSD Loaded");
} catch(e) {
    console.error("SSD Error", e);
}

try {
    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
    console.log("Landmark Loaded");
} catch(e) {
    console.error("Landmark Error", e);
}

try {
    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
    console.log("Recognition Loaded");
} catch(e) {
    console.error("Recognition Error", e);
}
    modelsLoaded = true;

    console.log("Face Models Loaded");
}

/*
|--------------------------------------------------------------------------
| IDENTITY VERIFICATION OVERLAY (UI)
|--------------------------------------------------------------------------
*/

function showVerificationOverlay() {

    injectThemeVariables();

    let overlay =
        document.getElementById(
            "face-verify-overlay"
        );

    if (overlay) return overlay;

    overlay =
        document.createElement("div");

    overlay.id =
        "face-verify-overlay";

    overlay.innerHTML = `

        <div id="face-verify-box">

            <div id="face-verify-icon">
                🪪
            </div>

            <h2>
                Identity Verification
            </h2>

            <p id="face-verify-message">
                Preparing verification...
            </p>

            <video
                id="face-verify-preview"
                autoplay
                muted
                playsinline
            ></video>

            <button
                id="face-verify-retry"
                style="display:none;"
            >
                Try Again
            </button>

        </div>
    `;

    document.body.appendChild(
        overlay
    );

    /*
    |--------------------------------------------------------------------------
    | OVERLAY BACKGROUND
    |--------------------------------------------------------------------------
    */

    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";

    overlay.style.width = "100%";
    overlay.style.height = "100%";

    overlay.style.display = "flex";

    overlay.style.justifyContent =
        "center";

    overlay.style.alignItems =
        "center";

    overlay.style.backdropFilter =
        "blur(12px)";

    overlay.style.zIndex =
        "999999999";

    /*
    |--------------------------------------------------------------------------
    | BOX STYLE
    |--------------------------------------------------------------------------
    */

    const box =
        document.getElementById(
            "face-verify-box"
        );

    box.style.width =
        "460px";

    box.style.maxWidth =
        "92%";

    box.style.padding =
        "38px";

    box.style.borderRadius =
        "24px";

    box.style.textAlign =
        "center";

    box.style.fontFamily =
        "'Inter', sans-serif";

    box.style.position =
        "relative";

    box.style.overflow =
        "hidden";

    /*
    |--------------------------------------------------------------------------
    | ICON
    |--------------------------------------------------------------------------
    */

    const icon =
        document.getElementById(
            "face-verify-icon"
        );

    icon.style.width =
        "85px";

    icon.style.height =
        "85px";

    icon.style.margin =
        "0 auto 22px";

    icon.style.borderRadius =
        "50%";

    icon.style.display =
        "flex";

    icon.style.alignItems =
        "center";

    icon.style.justifyContent =
        "center";

    icon.style.fontSize =
        "42px";

    /*
    |--------------------------------------------------------------------------
    | TITLE
    |--------------------------------------------------------------------------
    */

    const title =
        box.querySelector("h2");

    title.style.fontSize =
        "28px";

    title.style.fontWeight =
        "700";

    title.style.marginBottom =
        "14px";

    /*
    |--------------------------------------------------------------------------
    | TEXT
    |--------------------------------------------------------------------------
    */

    const text =
        document.getElementById(
            "face-verify-message"
        );

    text.style.fontSize =
        "16px";

    text.style.lineHeight =
        "1.9";

    /*
    |--------------------------------------------------------------------------
    | PREVIEW VIDEO
    |--------------------------------------------------------------------------
    */

    const preview =
        document.getElementById(
            "face-verify-preview"
        );

    preview.style.width =
        "100%";

    preview.style.marginTop =
        "18px";

    preview.style.borderRadius =
        "12px";

    preview.style.transform =
        "scaleX(-1)"; /* mirror, feels natural to the candidate */

    /*
    |--------------------------------------------------------------------------
    | RETRY BUTTON
    |--------------------------------------------------------------------------
    */

    const retryBtn =
        document.getElementById(
            "face-verify-retry"
        );

    retryBtn.style.marginTop =
        "24px";

    retryBtn.style.padding =
        "14px 28px";

    retryBtn.style.border =
        "none";

    retryBtn.style.borderRadius =
        "12px";

    retryBtn.style.fontSize =
        "16px";

    retryBtn.style.fontWeight =
        "600";

    retryBtn.style.cursor =
        "pointer";

    retryBtn.onclick = () => {

        location.reload();
    };

    return overlay;
}

function updateVerificationOverlay(
    message,
    isError = false,
    showRetry = false
) {

    const overlay =
        showVerificationOverlay();

    const msg =
        overlay.querySelector(
            "#face-verify-message"
        );

    msg.textContent = message;

    msg.classList.toggle(
        "is-error",
        isError
    );

    overlay.querySelector(
        "#face-verify-retry"
    ).style.display =
        showRetry ? "inline-block" : "none";
}

function hideVerificationOverlay() {

    const overlay =
        document.getElementById(
            "face-verify-overlay"
        );

    if (overlay) {

        overlay.remove();
    }
}

/*
|--------------------------------------------------------------------------
| STOP CAMERA + MIC STREAM
|--------------------------------------------------------------------------
*/

function stopMediaStream() {

    if (video.srcObject) {

        video.srcObject
            .getTracks()
            .forEach(
                (track) => track.stop()
            );

        video.srcObject = null;
    }
}

/*
|--------------------------------------------------------------------------
| TERMINATION OVERLAY (CANDIDATE REMOVED FROM INTERVIEW)
|--------------------------------------------------------------------------
*/

function showTerminationOverlay(
    message,
    redirectSeconds = 5
) {

    injectThemeVariables();

    hideVerificationOverlay();

    const existing =
        document.getElementById(
            "face-terminate-overlay"
        );

    if (existing) {

        existing.remove();
    }

    const overlay =
        document.createElement("div");

    overlay.id =
        "face-terminate-overlay";

    overlay.innerHTML = `

        <div id="face-terminate-box">

            <div id="face-terminate-icon">
                ⛔
            </div>

            <h2>
                Identity Verification Failed
            </h2>

            <p id="face-terminate-message">
                ${message}
            </p>

            <p id="face-terminate-countdown"></p>

        </div>
    `;

    document.body.appendChild(
        overlay
    );

    /*
    |--------------------------------------------------------------------------
    | OVERLAY BACKGROUND
    |--------------------------------------------------------------------------
    */

    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";

    overlay.style.width = "100%";
    overlay.style.height = "100%";

    overlay.style.display = "flex";

    overlay.style.justifyContent =
        "center";

    overlay.style.alignItems =
        "center";

    overlay.style.backdropFilter =
        "blur(12px)";

    overlay.style.zIndex =
        "9999999999";

    /*
    |--------------------------------------------------------------------------
    | BOX STYLE
    |--------------------------------------------------------------------------
    */

    const box =
        document.getElementById(
            "face-terminate-box"
        );

    box.style.width =
        "460px";

    box.style.maxWidth =
        "92%";

    box.style.padding =
        "38px";

    box.style.borderRadius =
        "24px";

    box.style.textAlign =
        "center";

    box.style.fontFamily =
        "'Inter', sans-serif";

    /*
    |--------------------------------------------------------------------------
    | ICON
    |--------------------------------------------------------------------------
    */

    const icon =
        document.getElementById(
            "face-terminate-icon"
        );

    icon.style.width =
        "85px";

    icon.style.height =
        "85px";

    icon.style.margin =
        "0 auto 22px";

    icon.style.borderRadius =
        "50%";

    icon.style.display =
        "flex";

    icon.style.alignItems =
        "center";

    icon.style.justifyContent =
        "center";

    icon.style.fontSize =
        "42px";

    /*
    |--------------------------------------------------------------------------
    | TITLE
    |--------------------------------------------------------------------------
    */

    const title =
        box.querySelector("h2");

    title.style.fontSize =
        "28px";

    title.style.fontWeight =
        "700";

    title.style.marginBottom =
        "14px";

    /*
    |--------------------------------------------------------------------------
    | TEXT
    |--------------------------------------------------------------------------
    */

    const text =
        document.getElementById(
            "face-terminate-message"
        );

    text.style.fontSize =
        "16px";

    text.style.lineHeight =
        "1.9";

    /*
    |--------------------------------------------------------------------------
    | COUNTDOWN
    |--------------------------------------------------------------------------
    */

    const countdown =
        document.getElementById(
            "face-terminate-countdown"
        );

    countdown.style.fontSize =
        "14px";

    countdown.style.marginTop =
        "18px";

    let secondsLeft = redirectSeconds;

    countdown.textContent =
        `Redirecting in ${secondsLeft}s...`;

    const countdownTimer =
        setInterval(() => {

            secondsLeft--;

            if (secondsLeft <= 0) {

                clearInterval(
                    countdownTimer
                );

                window.location.href =
                    INTERVIEW_EXIT_URL;

            } else {

                countdown.textContent =
                    `Redirecting in ${secondsLeft}s...`;
            }

        }, 1000);
}

/*
|--------------------------------------------------------------------------
| TERMINATE INTERVIEW (FACE DID NOT MATCH PROFILE PHOTO)
|--------------------------------------------------------------------------
*/

function terminateInterview(reason) {

    stopMediaStream();

    showWarning(
        "No face matched. You are being removed from this interview."
    );

    reportViolation(reason);

    showTerminationOverlay(
        "Your face does not match the profile photo on file for this account. " +
        "For security reasons, this interview session has been ended. " +
        "Please contact support if you believe this is an error."
    );
}

/*
|--------------------------------------------------------------------------
| GET CANDIDATE PROFILE PHOTO DESCRIPTOR
|--------------------------------------------------------------------------
*/

async function getProfileDescriptor() {

    const res =
        await fetch(
            aiInterviewApiUrl(`get_candidate_photo.php?candidate_id=${candidate_id}`)
        );

    const data =
        await res.json();

    if (!data.success) {

        // Check if it's a missing photo error specifically
        if (
            data.reason === "no_photo" ||
            data.message?.toLowerCase().includes("not found") ||
            data.message?.toLowerCase().includes("no photo") ||
            data.message?.toLowerCase().includes("no profile")
        ) {
            throw { type: "no_photo", message: data.message || "No profile photo found." };
        }

        throw new Error(
            data.message ||
            "Profile photo not found"
        );
    }

    const img =
        await faceapi.fetchImage(
            data.image
        );

    const detection =
        await faceapi
            .detectSingleFace(
                img,
                new faceapi.SsdMobilenetv1Options({
                    minConfidence: 0.5
                })
            )
            .withFaceLandmarks()
            .withFaceDescriptor();

    if (!detection) {

        throw new Error(
            "No face detected in profile photo"
        );
    }

    return detection.descriptor;
}

/*
|--------------------------------------------------------------------------
| GET LIVE CAMERA FACE DESCRIPTOR
|--------------------------------------------------------------------------
*/

async function getLiveDescriptor() {

    if (
        video.paused ||
        video.ended ||
        video.readyState < 2
    ) {

        return null;
    }

    const detection =
        await faceapi
            .detectSingleFace(
                video,
                new faceapi.SsdMobilenetv1Options({
                    minConfidence: 0.5
                })
            )
            .withFaceLandmarks()
            .withFaceDescriptor();

    return detection
        ? detection.descriptor
        : null;
}

/*
|--------------------------------------------------------------------------
| VERIFY CANDIDATE IDENTITY (FACE MATCH VS PROFILE PHOTO)
|--------------------------------------------------------------------------
*/

async function verifyCandidateFace() {

    showVerificationOverlay();

    document.getElementById(
        "face-verify-preview"
    ).srcObject = video.srcObject;

    updateVerificationOverlay(
        "Loading verification models..."
    );

    try {

        await loadModels();

    } catch (err) {

         console.error("MODEL ERROR:", err);

    if (window.AiInterviewDialog) {
        window.AiInterviewDialog.alert(err.message);
    } else {
        alert(err.message);
    }

    updateVerificationOverlay(
        err.message,
        true,
        true
    );

        // reportViolation(
        //     "Face verification model load failed: " + err.message
        // );

        return false;
    }

    updateVerificationOverlay(
        "Loading your profile photo..."
    );

    let profileDescriptor;

    try {

        profileDescriptor =
            await getProfileDescriptor();

    } catch (err) {

        console.error(err);

        // Handle missing photo case with Update Profile Photo button
        if (err.type === "no_photo") {

            const overlay = showVerificationOverlay();

            const msg = overlay.querySelector("#face-verify-message");
            msg.textContent = "No profile photo found. Please upload your photo before continuing.";
            msg.classList.add("is-error");

            // Hide the default retry button
            overlay.querySelector("#face-verify-retry").style.display = "none";

            // Inject Update Profile Photo button if not already there
            if (!document.getElementById("face-verify-update-photo-btn")) {

                const updateBtn = document.createElement("button");
                updateBtn.id = "face-verify-update-photo-btn";
                updateBtn.textContent = "Update Profile Photo";

                // Reuse same styles as retry button
                updateBtn.style.marginTop      = "24px";
                updateBtn.style.padding        = "14px 28px";
                updateBtn.style.border         = "none";
                updateBtn.style.borderRadius   = "12px";
                updateBtn.style.fontSize       = "16px";
                updateBtn.style.fontWeight     = "600";
                updateBtn.style.cursor         = "pointer";
                updateBtn.style.background     = "linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%)";
                updateBtn.style.color          = "#ffffff";
                updateBtn.style.boxShadow      = "0 10px 25px rgba(31, 183, 181, 0.35)";
                updateBtn.style.display        = "inline-block";

                updateBtn.onmouseenter = function () {
                    updateBtn.style.transform = "translateY(-2px) scale(1.02)";
                };
                updateBtn.onmouseleave = function () {
                    updateBtn.style.transform = "translateY(0) scale(1)";
                };

                updateBtn.onclick = function () {
                    window.location.href =
                        CANDIDATE_PROFILE_URL;
                };

                overlay.querySelector("#face-verify-box").appendChild(updateBtn);
            }

            //reportViolation("Face verification failed: No profile photo on file");

            return false;
        }

        // Original error handling for other failures
        updateVerificationOverlay(
            "Verification failed: " + err.message +
            ". Please contact support before continuing.",
            true,
            true
        );

        // reportViolation(
        //     "Face verification setup failed: " + err.message
        // );

        return false;
    }

    updateVerificationOverlay(
        "Please look directly at the camera. Verifying your identity..."
    );

    /*
    |--------------------------------------------------------------------------
    | WAIT FOR VIDEO TO BE READY
    |--------------------------------------------------------------------------
    */

    await new Promise((resolve) => {

        if (video.readyState >= 3) {

            resolve();

        } else {

            video.onloadedmetadata =
                () => resolve();
        }
    });

    try {

        await video.play();

    } catch (err) {

        console.warn(
            "Video play warning:",
            err
        );
    }

    const maxAttempts = 6;

    let matched = false;

    let lastDistance = null;

    for (
        let attempt = 1;
        attempt <= maxAttempts;
        attempt++
    ) {

        updateVerificationOverlay(
            `Verifying your identity... (${attempt}/${maxAttempts})`
        );

        let liveDescriptor = null;

        try {

            liveDescriptor =
                await getLiveDescriptor();

        } catch (err) {

            console.error(err);
        }

        if (liveDescriptor) {

            lastDistance =
                faceapi.euclideanDistance(
                    profileDescriptor,
                    liveDescriptor
                );

            console.log(
                `Attempt ${attempt}: distance = ${lastDistance.toFixed(3)}`
            );

            if (lastDistance < FACE_MATCH_THRESHOLD) {

                matched = true;

                break;
            }

        } else {

            console.log(
                `Attempt ${attempt}: no face detected in camera`
            );
        }

        await new Promise(
            (r) => setTimeout(r, 1200)
        );
    }

    if (matched) {

        updateVerificationOverlay(
            "Identity verified. Starting your interview..."
        );


        await new Promise(
            (r) => setTimeout(r, 800)
        );

        hideVerificationOverlay();

        // NEW: tell the rest of the page that identity verification has
        // actually completed successfully. The interview boot sequence
        // listens for this before letting Maya speak or starting the
        // interview timer, instead of relying on an arbitrary delay.
        window.faceVerificationComplete = true;
        document.dispatchEvent(new CustomEvent("faceVerified"));

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | NO MATCH AFTER ALL ATTEMPTS -> THROW CANDIDATE OUT OF INTERVIEW
    |--------------------------------------------------------------------------
    */

    terminateInterview(
        "Face verification failed - no face match" +
        (lastDistance !== null
            ? " (distance: " + lastDistance.toFixed(3) + ")"
            : " (no face detected)")
    );

    return false;
}

/*
|--------------------------------------------------------------------------
| START CAMERA + MIC
|--------------------------------------------------------------------------
*/

async function startCamera() {

    try {

        const stream =
            await navigator.mediaDevices.getUserMedia({

                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },

                audio: true

            });

        video.srcObject = stream;

        await video.play();

        console.log("Camera started");

        /*
        |--------------------------------------------------------------------------
        | REMOVE POPUP IF EXISTS
        |--------------------------------------------------------------------------
        */

        const popup =
            document.getElementById(
                "camera-permission-popup"
            );

        if (popup) {

            popup.remove();
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFY IDENTITY BEFORE STARTING PROCTORING / INTERVIEW
        |--------------------------------------------------------------------------
        */

        const verified =
            await verifyCandidateFace();

        if (verified) {

            detectNoise(stream);

            detectMovement();

            startFaceDetection();

            startPhoneDetection();

        } 

    } catch (error) {

        console.error(error);

        showPermissionPopup();
    }
}
function showPermissionPopup() {

    injectThemeVariables(); 

    const existingPopup =
        document.getElementById(
            "camera-permission-popup"
        );

    if (existingPopup) {

        existingPopup.remove();
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE POPUP
    |--------------------------------------------------------------------------
    */

    const popup =
        document.createElement("div");

    popup.id =
        "camera-permission-popup";

    popup.innerHTML = `

        <div id="permission-box">

            <div id="permission-icon">
                🎥
            </div>

            <h2>
                Camera & Microphone Permission
            </h2>

            <p>
                Please allow camera and microphone access
                from your browser permission bar/address bar
                to continue your AI interview.
                <br><br>

                <strong>How to allow permission:</strong>
                <br><br>

                1. Click the 🔒 lock icon or 🎥 camera icon
                near the browser search bar.
                <br><br>

                2. Set Camera and Microphone to
                <strong>Allow</strong>.
                <br><br>

                3. Click the button below after allowing permission.
            </p>

            <button id="grant-permission-btn">
                I Have Allowed Permission
            </button>

        </div>
    `;

    document.body.appendChild(
        popup
    );

    /*
    |--------------------------------------------------------------------------
    | POPUP BACKGROUND
    |--------------------------------------------------------------------------
    */

    popup.style.position = "fixed";
    popup.style.top = "0";
    popup.style.left = "0";

    popup.style.width = "100%";
    popup.style.height = "100%";

    popup.style.display = "flex";

    popup.style.justifyContent =
        "center";

    popup.style.alignItems =
        "center";

    popup.style.backdropFilter =
        "blur(12px)";

    popup.style.zIndex =
        "99999999";

    /*
    |--------------------------------------------------------------------------
    | BOX STYLE
    |--------------------------------------------------------------------------
    */

    const box =
        document.getElementById(
            "permission-box"
        );

    box.style.width =
        "460px";

    box.style.maxWidth =
        "92%";

    box.style.padding =
        "38px";

    box.style.borderRadius =
        "24px";

    box.style.textAlign =
        "center";

    box.style.fontFamily =
        "'Inter', sans-serif";

    box.style.position =
        "relative";

    box.style.overflow =
        "hidden";

    /*
    |--------------------------------------------------------------------------
    | ICON
    |--------------------------------------------------------------------------
    */

    const icon =
        document.getElementById(
            "permission-icon"
        );

    icon.style.width =
        "85px";

    icon.style.height =
        "85px";

    icon.style.margin =
        "0 auto 22px";

    icon.style.borderRadius =
        "50%";

    icon.style.display =
        "flex";

    icon.style.alignItems =
        "center";

    icon.style.justifyContent =
        "center";

    icon.style.fontSize =
        "42px";

    /*
    |--------------------------------------------------------------------------
    | TITLE
    |--------------------------------------------------------------------------
    */

    const title =
        box.querySelector("h2");

    title.style.fontSize =
        "28px";

    title.style.fontWeight =
        "700";

    title.style.marginBottom =
        "18px";

    /*
    |--------------------------------------------------------------------------
    | TEXT
    |--------------------------------------------------------------------------
    */

    const text =
        box.querySelector("p");

    text.style.fontSize =
        "16px";

    text.style.lineHeight =
        "1.9";

    /*
    |--------------------------------------------------------------------------
    | BUTTON STYLE
    |--------------------------------------------------------------------------
    */

    const button =
        document.getElementById(
            "grant-permission-btn"
        );

    button.style.marginTop =
        "28px";

    button.style.padding =
        "14px 28px";

    button.style.border =
        "none";

    button.style.borderRadius =
        "12px";

    button.style.fontSize =
        "16px";

    button.style.fontWeight =
        "600";

    button.style.cursor =
        "pointer";

    button.style.transition =
        "all 0.25s ease";

    /*
    |--------------------------------------------------------------------------
    | BUTTON HOVER
    |--------------------------------------------------------------------------
    */

    button.onmouseenter =
        function () {

            button.style.transform =
                "translateY(-2px) scale(1.02)";
        };

    button.onmouseleave =
        function () {

            button.style.transform =
                "translateY(0px) scale(1)";
        };

    /*
    |--------------------------------------------------------------------------
    | BUTTON CLICK
    |--------------------------------------------------------------------------
    */

    button.onclick = async function () {

        try {

            const stream =
                await navigator
                    .mediaDevices
                    .getUserMedia({

                        video: {
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: true
                    });

            video.srcObject = stream;

            await video.play();

            popup.remove();

            /*
            |--------------------------------------------------------------------------
            | VERIFY IDENTITY BEFORE STARTING PROCTORING / INTERVIEW
            |--------------------------------------------------------------------------
            */

            const verified =
                await verifyCandidateFace();

            if (verified) {

                detectNoise(stream);

                detectMovement();

                startFaceDetection();

                startPhoneDetection();
            }

        } catch (err) {

            console.error(err);

            if (window.AiInterviewDialog) {
                window.AiInterviewDialog.alert("Permission still denied");
            } else {
                alert(
                    "Permission still denied"
                );
            }
        }
    };
}

/*
|--------------------------------------------------------------------------
| SEND VIOLATION TO SERVER
|--------------------------------------------------------------------------
*/

function reportViolation(message) {

    console.log("Violation:", message);

    fetch(aiInterviewApiUrl("report_violation.php"), {

        method: "POST",

        headers: {
            "Content-Type": "application/json"
        },

        body: JSON.stringify({

            candidate_id: candidate_id,

    candidate_name: candidate_name,

    message: message,
    jobrole: jobrole,

        })

    })
    .then(response => response.json())
    .then(data => {

        console.log(data);

    })
    .catch(error => {

        console.error(error);

    });
}

/*
|--------------------------------------------------------------------------
| TAB SWITCH DETECTION
|--------------------------------------------------------------------------
| Intentionally removed from here. This fired a small toast on top of
| the blocking modal that fullscreen-lock.js already shows for the exact
| same visibilitychange event, so every tab switch produced two different
| warnings at once. fullscreen-lock.js's tabHiddenViolationMessage now
| covers this - do not re-add a separate listener here.
*/

/*
|--------------------------------------------------------------------------
| FULLSCREEN EXIT DETECTION
|--------------------------------------------------------------------------
| Intentionally removed from here. Fullscreen enter/exit/re-entry is now
| owned entirely by js/fullscreen-lock.js (loaded on every page). Having
| two separate fullscreenchange listeners - this one silently forcing
| enableFullscreen() with no user click, and fullscreen-lock.js's modal
| waiting for a real "Resume" click - raced each other and caused the
| fullscreen state to flicker in/out repeatedly. Do not re-add fullscreen
| handling here; add it to fullscreen-lock.js instead so there is only
| ever one source of truth for this on the page.
*/

/*
|--------------------------------------------------------------------------
| COPY DETECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "copy",
    e => {

        e.preventDefault(); 
        //  reportViolation(
        //         "Copy attempt detected"
        //     );
    }
);

/*
|--------------------------------------------------------------------------
| CUT DETECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "cut",
    e => {

        e.preventDefault(); 
        //  reportViolation(
        //         "Cut attempt detected"
        //     );
    }
);

/*
|--------------------------------------------------------------------------
| PASTE DETECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "paste",
    e => {

        e.preventDefault(); 
        //  reportViolation(
        //         "Paste attempt detected"
        //     );
    }
);

/*
|--------------------------------------------------------------------------
| DISABLE RIGHT CLICK
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "contextmenu",
    e => {

        e.preventDefault(); 
        //  reportViolation(
        //         "Right click disabled"
        //     );
    }
);

/*
|--------------------------------------------------------------------------
| DISABLE TEXT SELECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "selectstart",
    e => {

        e.preventDefault();
    }
);

/*
|--------------------------------------------------------------------------
| SCREENSHOT SHORTCUT DETECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "keydown",
    e => {

        /*
        |--------------------------------------------------------------------------
        | PRINT SCREEN
        |--------------------------------------------------------------------------
        */
        if (e.key === "PrintScreen") {

            e.preventDefault();

            triggerScreenshotBlackout(
                "Screenshot attempt detected"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CTRL+C
        |--------------------------------------------------------------------------
        */

        if (e.ctrlKey && e.key === "c") {
 
            //   reportViolation(
            //     "Copy shortcut detected"
            // );
        }

        /*
        |--------------------------------------------------------------------------
        | CTRL+V
        |--------------------------------------------------------------------------
        */

        if (e.ctrlKey && e.key === "v") {

            e.preventDefault(); 
            //   reportViolation(
            //     "Paste shortcut detected"
            // );
        }

        /*
        |--------------------------------------------------------------------------
        | CTRL+U
        |--------------------------------------------------------------------------
        */

        if (e.ctrlKey && e.key === "u") {

            e.preventDefault(); 
            //   reportViolation(
            //     "Source view blocked"
            // );
        }

        /*
        |--------------------------------------------------------------------------
        | F12
        |--------------------------------------------------------------------------
        */

        if (e.key === "F12") {

           e.preventDefault(); 
            //   reportViolation(
            //     "Developer tools detected"
            // );
        }

        /*
        |--------------------------------------------------------------------------
        | CTRL+SHIFT+I
        |--------------------------------------------------------------------------
        */

        if (
            e.ctrlKey &&
            e.shiftKey &&
            e.key === "I"
        ) {

            e.preventDefault(); 
            //   reportViolation(
            // //     "Developer tools detected"
            // // );
        }

        /*
        |--------------------------------------------------------------------------
        | WIN + SHIFT + S (Windows Snipping Tool)
        |--------------------------------------------------------------------------
        | Cannot actually be blocked (it's an OS-level shortcut the browser
        | never fully sees), but we still preventDefault whatever bubbles up
        | and log it in case the browser did receive the keydown.
        |--------------------------------------------------------------------------
        */

        if (
            e.shiftKey &&
            e.key &&
            e.key.toLowerCase() === "s" &&
            (e.metaKey || e.getModifierState?.("Meta"))
        ) {

            triggerScreenshotBlackout(
                "Snipping tool shortcut detected"
            );
        }
    }
);
/*
|--------------------------------------------------------------------------
| DETECT PRINTSCREEN KEY RELEASE
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "keyup",
    e => {

        if (
            e.key === "PrintScreen"
        ) {

            triggerScreenshotBlackout(
                "Screenshot shortcut detected"
            );
        }
    }
);

/*
|--------------------------------------------------------------------------
| MOVEMENT DETECTION
|--------------------------------------------------------------------------
*/

function detectMovement() {

    setInterval(() => {

        if (!video.videoWidth) return;

        canvas.width = video.videoWidth;

        canvas.height = video.videoHeight;

        ctx.drawImage(
            video,
            0,
            0,
            canvas.width,
            canvas.height
        );

        const currentFrame =
            ctx.getImageData(
                0,
                0,
                canvas.width,
                canvas.height
            );

        if (previousFrame) {

            let diff = 0;

            for (
                let i = 0;
                i < currentFrame.data.length;
                i += 4
            ) {

                diff += Math.abs(
                    currentFrame.data[i]
                    - previousFrame.data[i]
                );
            }

            let movement =
                diff / currentFrame.data.length;

            console.log(
                "Movement:",
                movement
            );

            if (movement > 15) {
                showWarning(
                    "Excessive movement detected"
                );

                reportViolation(
                    "Excessive movement detected"
                );
            }
        }

        previousFrame = currentFrame;

    }, 3000);
}

/*
|--------------------------------------------------------------------------
| NOISE DETECTION
|--------------------------------------------------------------------------
*/
let noiseWarningShown = false;

function detectNoise(stream) {

    console.log("detectNoise started ✅");

    const audioContext = new (window.AudioContext || window.webkitAudioContext)();

    if (audioContext.state === "suspended") {
        audioContext.resume();
    }

    const microphone = audioContext.createMediaStreamSource(stream);
    const analyser = audioContext.createAnalyser();

    analyser.fftSize = 512;

    microphone.connect(analyser);

    const dataArray = new Uint8Array(analyser.frequencyBinCount);

    let baselineNoise = 0;
    let calibrationCount = 0;
    let smoothedVolume = 0;

    const smoothingFactor = 0.2;
    const calibrationSamples = 15;

    let loudFrames = 0;

    function getVolume() {
        analyser.getByteTimeDomainData(dataArray);

        let sum = 0;

        for (let i = 0; i < dataArray.length; i++) {
            const normalized = (dataArray[i] - 128) / 128;
            sum += normalized * normalized;
        }

        return Math.sqrt(sum / dataArray.length);
    }

    setInterval(() => {

        const currentVolume = getVolume();

        smoothedVolume =
            smoothingFactor * currentVolume +
            (1 - smoothingFactor) * smoothedVolume;

        // 🔹 Calibration
        if (calibrationCount < calibrationSamples) {
            baselineNoise += smoothedVolume;
            calibrationCount++;

            console.log("Calibrating:", calibrationCount);

            if (calibrationCount === calibrationSamples) {
                baselineNoise /= calibrationSamples;
                console.log("Baseline:", baselineNoise);
            }

            return;
        }

        const noiseThreshold = baselineNoise * 1.9;

        console.log(
            "Volume:", smoothedVolume.toFixed(4),
            "Threshold:", noiseThreshold.toFixed(4)
        );

        if (smoothedVolume > noiseThreshold) {
            loudFrames++;
        } else {
            loudFrames = 0;
        }

        if (loudFrames >= 4 && !noiseWarningShown) {

            noiseWarningShown = true;

            showWarning("High background noise detected");
            reportViolation("High background noise detected");

            console.log("🚨 Noise detected");

            setTimeout(() => {
                noiseWarningShown = false;
            }, 20000);
        }

    }, 200);
}
/*
|--------------------------------------------------------------------------
| FACE API LOAD
|--------------------------------------------------------------------------
*/  
 let faceWarningShown = false;

let phoneDetectFrames = 0;
let phoneWarningShown = false;

// SWITCHED AWAY FROM TFJS + COCO-SSD ENTIRELY.
// face-api.js bundles its own private copy of tfjs-core and registers it
// globally. ANY separate tf.js + coco-ssd loaded on the same page -
// main thread OR worker, any version pairing - ends up fighting over
// tensor/kernel registries and throwing cryptic errors like
// "n is not a function" (same bug family as
// https://github.com/justadudewhohacks/face-api.js/issues/629).
//
// Instead of continuing to work around that, phone/device detection now
// uses Google's MediaPipe Tasks Vision object detector. It's WASM-based,
// not tfjs-based, so it never touches the `tf` global at all and can't
// collide with face-api.js no matter what. It runs fine directly on the
// main thread. This is Google's own currently-recommended library for
// browser object detection (tfjs-models/coco-ssd is the older, now
// lightly-maintained approach).
const PHONE_DETECTION_CLASSES = ["cell phone", "remote"];
const PHONE_CONFIDENCE_THRESHOLD = 0.3;
const PHONE_REQUIRED_FRAMES = 2;
const PHONE_DETECTION_INTERVAL_MS = 1000;
const PHONE_WARNING_COOLDOWN_MS = 15000;

const MEDIAPIPE_VISION_CDN_BASE =
    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14";

const MEDIAPIPE_OBJECT_MODEL_URL =
    "https://storage.googleapis.com/mediapipe-models/object_detector/efficientdet_lite0/float16/1/efficientdet_lite0.tflite";

/*
|--------------------------------------------------------------------------
| VISIBLE DEBUG READOUT (TEMPORARY - remove once tuned)
|--------------------------------------------------------------------------
| Writes what the model sees directly onto the page every cycle, so you
| can confirm it's running and see real confidence numbers without
| opening devtools. Delete showPhoneDebugReadout() and its call below
| once you've picked a working threshold.
|--------------------------------------------------------------------------
*/

function showPhoneDebugReadout(text) {

    let box = document.getElementById("phone-debug-readout");

    if (!box) {

        box = document.createElement("div");
        box.id = "phone-debug-readout";

        box.style.position = "fixed";
        box.style.bottom = "10px";
        box.style.left = "10px";
        box.style.maxWidth = "360px";
        box.style.background = "rgba(0,0,0,0.85)";
        box.style.color = "#00ff88";
        box.style.font = "12px monospace";
        box.style.padding = "8px 12px";
        box.style.borderRadius = "8px";
        box.style.zIndex = "999999999";
        box.style.whiteSpace = "pre-wrap";
        box.style.pointerEvents = "none";

        document.body.appendChild(box);
    }

    box.textContent = "[phone-detect] " + text;
}

let phoneDetector = null;
let phoneDetectorLoadFailed = false;
let lastPhoneDetectVideoTime = -1;

async function loadPhoneDetector() {

    if (phoneDetector) return phoneDetector;

    try {

        showPhoneDebugReadout("loading MediaPipe object detector...");

        const { ObjectDetector, FilesetResolver } =
            await import(MEDIAPIPE_VISION_CDN_BASE);

        const vision = await FilesetResolver.forVisionTasks(
            MEDIAPIPE_VISION_CDN_BASE + "/wasm"
        );

        let detector;

        try {

            detector = await ObjectDetector.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: MEDIAPIPE_OBJECT_MODEL_URL,
                    delegate: "GPU"
                },
                scoreThreshold: 0.25,
                runningMode: "VIDEO"
            });

        } catch (gpuErr) {

            console.warn(
                "[phone-detect] GPU delegate failed, falling back to CPU:",
                gpuErr
            );

            detector = await ObjectDetector.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: MEDIAPIPE_OBJECT_MODEL_URL,
                    delegate: "CPU"
                },
                scoreThreshold: 0.25,
                runningMode: "VIDEO"
            });
        }

        phoneDetector = detector;

        console.log("[phone-detect] MediaPipe object detector ready");
        showPhoneDebugReadout("model ready, checking every " + PHONE_DETECTION_INTERVAL_MS + "ms");

        return phoneDetector;

    } catch (err) {

        phoneDetectorLoadFailed = true;

        console.error("[phone-detect] Failed to load MediaPipe object detector:", err);

        showPhoneDebugReadout(
            "ERROR loading detector: " + (err && err.message ? err.message : err) +
            " (check network tab for blocked cdn.jsdelivr.net / storage.googleapis.com requests)"
        );

        return null;
    }
}

function handlePhonePredictions(predictions) {

    const summary = predictions.length
        ? predictions.map((p) => p.class + " " + p.score.toFixed(2)).join(", ")
        : "(nothing detected this frame)";

    console.log("[phone-detect] predictions:", summary);
    showPhoneDebugReadout(summary);

    const phoneDetected = predictions.some((p) =>
        PHONE_DETECTION_CLASSES.includes(p.class) &&
        p.score >= PHONE_CONFIDENCE_THRESHOLD
    );

    if (phoneDetected) {

        phoneDetectFrames++;

    } else {

        phoneDetectFrames = 0;
    }

    if (
        phoneDetectFrames >= PHONE_REQUIRED_FRAMES &&
        !phoneWarningShown
    ) {

        phoneWarningShown = true;

        showWarning(
            "Mobile phone detected in camera view. Please put it away."
        );

        reportViolation(
            "Mobile phone / device detected in camera feed"
        );

        setTimeout(() => {

            phoneWarningShown = false;

        }, PHONE_WARNING_COOLDOWN_MS);
    }
}

function detectPhoneUsageOnce() {

    if (!phoneDetector) return;

    if (
        video.paused ||
        video.ended ||
        video.readyState < 2
    ) {

        showPhoneDebugReadout("video not ready (paused/ended/readyState=" + video.readyState + ")");
        return;
    }

    // detectForVideo requires a strictly increasing timestamp and errors
    // if called again for the same frame the video hasn't advanced past
    // yet (e.g. if the interval fires faster than the camera's frame rate).
    if (video.currentTime === lastPhoneDetectVideoTime) {
        return;
    }

    lastPhoneDetectVideoTime = video.currentTime;

    try {

        const result = phoneDetector.detectForVideo(video, performance.now());

        const predictions = (result.detections || [])
            .filter((d) => d.categories && d.categories.length)
            .map((d) => ({
                class: d.categories[0].categoryName,
                score: d.categories[0].score
            }));

        handlePhonePredictions(predictions);

    } catch (err) {

        console.error("[phone-detect] detectForVideo() error:", err);
        showPhoneDebugReadout("ERROR during detect: " + err.message);
    }
}

function startPhoneDetection() {

    loadPhoneDetector().then((detector) => {

        if (detector) {
            setInterval(detectPhoneUsageOnce, PHONE_DETECTION_INTERVAL_MS);
            return;
        }

        // First attempt failed (e.g. transient network issue) - retry
        // once instead of disabling phone detection for the whole
        // interview.
        console.warn("[phone-detect] Initial detector load failed - retrying once in 5s...");
        showPhoneDebugReadout("initial load failed - retrying in 5s...");

        setTimeout(() => {

            phoneDetectorLoadFailed = false;

            loadPhoneDetector().then((retryDetector) => {

                if (!retryDetector) {
                    console.error(
                        "[phone-detect] Retry also failed - phone detection disabled " +
                        "for this session. Check the debug box / console / network tab."
                    );
                    return;
                }

                setInterval(detectPhoneUsageOnce, PHONE_DETECTION_INTERVAL_MS);
            });

        }, 5000);
    });
}


async function startFaceDetection() {

    /*
    |--------------------------------------------------------------------------
    | LOAD MODELS (NO-OP IF ALREADY LOADED DURING VERIFICATION)
    |--------------------------------------------------------------------------
    */

    await loadModels();

    /*
    |--------------------------------------------------------------------------
    | WAIT FOR VIDEO
    |--------------------------------------------------------------------------
    */

    await new Promise((resolve) => {

        if (video.readyState >= 3) {

            resolve();

        } else {

            video.onloadedmetadata =
                () => resolve();
        }
    });

    await video.play();

    console.log("Video Ready");

    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    */

    let noFaceFrames = 0;

    let multipleFaceFrames = 0;

    const requiredFrames = 3;

    /*
    |--------------------------------------------------------------------------
    | DETECTION LOOP
    |--------------------------------------------------------------------------
    */

    setInterval(async () => {

        try {

            /*
            |--------------------------------------------------------------------------
            | VIDEO VALIDATION
            |--------------------------------------------------------------------------
            */

            if (
                video.paused ||
                video.ended ||
                video.readyState < 2
            ) {

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | DETECT FACES
            |--------------------------------------------------------------------------
            */

            const detections =
                await faceapi
                    .detectAllFaces(
                        video,
                        new faceapi.SsdMobilenetv1Options({
                            minConfidence: 0.5
                        })
                    )
                    .withFaceLandmarks();

            console.log(
                "Detected Faces:",
                detections.length
            );

            /*
            |--------------------------------------------------------------------------
            | GAZE / LOOK-AWAY DETECTION (reuses the landmarks above - no
            | extra model or extra detect() call). Only runs when exactly
            | one face is present, since with 0 or 2+ faces the "which
            | face's gaze" question is already moot / covered by the
            | no-face / multiple-face checks below.
            |--------------------------------------------------------------------------
            */

            if (detections.length === 1) {

                const landmarks = detections[0].landmarks;
                const box = detections[0].detection.box;

                const eyePoints = [
                    ...landmarks.getLeftEye(),
                    ...landmarks.getRightEye()
                ];

                const eyeCenterY =
                    eyePoints.reduce((sum, p) => sum + p.y, 0) / eyePoints.length;

                // Normalize to 0-1 within this face's own bounding box so
                // it's independent of how close/far the candidate is
                // sitting from the camera.
                const relativeEyeY = (eyeCenterY - box.y) / box.height;

                if (gazeCalibrationCount < GAZE_CALIBRATION_SAMPLES) {

                    gazeBaseline += relativeEyeY;
                    gazeCalibrationCount++;

                    if (gazeCalibrationCount === GAZE_CALIBRATION_SAMPLES) {

                        gazeBaseline /= GAZE_CALIBRATION_SAMPLES;

                        console.log(
                            "[gaze] baseline relative eye Y:",
                            gazeBaseline.toFixed(3)
                        );
                    }

                } else {

                    const drop = relativeEyeY - gazeBaseline;

                    if (drop > GAZE_DOWN_THRESHOLD) {

                        gazeDownFrames++;

                    } else {

                        gazeDownFrames = 0;
                    }

                    if (
                        gazeDownFrames >= GAZE_DOWN_REQUIRED_FRAMES &&
                        !gazeWarningShown
                    ) {

                        gazeWarningShown = true;

                        showWarning(
                            "Looking away from the screen for an extended period"
                        );

                        reportViolation(
                            "Sustained downward gaze detected (possible phone/device use), drop=" +
                            drop.toFixed(3)
                        );

                        console.log("[gaze] sustained look-away violation, drop:", drop.toFixed(3));

                        setTimeout(() => {

                            gazeWarningShown = false;

                        }, GAZE_WARNING_COOLDOWN_MS);
                    }
                }

            } else {

                // Face count changed (none/multiple) - reset the streak so
                // a warning doesn't fire on stale frames once a single
                // face reappears.
                gazeDownFrames = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | NO FACE
            |--------------------------------------------------------------------------
            */

            if (
                detections.length === 0
            ) {

                noFaceFrames++;

            } else {

                noFaceFrames = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | MULTIPLE FACE
            |--------------------------------------------------------------------------
            */

            if (
                detections.length > 1
            ) {

                multipleFaceFrames++;

            } else {

                multipleFaceFrames = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | NO FACE WARNING
            |--------------------------------------------------------------------------
            */

            if (
                noFaceFrames >= requiredFrames &&
                !faceWarningShown
            ) {

                faceWarningShown = true;

                showWarning(
                    "No face detected"
                );

                reportViolation(
                    "No face detected"
                );

                console.log(
                    "No face violation"
                );

                setTimeout(() => {

                    faceWarningShown = false;

                }, 15000);
            }

            /*
            |--------------------------------------------------------------------------
            | MULTIPLE FACE WARNING
            |--------------------------------------------------------------------------
            */

            if (
                multipleFaceFrames >= requiredFrames &&
                !faceWarningShown
            ) {

                faceWarningShown = true;

                showWarning(
                    "Multiple faces detected"
                );

                reportViolation(
                    "Multiple faces detected"
                );

                console.log(
                    "Multiple face violation"
                );

                setTimeout(() => {

                    faceWarningShown = false;

                }, 15000);
            }

        } catch (error) {

            console.error(
                "Face Detection Error:",
                error
            );
        }

    }, 1000);
}

/*
|--------------------------------------------------------------------------
| START EVERYTHING
|--------------------------------------------------------------------------
*/

window.onload = async () => {

    // Create the anti-screenshot overlay up-front so it always exists
    // before any PrintScreen key event can fire.
    ensureAntiScreenshotOverlay();

    await startCamera();

};