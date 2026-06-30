(function () {
    'use strict';

    if (window.HMAlert && window.HMAlert.fire) {
        return;
    }

    var css = [
        '.hm-inline-notice-region{display:grid;gap:10px;margin:0 0 16px}',
        '.hm-inline-notice{display:flex;align-items:flex-start;gap:10px;border:1px solid #d9ece5;border-radius:12px;background:#f8fcfb;color:#16212b;padding:12px 14px;font-family:var(--portal-font-family,"Nunito",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif);font-size:13.5px;line-height:1.45}',
        '.hm-inline-notice strong{display:block;margin-bottom:2px;color:#16212b;font-size:13.5px}',
        '.hm-inline-notice-icon{width:24px;height:24px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 24px;font-size:13px;font-weight:800;background:rgba(31,183,181,.12);color:#0d8a90}',
        '.hm-inline-notice-message{min-width:0;white-space:normal;word-break:break-word}',
        '.hm-inline-notice.is-success{border-color:rgba(31,183,181,.28);background:rgba(31,183,181,.08)}',
        '.hm-inline-notice.is-error{border-color:rgba(239,68,68,.24);background:rgba(239,68,68,.08)}',
        '.hm-inline-notice.is-warning{border-color:rgba(245,158,11,.25);background:rgba(245,158,11,.10)}',
        '.hm-inline-notice.is-info{border-color:#d9ece5;background:#f8fcfb}',
        '.hm-inline-notice.is-success .hm-inline-notice-icon{background:rgba(31,183,181,.12);color:#0d8a90}',
        '.hm-inline-notice.is-error .hm-inline-notice-icon{background:rgba(239,68,68,.10);color:#dc2626}',
        '.hm-inline-notice.is-warning .hm-inline-notice-icon{background:rgba(245,158,11,.12);color:#d97706}',
        'body.dark .hm-inline-notice{background:#071214;color:#cbd5e1;border-color:#23343a}',
        'body.dark .hm-inline-notice strong{color:#f8fafc}',
        'body.dark .hm-inline-notice.is-success{background:rgba(31,183,181,.08);border-color:rgba(31,183,181,.22)}',
        'body.dark .hm-inline-notice.is-error{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.22)}',
        'body.dark .hm-inline-notice.is-warning{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.22)}'
    ].join('');

    var style = document.createElement('style');
    style.id = 'hm-inline-notice-style';
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);

    function normalizeIcon(icon) {
        icon = String(icon || 'info').toLowerCase();
        if (['success', 'error', 'warning', 'info'].indexOf(icon) === -1) {
            return 'info';
        }
        return icon;
    }

    function iconGlyph(icon) {
        return {
            success: 'OK',
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
        if (/pending|confirm|warning|attention/.test(text)) {
            return 'warning';
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
            warning: 'Notice',
            info: 'Notice'
        }[icon] || 'Notice';
    }

    function getRegion() {
        var existing = document.querySelector('[data-hm-inline-notice-region]');
        if (existing) {
            return existing;
        }

        var region = document.createElement('div');
        region.className = 'hm-inline-notice-region';
        region.setAttribute('data-hm-inline-notice-region', '1');
        region.setAttribute('aria-live', 'polite');

        var target = document.querySelector('main .container-fluid, main .container, .hm-page-content, main') || document.body;
        if (target.firstChild) {
            target.insertBefore(region, target.firstChild);
        } else {
            target.appendChild(region);
        }

        return region;
    }

    function showInline(options) {
        if (typeof options === 'string') {
            options = { text: options };
        }
        options = options || {};

        var text = String(options.text || options.message || '').trim();
        if (!text) {
            return Promise.resolve({ isConfirmed: true, isDismissed: false });
        }

        var icon = normalizeIcon(options.icon || inferIcon(text));
        var title = inferTitle(icon, options.title);
        var region = getRegion();
        var notice = document.createElement('div');
        notice.className = 'hm-inline-notice is-' + icon;
        notice.innerHTML = [
            '<span class="hm-inline-notice-icon" aria-hidden="true"></span>',
            '<span class="hm-inline-notice-message"><strong></strong><span></span></span>'
        ].join('');

        notice.querySelector('.hm-inline-notice-icon').textContent = iconGlyph(icon);
        notice.querySelector('strong').textContent = title;
        notice.querySelector('.hm-inline-notice-message span').textContent = text;

        region.appendChild(notice);
        notice.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

        return Promise.resolve({ isConfirmed: true, isDismissed: false });
    }

    window.HMAlert = {
        fire: showInline,
        success: function (message, title) { return showInline({ icon: 'success', title: title || 'Success', text: message }); },
        error: function (message, title) { return showInline({ icon: 'error', title: title || 'Something went wrong', text: message }); },
        info: function (message, title) { return showInline({ icon: 'info', title: title || 'Notice', text: message }); },
        warning: function (message, title) { return showInline({ icon: 'warning', title: title || 'Notice', text: message }); }
    };

    window.alert = function (message) {
        window.HMAlert.fire({
            icon: inferIcon(message),
            text: String(message || '')
        });
    };
})();
