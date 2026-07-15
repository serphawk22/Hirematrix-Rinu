(function () {
    function getConfig() {
        var root = document.getElementById('recruiterPipelinePage');
        return root ? root.dataset : {};
    }

    function csrfData(config) {
        var data = {};
        if (config.csrfName && config.csrfHash) {
            data[config.csrfName] = config.csrfHash;
        }
        return data;
    }

    function updateCsrf(config, response) {
        if (response && response.csrf_hash) {
            config.csrfHash = response.csrf_hash;
        }
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function showRecruiterAlert(message, icon, title) {
        if (window.HMAlert && typeof window.HMAlert.fire === 'function') {
            return window.HMAlert.fire({
                icon: icon || undefined,
                title: title || undefined,
                text: message || ''
            });
        }
        alert(message || '');
        return Promise.resolve({ isConfirmed: true });
    }

    function setBulkEmailFeedback(message, type) {
        var $ = window.jQuery;
        if (!$) {
            return;
        }

        var modal = $('#bulkEmailModal');
        if (!modal.length) {
            return;
        }

        var feedback = modal.find('.js-bulk-email-feedback');
        if (!feedback.length) {
            feedback = $('<div class="alert js-bulk-email-feedback mb-3" role="alert"></div>');
            modal.find('.modal-body').prepend(feedback);
        }

        if (!message) {
            feedback.addClass('d-none').removeClass('alert-danger alert-success alert-warning').text('');
            return;
        }

        feedback
            .removeClass('d-none alert-danger alert-success alert-warning')
            .addClass(type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger'))
            .text(message);
    }

    function refreshApplicationsAjax() {
        var $ = window.jQuery;
        var ajaxTarget = $('#applications-ajax-container');
        if (!$ || !ajaxTarget.length) {
            return Promise.resolve(false);
        }

        ajaxTarget.css('opacity', '0.5');
        return $.ajax({
            url: window.location.href,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (response && response.success) {
                ajaxTarget.html(response.html || '').css('opacity', '1');
                $('#candidatePipelineSearch').val('');
                if (typeof window.updateBulkBar === 'function') {
                    window.updateBulkBar();
                }
                return true;
            }
            ajaxTarget.css('opacity', '1');
            return false;
        }, function () {
            ajaxTarget.css('opacity', '1');
            return false;
        });
    }

    function formatCommunicationDate(value) {
        if (!value) {
            return '';
        }
        var date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function communicationLabel(item) {
        var actor = (item.direction === 'incoming' || item.direction === 'inbound')
            ? 'Candidate'
            : ((item.direction === 'outgoing' || item.direction === 'outbound') ? 'Recruiter' : 'Latest');
        var type = item.type ? item.type.charAt(0).toUpperCase() + item.type.slice(1) : 'Message';
        return actor + ' ' + type;
    }

    function revealCandidateContact(button) {
        var url = button.getAttribute('data-contact-url') || '';
        if (!url) {
            return;
        }

        var originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading';

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload || payload.status !== 'success') {
                throw new Error((payload && payload.message) || 'Could not load contact details.');
            }
            var target = button.closest('.response-contact-reveal');
            if (!target) {
                return;
            }
            var phone = payload.phone ? '<span class="response-contact-separator">·</span>' + escapeHtml(payload.phone) : '';
            target.innerHTML = '<span class="response-contact-value">' + escapeHtml(payload.email || 'Not provided') + phone + '</span>';
        }).catch(function (error) {
            button.disabled = false;
            button.innerHTML = originalHtml;
            showRecruiterAlert(error.message || 'Could not load contact details.', 'error', 'Contact unavailable');
        });
    }

    function updateVisibleContact(card, payload) {
        var target = card ? card.querySelector('.response-contact-reveal') : null;
        if (!target) {
            return;
        }
        var parts = [];
        if (payload.email) parts.push(escapeHtml(payload.email));
        if (payload.phone) parts.push(escapeHtml(payload.phone));
        target.innerHTML = '<span class="response-contact-value">' + (parts.join('<span class="response-contact-separator">&middot;</span>') || 'Not provided') + '</span>';
    }

    function openAuthorizedContactAction(button) {
        var mode = button.getAttribute('data-mode') || 'call';
        var url = button.getAttribute('data-contact-url') || '';
        var popup = mode === 'whatsapp' ? window.open('', '_blank') : null;
        var originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authorizing';

        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) { return response.json(); }).then(function (payload) {
            if (!payload || payload.status !== 'success') {
                throw new Error((payload && payload.message) || 'Could not authorize contact access.');
            }
            var phone = String(payload.phone || '').trim();
            if (!phone) {
                throw new Error('This candidate has not provided a phone number.');
            }
            updateVisibleContact(button.closest('.response-card'), payload);
            var cockpit = button.closest('.candidate-cockpit');
            var outcomeForm = cockpit ? cockpit.querySelector('.js-outcome-form') : null;
            if (outcomeForm) {
                outcomeForm.hidden = false;
                outcomeForm.querySelector('[name="channel"]').value = mode;
                var select = outcomeForm.querySelector('[name="outcome"]');
                if (select && mode === 'whatsapp') select.value = 'message_sent';
            }
            if (mode === 'call') {
                window.location.href = 'tel:' + phone.replace(/[^\d+]/g, '');
            } else {
                var digits = phone.replace(/\D/g, '');
                if (digits.length === 10) digits = '91' + digits;
                var message = 'Hello, this is regarding your application for ' + (getConfig().jobTitle || 'our open role') + '.';
                var whatsappUrl = 'https://wa.me/' + digits + '?text=' + encodeURIComponent(message);
                if (popup) popup.location.href = whatsappUrl;
                else window.open(whatsappUrl, '_blank', 'noopener');
            }
        }).catch(function (error) {
            if (popup) popup.close();
            showRecruiterAlert(error.message || 'Contact action could not be started.', 'error', 'Contact unavailable');
        }).finally(function () {
            button.disabled = false;
            button.innerHTML = originalHtml;
        });
    }

    function submitCockpitForm(form) {
        var config = getConfig();
        var data = new FormData(form);
        if (config.csrfName && config.csrfHash) data.set(config.csrfName, config.csrfHash);
        var submit = form.querySelector('[type="submit"]');
        if (submit) submit.disabled = true;
        return fetch(form.getAttribute('action'), {
            method: 'POST', body: data, credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload || payload.status !== 'success') {
                    throw new Error((payload && payload.message) || 'Could not save this update.');
                }
                updateCsrf(config, payload);
                return refreshApplicationsAjax().then(function () {
                    return showRecruiterAlert(payload.message || 'Saved.', 'success', 'Updated');
                });
            });
        }).catch(function (error) {
            showRecruiterAlert(error.message || 'Could not save this update.', 'error', 'Update failed');
        }).finally(function () {
            if (submit) submit.disabled = false;
        });
    }

    function localDateAfter(days) {
        var date = new Date();
        date.setHours(12, 0, 0, 0);
        date.setDate(date.getDate() + days);
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    }

    function openScheduleInterviewModal(button) {
        var modal = document.getElementById('scheduleInterviewModal');
        var form = document.getElementById('scheduleInterviewForm');
        if (!modal || !form) {
            return;
        }

        form.reset();
        form.querySelector('[name="application_id"]').value = button.getAttribute('data-application-id') || '';
        var candidate = button.getAttribute('data-candidate-name') || 'Candidate';
        var email = button.getAttribute('data-candidate-email') || '';
        var subtitle = document.getElementById('scheduleInterviewSubtitle');
        if (subtitle) {
            subtitle.textContent = candidate + (email ? ' - ' + email : '');
        }
        var dateInput = form.querySelector('[name="interview_date"]');
        if (dateInput) {
            dateInput.min = new Date().toISOString().slice(0, 10);
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeScheduleInterviewModal() {
        var modal = document.getElementById('scheduleInterviewModal');
        if (!modal) {
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function submitScheduleInterview(form) {
        var $ = window.jQuery;
        var config = getConfig();
        var applicationId = form.querySelector('[name="application_id"]').value;
        var submit = form.querySelector('[type="submit"]');
        var originalText = submit ? submit.innerHTML : '';
        var payload = Object.assign({
            interview_date: form.querySelector('[name="interview_date"]').value,
            interview_time: form.querySelector('[name="interview_time"]').value,
            duration_minutes: form.querySelector('[name="duration_minutes"]').value,
            interview_mode: form.querySelector('[name="interview_mode"]').value,
            interview_location: form.querySelector('[name="interview_location"]').value,
            message: form.querySelector('[name="message"]').value,
            send_email: form.querySelector('[name="send_email"]').checked ? '1' : '0'
        }, csrfData(config));

        if (!applicationId || !payload.interview_date || !payload.interview_time) {
            alert('Please choose interview date and time.');
            return;
        }

        if (submit) {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scheduling';
        }

        $.ajax({
            url: config.scheduleUrlBase + applicationId,
            type: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            updateCsrf(config, response);
            if (response && response.status === 'success') {
                closeScheduleInterviewModal();
                alert(response.message || 'Interview scheduled.');
                location.reload();
                return;
            }
            alert((response && response.message) || 'Could not schedule interview.');
        }).fail(function (xhr) {
            var messageText = 'Could not schedule interview. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                messageText = xhr.responseJSON.message;
            }
            alert(messageText);
        }).always(function () {
            if (submit) {
                submit.disabled = false;
                submit.innerHTML = originalText;
            }
        });
    }

    function openCommunicationDrawer(payload) {
        var drawer = document.getElementById('communicationDrawer');
        var backdrop = document.getElementById('communicationDrawerBackdrop');
        if (!drawer || !backdrop) {
            return;
        }

        var title = document.getElementById('communicationDrawerTitle');
        var subtitle = document.getElementById('communicationDrawerSubtitle');
        var stats = document.getElementById('communicationDrawerStats');
        var timeline = document.getElementById('communicationDrawerTimeline');
        var items = Array.isArray(payload.items) ? payload.items : [];

        title.textContent = payload.candidateName || 'Recruiter conversations';
        subtitle.textContent = payload.candidateEmail || '';

        stats.innerHTML =
            '<section class="review-section">' +
                '<h4 class="review-section-title">Recruiter Conversations</h4>' +
                '<div class="communication-drawer-stats">' +
                    '<span class="communication-chip"><i class="fas fa-at"></i>' + escapeHtml(payload.emailCount || 0) + ' emails</span>' +
                    '<span class="communication-chip"><i class="fas fa-comments"></i>' + escapeHtml(payload.messageCount || 0) + ' messages</span>' +
                '</div>' +
            '</section>';

        if (!items.length) {
            timeline.innerHTML = '<div class="communication-empty-state">No communication has been recorded for this applicant yet.</div>';
        } else {
            timeline.innerHTML = items.map(function (item) {
                var subject = item.subject || communicationLabel(item);
                var preview = item.preview || '';
                var itemTypeClass = item.type === 'email' ? 'fa-at'
                    : (item.type === 'call' ? 'fa-phone' : (item.type === 'whatsapp' ? 'fa-whatsapp' : 'fa-comments'));
                var itemIconPrefix = item.type === 'whatsapp' ? 'fab' : 'fas';
                return '<article class="communication-timeline-item">' +
                    '<div class="communication-timeline-meta">' +
                        '<span class="communication-chip"><i class="' + itemIconPrefix + ' ' + itemTypeClass + '"></i>' + escapeHtml(communicationLabel(item)) + '</span>' +
                        '<span>' + escapeHtml(formatCommunicationDate(item.at)) + '</span>' +
                    '</div>' +
                    '<div class="communication-timeline-subject">' + escapeHtml(subject) + '</div>' +
                    (preview ? '<div class="communication-timeline-preview">' + escapeHtml(preview) + '</div>' : '') +
                '</article>';
            }).join('');
        }
        timeline.innerHTML = '<section class="review-section"><h4 class="review-section-title">Recent Timeline</h4>' + timeline.innerHTML + '</section>';

        drawer.classList.add('is-open');
        backdrop.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflowX = 'hidden';
        document.body.style.overflowX = 'hidden';
        if (window.pageXOffset > 0) {
            window.scrollTo(0, window.pageYOffset);
        }
    }

    function closeCommunicationDrawer() {
        var drawer = document.getElementById('communicationDrawer');
        var backdrop = document.getElementById('communicationDrawerBackdrop');
        if (!drawer || !backdrop) {
            return;
        }
        drawer.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflowX = '';
        document.body.style.overflowX = '';
    }

    window.togglePipelineCandidates = function (source) {
        var container = source.closest('#candidatePipelineTable') || document.getElementById('candidatePipelineTable');
        var checkboxes = container
            ? container.querySelectorAll('input[name="candidate_ids[]"]')
            : document.querySelectorAll('#candidatePipelineTable input[name="candidate_ids[]"]');

        checkboxes.forEach(function (checkbox) {
            checkbox.checked = source.checked;
        });

        if (typeof window.updateBulkBar === 'function') {
            window.updateBulkBar();
        } else {
            window.updatePipelineSelectAllState();
        }
    };

    window.updatePipelineSelectAllState = function () {
        var checkboxes = document.querySelectorAll('#candidatePipelineTable input[name="candidate_ids[]"]');
        var checkedCount = Array.from(checkboxes).filter(function (checkbox) {
            return checkbox.checked;
        }).length;
        var allChecked = checkboxes.length > 0 && checkedCount === checkboxes.length;
        var partiallyChecked = checkedCount > 0 && checkedCount < checkboxes.length;

        document.querySelectorAll('.select-all').forEach(function (checkbox) {
            checkbox.checked = allChecked;
            checkbox.indeterminate = partiallyChecked;
        });
    };

    window.updateBulkBar = function () {
        var $ = window.jQuery;
        if (!$) {
            return;
        }

        window.updatePipelineSelectAllState();
    };

    window.openBulkMessageModal = function () {
        window.jQuery('#bulkMessageModal').modal('show');
    };

    window.openBulkEmailModal = function () {
        var $ = window.jQuery;
        var emails = [];
        var names = [];

        $('input[name="candidate_ids[]"]:checked').each(function () {
            var email = $(this).data('email');
            if (email) {
                emails.push(email);
                var row = $(this).closest('tr');
                var name = row.find('.candidate-name-cell strong').text() || email;
                names.push(name);
            }
        });

        if (emails.length === 0) {
            alert('Please select at least one candidate with an email address.');
            return;
        }

        var recipientHtml = names.map(function (name, i) {
            return '<div class="mb-1"><i class="fas fa-user text-primary mr-1"></i>' +
                escapeHtml(name) + ' <small class="text-muted">&lt;' + escapeHtml(emails[i]) + '&gt;</small></div>';
        }).join('');

        $('#emailRecipients').html(recipientHtml);
        $('#emailRecipientCount').text(emails.length);
        $('#emailSubject').val('');
        $('#emailBody').val('');
        setBulkEmailFeedback('');
        $('#bulkEmailModal').modal('show');
    };

    window.applyEmailTemplate = function (template) {
        var config = getConfig();
        var jobTitle = config.jobTitle || 'this role';
        var templates = {
            interview: {
                subject: 'Interview Invitation - ' + jobTitle,
                body: 'Dear Candidate,\n\nWe have reviewed your application and would like to invite you for an interview for the position of ' + jobTitle + '.\n\nPlease let us know your preferred dates and times, and we will schedule a suitable slot for you.\n\nBest regards,\nRecruiting Team'
            },
            followup: {
                subject: 'Following Up - ' + jobTitle + ' Application',
                body: 'Dear Candidate,\n\nWe wanted to follow up regarding your application for ' + jobTitle + '. We are still reviewing candidates and will get back to you soon.\n\nThank you for your patience.\n\nBest regards,\nRecruiting Team'
            },
            rejection: {
                subject: 'Update on Your Application - ' + jobTitle,
                body: 'Dear Candidate,\n\nThank you for taking the time to apply for the position of ' + jobTitle + '. After careful consideration, we have decided to move forward with other candidates whose qualifications more closely match our current requirements.\n\nWe appreciate your interest in our organization and encourage you to apply for future openings that match your skills.\n\nBest regards,\nRecruiting Team'
            },
            offer: {
                subject: 'Job Offer - ' + jobTitle,
                body: 'Dear Candidate,\n\nWe are pleased to extend an offer for the position of ' + jobTitle + '. After your interviews, we believe you would be a great fit for our team.\n\nPlease review the attached offer details and let us know if you have any questions. We look forward to welcoming you aboard.\n\nBest regards,\nRecruiting Team'
            }
        };

        if (templates[template]) {
            window.jQuery('#emailSubject').val(templates[template].subject);
            window.jQuery('#emailBody').val(templates[template].body);
        }
    };

    window.sendBulkEmail = function () {
        var $ = window.jQuery;
        var config = getConfig();
        var subject = $('#emailSubject').val().trim();
        var body = $('#emailBody').val().trim();

        if (!subject) {
            setBulkEmailFeedback('Add an email subject before sending.');
            $('#emailSubject').trigger('focus');
            return;
        }

        if (!body) {
            setBulkEmailFeedback('Add the email message before sending.');
            $('#emailBody').trigger('focus');
            return;
        }

        var emails = [];
        $('input[name="candidate_ids[]"]:checked').each(function () {
            var email = $(this).data('email');
            if (email) {
                emails.push(email);
            }
        });

        if (emails.length === 0) {
            setBulkEmailFeedback('Select at least one candidate with an email address.');
            return;
        }

        var btn = $('#pipelineBulkEmailSendButton');
        if (!btn.length) {
            btn = $('#bulkEmailModal button[onclick="sendBulkEmail()"]');
        }
        var originalText = btn.html();
        btn.prop('disabled', true)
            .attr('aria-busy', 'true')
            .html('<i class="fas fa-spinner fa-spin mr-1"></i> Sending...');

        var payload = Object.assign({
            emails: emails,
            subject: subject,
            body: body
        }, csrfData(config));

        $.post(config.emailUrl, payload, function (response) {
            var res = typeof response === 'string' ? JSON.parse(response) : response;
            updateCsrf(config, res);
            if (res.status === 'success') {
                $('#bulkEmailModal').modal('hide');
                setBulkEmailFeedback('');
                alert('Email sent successfully to ' + emails.length + ' candidate(s)!');
                location.reload();
            } else {
                setBulkEmailFeedback(res.message || 'Failed to send email.');
            }
        }).fail(function (xhr) {
            if (xhr.status === 404 || xhr.status === 405) {
                window.location.href = 'mailto:' + emails.join(',') + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
            } else {
                var messageText = 'Failed to send email. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    messageText = xhr.responseJSON.message;
                }
                setBulkEmailFeedback(messageText);
            }
        }).always(function () {
            btn.prop('disabled', false)
                .removeAttr('aria-busy')
                .html(originalText);
        });
    };

    window.executeBulkAction = function (action) {
        var $ = window.jQuery;
        var config = getConfig();
        var selectedIds = [];

        $('input[name="candidate_ids[]"]:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one candidate.');
            return;
        }

        var message = '';
        if (action === 'message') {
            message = $('#bulkMessageText').val().trim();
            if (!message) {
                alert('Please enter a message.');
                return;
            }
            $('#bulkMessageModal').modal('hide');
        } else if (!confirm('Apply ' + action + ' to ' + selectedIds.length + ' candidates?')) {
            return;
        }

        var payload = Object.assign({
            application_ids: selectedIds,
            bulk_action: action,
            bulk_message: message
        }, csrfData(config));

        $.ajax({
            url: config.bulkUrl,
            method: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            updateCsrf(config, response);
            if (response && response.success) {
                alert(response.message || 'Bulk action completed.');
                location.reload();
                return;
            }
            alert((response && response.message) || 'Bulk action failed.');
        }).fail(function (xhr) {
            var messageText = 'Bulk action failed. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                messageText = xhr.responseJSON.message;
            }
            alert(messageText);
        });
    };

    window.updateApplicationStatus = function (applicationId, newStatus) {
        var $ = window.jQuery;
        var config = getConfig();
        var statusLabels = {
            applied: 'Reopened',
            shortlisted: 'Shortlisted',
            hold: 'On Hold',
            on_hold: 'On Hold',
            rejected: 'Rejected'
        };
        if (!confirm('Move candidate to ' + (statusLabels[newStatus] || newStatus) + '?')) {
            return;
        }

        var payload = Object.assign({ status: newStatus }, csrfData(config));
        $.ajax({
            url: config.statusUrlBase + applicationId,
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function (res) {
                updateCsrf(config, res);
                if (res.status === 'success') {
                    closeCommunicationDrawer();
                    refreshApplicationsAjax().then(function (didRefresh) {
                        if (!didRefresh) {
                            location.reload();
                        }
                    });
                }
            }
        });
    };

    function initQuestionnaireBuilder() {
        var addButton = document.getElementById('addQuestionnaireRow');
        var addCoverLetterButton = document.getElementById('addCoverLetterQuestion');
        var questionnaireWrap = document.getElementById('questionnaireBuilder');
        if (!questionnaireWrap || !addButton || !addCoverLetterButton) {
            return;
        }

        var nextIndex = parseInt(questionnaireWrap.dataset.nextIndex || '0', 10) || 0;
        var initialItems = [];
        try {
            initialItems = JSON.parse(questionnaireWrap.dataset.initialItems || '[]');
        } catch (e) {
            initialItems = [];
        }

        function createRow(data) {
            var index = nextIndex++;
            var row = document.createElement('div');
            row.className = 'border rounded p-3 mb-3 questionnaire-row bg-light';
            row.innerHTML =
                '<div class="row">' +
                '<input type="hidden" name="questionnaire[' + index + '][id]" value="' + escapeHtml(data.id || '') + '">' +
                '<div class="col-md-5"><label class="small text-muted font-weight-bold">Question Prompt</label>' +
                '<input type="text" class="form-control form-control-sm" name="questionnaire[' + index + '][label]" maxlength="150" placeholder="e.g. Why are you a fit?" value="' + escapeHtml(data.label || '') + '"></div>' +
                '<div class="col-md-3"><label class="small text-muted font-weight-bold">Field Type</label>' +
                '<select class="form-control form-control-sm" name="questionnaire[' + index + '][type]">' +
                '<option value="textarea"' + (data.type === 'textarea' ? ' selected' : '') + '>Long answer</option>' +
                '<option value="text"' + (data.type === 'text' ? ' selected' : '') + '>Short answer</option>' +
                '</select></div>' +
                '<div class="col-md-3"><label class="small text-muted font-weight-bold">Placeholder</label>' +
                '<input type="text" class="form-control form-control-sm" name="questionnaire[' + index + '][placeholder]" maxlength="200" value="' + escapeHtml(data.placeholder || '') + '"></div>' +
                '<div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger btn-block js-remove-question"><i class="fas fa-trash"></i></button></div>' +
                '<div class="col-12 mt-2"><div class="custom-control custom-checkbox">' +
                '<input type="hidden" name="questionnaire[' + index + '][required]" value="0">' +
                '<input type="checkbox" class="custom-control-input" id="q_req_' + index + '" name="questionnaire[' + index + '][required]" value="1"' + (data.required ? ' checked' : '') + '>' +
                '<label class="custom-control-label small" for="q_req_' + index + '">Required</label>' +
                '</div></div>' +
                '<div class="col-12 mt-2"><div class="custom-control custom-checkbox">' +
                '<input type="hidden" name="questionnaire[' + index + '][knockout]" value="0">' +
                '<input type="checkbox" class="custom-control-input js-knockout-toggle" id="q_ko_' + index + '" name="questionnaire[' + index + '][knockout]" value="1"' + (data.knockout ? ' checked' : '') + '>' +
                '<label class="custom-control-label small" for="q_ko_' + index + '">Knock-out must-have</label>' +
                '</div>' +
                '<div class="row mt-2 js-knockout-fields"' + (data.knockout ? '' : ' style="display: none;"') + '>' +
                '<div class="col-md-7"><label class="small text-muted font-weight-bold">Expected answer</label>' +
                '<input type="text" class="form-control form-control-sm" name="questionnaire[' + index + '][knockout_answer]" value="' + escapeHtml(data.knockout_answer || '') + '"></div>' +
                '<div class="col-md-5"><label class="small text-muted font-weight-bold">Match type</label>' +
                '<select class="form-control form-control-sm" name="questionnaire[' + index + '][knockout_match]">' +
                '<option value="exact"' + ((data.knockout_match || 'exact') === 'exact' ? ' selected' : '') + '>Exact</option>' +
                '<option value="contains"' + (data.knockout_match === 'contains' ? ' selected' : '') + '>Contains</option>' +
                '</select></div>' +
                '</div></div>' +
                '</div>';
            questionnaireWrap.appendChild(row);
        }

        addButton.addEventListener('click', function () {
            createRow({ type: 'textarea', required: false });
        });
        addCoverLetterButton.addEventListener('click', function () {
            createRow({
                label: 'Cover letter / Why are you a fit?',
                type: 'textarea',
                placeholder: 'Share why you are interested in this role and what makes you a strong fit.',
                required: true
            });
        });
        questionnaireWrap.addEventListener('click', function (event) {
            var btn = event.target.closest('.js-remove-question');
            if (btn) {
                btn.closest('.questionnaire-row').remove();
            }
        });
        questionnaireWrap.addEventListener('change', function (event) {
            if (!event.target.classList.contains('js-knockout-toggle')) {
                return;
            }
            var row = event.target.closest('.questionnaire-row');
            var fields = row.querySelector('.js-knockout-fields');
            var required = row.querySelector('[name$="[required]"][type="checkbox"]');
            fields.style.display = event.target.checked ? '' : 'none';
            if (required && event.target.checked) {
                required.checked = true;
            }
        });
        if (initialItems.length > 0) {
            initialItems.forEach(function (item) {
                createRow(item || {});
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        if (!$ || !document.getElementById('recruiterPipelinePage')) {
            return;
        }

        initQuestionnaireBuilder();

        $(document).on('change', '.select-all', function () {
            window.togglePipelineCandidates(this);
        });

        $(document).on('change', 'input[name="candidate_ids[]"]', function () {
            window.updateBulkBar();
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
            var target = $(event.target).attr('href');
            if (target === '#edit-job') {
                $('input[name="candidate_ids[]"]').prop('checked', false);
                $('.select-all').prop('checked', false);
                window.updateBulkBar();
            }
        });

        var hash = window.location.hash;
        if (hash) {
            $('.nav-link[href="' + hash + '"]').tab('show');
        }

        $(document).on('input', '#candidatePipelineSearch', function () {
            var needle = $(this).val().toLowerCase().trim();
            $('#candidatePipelineTable [data-application-row]').each(function () {
                var cardText = $(this).text().toLowerCase();
                $(this).toggle(cardText.indexOf(needle) !== -1);
            });
        });

        $(document).on('click', '.js-open-communication-drawer', function (event) {
            event.stopPropagation();
            var raw = this.getAttribute('data-communication') || '{}';
            var payload = {};
            try {
                payload = JSON.parse(raw);
            } catch (error) {
                payload = {};
            }
            openCommunicationDrawer(payload);
        });

        $(document).on('click', '.js-open-candidate-review', function (event) {
            if (event.target.closest('a, button, input, select, textarea, label, .candidate-cockpit')) {
                return;
            }
            var raw = this.getAttribute('data-review') || '{}';
            var payload = {};
            try {
                payload = JSON.parse(raw);
            } catch (error) {
                payload = {};
            }
            openCommunicationDrawer(payload);
        });

        $(document).on('click', '.js-view-contact', function (event) {
            event.preventDefault();
            event.stopPropagation();
            revealCandidateContact(this);
        });

        $(document).on('click', '.js-contact-action', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openAuthorizedContactAction(this);
        });

        $(document).on('click', '.js-toggle-cockpit-panel', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var cockpit = this.closest('.candidate-cockpit');
            var panelName = this.getAttribute('data-panel');
            if (!cockpit) return;
            cockpit.querySelectorAll('.cockpit-panel').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-panel-name') !== panelName || !panel.hidden;
            });
        });

        $(document).on('click', '.js-quick-tag', function (event) {
            event.preventDefault();
            var form = this.closest('.js-inline-note-form');
            var input = form ? form.querySelector('[name="tags"]') : null;
            if (!input) return;
            var tags = input.value.split(',').map(function (tag) { return tag.trim(); }).filter(Boolean);
            var tag = this.getAttribute('data-tag') || '';
            if (tag && tags.indexOf(tag) === -1) tags.push(tag);
            input.value = tags.join(', ');
            this.classList.add('is-selected');
        });

        $(document).on('click', '.js-followup-preset', function (event) {
            event.preventDefault();
            var form = this.closest('.js-followup-form');
            var input = form ? form.querySelector('[name="follow_up_date"]') : null;
            if (input) input.value = localDateAfter(parseInt(this.getAttribute('data-days'), 10) || 0);
        });

        $(document).on('submit', '.js-inline-note-form, .js-followup-form, .js-outcome-form', function (event) {
            event.preventDefault();
            event.stopPropagation();
            submitCockpitForm(this);
        });

        $(document).on('click', '.js-review-stage-action', function () {
            var applicationId = this.getAttribute('data-application-id');
            var status = this.getAttribute('data-status');
            if (applicationId && status && typeof window.updateApplicationStatus === 'function') {
                window.updateApplicationStatus(applicationId, status);
            }
        });

        $(document).on('click', '.js-open-schedule-interview', function () {
            openScheduleInterviewModal(this);
        });

        $(document).on('click', '.js-schedule-interview-close', closeScheduleInterviewModal);

        $(document).on('submit', '#scheduleInterviewForm', function (event) {
            event.preventDefault();
            submitScheduleInterview(this);
        });

        $(document).on('click', '.js-communication-drawer-close', closeCommunicationDrawer);

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeScheduleInterviewModal();
                closeCommunicationDrawer();
            }
        });

        $(document).on('click', '.stage-ajax-link, .next-action-filter, #applications-ajax-container .pagination a', function (event) {
            var url = $(this).attr('href');
            if (!url || url === '#' || url.startsWith('javascript:')) {
                return;
            }

            event.preventDefault();
            var ajaxTarget = $('#applications-ajax-container');
            ajaxTarget.css('opacity', '0.5');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (response && response.success) {
                        ajaxTarget.html(response.html || '').css('opacity', '1');
                        $('#candidatePipelineSearch').val('');
                        window.updateBulkBar();

                        var urlParams = new URL(url, window.location.origin).searchParams;
                        var currentStage = response.activeStage || urlParams.get('stage') || 'all';
                        var currentNextAction = urlParams.get('next_action') || '';

                        $('.stage-ajax-link').removeClass('active');
                        var activeStageLink = $('.stage-ajax-link').filter(function () {
                            return new URL($(this).attr('href'), window.location.origin).searchParams.get('stage') === currentStage;
                        }).addClass('active').first();

                        if (activeStageLink.length) {
                            var stageLabel = activeStageLink.data('label') || activeStageLink.text().replace(/\s*\(\d+\)\s*$/, '').trim();
                            var stageCount = parseInt(activeStageLink.data('count'), 10);
                            if (Number.isNaN(stageCount)) {
                                stageCount = $('#applications-ajax-container [data-application-row]').length;
                            }
                            $('#responseShowingCount').text('Showing ' + stageCount + ' ' + (stageCount === 1 ? 'response' : 'responses'));
                        }

                        $('.next-action-filter').removeClass('is-active').filter(function () {
                            return ($(this).data('next-action') || '') === currentNextAction;
                        }).addClass('is-active');
                        $('.next-action-filter').each(function () {
                            var filterKey = $(this).data('next-action') || '';
                            var filterUrl = new URL(url, window.location.origin);
                            if (filterKey === currentNextAction) filterUrl.searchParams.delete('next_action');
                            else filterUrl.searchParams.set('next_action', filterKey);
                            filterUrl.searchParams.delete('page');
                            $(this).attr('href', filterUrl.toString());
                        });
                        if (response.nextActionCounts) {
                            Object.keys(response.nextActionCounts).forEach(function (key) {
                                $('[data-next-action-count="' + key + '"]').text(response.nextActionCounts[key]);
                            });
                        }
                        if (currentNextAction) {
                            var visibleCount = $('#applications-ajax-container [data-application-row]').length;
                            $('#responseShowingCount').text('Showing ' + visibleCount + ' next-action ' + (visibleCount === 1 ? 'candidate' : 'candidates'));
                        }

                        window.history.pushState({ path: url }, '', url);
                    } else {
                        ajaxTarget.css('opacity', '1');
                        alert('Could not load this stage. Please try again.');
                    }
                },
                error: function (xhr) {
                    ajaxTarget.css('opacity', '1');
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Could not load this stage asynchronously. Please try again.';
                    alert(message);
                }
            });
        });
    });
})();
