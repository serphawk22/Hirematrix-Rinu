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

    window.togglePipelineCandidates = function (source) {
        var table = source.closest('table') || document.getElementById('candidatePipelineTable');
        var checkboxes = table
            ? table.querySelectorAll('tbody input[name="candidate_ids[]"]')
            : document.querySelectorAll('#candidatePipelineTable tbody input[name="candidate_ids[]"]');

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
        var checkboxes = document.querySelectorAll('#candidatePipelineTable tbody input[name="candidate_ids[]"]');
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

        var checked = $('input[name="candidate_ids[]"]:checked');
        var count = checked.length;
        window.updatePipelineSelectAllState();
        if (count > 0) {
            $('#selectedCount').text(count);
            $('.js-selected-count').text(count);
            $('#bulkActionBar').removeClass('d-none');
        } else {
            $('.js-selected-count').text('0');
            $('#bulkActionBar').addClass('d-none');
        }
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
            alert('Please enter an email subject.');
            return;
        }

        if (!body) {
            alert('Please enter an email message.');
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
            alert('No valid recipients found.');
            return;
        }

        var btn = $('#bulkEmailModal .btn-primary');
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Sending...');

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
                alert('Email sent successfully to ' + emails.length + ' candidate(s)!');
                location.reload();
            } else {
                alert('Error: ' + (res.message || 'Failed to send email'));
            }
        }).fail(function (xhr) {
            if (xhr.status === 404 || xhr.status === 405) {
                window.location.href = 'mailto:' + emails.join(',') + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
            } else {
                alert('Failed to send email. Please try again.');
            }
        }).always(function () {
            btn.prop('disabled', false).html(originalText);
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

    window.executeSelectedBulkAction = function () {
        var $ = window.jQuery;
        var action = $('#pipelineBulkAction').val();
        if (!action) {
            alert('Choose a bulk action first.');
            return;
        }

        if (action === 'email') {
            window.openBulkEmailModal();
            return;
        }

        if (action === 'message') {
            window.openBulkMessageModal();
            return;
        }

        window.executeBulkAction(action);
    };

    window.updateApplicationStatus = function (applicationId, newStatus) {
        var $ = window.jQuery;
        var config = getConfig();
        if (!confirm('Move candidate to ' + newStatus + '?')) {
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
                    location.reload();
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
            $('#candidatePipelineTable tbody tr').each(function () {
                var rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(needle) !== -1);
            });
        });

        $(document).on('click', '.stage-ajax-link, #applications-ajax-container .pagination a', function (event) {
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

                        $('.stage-ajax-link').removeClass('active');
                        $('.stage-ajax-link').filter(function () {
                            return new URL($(this).attr('href'), window.location.origin).searchParams.get('stage') === currentStage;
                        }).addClass('active');

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
