(function () {
    function getBaseUrl() {
        const meta = document.querySelector('meta[name="base-url"]');
        return meta ? meta.content.replace(/\/$/, '') : '';
    }

    function updateCsrf(tokenName, tokenHash) {
        if (!tokenName || !tokenHash) {
            return;
        }

        document.querySelectorAll('input[name="' + tokenName + '"]').forEach(function (input) {
            input.value = tokenHash;
        });

    }

    function showAlert(message, type, targetId) {
        const target = targetId ? document.getElementById(targetId) : null;
        if (!target) {
            return;
        }

        target.innerHTML = '<div class="alert alert-' + type + ' recruiter-alert">' + message + '</div>';
    }

    function setHeaderBadge(count) {
        const badges = document.querySelectorAll('.js-notification-badge');
        badges.forEach(function (badge) {
            const link = badge.closest('.header-notification-link, .mobile-nav-icon, .recruiter-notification-link, .recruiter-mobile-notification-link');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.dataset.unreadCount = String(count);
                badge.style.display = '';
                if (link) {
                    link.classList.add('has-unread');
                }
            } else {
                if (link) {
                    link.classList.remove('has-unread');
                }
                badge.remove();
            }
        });
    }

    function setRecruiterUnreadCount(count) {
        const unreadCount = document.getElementById('recruiterUnreadCount');
        if (unreadCount) {
            unreadCount.textContent = String(count);
        }
    }

    function markCardRead(card) {
        if (!card) {
            return;
        }

        card.classList.remove('is-unread');
        const newBadge = card.querySelector('.badge.badge-primary');
        if (newBadge && newBadge.textContent.trim() === 'New') {
            newBadge.remove();
        }

        const markReadLink = card.querySelector('.js-mark-notification-read');
        if (markReadLink) {
            markReadLink.remove();
        }
    }

    function markAllCardsRead() {
        document.querySelectorAll('[data-notification-card]').forEach(function (card) {
            markCardRead(card);
        });
    }

    function removeNotificationCard(card) {
        if (!card) {
            return;
        }

        card.remove();
    }

    function setBusy(button, busy) {
        if (!button) {
            return;
        }

        if (busy) {
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Saving';
            return;
        }

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
        button.disabled = false;
    }

    async function postNotificationAction(url) {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const payload = await response.json();
        updateCsrf(payload.csrf_token_name, payload.csrf_hash);

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Could not update notification.');
        }

        return payload;
    }

    document.addEventListener('click', function (event) {
        const markReadLink = event.target.closest('.js-mark-notification-read');
        if (markReadLink) {
            event.preventDefault();

            const alertTarget = document.getElementById('candidateNotificationAjaxAlert') ? 'candidateNotificationAjaxAlert' : 'recruiterNotificationAjaxAlert';
            const card = markReadLink.closest('[data-notification-card]');
            const button = markReadLink;

            setBusy(button, true);
            postNotificationAction(markReadLink.href)
                .then(function (payload) {
                    markCardRead(card);
                    if (typeof payload.unread_count === 'number') {
                        setHeaderBadge(payload.unread_count);
                        setRecruiterUnreadCount(payload.unread_count);
                    }
                    showAlert(payload.message, 'success', alertTarget);
                })
                .catch(function (error) {
                    showAlert(error.message || 'Could not update notification.', 'danger', alertTarget);
                })
                .finally(function () {
                    setBusy(button, false);
                });
            return;
        }

        const markAllLink = event.target.closest('.js-mark-all-notifications-read');
        if (markAllLink) {
            event.preventDefault();

            const alertTarget = document.getElementById('candidateNotificationAjaxAlert') ? 'candidateNotificationAjaxAlert' : 'recruiterNotificationAjaxAlert';
            const button = markAllLink;

            setBusy(button, true);
            postNotificationAction(markAllLink.href)
                .then(function (payload) {
                    markAllCardsRead();
                    setHeaderBadge(0);
                    setRecruiterUnreadCount(0);
                    showAlert(payload.message, 'success', alertTarget);
                    button.remove();
                })
                .catch(function (error) {
                    showAlert(error.message || 'Could not update notifications.', 'danger', alertTarget);
                })
                .finally(function () {
                    setBusy(button, false);
                });
            return;
        }

        const deleteLink = event.target.closest('.js-delete-notification');
        if (deleteLink) {
            event.preventDefault();

            const confirmMessage = deleteLink.dataset.confirmMessage || 'Delete this notification?';
            if (!window.confirm(confirmMessage)) {
                return;
            }

            const alertTarget = document.getElementById('candidateNotificationAjaxAlert') ? 'candidateNotificationAjaxAlert' : 'recruiterNotificationAjaxAlert';
            const card = deleteLink.closest('[data-notification-card]');
            const button = deleteLink;

            setBusy(button, true);
            postNotificationAction(deleteLink.href)
                .then(function (payload) {
                    removeNotificationCard(card);
                    if (typeof payload.unread_count === 'number') {
                        setHeaderBadge(payload.unread_count);
                        setRecruiterUnreadCount(payload.unread_count);
                    }
                    showAlert(payload.message, 'success', alertTarget);
                })
                .catch(function (error) {
                    showAlert(error.message || 'Could not delete notification.', 'danger', alertTarget);
                })
                .finally(function () {
                    setBusy(button, false);
                });
        }
    });
})();
