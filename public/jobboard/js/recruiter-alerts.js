(function () {
    'use strict';

    if (window.HMAlert && window.HMAlert.fire) {
        return;
    }

    var css = [
        '.hm-alert-overlay{position:fixed;inset:0;z-index:2147483000;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.48);backdrop-filter:blur(3px);padding:20px}',
        '.hm-alert-overlay.is-visible{display:flex}',
        '.hm-alert-box{width:min(430px,100%);border-radius:22px;background:#fff;border:1px solid rgba(217,236,229,.95);box-shadow:0 28px 70px rgba(15,23,42,.26);padding:28px 26px 24px;text-align:center;transform:translateY(10px) scale(.98);opacity:0;transition:opacity .18s ease,transform .18s ease;font-family:var(--portal-font-family,"Nunito",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif)}',
        '.hm-alert-overlay.is-visible .hm-alert-box{opacity:1;transform:translateY(0) scale(1)}',
        '.hm-alert-icon{width:64px;height:64px;margin:0 auto 16px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:900}',
        '.hm-alert-icon.is-success{background:rgba(31,183,181,.12);color:#0d8a90;border:1px solid rgba(31,183,181,.28)}',
        '.hm-alert-icon.is-error{background:rgba(239,68,68,.10);color:#dc2626;border:1px solid rgba(239,68,68,.24)}',
        '.hm-alert-icon.is-warning{background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.25)}',
        '.hm-alert-icon.is-info{background:rgba(59,130,246,.10);color:#2563eb;border:1px solid rgba(59,130,246,.22)}',
        '.hm-alert-title{margin:0 0 8px;font-size:1.18rem;line-height:1.25;font-weight:800;color:#0f172a}',
        '.hm-alert-message{margin:0;color:#475569;font-size:.95rem;line-height:1.6;white-space:pre-wrap;word-break:break-word}',
        '.hm-alert-actions{display:flex;justify-content:center;gap:10px;margin-top:22px}',
        '.hm-alert-btn{min-width:108px;border:1.5px solid #1fb7b5;border-radius:12px;padding:10px 18px;background:#1fb7b5;color:#fff;font-weight:800;cursor:pointer;box-shadow:none;transition:transform .16s ease,background .16s ease,border-color .16s ease}',
        '.hm-alert-btn:hover,.hm-alert-btn:focus{background:#0d8a90;border-color:#0d8a90;transform:translateY(-1px);outline:none}',
        '.hm-alert-btn.is-cancel{background:#fff;color:#64748b;border-color:#d9ece5}',
        '.hm-alert-btn.is-cancel:hover,.hm-alert-btn.is-cancel:focus{background:#f8fcfb;color:#0f172a;border-color:#b8d8d0}',
        'body.dark .hm-alert-box{background:#111;border-color:#23343a;box-shadow:0 28px 70px rgba(0,0,0,.55)}',
        'body.dark .hm-alert-title{color:#f8fafc}',
        'body.dark .hm-alert-message{color:#cbd5e1}',
        'body.dark .hm-alert-btn.is-cancel{background:#111;color:#cbd5e1;border-color:#23343a}'
    ].join('');

    var style = document.createElement('style');
    style.id = 'hm-alert-style';
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);

    var overlay = document.createElement('div');
    overlay.className = 'hm-alert-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = [
        '<div class="hm-alert-box" role="document">',
        '  <div class="hm-alert-icon is-info" aria-hidden="true">i</div>',
        '  <h3 class="hm-alert-title">Notice</h3>',
        '  <p class="hm-alert-message"></p>',
        '  <div class="hm-alert-actions">',
        '    <button type="button" class="hm-alert-btn is-cancel" data-hm-alert-cancel style="display:none">Cancel</button>',
        '    <button type="button" class="hm-alert-btn" data-hm-alert-ok>OK</button>',
        '  </div>',
        '</div>'
    ].join('');
    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(overlay);
    });

    var queue = [];
    var active = false;

    function normalizeIcon(icon) {
        icon = String(icon || 'info').toLowerCase();
        if (['success', 'error', 'warning', 'info'].indexOf(icon) === -1) {
            return 'info';
        }
        return icon;
    }

    function iconGlyph(icon) {
        return {
            success: '✓',
            error: '!',
            warning: '!',
            info: 'i'
        }[icon] || 'i';
    }

    function inferIcon(message) {
        var text = String(message || '').toLowerCase();
        if (/error|failed|invalid|unauthorized|cannot|could not|required|please select|please enter|no valid/.test(text)) {
            return 'error';
        }
        if (/success|sent|scheduled|saved|updated|completed|imported|connected|deleted/.test(text)) {
            return 'success';
        }
        return 'info';
    }

    function inferTitle(icon, title) {
        if (title) {
            return title;
        }
        return {
            success: 'Success',
            error: 'Something went wrong',
            warning: 'Please confirm',
            info: 'Notice'
        }[icon] || 'Notice';
    }

    function fire(options) {
        if (typeof options === 'string') {
            options = { text: options };
        }
        options = options || {};

        return new Promise(function (resolve) {
            queue.push({ options: options, resolve: resolve });
            runNext();
        });
    }

    function runNext() {
        if (active || queue.length === 0 || !document.body) {
            return;
        }
        active = true;

        var item = queue.shift();
        var options = item.options || {};
        var icon = normalizeIcon(options.icon || inferIcon(options.text || options.message || ''));
        var title = inferTitle(icon, options.title);
        var text = String(options.text || options.message || '');
        var showCancel = !!options.showCancelButton;
        var okText = String(options.confirmButtonText || 'OK');
        var cancelText = String(options.cancelButtonText || 'Cancel');

        var iconEl = overlay.querySelector('.hm-alert-icon');
        var titleEl = overlay.querySelector('.hm-alert-title');
        var msgEl = overlay.querySelector('.hm-alert-message');
        var okBtn = overlay.querySelector('[data-hm-alert-ok]');
        var cancelBtn = overlay.querySelector('[data-hm-alert-cancel]');

        iconEl.className = 'hm-alert-icon is-' + icon;
        iconEl.textContent = iconGlyph(icon);
        titleEl.textContent = title;
        msgEl.textContent = text;
        okBtn.textContent = okText;
        cancelBtn.textContent = cancelText;
        cancelBtn.style.display = showCancel ? '' : 'none';

        function close(result) {
            overlay.classList.remove('is-visible');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            document.removeEventListener('keydown', onKey);
            window.setTimeout(function () {
                active = false;
                item.resolve(result);
                runNext();
            }, 150);
        }

        function onOk() {
            close({ isConfirmed: true, isDismissed: false });
        }

        function onCancel() {
            close({ isConfirmed: false, isDismissed: true });
        }

        function onKey(event) {
            if (event.key === 'Escape' && showCancel) {
                event.preventDefault();
                onCancel();
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                onOk();
            }
        }

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        document.addEventListener('keydown', onKey);
        overlay.classList.add('is-visible');
        window.setTimeout(function () { okBtn.focus(); }, 30);
    }

    window.HMAlert = {
        fire: fire,
        success: function (message, title) { return fire({ icon: 'success', title: title || 'Success', text: message }); },
        error: function (message, title) { return fire({ icon: 'error', title: title || 'Something went wrong', text: message }); },
        info: function (message, title) { return fire({ icon: 'info', title: title || 'Notice', text: message }); },
        warning: function (message, title) { return fire({ icon: 'warning', title: title || 'Please confirm', text: message }); }
    };

    window.alert = function (message) {
        window.HMAlert.fire({
            icon: inferIcon(message),
            text: String(message || '')
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.alert.alert-success, .alert.alert-danger, .alert.alert-warning, .alert.alert-info').forEach(function (alertEl) {
            if (alertEl.dataset.hmAlertShown === '1' || alertEl.dataset.hmAlertInline === '1') {
                return;
            }
            var message = (alertEl.textContent || '').replace(/\s+/g, ' ').trim();
            if (!message) {
                return;
            }
            alertEl.dataset.hmAlertShown = '1';
            alertEl.style.display = 'none';
            var icon = alertEl.classList.contains('alert-success') ? 'success'
                : alertEl.classList.contains('alert-danger') ? 'error'
                    : alertEl.classList.contains('alert-warning') ? 'warning'
                        : 'info';
            window.HMAlert.fire({ icon: icon, text: message });
        });
    });
})();
