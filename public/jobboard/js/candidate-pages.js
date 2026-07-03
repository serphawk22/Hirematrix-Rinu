(function () {
    function getBaseUrl() {
        var meta = document.querySelector('meta[name="base-url"]');
        return meta ? meta.getAttribute('content').replace(/\/$/, '') : window.location.origin;
    }

    function removeLegacyCandidateDarkTheme() {
        var link = document.getElementById('candidate-dark-theme-css');
        if (link) {
            link.remove();
        }
    }

    function applyCandidateTheme(theme) {
        var isDark = theme === 'dark';
        document.body.classList.toggle('dark', isDark);
        removeLegacyCandidateDarkTheme();

        document.querySelectorAll('input[name="theme-preference"]').forEach(function (input) {
            input.checked = input.value === theme;
        });
    }

    function initCandidateThemeSettings() {
        var themeInputs = document.querySelectorAll('input[name="theme-preference"]');
        var savedTheme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';

        applyCandidateTheme(savedTheme);

        themeInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                if (!input.checked) {
                    return;
                }

                var nextTheme = input.value === 'dark' ? 'dark' : 'light';
                localStorage.setItem('theme', nextTheme);
                applyCandidateTheme(nextTheme);
            });
        });
    }

    function fetchHtml(url) {
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Request failed');
            }
            return response.text();
        });
    }

    function normalizeUrl(url) {
        return new URL(url, window.location.origin).toString();
    }

    function replaceJobsMainFromHtml(html, url) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newJobsMain = doc.querySelector('.jobs-page-jobboard .jobs-main');
        var currentJobsMain = document.querySelector('.jobs-page-jobboard .jobs-main');

        if (!newJobsMain || !currentJobsMain) {
            window.location.href = url;
            return false;
        }

        currentJobsMain.replaceWith(newJobsMain);
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', url);
        }
        return true;
    }

    function replaceFilterFormFromHtml(html, url) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newFilterForm = doc.querySelector('.jobs-page-jobboard #filterForm');
        var currentFilterForm = document.querySelector('.jobs-page-jobboard #filterForm');

        if (!newFilterForm || !currentFilterForm) {
            window.location.href = url;
            return false;
        }

        currentFilterForm.replaceWith(newFilterForm);
        initJobsFilterSelects(newFilterForm);
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', url);
        }
        return true;
    }

    function replaceCompaniesGridFromHtml(html, url) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newCompaniesGrid = doc.querySelector('.companies-directory-grid');
        var currentCompaniesGrid = document.querySelector('.companies-directory-grid');
        var newPagination = doc.querySelector('.companies-directory-page .pagination-wrap');
        var currentPagination = document.querySelector('.companies-directory-page .pagination-wrap');
        var newViewMoreBtn = doc.querySelector('.view-more-companies-btn');
        var currentViewMoreBtn = document.querySelector('.view-more-companies-btn');

        if (!newCompaniesGrid || !currentCompaniesGrid) {
            window.location.href = url; // Fallback to full page reload
            return false;
        }

        currentCompaniesGrid.replaceWith(newCompaniesGrid);

        if (currentPagination && newPagination) {
            currentPagination.replaceWith(newPagination);
        } else if (currentPagination && !newPagination) {
            currentPagination.remove();
        }

        if (currentViewMoreBtn) {
            currentViewMoreBtn.remove(); // Remove the button after loading all companies
        }

        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', url);
        }
        return true;
    }

    function setRecommendationTabState(recType) {
        var tabButtons = document.querySelectorAll('.jobs-page-jobboard .tab-pill');
        tabButtons.forEach(function (button) {
            var buttonRecType = button.getAttribute('data-rec-type') || '';
            var isActive = buttonRecType === recType;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function setJobsLoadingState(isLoading) {
        var jobsMain = document.querySelector('.jobs-page-jobboard .jobs-main');
        if (jobsMain) {
            jobsMain.classList.toggle('is-switching', !!isLoading);
        }
        var filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.classList.toggle('is-switching', !!isLoading);
        }
        var recommendedStage = document.querySelector('.jobs-page-jobboard .recommended-jobs-stage');
        if (recommendedStage) {
            recommendedStage.classList.toggle('is-switching', !!isLoading);
        }
    }

    function closeJobsFilterSelects(except) {
        document.querySelectorAll('.jobs-filter-select.is-open').forEach(function (select) {
            if (select !== except) {
                select.classList.remove('is-open');
                var button = select.querySelector('.jobs-filter-select__button');
                if (button) {
                    button.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }

    function initJobsFilterSelects(scope) {
        var root = scope || document;
        var selects = root.querySelectorAll('.jobs-page-jobboard .sidebar .filter-section select, .sidebar .filter-section select');

        selects.forEach(function (select) {
            if (!select.closest('.jobs-page-jobboard')) {
                return;
            }

            if (select.dataset.customFilterSelect === '1') {
                return;
            }

            select.dataset.customFilterSelect = '1';
            select.classList.add('jobs-native-filter-select');

            var wrapper = document.createElement('div');
            wrapper.className = 'jobs-filter-select';

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'jobs-filter-select__button';
            button.setAttribute('aria-haspopup', 'listbox');
            button.setAttribute('aria-expanded', 'false');

            var valueSpan = document.createElement('span');
            valueSpan.className = 'jobs-filter-select__value';

            var icon = document.createElement('i');
            icon.className = 'fas fa-chevron-down';
            icon.setAttribute('aria-hidden', 'true');

            button.appendChild(valueSpan);
            button.appendChild(icon);

            var list = document.createElement('div');
            list.className = 'jobs-filter-select__list';
            list.setAttribute('role', 'listbox');

            function syncLabel() {
                var selected = select.options[select.selectedIndex];
                valueSpan.textContent = selected ? selected.textContent : '';
            }

            Array.prototype.slice.call(select.options).forEach(function (option) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'jobs-filter-select__option';
                item.setAttribute('role', 'option');
                item.dataset.value = option.value;
                item.textContent = option.textContent;
                item.classList.toggle('is-selected', option.selected);
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');

                item.addEventListener('click', function () {
                    select.value = option.value;
                    syncLabel();
                    list.querySelectorAll('.jobs-filter-select__option').forEach(function (other) {
                        var selected = other === item;
                        other.classList.toggle('is-selected', selected);
                        other.setAttribute('aria-selected', selected ? 'true' : 'false');
                    });
                    closeJobsFilterSelects();
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });

                list.appendChild(item);
            });

            syncLabel();
            select.parentNode.insertBefore(wrapper, select.nextSibling);
            wrapper.appendChild(button);
            wrapper.appendChild(list);

            button.addEventListener('click', function () {
                var willOpen = !wrapper.classList.contains('is-open');
                closeJobsFilterSelects(wrapper);
                wrapper.classList.toggle('is-open', willOpen);
                button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            button.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeJobsFilterSelects();
                    button.focus();
                }
            });
        });
    }

    function showRecommendationPane(recType) {
        var panes = document.querySelectorAll('.jobs-page-jobboard [data-rec-pane]');
        if (!panes.length) {
            return false;
        }

        var targetPane = document.querySelector('.jobs-page-jobboard [data-rec-pane="' + recType + '"]');
        if (!targetPane || targetPane.getAttribute('data-rec-loaded') !== '1') {
            return false;
        }

        panes.forEach(function (pane) {
            var isActive = pane.getAttribute('data-rec-pane') === recType;
            pane.classList.toggle('d-none', !isActive);
        });

        return true;
    }

    function updateSaveButtonState(button, saved) {
        var icon = button.querySelector('.js-save-icon') || button.querySelector('i');
        var label = button.querySelector('.js-save-label');
        var savedLabel = button.getAttribute('data-save-label-saved') || 'Saved';
        var saveLabel = button.getAttribute('data-save-label-save') || 'Save Job';
        var jobId = button.getAttribute('data-job-id') || '';
        var explicitSaveUrl = button.getAttribute('data-save-url-save') || '';
        var explicitUnsaveUrl = button.getAttribute('data-save-url-unsave') || '';
        var nextUrl = '';

        if (explicitSaveUrl || explicitUnsaveUrl) {
            nextUrl = saved ? explicitUnsaveUrl : explicitSaveUrl;
        } else if (jobId) {
            nextUrl = getBaseUrl() + '/job/' + (saved ? 'unsave/' : 'save/') + jobId;
        } else {
            nextUrl = button.getAttribute('data-save-url') || '';
        }

        button.setAttribute('data-saved', saved ? '1' : '0');
        button.setAttribute('aria-label', saved ? 'Saved job' : 'Save job');
        button.setAttribute('title', saved ? 'Saved' : 'Save Job');
        button.classList.toggle('is-saved', !!saved);

        if (icon) {
            icon.className = (saved ? 'fas' : 'far') + ' fa-bookmark' + (label ? ' mr-1' : '');
        }

        if (label) {
            label.textContent = saved ? savedLabel : saveLabel;
        }

        if (nextUrl) {
            var absoluteNextUrl = normalizeUrl(nextUrl);
            button.setAttribute('data-save-url', absoluteNextUrl);
            if (button.tagName === 'A') {
                button.setAttribute('href', absoluteNextUrl);
            }
        }
    }

    function handleSavedJobsRemoval(button) {
        var card = button.closest('.job-card');
        var grid = button.closest('.saved-job-grid');
        if (card) {
            card.remove();
        }
        if (grid && !grid.querySelector('.saved-job-card')) {
            var resultsBar = document.querySelector('.saved-jobs-jobboard .results-bar');
            if (resultsBar) {
                resultsBar.remove();
            }
            grid.outerHTML = '<div class="empty-state"><i class="fas fa-bookmark"></i><h5>No saved jobs yet</h5><p>Save jobs from listings and they will appear here.</p><a href="' + getBaseUrl() + '/jobs" style="display:inline-block;margin-top:12px;background:var(--ink);color:white;padding:10px 24px;border-radius:8px;text-decoration:none;font-family:\'Syne\',sans-serif;font-weight:700;">Browse Jobs</a></div>';
        }
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.jobs-filter-select')) {
            closeJobsFilterSelects();
        }

        var saveButton = event.target.closest('.js-save-job-toggle');
        if (!saveButton) {
            var filterLink = event.target.closest('.jobs-page-jobboard a[data-jobs-filter-link]');
            if (!filterLink) {
                return;
            }

            var filterUrl = normalizeUrl(filterLink.getAttribute('href') || '');
            if (!filterUrl) {
                return;
            }

            event.preventDefault();
            if (filterLink.getAttribute('data-saving') === '1') {
                return;
            }

            filterLink.setAttribute('data-saving', '1');
            setJobsLoadingState(true);
            fetchHtml(filterUrl)
                .then(function (html) {
                    if (!replaceFilterFormFromHtml(html, filterUrl)) {
                        window.location.href = filterUrl;
                    }
                })
                .catch(function () {
                    window.location.href = filterUrl;
                })
                .finally(function () {
                    filterLink.removeAttribute('data-saving');
                    setJobsLoadingState(false);
                });
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (saveButton.getAttribute('data-saving') === '1') {
            return;
        }

        var url = normalizeUrl(saveButton.getAttribute('data-save-url') || '');
        if (!url) {
            return;
        }

        saveButton.setAttribute('data-saving', '1');
        saveButton.disabled = true;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.json();
            })
            .then(function (data) {
                var isSaved = !!(data && data.saved);
                updateSaveButtonState(saveButton, isSaved);

                if (saveButton.closest('.saved-jobs-jobboard') && !isSaved) {
                    handleSavedJobsRemoval(saveButton);
                }
            })
            .catch(function () {
                window.location.href = url;
            })
            .finally(function () {
                saveButton.removeAttribute('data-saving');
                saveButton.disabled = false;
            });
    });

    document.addEventListener('DOMContentLoaded', function () {
        initCandidateThemeSettings();
        initJobsFilterSelects(document);
    });

    window.dismissAllSuggestions = function () {
        fetch(getBaseUrl() + '/career-transition/dismiss-suggestion', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function () {
            window.location.reload();
        });
    };

    window.confirmCareerReset = function (event, targetRole) {
        if (event) {
            event.preventDefault();
        }

        fetch(getBaseUrl() + '/career-transition')
            .then(function (response) { return response.text(); })
            .then(function (html) {
                if (html.includes('Change Career Path')) {
                    var shouldReset = window.confirm(
                        'You have an existing career path. Do you want to reset it and start a new one? Your current progress will be lost.'
                    );
                    if (!shouldReset) {
                        return;
                    }
                    if (targetRole) {
                        window.location.href = getBaseUrl() + '/career-transition?reset=1&target=' + targetRole;
                    } else {
                        window.location.href = getBaseUrl() + '/career-transition?reset=1';
                    }
                } else {
                    if (targetRole) {
                        window.location.href = getBaseUrl() + '/career-transition?target=' + targetRole;
                    } else {
                        window.location.href = getBaseUrl() + '/career-transition';
                    }
                }
            });
    };

    window.editExperience = function (exp) {
        var expId = document.getElementById('exp_id');
        if (!expId) {
            return;
        }
        expId.value = exp.id;
        document.querySelector('[name="job_title"]').value = exp.job_title;
        document.querySelector('[name="company_name"]').value = exp.company_name;
        document.querySelector('[name="employment_type"]').value = exp.employment_type;
        document.querySelector('[name="location"]').value = exp.location || '';
        document.querySelector('[name="start_date"]').value = exp.start_date;
        document.querySelector('[name="end_date"]').value = exp.end_date || '';
        document.getElementById('isCurrent').checked = exp.is_current == 1;
        document.querySelector('[name="description"]').value = exp.description || '';
        document.querySelector('#addExperienceModal .modal-title').textContent = 'Edit Work Experience';
        if (window.jQuery) {
            window.jQuery('#addExperienceModal').modal('show');
        }
    };

    window.editEducation = function (edu) {
        var eduId = document.getElementById('edu_id');
        if (!eduId) {
            return;
        }
        eduId.value = edu.id;
        document.querySelector('#educationForm [name="degree"]').value = edu.degree;
        document.querySelector('#educationForm [name="field_of_study"]').value = edu.field_of_study;
        document.querySelector('#educationForm [name="institution"]').value = edu.institution;
        document.querySelector('#educationForm [name="start_year"]').value = edu.start_year;
        document.querySelector('#educationForm [name="end_year"]').value = edu.end_year;
        document.querySelector('#educationForm [name="grade"]').value = edu.grade || '';
        document.querySelector('#addEducationModal .modal-title').textContent = 'Edit Education';
        if (window.jQuery) {
            window.jQuery('#addEducationModal').modal('show');
        }
    };

    window.editCertification = function (cert) {
        var certId = document.getElementById('cert_id');
        if (!certId) {
            return;
        }
        certId.value = cert.id;
        document.querySelector('#certificationForm [name="certification_name"]').value = cert.certification_name;
        document.querySelector('#certificationForm [name="issuing_organization"]').value = cert.issuing_organization;
        document.querySelector('#certificationForm [name="issue_date"]').value = cert.issue_date;
        document.querySelector('#certificationForm [name="expiry_date"]').value = cert.expiry_date || '';
        document.querySelector('#certificationForm [name="credential_id"]').value = cert.credential_id || '';
        document.querySelector('#certificationForm [name="credential_url"]').value = cert.credential_url || '';
        document.querySelector('#addCertificationModal .modal-title').textContent = 'Edit Certification';
        if (window.jQuery) {
            window.jQuery('#addCertificationModal').modal('show');
        }
    };

    window.editProject = function (project) {
        var form = document.getElementById('projectForm');
        if (!form || !project) {
            return;
        }

        form.querySelector('[name="id"]').value = project.id || '';
        form.querySelector('[name="project_name"]').value = project.project_name || '';
        form.querySelector('[name="role_name"]').value = project.role_name || '';
        form.querySelector('[name="tech_stack"]').value = project.tech_stack || '';
        form.querySelector('[name="project_url"]').value = project.project_url || '';
        form.querySelector('[name="start_date"]').value = project.start_date || '';
        form.querySelector('[name="end_date"]').value = project.end_date || '';
        form.querySelector('[name="project_summary"]').value = project.project_summary || '';
        form.querySelector('[name="impact_metrics"]').value = project.impact_metrics || '';

        if (window.jQuery) {
            window.jQuery('#addProjectModal').modal('show');
        }
    };

    window.quickAddInterest = function (value) {
        var input = document.getElementById('quickInterestValue');
        var form = document.getElementById('quickInterestForm');
        if (!input || !form) {
            return;
        }
        input.value = value;
        form.submit();
    };

    window.previewResume = function () {
        fetch(getBaseUrl() + '/candidate/preview-resume')
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(function (data) {
                if (data.error) {
                    window.alert(data.error);
                } else if (data.url) {
                    window.open(data.url, '_blank');
                } else {
                    window.alert('Error previewing resume');
                }
            })
            .catch(function (error) {
                window.alert('Error previewing resume: ' + error.message);
            });
    };

    window.shareProfile = function () {
        var profileUrl = window.location.href;
        var userNameMeta = document.querySelector('meta[name="user-name"]');
        var userName = userNameMeta ? userNameMeta.getAttribute('content') : 'User';

        if (navigator.share) {
            navigator.share({
                title: 'My Profile - ' + userName,
                text: 'Check out my professional profile',
                url: profileUrl
            });
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(profileUrl)
                .then(function () {
                    window.alert('Profile link copied to clipboard!');
                })
                .catch(function () {
                    var textArea = document.createElement('textarea');
                    textArea.value = profileUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    window.alert('Profile link copied to clipboard!');
                });
            return;
        }

        var fallbackArea = document.createElement('textarea');
        fallbackArea.value = profileUrl;
        document.body.appendChild(fallbackArea);
        fallbackArea.select();
        document.execCommand('copy');
        document.body.removeChild(fallbackArea);
        window.alert('Profile link copied to clipboard!');
    };

    if (window.jQuery) {
        window.jQuery(function () {
            window.jQuery('#addExperienceModal').on('hidden.bs.modal', function () {
                var form = document.getElementById('workExpForm');
                if (!form) {
                    return;
                }
                form.reset();
                document.getElementById('exp_id').value = '';
                document.querySelector('#addExperienceModal .modal-title').textContent = 'Add Work Experience';
            });

            window.jQuery('#addEducationModal').on('hidden.bs.modal', function () {
                var form = document.getElementById('educationForm');
                if (!form) {
                    return;
                }
                form.reset();
                document.getElementById('edu_id').value = '';
                document.querySelector('#addEducationModal .modal-title').textContent = 'Add Education';
            });

            window.jQuery('#addCertificationModal').on('hidden.bs.modal', function () {
                var form = document.getElementById('certificationForm');
                if (!form) {
                    return;
                }
                form.reset();
                document.getElementById('cert_id').value = '';
                document.querySelector('#addCertificationModal .modal-title').textContent = 'Add Certification';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var viewMoreCompaniesBtn = document.querySelector('.view-more-companies-btn');
        if (viewMoreCompaniesBtn) {
            viewMoreCompaniesBtn.addEventListener('click', function (event) {
                event.preventDefault();

                var btn = this;
                var originalText = btn.textContent;
                var loadingText = btn.getAttribute('data-loading-text') || 'Loading...';
                var originalHref = btn.getAttribute('href');

                if (!originalHref) {
                    return;
                }

                // Disable button and show loading state
                btn.disabled = true;
                btn.textContent = loadingText;
                btn.classList.add('is-loading'); // Add a class for styling loading state

                fetchHtml(originalHref)
                    .then(function (html) {
                        if (!replaceCompaniesGridFromHtml(html, originalHref)) {
                            window.location.href = originalHref; // Fallback
                        }
                    })
                    .catch(function (error) {
                        console.error('Error loading more companies:', error);
                        window.location.href = originalHref; // Fallback on error
                    })
                    .finally(function () {
                        // The button is removed by replaceCompaniesGridFromHtml, so no need to re-enable/restore text here.
                    });
            });
        }

        document.querySelectorAll('[data-career-transition-form]').forEach(function (transitionForm) {
            transitionForm.addEventListener('submit', function () {
                var submitBtn = transitionForm.querySelector('.career-transition-submit-btn');
                var btnText = submitBtn ? submitBtn.querySelector('[data-submit-label]') : null;
                var btnLoading = submitBtn ? submitBtn.querySelector('[data-submit-loading]') : null;
                if (btnText) {
                    btnText.style.display = 'none';
                }
                if (btnLoading) {
                    btnLoading.style.display = 'inline-flex';
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('is-loading');
                    submitBtn.setAttribute('aria-busy', 'true');
                }
            });
        });

        var autoRefreshBlock = document.querySelector('[data-auto-refresh="1"]');
        if (autoRefreshBlock) {
            var delay = parseInt(autoRefreshBlock.getAttribute('data-refresh-delay') || '5000', 10);
            window.setTimeout(function () {
                window.location.reload();
            }, delay);
        }

        var offlineStatus = document.getElementById('offlineStatus');
        if (offlineStatus) {
            var updateOfflineStatus = function () {
                if (navigator.onLine) {
                    offlineStatus.textContent = 'Online';
                    offlineStatus.className = 'offline-badge online';
                } else {
                    offlineStatus.textContent = 'Offline';
                    offlineStatus.className = 'offline-badge offline';
                }
            };
            window.addEventListener('online', updateOfflineStatus);
            window.addEventListener('offline', updateOfflineStatus);
            updateOfflineStatus();
        }

        var coursePdfBtn = document.getElementById('coursePdfBtn');
        if (coursePdfBtn) {
            coursePdfBtn.addEventListener('click', function () {
                var downloadUrl = coursePdfBtn.getAttribute('data-download-url');
                var btnText = document.getElementById('coursePdfBtnText');
                var btnLoading = document.getElementById('coursePdfBtnLoading');
                if (btnText) {
                    btnText.style.display = 'none';
                }
                if (btnLoading) {
                    btnLoading.style.display = 'inline';
                }

                if (downloadUrl) {
                    window.location.href = downloadUrl;
                }

                window.setTimeout(function () {
                    if (btnText) {
                        btnText.style.display = 'inline';
                    }
                    if (btnLoading) {
                        btnLoading.style.display = 'none';
                    }
                }, 3000);
            });
        }

        var escapeHtml = function (value) {
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        };

        var renderLessonInlineMarkdown = function (value) {
            return escapeHtml(value)
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                .replace(/__([^_]+)__/g, '<strong>$1</strong>')
                .replace(/\*([^*]+)\*/g, '<em>$1</em>')
                .replace(/_([^_]+)_/g, '<em>$1</em>');
        };

        var renderLessonMarkdown = function (content) {
            var lines = String(content || '').split(/\r?\n/);
            var html = '';
            var inList = false;
            var inOrderedList = false;

            lines.forEach(function (line) {
                var trimmed = line.trim();
                if (!trimmed) {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    if (inOrderedList) {
                        html += '</ol>';
                        inOrderedList = false;
                    }
                    return;
                }

                if (trimmed.indexOf('### ') === 0) {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    if (inOrderedList) {
                        html += '</ol>';
                        inOrderedList = false;
                    }
                    html += '<h4>' + renderLessonInlineMarkdown(trimmed.slice(4)) + '</h4>';
                    return;
                }

                if (trimmed.indexOf('## ') === 0) {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    if (inOrderedList) {
                        html += '</ol>';
                        inOrderedList = false;
                    }
                    html += '<h3>' + renderLessonInlineMarkdown(trimmed.slice(3)) + '</h3>';
                    return;
                }

                if (trimmed.indexOf('- ') === 0) {
                    if (inOrderedList) {
                        html += '</ol>';
                        inOrderedList = false;
                    }
                    if (!inList) {
                        html += '<ul>';
                        inList = true;
                    }
                    html += '<li>' + renderLessonInlineMarkdown(trimmed.slice(2)) + '</li>';
                    return;
                }

                var orderedMatch = trimmed.match(/^\d+[\.)]\s+(.+)$/);
                if (orderedMatch) {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    if (!inOrderedList) {
                        html += '<ol>';
                        inOrderedList = true;
                    }
                    html += '<li>' + renderLessonInlineMarkdown(orderedMatch[1]) + '</li>';
                    return;
                }

                if (
                    trimmed.length <= 90 &&
                    /:$/.test(trimmed) &&
                    !/[.!?]:$/.test(trimmed)
                ) {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    if (inOrderedList) {
                        html += '</ol>';
                        inOrderedList = false;
                    }
                    html += '<h4>' + renderLessonInlineMarkdown(trimmed.replace(/:$/, '')) + '</h4>';
                    return;
                }

                if (inList) {
                    html += '</ul>';
                    inList = false;
                }
                if (inOrderedList) {
                    html += '</ol>';
                    inOrderedList = false;
                }
                html += '<p>' + renderLessonInlineMarkdown(trimmed) + '</p>';
            });

            if (inList) {
                html += '</ul>';
            }
            if (inOrderedList) {
                html += '</ol>';
            }

            return html;
        };

        var renderCourseLesson = function (lesson) {
            var resources = Array.isArray(lesson.resources) ? lesson.resources : [];
            var exercises = Array.isArray(lesson.exercises) ? lesson.exercises : [];
            var lessonContent = String(lesson.content || '').trim();
            var lessonHtml = lessonContent
                ? '<div class="course-lesson-content">' + renderLessonMarkdown(lessonContent) + '</div>'
                : '<div class="alert alert-warning mb-0">The full lesson text is not available yet.</div>';
            var resourceHtml = resources.length
                ? resources.map(function (resource) {
                    var safeResource = escapeHtml(resource);
                    if (/^https?:\/\//i.test(resource)) {
                        var host = resource;
                        try { host = new URL(resource).host; } catch (e) {}
                        return '<a href="' + safeResource + '" target="_blank" rel="noopener" class="course-resource-link"><i class="fas fa-link"></i> ' + escapeHtml(host) + '</a>';
                    }
                    return '<span class="course-resource-link muted">' + safeResource + '</span>';
                }).join('')
                : '<p class="text-muted mb-0">No additional resources for this lesson.</p>';
            var exerciseHtml = exercises.length
                ? exercises.map(function (exercise, index) {
                    return '<div class="course-exercise-item"><strong>Exercise ' + (index + 1) + ':</strong> ' + escapeHtml(exercise) + '</div>';
                }).join('')
                : '<p class="text-muted mb-0">No exercises for this lesson.</p>';

            return lessonHtml +
                '<div class="mt-4"><h6 class="course-section-title"><i class="fas fa-book"></i> Learning Resources</h6><div>' + resourceHtml + '</div></div>' +
                '<div class="mt-4"><h6 class="course-section-title"><i class="fas fa-pen"></i> Practice Exercises</h6>' + exerciseHtml + '</div>';
        };

        var loadCourseLesson = function (button) {
                var lessonId = button.getAttribute('data-lesson-id');
                var card = button.closest('[data-course-lesson-card]');
                var detail = card ? card.querySelector('[data-course-lesson-detail]') : null;
                if (!lessonId || !detail) {
                    return;
                }

                var isOpen = !detail.hasAttribute('hidden');
                if (isOpen) {
                    detail.setAttribute('hidden', 'hidden');
                    button.setAttribute('aria-expanded', 'false');
                    button.innerHTML = '<i class="fas fa-book-open"></i> Open Lesson';
                    return;
                }

                detail.removeAttribute('hidden');
                button.setAttribute('aria-expanded', 'true');
                button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Lesson';

                if (detail.getAttribute('data-loaded') === '1') {
                    return;
                }

                detail.innerHTML = '<div class="course-lesson-loading"><span class="spinner-border spinner-border-sm" role="status"></span> Preparing full lesson...</div>';
                fetch(getBaseUrl() + '/career-transition/lesson/' + lessonId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data || !data.success || !data.lesson) {
                            throw new Error('Lesson unavailable');
                        }
                        detail.innerHTML = renderCourseLesson(data.lesson);
                        detail.setAttribute('data-loaded', '1');
                    })
                    .catch(function () {
                        detail.innerHTML = '<div class="alert alert-warning mb-0">Unable to load this lesson. Please try again.</div>';
                    });
        };

        var bindCourseLessonButtons = function (scope) {
            (scope || document).querySelectorAll('.js-load-course-lesson').forEach(function (button) {
                if (button.getAttribute('data-lesson-bound') === '1') {
                    return;
                }
                button.setAttribute('data-lesson-bound', '1');
                button.addEventListener('click', function () {
                    loadCourseLesson(button);
                });
            });
        };

        var renderCourseGaps = function (gaps) {
            gaps = Array.isArray(gaps) ? gaps : [];
            if (!gaps.length) {
                return '';
            }
            return gaps.map(function (gap) {
                return '<span class="course-module-gap">' + escapeHtml(gap) + '</span>';
            }).join('');
        };

        var renderCourseModuleHeader = function (module, completedLessons, lessonCount) {
            var gaps = Array.isArray(module.covered_skill_gaps) ? module.covered_skill_gaps : [];
            return '<div class="card-body d-flex justify-content-between align-items-start flex-wrap transition-header-row">' +
                '<div>' +
                    '<span class="badge badge-light mb-2">Module ' + escapeHtml(module.module_number || '') + '</span>' +
                    '<h4 class="mb-1">' + escapeHtml(module.title || 'Course Module') + '</h4>' +
                    '<p class="text-muted mb-0">' + escapeHtml(module.description || '') + '</p>' +
                    '<small class="text-muted d-block mt-2"><i class="far fa-clock"></i> ' + escapeHtml(module.duration_weeks || 0) + ' week(s)</small>' +
                    (gaps.length ? '<div class="course-module-gap-row mt-3"><span class="course-module-gap-label">This module covers</span>' + renderCourseGaps(gaps) + '</div>' : '') +
                '</div>' +
                '<div class="course-progress-summary"><strong>' + completedLessons + '/' + lessonCount + '</strong><span>lessons complete</span></div>' +
            '</div>';
        };

        var renderCourseLessonSummary = function (lesson) {
            var gaps = Array.isArray(lesson.covered_skill_gaps) ? lesson.covered_skill_gaps : [];
            return '<div class="course-lesson-card mb-4 ' + (lesson.is_completed ? 'lesson-completed' : '') + '" data-course-lesson-card data-lesson-id="' + escapeHtml(lesson.id) + '">' +
                '<div class="card-body">' +
                    '<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">' +
                        '<div class="d-flex align-items-center">' +
                            '<span class="course-lesson-number">' + escapeHtml(lesson.lesson_number || '') + '</span>' +
                            '<div>' +
                                '<h5 class="mb-1">' + escapeHtml(lesson.title || 'Lesson') + '</h5>' +
                                (gaps.length ? '<div class="course-module-gap-row"><span class="course-module-gap-label">Gaps</span>' + renderCourseGaps(gaps) + '</div>' : '') +
                            '</div>' +
                        '</div>' +
                        '<div class="course-lesson-actions">' +
                            '<button type="button" class="btn btn-sm btn-outline-primary js-load-course-lesson" data-lesson-id="' + escapeHtml(lesson.id) + '" aria-expanded="false"><i class="fas fa-book-open"></i> Open Lesson</button>' +
                            (lesson.is_completed
                                ? '<span class="badge badge-primary"><i class="fas fa-check"></i> Complete</span>'
                                : '<button type="button" class="btn btn-sm btn-primary" onclick="completeLesson(' + escapeHtml(lesson.id) + ')"><i class="fas fa-check"></i> Mark Complete</button>') +
                        '</div>' +
                    '</div>' +
                    '<div class="course-lesson-detail" data-course-lesson-detail hidden>' +
                        '<div class="course-lesson-loading"><span class="spinner-border spinner-border-sm" role="status"></span> Preparing full lesson...</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        };

        var renderCourseModule = function (module, lessons) {
            var content = document.getElementById('courseModuleContent');
            var header = content ? content.querySelector('[data-course-module-header]') : null;
            var lessonsList = content ? content.querySelector('[data-course-lessons-list]') : null;
            if (!content || !header || !lessonsList || !module) {
                return;
            }

            lessons = Array.isArray(lessons) ? lessons : [];
            var completedLessons = lessons.filter(function (lesson) { return !!lesson.is_completed; }).length;
            header.innerHTML = renderCourseModuleHeader(module, completedLessons, lessons.length);
            lessonsList.innerHTML = lessons.length
                ? lessons.map(renderCourseLessonSummary).join('')
                : '<div class="alert alert-warning">No lessons available for this module.</div>';
            content.setAttribute('data-current-module-id', module.id || '');

            var title = document.querySelector('.course-content-jobboard .page-board-title');
            if (title) {
                title.textContent = module.title || 'Course Module';
            }
            bindCourseLessonButtons(lessonsList);
        };

        var setModuleLoading = function (isLoading) {
            var content = document.getElementById('courseModuleContent');
            if (!content) {
                return;
            }
            content.classList.toggle('is-loading', !!isLoading);
            if (isLoading) {
                content.setAttribute('aria-busy', 'true');
            } else {
                content.removeAttribute('aria-busy');
            }
        };

        bindCourseLessonButtons(document);

        document.querySelectorAll('[data-course-module-tab]').forEach(function (tab) {
            tab.addEventListener('click', function (event) {
                var url = tab.getAttribute('href');
                var moduleId = tab.getAttribute('data-module-id');
                if (!url || !moduleId) {
                    return;
                }
                event.preventDefault();

                document.querySelectorAll('[data-course-module-tab]').forEach(function (item) {
                    var active = item === tab;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                setModuleLoading(true);
                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data || !data.success || !data.module) {
                            throw new Error('Module unavailable');
                        }
                        renderCourseModule(data.module, data.lessons || []);
                        if (window.history && window.history.pushState) {
                            window.history.pushState({ courseModuleId: moduleId }, '', url);
                        }
                    })
                    .catch(function () {
                        window.location.href = url;
                    })
                    .finally(function () {
                        setModuleLoading(false);
                    });
            });
        });
    });

    window.completeTask = function (taskId) {
        fetch(getBaseUrl() + '/career-transition/complete/' + taskId, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data && data.success) {
                    window.location.reload();
                } else {
                    window.alert('Failed to mark task as complete. Please try again.');
                }
            })
            .catch(function () {
                window.alert('Failed to mark task as complete. Please try again.');
            });
    };

    window.completeLesson = function (lessonId) {
        fetch(getBaseUrl() + '/career-transition/complete-lesson/' + lessonId, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data && data.success) {
                    window.location.reload();
                } else {
                    window.alert('Failed to mark lesson as complete. Please try again.');
                }
            })
            .catch(function () {
                window.alert('Failed to mark lesson as complete. Please try again.');
            });
    };

    window.switchTab = function (tab, e) {
        if (e) {
            e.preventDefault();
        }

        var url = new URL(getBaseUrl() + '/jobs', window.location.origin);
        var recInput = document.getElementById('recommendationTypeInput');
        var recType = recInput ? recInput.value : 'skills';

        if (tab === 'recommended') {
            url.searchParams.set('tab', 'recommended');
            url.searchParams.set('rec', recType || 'skills');
            setJobsLoadingState(true);
            fetchHtml(url.toString())
                .then(function (html) {
                    if (!replaceJobsMainFromHtml(html, url.toString())) {
                        window.location.href = url.toString();
                    }
                })
                .catch(function () {
                    window.location.href = url.toString();
                })
                .finally(function () {
                    setJobsLoadingState(false);
                });
            return;
        }

        url.searchParams.set('tab', 'all');
        setJobsLoadingState(true);
        fetchHtml(url.toString())
            .then(function (html) {
                if (!replaceJobsMainFromHtml(html, url.toString())) {
                    window.location.href = url.toString();
                }
            })
            .catch(function () {
                window.location.href = url.toString();
            })
            .finally(function () {
                setJobsLoadingState(false);
            });
    };

    window.switchRecommendation = function (recType, e) {
        if (e) {
            e.preventDefault();
        }

        setRecommendationTabState(recType);
        if (showRecommendationPane(recType)) {
            var instantUrl = new URL(getBaseUrl() + '/jobs', window.location.origin);
            instantUrl.searchParams.set('tab', 'recommended');
            instantUrl.searchParams.set('rec', recType);
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', instantUrl.toString());
            }
            setJobsLoadingState(false);
            return;
        }

        var url = new URL(getBaseUrl() + '/jobs', window.location.origin);
        url.searchParams.set('tab', 'recommended');
        url.searchParams.set('rec', recType);
        setJobsLoadingState(true);
        fetchHtml(url.toString())
            .then(function (html) {
                if (!replaceJobsMainFromHtml(html, url.toString())) {
                    window.location.href = url.toString();
                }
            })
            .catch(function () {
                window.location.href = url.toString();
            })
            .finally(function () {
                setJobsLoadingState(false);
            });
    };

    window.submitFilters = function () {
        var activeTabInput = document.getElementById('activeTabInput');
        var filterForm = document.getElementById('filterForm');
        if (!activeTabInput || !filterForm) {
            return;
        }

        activeTabInput.value = 'all';
        var url = new URL(filterForm.getAttribute('action') || (getBaseUrl() + '/jobs'), window.location.origin);
        var formData = new FormData(filterForm);

        url.search = '';
        formData.forEach(function (value, key) {
            if (value === null || value === '') {
                return;
            }
            url.searchParams.append(key, value);
        });
        url.searchParams.set('tab', 'all');

        setJobsLoadingState(true);
        fetchHtml(url.toString())
            .then(function (html) {
                if (!replaceFilterFormFromHtml(html, url.toString())) {
                    window.location.href = url.toString();
                }
            })
            .catch(function () {
                window.location.href = url.toString();
            })
            .finally(function () {
                setJobsLoadingState(false);
            });
    };

    window.toggleMobileFilters = function () {
        var drawer = document.getElementById('mobileFilterDrawer');
        var icon = document.getElementById('mobileFilterIcon');
        if (!drawer || !icon) {
            return;
        }

        var isOpen = drawer.classList.contains('open');
        drawer.classList.toggle('open', !isOpen);
        icon.className = isOpen ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
    };

    window.applyMobileFilters = function () {
        var mobileCategory = document.getElementById('mobileCategory');
        var mobileLocation = document.getElementById('mobileLocation');
        var mobileExperience = document.getElementById('mobileExperience');
        var mobileEmploymentType = document.getElementById('mobileEmploymentType');
        var mobileWorkMode = document.getElementById('mobileWorkMode');
        var mobileSalaryRange = document.getElementById('mobileSalaryRange');

        var desktopCategory = document.querySelector('.sidebar select[name="category"]');
        var desktopLocation = document.querySelector('.sidebar select[name="location"]');
        var desktopWorkMode = document.querySelector('.sidebar select[name="work_mode"]');
        var desktopSalaryRange = document.querySelector('.sidebar select[name="salary_range"]');

        if (desktopCategory && mobileCategory) {
            desktopCategory.value = mobileCategory.value;
        }

        if (desktopLocation && mobileLocation) {
            desktopLocation.value = mobileLocation.value;
        }

        if (desktopWorkMode && mobileWorkMode) {
            desktopWorkMode.value = mobileWorkMode.value;
        }

        if (desktopSalaryRange && mobileSalaryRange) {
            desktopSalaryRange.value = mobileSalaryRange.value;
        }

        var expChecks = document.querySelectorAll('.sidebar input[name="experience_level[]"]');
        expChecks.forEach(function (cb) {
            cb.checked = !!(mobileExperience && mobileExperience.value && cb.value === mobileExperience.value);
        });

        var typeChecks = document.querySelectorAll('.sidebar input[name="employment_type[]"]');
        typeChecks.forEach(function (cb) {
            cb.checked = !!(mobileEmploymentType && mobileEmploymentType.value && cb.value === mobileEmploymentType.value);
        });

        var activeTabInput = document.getElementById('activeTabInput');
        if (activeTabInput) {
            activeTabInput.value = 'all';
        }

        window.submitFilters();
    };

    document.addEventListener('DOMContentLoaded', function () {
        var settingsNav = document.getElementById('settingsNav');
        if (settingsNav) {
            var navLinks = settingsNav.querySelectorAll('[data-settings-tab]');
            var panels = document.querySelectorAll('[data-settings-panel]');

            var activateTab = function (tabName) {
                navLinks.forEach(function (link) {
                    link.classList.toggle('is-active', link.getAttribute('data-settings-tab') === tabName);
                });

                panels.forEach(function (panel) {
                    panel.classList.toggle('is-active', panel.getAttribute('data-settings-panel') === tabName);
                });
            };

            navLinks.forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    var tabName = link.getAttribute('data-settings-tab') || 'visibility';
                    activateTab(tabName);
                    if (window.history && window.history.replaceState) {
                        var url = new URL(window.location.href);
                        url.searchParams.set('tab', tabName);
                        window.history.replaceState({}, '', url.toString());
                    }
                });
            });
        }

        var notificationForm = document.getElementById('notificationSettingsForm');
        if (notificationForm) {
            var toggleInputs = notificationForm.querySelectorAll('input[type="checkbox"]');
            toggleInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    notificationForm.submit();
                });
            });
        }

        var visibilityForm = document.getElementById('visibilitySettingsForm');
        if (visibilityForm) {
            var visibilityToggle = visibilityForm.querySelector('input[type="checkbox"]');
            if (visibilityToggle) {
                visibilityToggle.addEventListener('change', function () {
                    visibilityForm.submit();
                });
            }
        }

        var loadingForms = document.querySelectorAll('form[data-loading-form]');
        var generationModeInputs = document.querySelectorAll('input[name="generation_mode"]');
        var generationPanels = document.querySelectorAll('[data-generation-panel]');
        var targetRoleInput = document.querySelector('input[name="target_role"]');
        var jobSelectInput = document.querySelector('select[name="job_id"]');

        if (generationModeInputs.length || generationPanels.length) {
            var syncGenerationMode = function (mode) {
                generationPanels.forEach(function (panel) {
                    panel.classList.toggle('is-active', panel.getAttribute('data-generation-panel') === mode);
                });

                if (targetRoleInput) {
                    if (mode === 'role') {
                        targetRoleInput.disabled = false;
                    } else {
                        targetRoleInput.value = '';
                        targetRoleInput.disabled = true;
                    }
                }

                if (jobSelectInput) {
                    if (mode === 'job') {
                        jobSelectInput.disabled = false;
                    } else {
                        jobSelectInput.value = '';
                        jobSelectInput.disabled = true;
                    }
                }
            };

            generationModeInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    syncGenerationMode(input.value);
                });
            });

            syncGenerationMode((document.querySelector('input[name="generation_mode"]:checked') || { value: 'role' }).value);
        }

        loadingForms.forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('[data-loading-button]');
                if (!button || button.disabled) {
                    return;
                }

                button.disabled = true;
                button.classList.add('is-loading');

                var submitText = button.querySelector('.btn-submit-text');
                var loadingState = button.querySelector('.btn-loading-state');
                if (submitText) {
                    submitText.classList.add('is-hidden');
                }
                if (loadingState) {
                    loadingState.style.display = 'inline-flex';
                }
            });
        });

        var applicationButtons = document.querySelectorAll('.application-list-item');
        var applicationCards = document.querySelectorAll('.application-detail-card');
        if (applicationButtons.length && applicationCards.length) {
            applicationButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var targetId = button.getAttribute('data-application-target');

                    applicationButtons.forEach(function (item) {
                        item.classList.remove('is-active');
                    });

                    applicationCards.forEach(function (card) {
                        card.classList.remove('is-active');
                    });

                    button.classList.add('is-active');

                    var activeCard = document.getElementById(targetId);
                    if (activeCard) {
                        activeCard.classList.add('is-active');
                    }
                });
            });
        }

        var fresherCheckbox = document.getElementById('is_fresher_candidate');
        var experienceFields = document.getElementById('experienceFields');
        var educationList = document.getElementById('educationList');
        var addEducationItem = document.getElementById('addEducationItem');
        var addExperienceItem = document.getElementById('addExperienceItem');
        var onboardingForms = document.querySelectorAll('[data-onboarding-form]');

        if (onboardingForms.length) {
            var getFieldLabel = function (field) {
                var wrap = field.closest('.mb-3') || field.closest('.onboarding-card') || field.parentElement;
                var label = wrap ? wrap.querySelector('label') : null;
                return label ? String(label.textContent || '').replace(/\s+/g, ' ').trim() : 'This field';
            };

            var fieldHasValue = function (field) {
                if (!field || field.disabled) {
                    return true;
                }

                if (field.type === 'file') {
                    return field.files && field.files.length > 0;
                }

                if (field.type === 'checkbox' || field.type === 'radio') {
                    return field.checked;
                }

                return String(field.value || '').trim() !== '';
            };

            var getFieldWarning = function (field, forceRequired) {
                if (!field || field.disabled) {
                    return '';
                }

                var label = getFieldLabel(field);
                var hasValue = fieldHasValue(field);
                var isRequired = forceRequired || field.required;

                if (field.type === 'file') {
                    var resumeForm = field.closest('form[data-existing-resume="1"]');
                    if (resumeForm && !hasValue) {
                        return '';
                    }
                    if (isRequired && !hasValue) {
                        return label + ' is required. Upload a PDF, DOC, or DOCX file.';
                    }
                    return '';
                }

                if (isRequired && !hasValue) {
                    if (field.tagName === 'SELECT') {
                        return 'Please select ' + label.toLowerCase() + '.';
                    }
                    return label + ' is required.';
                }

                if (hasValue && field.getAttribute('minlength')) {
                    var minLength = parseInt(field.getAttribute('minlength'), 10);
                    if (String(field.value || '').trim().length < minLength) {
                        return label + ' must be at least ' + minLength + ' characters.';
                    }
                }

                if (hasValue && field.type === 'email' && field.validity && field.validity.typeMismatch) {
                    return 'Enter a valid email address.';
                }

                if (hasValue && field.type === 'number' && field.validity && (field.validity.rangeUnderflow || field.validity.rangeOverflow || field.validity.badInput)) {
                    return 'Enter a valid number for ' + label.toLowerCase() + '.';
                }

                return '';
            };

            var ensureFieldHint = function (field) {
                var wrap = field.closest('.mb-3') || field.closest('.onboarding-card') || field.parentElement;
                if (!wrap) {
                    return null;
                }

                var hint = field.nextElementSibling;
                if (!hint || !hint.classList || !hint.classList.contains('onboarding-field-warning')) {
                    hint = document.createElement('small');
                    hint.className = 'onboarding-field-warning';
                    hint.setAttribute('aria-live', 'polite');
                    field.insertAdjacentElement('afterend', hint);
                }

                return hint;
            };

            var setFieldWarning = function (field, message) {
                var hint = ensureFieldHint(field);
                if (!hint) {
                    return;
                }

                hint.textContent = message;
                hint.classList.toggle('is-visible', !!message);
                field.classList.toggle('is-onboarding-invalid', !!message);
            };

            var updateFormWarnings = function (form) {
                var warnings = [];
                var requiredFields = Array.prototype.slice.call(form.querySelectorAll('[required]'));

                requiredFields.forEach(function (field) {
                    var warning = getFieldWarning(field, false);
                    setFieldWarning(field, warning);
                    if (warning) {
                        warnings.push(warning);
                    }
                });

                if (form.action.indexOf('/candidate/onboarding/experience') !== -1) {
                    var experienceFieldsToClear = Array.prototype.slice.call(form.querySelectorAll('.experience-item [name="job_title[]"], .experience-item input[name="company_name[]"], .experience-item input[name="start_date[]"]'));
                    experienceFieldsToClear.forEach(function (field) {
                        setFieldWarning(field, '');
                    });

                    if (fresherCheckbox && !fresherCheckbox.checked) {
                        var experienceItems = Array.prototype.slice.call(form.querySelectorAll('.experience-item'));
                        experienceItems.forEach(function (item) {
                            ['[name="job_title[]"]', 'input[name="company_name[]"]', 'input[name="start_date[]"]'].forEach(function (selector) {
                                var field = item.querySelector(selector);
                                var warning = getFieldWarning(field, true);
                                setFieldWarning(field, warning);
                                if (warning) {
                                    warnings.push(warning);
                                }
                            });
                        });
                    }
                }

                return warnings;
            };

            var syncOnboardingSubmit = function (form) {
                var submit = form.querySelector('[data-onboarding-submit]');
                if (!submit) {
                    return;
                }

                var requiredFields = Array.prototype.slice.call(form.querySelectorAll('[required]'));
                var allValid = requiredFields.every(function (field) {
                    if (field.type === 'file' && form.getAttribute('data-existing-resume') === '1' && !fieldHasValue(field)) {
                        return true;
                    }
                    return fieldHasValue(field) && (!field.checkValidity || field.checkValidity());
                });

                if (form.action.indexOf('/candidate/onboarding/experience') !== -1) {
                    if (fresherCheckbox && fresherCheckbox.checked) {
                        allValid = true;
                    } else {
                        var experienceItems = Array.prototype.slice.call(form.querySelectorAll('.experience-item'));
                        allValid = experienceItems.length > 0 && experienceItems.every(function (item) {
                            var jobTitle = item.querySelector('[name="job_title[]"]');
                            var companyName = item.querySelector('input[name="company_name[]"]');
                            var startDate = item.querySelector('input[name="start_date[]"]');
                            if (!jobTitle || jobTitle.disabled) {
                                return true;
                            }
                            return [jobTitle, companyName, startDate].every(function (field) {
                                return fieldHasValue(field) && (!field.checkValidity || field.checkValidity());
                            });
                        });
                    }
                }

                updateFormWarnings(form);
                submit.disabled = !allValid;
            };

            onboardingForms.forEach(function (form) {
                form.addEventListener('input', function () {
                    syncOnboardingSubmit(form);
                });
                form.addEventListener('change', function () {
                    syncOnboardingSubmit(form);
                });
                syncOnboardingSubmit(form);
            });

            if (fresherCheckbox && experienceFields) {
                var syncExperienceFields = function () {
                    var disabled = fresherCheckbox.checked;
                    experienceFields.querySelectorAll('input, select, textarea').forEach(function (field) {
                        if (field.id === 'is_current') {
                            return;
                        }
                        field.disabled = disabled;
                    });

                    onboardingForms.forEach(function (form) {
                        syncOnboardingSubmit(form);
                    });
                };

                fresherCheckbox.addEventListener('change', syncExperienceFields);
                syncExperienceFields();
            }

            document.addEventListener('click', function (event) {
                if (!event.target.matches('[data-remove-item]')) {
                    return;
                }

                event.preventDefault();
                var item = event.target.closest('.repeatable-item');
                if (!item) {
                    return;
                }
                var container = item.parentElement;
                if (container && container.children.length > 1) {
                    item.remove();
                    onboardingForms.forEach(function (form) {
                        syncOnboardingSubmit(form);
                    });
                }
            });

            if (addEducationItem && educationList && addEducationItem.getAttribute('data-inline-repeatable') !== '1') {
                addEducationItem.addEventListener('click', function () {
                    var itemCount = educationList.querySelectorAll('.education-item').length + 1;
                    var wrapper = document.createElement('div');
                    wrapper.className = 'repeatable-item education-item';
                    wrapper.innerHTML = '<div class="repeatable-item-title">Education ' + itemCount + '</div>'
                        + '<button type="button" class="btn btn-sm btn-outline-danger repeatable-remove" data-remove-item>Remove</button>'
                        + '<div class="row">'
                        + '<div class="col-md-6 mb-3"><label>Degree</label><input type="text" name="degree[]" class="form-control" minlength="2" required></div>'
                        + '<div class="col-md-6 mb-3"><label>Field of Study</label><input type="text" name="field_of_study[]" class="form-control" minlength="2" required></div>'
                        + '<div class="col-md-6 mb-3"><label>Institution</label><input type="text" name="institution[]" class="form-control" minlength="2" required></div>'
                        + '<div class="col-md-3 mb-3"><label>Start Year</label><input type="number" name="start_year[]" class="form-control" required></div>'
                        + '<div class="col-md-3 mb-3"><label>End Year</label><input type="number" name="end_year[]" class="form-control" required></div>'
                        + '<div class="col-md-6 mb-3"><label>Grade / CGPA</label><input type="text" name="grade[]" class="form-control"></div>'
                        + '</div>';
                    educationList.appendChild(wrapper);
                    onboardingForms.forEach(function (form) {
                        syncOnboardingSubmit(form);
                    });
                });
            }

            if (addExperienceItem && experienceFields && addExperienceItem.getAttribute('data-inline-repeatable') !== '1') {
                addExperienceItem.addEventListener('click', function () {
                    var index = experienceFields.querySelectorAll('.experience-item').length;
                    var itemCount = index + 1;
                    var wrapper = document.createElement('div');
                    wrapper.className = 'repeatable-item experience-item';
                    wrapper.innerHTML = '<div class="repeatable-item-title">Experience ' + itemCount + '</div>'
                        + '<button type="button" class="btn btn-sm btn-outline-danger repeatable-remove" data-remove-item>Remove</button>'
                        + '<div class="row">'
                        + '<div class="col-md-6 mb-3"><label>Job Title</label><input type="text" name="job_title[]" class="form-control" minlength="2"></div>'
                        + '<div class="col-md-6 mb-3"><label>Company Name</label><input type="text" name="company_name[]" class="form-control" minlength="2"></div>'
                        + '<div class="col-md-6 mb-3"><label>Employment Type</label><select name="employment_type[]" class="form-control"><option>Full-time</option><option>Part-time</option><option>Contract</option><option>Internship</option><option>Freelance</option></select></div>'
                        + '<div class="col-md-6 mb-3"><label>Location</label><input type="text" name="location[]" class="form-control"></div>'
                        + '<div class="col-md-6 mb-3"><label>Start Date</label><input type="date" name="start_date[]" class="form-control"></div>'
                        + '<div class="col-md-6 mb-3"><label>End Date</label><input type="date" name="end_date[]" class="form-control"></div>'
                        + '<div class="col-12 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="is_current_' + index + '" name="is_current[' + index + ']" value="1"><label class="custom-control-label" for="is_current_' + index + '">I currently work here</label></div></div>'
                        + '<div class="col-12 mb-3"><label>Work Summary</label><textarea name="description[]" class="form-control" rows="4"></textarea></div>'
                        + '</div>';
                    experienceFields.appendChild(wrapper);
                    if (fresherCheckbox) {
                        var disabled = fresherCheckbox.checked;
                        experienceFields.querySelectorAll('input, select, textarea').forEach(function (field) {
                            if (field.id === 'is_current') {
                                return;
                            }
                            field.disabled = disabled;
                        });
                    }
                    onboardingForms.forEach(function (form) {
                        syncOnboardingSubmit(form);
                    });
                });
            }
        }

        var profileSections = document.querySelectorAll('.profile-section');
        if (profileSections.length) {
            profileSections.forEach(function (section) {
                var toggle = section.querySelector('[data-edit-toggle]');
                var cancel = section.querySelector('[data-edit-cancel]');

                if (toggle) {
                    toggle.addEventListener('click', function () {
                        section.classList.add('is-editing');
                    });
                }

                if (cancel) {
                    cancel.addEventListener('click', function () {
                        section.classList.remove('is-editing');
                    });
                }
            });

            var profileLoadingForms = document.querySelectorAll('form[data-loading-form]');
            profileLoadingForms.forEach(function (form) {
                form.addEventListener('submit', function () {
                    var button = form.querySelector('[data-loading-button]');
                    if (!button || button.disabled) {
                        return;
                    }

                    button.disabled = true;
                    button.classList.add('is-loading');

                    var submitText = button.querySelector('.btn-submit-text');
                    var loadingState = button.querySelector('.btn-loading-state');

                    if (submitText) {
                        submitText.classList.add('is-hidden');
                    }

                    if (loadingState) {
                        loadingState.style.display = 'inline-flex';
                    }
                });
            });
        }
    });
})();
