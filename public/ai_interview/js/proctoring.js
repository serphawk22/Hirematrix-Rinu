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

/*
|--------------------------------------------------------------------------
| WARNING SYSTEM
|--------------------------------------------------------------------------
*/

function showWarning(message) {

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

                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeeba;
                border-left: 5px solid #ff9800;

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

            .custom-warning-title {

                font-weight: bold;
                margin-bottom: 5px;
                font-size: 16px;
            }

            .custom-warning-close {

                position: absolute;
                top: 10px;
                right: 12px;

                cursor: pointer;

                font-size: 18px;

                color: #856404;
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
| START CAMERA + MIC
|--------------------------------------------------------------------------
*/

async function startCamera() {

    try {

        const stream =
            await navigator.mediaDevices.getUserMedia({

                video: true,

                audio: true

            });

        video.srcObject = stream;

        console.log("Camera started");  

        detectNoise(stream);

        detectMovement();

        startFaceDetection(); /*
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

    } catch (error) {

        console.error(error);

        showPermissionPopup();
    }
}
function showPermissionPopup() {

    /*
    |--------------------------------------------------------------------------
    | REMOVE EXISTING POPUP
    |--------------------------------------------------------------------------
    */

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

    popup.style.background =
        "rgba(3, 7, 18, 0.88)";

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

    box.style.background =
        "linear-gradient(145deg, #0f172a, #111c34)";

    box.style.border =
        "1px solid rgba(108,99,255,0.25)";

    box.style.boxShadow =
        "0 20px 60px rgba(0,0,0,0.55)";

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

    icon.style.background =
        "rgba(108,99,255,0.16)";

    icon.style.border =
        "1px solid rgba(108,99,255,0.35)";

    icon.style.boxShadow =
        "0 0 25px rgba(108,99,255,0.35)";

    /*
    |--------------------------------------------------------------------------
    | TITLE
    |--------------------------------------------------------------------------
    */

    const title =
        box.querySelector("h2");

    title.style.color =
        "#ffffff";

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

    text.style.color =
        "#94a3b8";

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

    button.style.background =
        "linear-gradient(90deg, #6c63ff, #4f46e5)";

    button.style.color =
        "#ffffff";

    button.style.fontSize =
        "16px";

    button.style.fontWeight =
        "600";

    button.style.cursor =
        "pointer";

    button.style.boxShadow =
        "0 10px 25px rgba(108,99,255,0.35)";

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

                        video: true,
                        audio: true
                    });

            video.srcObject = stream;

            popup.remove(); 

            detectNoise(stream);

            detectMovement();

            startFaceDetection();

        } catch (err) {

            console.error(err);

            alert(
                "Permission still denied"
            );
        }
    };
}
/*

/*
|--------------------------------------------------------------------------
| SEND VIOLATION TO SERVER
|--------------------------------------------------------------------------
*/

function reportViolation(message) {

    console.log("Violation:", message);

    fetch("api/report_violation.php", {

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
*/

document.addEventListener(
    "visibilitychange",
    () => {

        if (document.hidden) {

            showWarning(
                "Tab switched"
            );

            reportViolation(
                "Tab switched"
            );
        }
    }
);

/*
|--------------------------------------------------------------------------
| FULLSCREEN EXIT DETECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "fullscreenchange",
    () => {

        if (!document.fullscreenElement) {

            showWarning(
                "Fullscreen mode exited"
            );

            reportViolation(
                "Fullscreen mode exited"
            );

            enableFullscreen();
        }
    }
);

/*
|--------------------------------------------------------------------------
| COPY DETECTION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "copy",
    e => {

        e.preventDefault(); 
         reportViolation(
                "Copy attempt detected"
            );
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
         reportViolation(
                "Cut attempt detected"
            );
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
         reportViolation(
                "Paste attempt detected"
            );
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
         reportViolation(
                "Right click disabled"
            );
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

    /*
    |--------------------------------------------------------------------------
    | BLACKOUT SCREEN
    |--------------------------------------------------------------------------
    */

    const overlay =
        document.getElementById(
            "anti-screenshot-overlay"
        );

    overlay.style.display = "flex";

    /*
    |--------------------------------------------------------------------------
    | CLEAR CLIPBOARD
    |--------------------------------------------------------------------------
    */

    navigator.clipboard.writeText(
        "Screenshots are disabled during interview"
    );

    /*
    |--------------------------------------------------------------------------
    | WARNING
    |--------------------------------------------------------------------------
    */

    showWarning(
        "Screenshot attempt detected"
    );

    /*
    |--------------------------------------------------------------------------
    | SAVE VIOLATION
    |--------------------------------------------------------------------------
    */

    reportViolation(
        "Screenshot attempt detected"
    );

    /*
    |--------------------------------------------------------------------------
    | HIDE CONTENT TEMPORARILY
    |--------------------------------------------------------------------------
    */

    document.body.style.filter =
        "blur(25px)";

    setTimeout(() => {

        overlay.style.display = "none";

        document.body.style.filter =
            "blur(0px)";

    }, 3000);
}
       

        /*
        |--------------------------------------------------------------------------
        | CTRL+C
        |--------------------------------------------------------------------------
        */

        if (e.ctrlKey && e.key === "c") {
 
              reportViolation(
                "Copy shortcut detected"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CTRL+V
        |--------------------------------------------------------------------------
        */

        if (e.ctrlKey && e.key === "v") {

            e.preventDefault(); 
              reportViolation(
                "Paste shortcut detected"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CTRL+U
        |--------------------------------------------------------------------------
        */

        if (e.ctrlKey && e.key === "u") {

            e.preventDefault(); 
              reportViolation(
                "Source view blocked"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | F12
        |--------------------------------------------------------------------------
        */

        if (e.key === "F12") {

            e.preventDefault(); 
              reportViolation(
                "Developer tools detected"
            );
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
              reportViolation(
                "Developer tools detected"
            );
        }
    }
);
/*
|--------------------------------------------------------------------------
| DETECT WINDOWS SNIPPING TOOL
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "keyup",
    e => {

        if (
            e.key === "PrintScreen"
        ) {

            showWarning(
                "Screenshot shortcut detected"
            );
        }
    }
);
/*
|--------------------------------------------------------------------------
| DEVTOOLS DETECTION
|--------------------------------------------------------------------------
*/

 

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

async function startFaceDetection() {

    /*
    |--------------------------------------------------------------------------
    | LOAD MODELS
    |--------------------------------------------------------------------------
    */

    await Promise.all([

        faceapi.nets.ssdMobilenetv1
            .loadFromUri("models"),

        faceapi.nets.faceLandmark68Net
            .loadFromUri("models")

    ]);

    console.log("Face Models Loaded");

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
| DISABLE RIGHT CLICK
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "contextmenu",
    e => e.preventDefault()
);

/*
|--------------------------------------------------------------------------
| START EVERYTHING
|--------------------------------------------------------------------------
*/

window.onload = async () => {

    await startCamera();

};