<div class="google-translate-widget">
    <div id="google_translate_element"></div>
</div>

<style>
/* --- Suppress Google's injected top banner --- */
body {
    top: 0 !important;
    position: static !important;
}

.goog-te-banner-frame,
.goog-te-banner-frame.skiptranslate,
#goog-gt-tt,
.goog-tooltip,
.goog-tooltip:hover,
.goog-text-highlight {
    display: none !important;
    visibility: hidden !important;
}

/* --- Widget container --- */
.google-translate-widget {
    width: 100%;
    max-width: 220px;
}

/* --- Wrapper injected by Google --- */
#google_translate_element {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 8px;
    padding: 6px 8px;
    width: 100%;
}

#google_translate_element .goog-te-gadget {
    font-family: inherit !important;
    color: rgba(255,255,255,0.85) !important;
    font-size: 12px !important;
    line-height: 1.4;
    margin: 0 !important;
}

#google_translate_element .goog-te-gadget > span {
    display: none !important; /* hides "Powered by Google" text */
}

/* --- The actual language dropdown --- */
#google_translate_element .goog-te-combo {
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    height: 32px;
    border: 1px solid rgba(255,255,255,0.22);
    border-radius: 6px;
    padding: 4px 8px;
    background: #ffffff;
    color: #1f2937;
    font-size: 13px !important;
    cursor: pointer;
    appearance: auto;
    -webkit-appearance: auto;
}

#google_translate_element .goog-te-combo option {
    background: #ffffff;
    color: #1f2937;
}

#google_translate_element .goog-te-combo:focus {
    outline: none;
    border-color: rgba(255,255,255,0.5);
}

#google_translate_element a {
    color: rgba(255,255,255,0.7) !important;
    text-decoration: none !important;
    font-size: 11px !important;
}

/* --- Responsive: full width on mobile --- */
@media (max-width: 767.98px) {
    .google-translate-widget {
        max-width: 100%;
        width: 100%;
    }

    #google_translate_element .goog-te-combo {
        width: 100% !important;
        font-size: 14px !important;
        height: 36px;
    }
}

/* --- Hide the floating Google translate bar if it appears --- */
.skiptranslate iframe {
    display: none !important;
    height: 0 !important;
    visibility: hidden !important;
}
</style>

<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,hi,ta,te,kn,ml,mr,gu,bn,pa',
        autoDisplay: false,
        gaTrack: false
    }, 'google_translate_element');
}

// Remove the Google top banner after it loads
(function suppressGoogleBanner() {
    var observer = new MutationObserver(function() {
        var banner = document.querySelector('.goog-te-banner-frame');
        if (banner) {
            banner.style.display = 'none';
            document.body.style.top = '0';
        }
        var tt = document.getElementById('goog-gt-tt');
        if (tt) tt.style.display = 'none';
    });
    observer.observe(document.body, { childList: true, subtree: true });
})();
</script>

<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async defer></script>
