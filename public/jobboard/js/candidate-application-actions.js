(function () {
    function refreshCsrf(tokenName, tokenHash) {
        if (!tokenName || !tokenHash) {
            return;
        }

        document.querySelectorAll('input[name="' + tokenName + '"]').forEach(function (input) {
            input.value = tokenHash;
        });
    }

    function showAlert(targetId, type, message) {
        var target = document.getElementById(targetId);
        if (!target) {
            return;
        }

        target.innerHTML = '<div class="alert alert-' + type + ' recruiter-alert">' + message + '</div>';
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

    function setApplicationStatusBadge(article, status, label, badgeClass) {
        if (!article) {
            return;
        }

        var badge = article.querySelector('.status-badge');
        if (!badge) {
            return;
        }

        badge.className = 'badge status-badge badge-' + (badgeClass || 'secondary');
        badge.textContent = label || status;
    }

    function setSidebarApplicationState(applicationId, label, badgeClass) {
        var item = document.querySelector('[data-application-list-item][data-application-id="' + applicationId + '"]');
        if (!item) {
            return;
        }

        var statusText = item.querySelector('.js-application-status-text');
        if (statusText) {
            statusText.className = 'small mb-2 js-application-status-text text-muted';
            statusText.textContent = 'Status: ' + (label || 'Withdrawn');
        }

        var badge = item.querySelector('.js-application-status-badge');
        if (badge) {
            badge.className = 'badge status-badge badge-' + (badgeClass || 'secondary') + ' js-application-status-badge';
            badge.textContent = label || 'Withdrawn';
        }
    }

    function setApplicationsCounters(deltaActive) {
        var activeCount = document.getElementById('applicationsActiveCount');
        var sidebarActive = document.getElementById('applicationsSidebarActive');

        if (activeCount) {
            var nextActive = Math.max(0, parseInt(activeCount.textContent || '0', 10) + deltaActive);
            activeCount.textContent = String(nextActive);
            if (sidebarActive) {
                sidebarActive.textContent = String(nextActive);
            }
        }
    }

    function setWithdrawnState(article) {
        if (!article) {
            return;
        }

        var applicationId = article.getAttribute('data-application-id') || '';
        setApplicationStatusBadge(article, 'withdrawn', 'Withdrawn', 'secondary');
        setSidebarApplicationState(applicationId, 'Withdrawn', 'secondary');

        var progressBar = article.querySelector('.application-progress-bar');
        if (progressBar) {
            var progressSection = progressBar.closest('.mb-4');
            if (progressSection) {
                progressSection.innerHTML = '<div class="application-section-title">Progress</div><div class="alert alert-secondary mb-0"><i class="fas fa-ban"></i> Application Withdrawn</div>';
            }
        }

        var withdrawForm = article.querySelector('.js-withdraw-application-form');
        if (withdrawForm) {
            withdrawForm.remove();
        }

        setApplicationsCounters(-1);
    }

    function setAppliedState(form, label) {
        if (!form) {
            return;
        }

        var wrapper = form.closest('.detail-card');
        if (!wrapper) {
            return;
        }

        var existingForm = wrapper.querySelector('.js-apply-job-form');
        if (existingForm) {
            existingForm.remove();
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-block btn-outline-primary btn-md job-details-applied-btn';
        button.disabled = true;
        button.setAttribute('data-application-state', 'applied');
        button.innerHTML = '<span class="icon-check mr-2"></span>' + (label || 'Already Applied');

        wrapper.appendChild(button);

        var topAction = document.getElementById('jobDetailsTopAction');
        if (topAction) {
            topAction.innerHTML = '<button class="btn job-details-applied-btn js-job-details-top-applied" disabled><i class="fas fa-check mr-1"></i>' + (label || 'Already Applied') + '</button>';
        }
    }

    function postForm(form) {
        return fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        }).then(function (response) {
            return response.json().then(function (payload) {
                return { response: response, payload: payload };
            });
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (form.classList.contains('js-apply-job-form')) {
            event.preventDefault();

            var button = form.querySelector('button[type="submit"]');
            setBusy(button, true);

            postForm(form)
                .then(function (result) {
                    var payload = result.payload || {};
                    refreshCsrf(payload.csrf_token_name, payload.csrf_hash);

                    if (!result.response.ok || !payload.success) {
                        if (payload.redirect_url) {
                            window.location.href = payload.redirect_url;
                            return;
                        }
                        throw new Error(payload.message || 'Could not submit application.');
                    }

                    if (payload.redirect_url) {
                        window.location.href = payload.redirect_url;
                        return;
                    }

                    showAlert('candidateApplicationAjaxAlert', 'success', payload.message);
                    setAppliedState(form, payload.status_label || 'Already Applied');
                })
                .catch(function (error) {
                    showAlert('candidateApplicationAjaxAlert', 'danger', error.message || 'Could not submit application.');
                })
                .finally(function () {
                    setBusy(button, false);
                });
            return;
        }

        if (form.classList.contains('js-withdraw-application-form')) {
            event.preventDefault();

            var button = form.querySelector('button[type="submit"]');
            setBusy(button, true);

            postForm(form)
                .then(function (result) {
                    var payload = result.payload || {};
                    refreshCsrf(payload.csrf_token_name, payload.csrf_hash);

                    if (!result.response.ok || !payload.success) {
                        throw new Error(payload.message || 'Could not withdraw application.');
                    }

                    var article = form.closest('.application-detail-card');
                    setWithdrawnState(article);
                    showAlert('candidateApplicationsAjaxAlert', 'success', payload.message);
                })
                .catch(function (error) {
                    showAlert('candidateApplicationsAjaxAlert', 'danger', error.message || 'Could not withdraw application.');
                })
                .finally(function () {
                    setBusy(button, false);
                });
        }
    });
})();
