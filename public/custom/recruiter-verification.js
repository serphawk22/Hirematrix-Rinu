(function () {
  function initRecruiterVerification() {
    const app = document.getElementById("recruiterVerificationApp");
    if (!app) {
      return;
    }

    const verifyBtn = document.getElementById("verifyOtpBtn");
    const formEl = document.getElementById("phoneVerifyForm");
    const otpInput = document.getElementById("otpCode");
    const phoneE164 = (app.dataset.phoneE164 || "").trim();
    const tokenInput = document.getElementById("firebaseIdToken");
    const statusEl = document.getElementById("otpStatus");
    const firebaseConfigured = app.dataset.firebaseConfigured === "1";

    function setStatus(message, isError) {
      if (!statusEl) {
        return;
      }
      statusEl.textContent = message;
      statusEl.className = "small mt-3 mb-0 " + (isError ? "text-danger" : "text-muted");
    }

    function disableOtpButtons() {
      if (verifyBtn) {
        verifyBtn.disabled = true;
      }
    }

    window.addEventListener("error", function (e) {
      setStatus("Script error: " + (e.message || "Unknown error"), true);
    });

    window.addEventListener("unhandledrejection", function (e) {
      const msg = e && e.reason && e.reason.message ? e.reason.message : "Unhandled promise error";
      setStatus("Runtime error: " + msg, true);
    });

    try {
      if (!firebaseConfigured) {
        setStatus("Firebase is not configured. Add Firebase web config values in .env to enable phone OTP.", true);
        disableOtpButtons();
        return;
      }

      if (typeof firebase === "undefined" || !firebase.auth) {
        setStatus("Firebase SDK failed to load. Check internet/firewall or blocked scripts.", true);
        disableOtpButtons();
        return;
      }

      if (!phoneE164) {
        setStatus("Invalid phone number format. Update phone number and try again.", true);
        disableOtpButtons();
        return;
      }

      const firebaseConfig = {
        apiKey: app.dataset.firebaseApiKey || "",
        authDomain: app.dataset.firebaseAuthDomain || "",
        projectId: app.dataset.firebaseProjectId || "",
        appId: app.dataset.firebaseAppId || "",
        messagingSenderId: app.dataset.firebaseMessagingSenderId || ""
      };

      if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
      }

      const auth = firebase.auth();
      const isLocalHost = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
      if (isLocalHost) {
        auth.settings.appVerificationDisabledForTesting = true;
      }

      if (typeof navigator !== "undefined" && navigator.onLine === false) {
        setStatus("No internet connection. Please reconnect and reload.", true);
        disableOtpButtons();
        return;
      }

      window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier("recaptcha-container", {
        size: "invisible"
      });

      window.recaptchaVerifier.render()
        .then(function () {
          setStatus("Firebase initialized. Sending OTP...", false);
          return auth.signInWithPhoneNumber(phoneE164, window.recaptchaVerifier);
        })
        .then(function (result) {
          window.confirmationResult = result;
          setStatus("OTP sent successfully.", false);
        })
        .catch(function (error) {
          if (error && error.code === "auth/network-request-failed") {
            setStatus("Network request failed. Check internet, firewall/proxy, and allow access to googleapis.com/gstatic.com.", true);
          } else {
            setStatus(error && error.message ? error.message : "Failed to send OTP.", true);
          }
          disableOtpButtons();
        });

      verifyBtn.addEventListener("click", function () {
        const code = (otpInput.value || "").trim();
        if (!window.confirmationResult) {
          setStatus("OTP not initialized. Please reload and try again.", true);
          return;
        }
        if (code.length < 6) {
          setStatus("Enter a valid 6-digit OTP.", true);
          return;
        }

        verifyBtn.disabled = true;
        setStatus("Verifying OTP...", false);

        window.confirmationResult.confirm(code)
          .then(function (result) {
            return result.user.getIdToken(true);
          })
          .then(function (idToken) {
            tokenInput.value = idToken;
            setStatus("OTP verified. Completing verification...", false);
            if (formEl) {
              formEl.submit();
            }
          })
          .catch(function (error) {
            setStatus(error && error.message ? error.message : "Invalid OTP.", true);
            tokenInput.value = "";
          })
          .finally(function () {
            verifyBtn.disabled = false;
          });
      });
    } catch (error) {
      setStatus(error && error.message ? error.message : "Failed to initialize Firebase phone auth.", true);
      disableOtpButtons();
    }
  }

  document.addEventListener("DOMContentLoaded", initRecruiterVerification);
})();
