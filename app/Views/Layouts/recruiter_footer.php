</main>
 
<style>
body.recruiter-jobboard table thead th,
body.recruiter-jobboard .table thead th,
body.recruiter-jobboard .recruiter-jobs-table thead th,
body.recruiter-jobboard .recruiter-slots-table thead th,
body.recruiter-jobboard .pipeline-table thead th,
body.recruiter-jobboard .conversion-table thead th,
body.recruiter-jobboard .ai-modal #aiReportContent .table thead th {
    font-family: var(--portal-font-family, "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif) !important;
    font-size: 12.5px !important;
    font-weight: 700 !important;
    line-height: 1.3 !important;
    letter-spacing: 0.04em !important;
}

body.recruiter-jobboard table tbody td,
body.recruiter-jobboard .table tbody td,
body.recruiter-jobboard .recruiter-jobs-table tbody td,
body.recruiter-jobboard .recruiter-slots-table tbody td,
body.recruiter-jobboard .pipeline-table tbody td,
body.recruiter-jobboard .conversion-table tbody td,
body.recruiter-jobboard .ai-modal #aiReportContent .table tbody td {
    font-family: var(--portal-font-family, "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif) !important;
    font-size: 13.5px !important;
    line-height: 1.45 !important;
}

body.recruiter-jobboard table tbody td strong,
body.recruiter-jobboard table tbody td .font-weight-bold,
body.recruiter-jobboard .table tbody td strong,
body.recruiter-jobboard .table tbody td .font-weight-bold,
body.recruiter-jobboard .recruiter-jobs-table tbody td strong,
body.recruiter-jobboard .pipeline-table tbody td strong {
    font-size: 14px !important;
    line-height: 1.35 !important;
    font-weight: 700 !important;
}

body.recruiter-jobboard table tbody td small,
body.recruiter-jobboard table tbody td .text-muted,
body.recruiter-jobboard .table tbody td small,
body.recruiter-jobboard .table tbody td .text-muted,
body.recruiter-jobboard .recruiter-jobs-table tbody td small,
body.recruiter-jobboard .recruiter-jobs-table tbody td .text-muted,
body.recruiter-jobboard .recruiter-slots-table tbody td small,
body.recruiter-jobboard .recruiter-slots-table tbody td .text-muted,
body.recruiter-jobboard .pipeline-table tbody td small,
body.recruiter-jobboard .pipeline-table tbody td .text-muted,
body.recruiter-jobboard .conversion-table tbody td small,
body.recruiter-jobboard .conversion-table tbody td .text-muted,
body.recruiter-jobboard .ai-modal #aiReportContent .table tbody td small,
body.recruiter-jobboard .ai-modal #aiReportContent .table tbody td .text-muted {
    font-size: 12.5px !important;
    line-height: 1.4 !important;
}
</style>

<!-- SCRIPTS -->
<script src="<?= base_url('jobboard/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/isotope.pkgd.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/stickyfill.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/jquery.easing.1.3.js') ?>"></script>
<script src="<?= base_url('jobboard/js/jquery.waypoints.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/jquery.animateNumber.min.js') ?>"></script>
<script src="<?= base_url('jobboard/js/custom.js') ?>"></script>
<script src="<?= base_url('jobboard/js/recruiter-pages.js') ?>"></script>
<script src="<?= base_url('jobboard/js/recruiter-alerts.js?v=' . @filemtime(FCPATH . 'jobboard/js/recruiter-alerts.js')) ?>"></script>
<script src="<?= base_url('jobboard/js/notification-actions.js?v=' . @filemtime(FCPATH . 'jobboard/js/notification-actions.js')) ?>"></script>
<script>
(function () {
    'use strict';

    var pollUrl = '<?= base_url('recruiter/mailbox/poll') ?>';
    var intervalMs = 5000;
    var firstDelayMs = 2000;
    var running = false;
    var audioContext = null;
    var audioEnabled = false;

    function enableNotificationAudio() {
        audioEnabled = true;
        try {
            var AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                audioContext = audioContext || new AudioContextClass();
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }
            }
        } catch (error) {}
        document.removeEventListener('pointerdown', enableNotificationAudio);
        document.removeEventListener('keydown', enableNotificationAudio);
    }

    function playNotificationChime() {
        if (!audioEnabled) {
            return;
        }
        try {
            var AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                return;
            }
            audioContext = audioContext || new AudioContextClass();
            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }

            var now = audioContext.currentTime;
            [
                { frequency: 660, start: 0, duration: .11 },
                { frequency: 880, start: .12, duration: .16 }
            ].forEach(function (tone) {
                var oscillator = audioContext.createOscillator();
                var gain = audioContext.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(tone.frequency, now + tone.start);
                gain.gain.setValueAtTime(0.0001, now + tone.start);
                gain.gain.exponentialRampToValueAtTime(0.045, now + tone.start + .018);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + tone.start + tone.duration);
                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.start(now + tone.start);
                oscillator.stop(now + tone.start + tone.duration + .02);
            });
        } catch (error) {}
    }

    document.addEventListener('pointerdown', enableNotificationAudio, { once: true });
    document.addEventListener('keydown', enableNotificationAudio, { once: true });


    function setNotificationBadge(count) {
        var notificationLink = document.querySelector('.hm-topbar-notifications[href="<?= base_url('notifications') ?>"]');
        var previousCount = notificationLink
            ? (parseInt(notificationLink.getAttribute('data-unread-count'), 10) || 0)
            : 0;
        var badge = notificationLink ? notificationLink.querySelector('.js-recruiter-notification-badge') : null;
        if (notificationLink && count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'hm-notif-badge js-recruiter-notification-badge';
                notificationLink.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : String(count);
        } else if (badge) {
            badge.remove();
        }
        if (notificationLink) {
            notificationLink.setAttribute('data-unread-count', String(count));
            notificationLink.setAttribute('aria-label', count > 0
                ? 'Notifications (' + count + ' unread)'
                : 'Notifications');
            if (count > previousCount) {
                notificationLink.classList.remove('has-new-notification');
                void notificationLink.offsetWidth;
                notificationLink.classList.add('has-new-notification');
                playNotificationChime();
            }
        }
    }

    function pollMailbox() {
        if (running || document.hidden) {
            return;
        }
        running = true;
        fetch(pollUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (data) {
                if (!data || !data.success) {
                    return;
                }
                if (typeof data.unread_count !== 'undefined') {
                    setNotificationBadge(parseInt(data.unread_count, 10) || 0);
                }
                if ((parseInt(data.imported, 10) || 0) > 0 && window.location.pathname.indexOf('/notifications') !== -1) {
                    window.location.reload();
                }
            })
            .catch(function () {})
            .finally(function () {
                running = false;
            });
    }

    window.setTimeout(pollMailbox, firstDelayMs);
    window.setInterval(pollMailbox, intervalMs);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            pollMailbox();
        }
    });
})();
</script>
<?php foreach ((array) ($pageScripts ?? []) as $pageScript): ?>
    <?php if (is_string($pageScript) && trim($pageScript) !== ''): ?>
        <script src="<?= esc($pageScript, 'attr') ?>"></script>
    <?php endif; ?>
<?php endforeach; ?>

<?= view('recruiter/partials/chatbot_widget') ?>

</div>
</body>
</html>
