<?= view('Layouts/candidate_header', ['title' => 'Company & Job Discovery']) ?>

<div class="companies-directory-jobboard">
    <div class="container">
        <div class="page-board-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-search"></i> AI Discovery</span>
                <h1 class="page-board-title">Company & Job Discovery</h1>
                <p class="page-board-subtitle">Search for live job listings from top multinational companies or browse our comprehensive company directory</p>
            </div>
        </div>
    </div>

    <section class="site-section pt-0">
        <div class="container">
            <!-- Search Panel -->
            <div class="companies-filter-card card mb-4">
                <div class="card-body">
                    <form id="unifiedDiscoverySearchForm" method="get" action="<?= base_url('candidate/company-job-discovery') ?>">
                        <div class="row align-items-end">
                            <div class="col-12 col-md-10">
                                <label for="mncCompanySearchInput" class="form-label">Search Company</label>
                                <div class="company-autocomplete position-relative">
                                    <input type="text" id="mncCompanySearchInput" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search company, e.g. HubSpot, Google, Amazon..." autocomplete="off">
                                    <div class="company-autocomplete-dropdown" id="companyAutocompleteDropdown"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-2">
                                <button class="btn btn-primary w-100" type="submit" id="searchMainBtn">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MNC Discovery Results (Dynamic) -->
            <div id="mncDiscoverySection">
                <div id="mncLoadingSpinner" class="text-center my-5 d-none">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <h5 class="mt-4 mb-2 fw-bold">Analyzing public job boards with AI...</h5>
                    <p class="text-muted">This usually takes 10-50 seconds as we verify recent listings</p>
                </div>
                
                <div id="mncDiscoveryResults" class="mt-4 d-none">
                    <div class="row">
                        <!-- Left Panel: Job Listings -->
                        <div class="col-lg-8 mb-4">
                            <div id="mncJobListingsPanel">
                                <!-- Job results will be loaded here -->
                            </div>
                        </div>
                        <!-- Right Panel: Company Information -->
                        <div class="col-lg-4">
                            <div id="mncCompanyInfoPanel" class="sticky-top" style="top: 100px;">
                                <!-- Company card will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Directory Section (Paginated) -->
            <?php if (!$shouldAutoTriggerAiSearch): ?>
                <div id="companyDirectorySection" class="mt-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h2 class="section-title mb-1">Registered Companies</h2>
                            <p class="section-subtitle mb-0">Browse companies actively hiring on our platform</p>
                        </div>
                        <span class="badge badge-light border">Directory Listing</span>
                    </div>

                    <?php if (empty($companies)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                <h4 class="mb-2">No companies found</h4>
                                <p class="text-muted mb-0">Try a different company name, location, or industry filter</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="company-directory-grid mb-4">
                            <?php foreach ($companies as $company): ?>
                                <?php
                                $companyName    = (string) ($company['name'] ?? 'Company');
                                $companyInitial = strtoupper(substr($companyName, 0, 1) ?: 'C');
                                $companyIndustry = trim((string) ($company['industry'] ?? ''));
                                $companyHq      = trim((string) ($company['hq'] ?? ''));
                                $companySize    = trim((string) ($company['size'] ?? ''));
                                $companyLogo    = trim((string) ($company['logo'] ?? ''));
                                $companyWebsite = trim((string) ($company['website'] ?? ''));
                                $websiteHost    = $companyWebsite !== '' ? (parse_url($companyWebsite, PHP_URL_HOST) ?: $companyWebsite) : '';
                                $websiteHost    = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                                $googleLogoUrl  = $websiteHost !== '' ? 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96' : '';
                                $logoUrl        = $companyLogo !== '' ? base_url($companyLogo) : $googleLogoUrl;
                                $fallbackHtml   = '<span>' . esc($companyInitial) . '</span>';
                                $logoErrorJs    = "if(this.dataset.googleLogo&&this.src!==this.dataset.googleLogo){this.src=this.dataset.googleLogo;}else{this.parentNode.innerHTML='" . $fallbackHtml . "';}";
                                ?>
                                <article class="job-card company-directory-card" data-company-id="<?= (int) $company['id'] ?>" data-company-name="<?= esc($companyName) ?>">
                                    <div class="job-card-icon company-directory-logo">
                                        <?php if ($logoUrl !== ''): ?>
                                            <img src="<?= esc($logoUrl) ?>"
                                                 alt="<?= esc($companyName) ?>"
                                                 data-google-logo="<?= esc($googleLogoUrl) ?>"
                                                 onerror="<?= esc($logoErrorJs, 'attr') ?>">
                                        <?php else: ?>
                                            <span><?= esc($companyInitial) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="job-card-title">
                                        <a href="<?= base_url('company/' . (int) $company['id']) ?>"><?= esc($companyName) ?></a>
                                    </h3>
                                    <?php if (!empty($companyIndustry)): ?>
                                        <p class="job-card-company"><?= esc($companyIndustry) ?></p>
                                    <?php endif; ?>
                                    <div class="job-card-meta company-directory-meta">
                                        <?php if (!empty($companyHq)): ?>
                                            <span><i class="fas fa-map-pin"></i> <?= esc($companyHq) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="job-card-tags company-directory-tags">
                                        <?php if (!empty($companySize)): ?>
                                            <span class="badge badge-primary"><?= esc($companySize) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="company-directory-actions">
                                        <a href="<?= base_url('jobs?company=' . urlencode($companyName)) ?>"
                                           class="company-directory-jobs-link">
                                            <i class="fas fa-briefcase me-1"></i> See live jobs
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!($viewAll ?? false) && isset($pager) && $pager->getPageCount() > 1): ?>
                            <div class="text-center mt-4">
                                <?php 
                                $viewMoreUrl = base_url('candidate/company-job-discovery') . '?' . http_build_query(array_merge(array_filter($filters), ['view_all' => 1]));
                                ?>
                                <a href="<?= $viewMoreUrl ?>" class="btn btn-primary px-4">
                                    View More Companies <i class="fas fa-plus ms-2"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // MNC Job Discovery elements
    const mncSearchInput = document.getElementById('mncCompanySearchInput');
    const mncSearchButton = document.getElementById('searchMainBtn');
    const mncLoadingSpinner = document.getElementById('mncLoadingSpinner');
    const mncDiscoveryResultsDiv = document.getElementById('mncDiscoveryResults');
    const mncJobListingsPanel = document.getElementById('mncJobListingsPanel');
    const mncCompanyInfoPanel = document.getElementById('mncCompanyInfoPanel');
    const autocompleteDropdown = document.getElementById('companyAutocompleteDropdown');

    const hasSearchQuery = <?= $hasSearchQuery ? 'true' : 'false' ?>;
    const foundRegisteredCompanies = <?= $foundRegisteredCompanies ? 'true' : 'false' ?>;
    const shouldAutoTriggerAiSearch = <?= $shouldAutoTriggerAiSearch ? 'true' : 'false' ?>;
    const companyDirectorySection = document.getElementById('companyDirectorySection');

    // Autocomplete functionality
    let autocompleteTimeout = null;
    let currentSuggestions = [];
    let selectedIndex = -1;

    mncSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length < 2) {
            hideAutocomplete();
            return;
        }

        clearTimeout(autocompleteTimeout);
        autocompleteTimeout = setTimeout(() => {
            fetchCompanySuggestions(query);
        }, 300);
    });

    mncSearchInput.addEventListener('keydown', function(e) {
        if (!autocompleteDropdown.classList.contains('show')) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, currentSuggestions.length - 1);
            updateSelectedItem();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelectedItem();
        } else if (e.key === 'Enter') {
            if (selectedIndex >= 0) {
                e.preventDefault();
                selectSuggestion(currentSuggestions[selectedIndex]);
            }
        } else if (e.key === 'Escape') {
            hideAutocomplete();
        }
    });

    document.addEventListener('click', function(e) {
        if (!mncSearchInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
            hideAutocomplete();
        }
    });

    function fetchCompanySuggestions(query) {
        autocompleteDropdown.innerHTML = '<div class="company-autocomplete-loading"><i class="fas fa-spinner fa-spin"></i> Loading suggestions...</div>';
        autocompleteDropdown.classList.add('show');

        fetch('<?= base_url('candidate/company-jobs/suggestions') ?>?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && Array.isArray(data.suggestions)) {
                    currentSuggestions = data.suggestions;
                    selectedIndex = -1;
                    renderSuggestions(data.suggestions);
                } else {
                    hideAutocomplete();
                }
            })
            .catch(error => {
                console.error('Autocomplete error:', error);
                hideAutocomplete();
            });
    }

    function renderSuggestions(suggestions) {
        if (suggestions.length === 0) {
            autocompleteDropdown.innerHTML = '<div class="company-autocomplete-loading">No companies found</div>';
            return;
        }

        let html = '';
        suggestions.forEach((suggestion, index) => {
            const isInternal = suggestion.source === 'internal';
            const badge = isInternal
                ? '<span class="company-autocomplete-badge">Registered</span>'
                : '';

            const logoHtml = suggestion.logo
                ? `<img src="${escapeHtml(suggestion.logo)}" alt="" class="company-autocomplete-logo" onerror="this.style.display='none'">`
                : `<span class="company-autocomplete-logo-fallback">${escapeHtml(suggestion.name.charAt(0).toUpperCase())}</span>`;

            const meta = [];
            if (suggestion.domain) meta.push(suggestion.domain);
            const metaText = meta.join('');

            html += `
                <div class="company-autocomplete-item" data-index="${index}">
                    <div class="company-autocomplete-logo-wrap">${logoHtml}</div>
                    <div class="company-autocomplete-text">
                        <div class="company-autocomplete-name">${escapeHtml(suggestion.name)}${badge}</div>
                        ${metaText ? `<div class="company-autocomplete-meta">${escapeHtml(metaText)}</div>` : ''}
                    </div>
                </div>
            `;
        });

        autocompleteDropdown.innerHTML = html;
        autocompleteDropdown.classList.add('show');

        autocompleteDropdown.querySelectorAll('.company-autocomplete-item').forEach(item => {
            item.addEventListener('click', function() {
                selectSuggestion(currentSuggestions[parseInt(this.dataset.index)]);
            });
        });
    }

    function updateSelectedItem() {
        const items = autocompleteDropdown.querySelectorAll('.company-autocomplete-item');
        items.forEach((item, index) => {
            item.classList.toggle('active', index === selectedIndex);
        });

        if (selectedIndex >= 0 && items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    function selectSuggestion(suggestion) {
        mncSearchInput.value = suggestion.name;
        hideAutocomplete();
        mncSearchInput.focus();
    }

    function hideAutocomplete() {
        autocompleteDropdown.classList.remove('show');
        autocompleteDropdown.innerHTML = '';
        currentSuggestions = [];
        selectedIndex = -1;
    }

    // Auto-trigger AI discovery if a search query is present on load
    if (shouldAutoTriggerAiSearch) {
        fetchMncJobs();
    }

    // Function for MNC Job Discovery
    function fetchMncJobs() {
        const companyName = mncSearchInput.value.trim();
        if (companyName === '') {
            alert('Please enter a company name.');
            return;
        }
        const limit = '10';

        mncDiscoveryResultsDiv.classList.add('d-none');
        document.getElementById('mncDiscoverySection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        mncCompanyInfoPanel.innerHTML = '';
        mncJobListingsPanel.innerHTML = '';
        mncLoadingSpinner.classList.remove('d-none');
        mncSearchButton.disabled = true;

        fetch('<?= base_url('mnc/discover') ?>?company=' + encodeURIComponent(companyName) + '&limit=' + encodeURIComponent(limit))
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Server Error (${response.status}): ${text.substring(0, 100)}...`);
                    });
                }
                return response.json();
            })
            .then(data => {
                mncLoadingSpinner.classList.add('d-none');
                mncSearchButton.disabled = false;
                mncDiscoveryResultsDiv.classList.remove('d-none');

                if (data.success) {
                    // Render Company Info Card
                    if (data.company_info) {
                        const info = data.company_info;
                        const companyName = info.name || data.company || 'Company';
                        const companyInitial = companyName.charAt(0).toUpperCase();
                        const websiteUrl = normalizeUrl(info.website || '');
                        const websiteLabel = websiteUrl ? websiteUrl.replace(/^https?:\/\//, '').replace(/\/$/, '') : '';
                        const websiteHost = websiteLabel.replace(/^www\./, '').split('/')[0];
                        const googleLogoUrl = websiteHost ? `https://www.google.com/s2/favicons?domain=${encodeURIComponent(websiteHost)}&sz=96` : '';
                        const logoSrc = info.logo_url || googleLogoUrl;
                        const logoHtml = logoSrc
                            ? `<img src="${escapeHtml(logoSrc)}" alt="${escapeHtml(companyName)}" data-google-logo="${escapeHtml(googleLogoUrl)}" onerror="window.hmCompanyLogoFallback(this, '${escapeHtml(companyInitial)}')">`
                            : `<span>${escapeHtml(companyInitial)}</span>`;
                        let socialHtml = '';
                        ['linkedin', 'twitter', 'facebook', 'instagram', 'youtube'].forEach(platform => {
                            if (info[platform]) {
                                socialHtml += `<a href="${escapeHtml(normalizeUrl(info[platform]))}" target="_blank" rel="noopener" class="me-3 text-muted"><i class="fab fa-${platform} fa-lg"></i></a>`;
                            }
                        });
                        const websiteHtml = websiteUrl
                            ? `<a href="${escapeHtml(websiteUrl)}" target="_blank" rel="noopener" class="text-truncate d-inline-block" style="max-width: 100%;"><i class="fas fa-globe me-2"></i>${escapeHtml(websiteLabel)}</a>`
                            : '';

                        const companyCardHtml = `
                            <article class="job-card company-directory-card">
                                <div class="job-card-icon company-directory-logo">
                                    ${logoHtml}
                                </div>
                                <div class="mt-3">
                                    <h3 class="job-card-title mb-2">${escapeHtml(companyName)}</h3>
                                    <p class="job-card-company mb-3">${escapeHtml(info.industry || 'MNC')}</p>
                                    
                                    <div class="job-card-meta company-directory-meta mb-3">
                                        <span><i class="fas fa-map-pin"></i> ${escapeHtml(info.hq || 'Global HQ')}</span>
                                        <span><i class="fas fa-users"></i> ${escapeHtml(info.size || 'Enterprise')}</span>
                                    </div>
                                    ${websiteHtml ? `<div class="mb-3">${websiteHtml}</div>` : ''}
                                    
                                    <hr class="my-3">
                                    <p class="text-muted small mb-3">${escapeHtml(info.short_description || 'Public profile fetched via AI discovery engine.')}</p>
                                    
                                    ${socialHtml ? `<div class="mt-3">${socialHtml}</div>` : ''}
                                </div>
                            </article>
                        `;
                        mncCompanyInfoPanel.innerHTML = companyCardHtml;
                    }

                    // Render Job Listings
                    if (Array.isArray(data.jobs) && data.jobs.length > 0) {
                        let jobsHtml = '';
                        data.jobs.forEach(job => {
                            const applyUrl = normalizeUrl(job.apply_url || '');
                            const jobId = String(job.id || '');
                            const isSaved = job.is_saved === true || job.is_saved === '1' || job.is_saved === 1;
                            const saveUrl = jobId ? `<?= base_url('mnc/job/save/') ?>${encodeURIComponent(jobId)}` : '';
                            const unsaveUrl = jobId ? `<?= base_url('mnc/job/unsave/') ?>${encodeURIComponent(jobId)}` : '';
                            const saveButtonHtml = jobId ? `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary py-0 px-2 job-card-save js-save-job-toggle ${isSaved ? 'is-saved' : ''}"
                                    style="position: absolute; bottom: 0.9rem; right: 0.9rem; z-index: 2;"
                                    aria-label="${isSaved ? 'Saved job' : 'Save job'}"
                                    title="${isSaved ? 'Saved' : 'Save Job'}"
                                    data-save-url="${escapeHtml(isSaved ? unsaveUrl : saveUrl)}"
                                    data-save-url-save="${escapeHtml(saveUrl)}"
                                    data-save-url-unsave="${escapeHtml(unsaveUrl)}"
                                    data-saved="${isSaved ? '1' : '0'}"
                                    data-save-label-save="Save Job"
                                    data-save-label-saved="Saved"
                                >
                                    <i class="js-save-icon ${isSaved ? 'fas' : 'far'} fa-bookmark"></i>
                                </button>
                            ` : '';
                            jobsHtml += `
                                <article class="job-card mb-3 position-relative">
                                    <div class="job-card-icon">
                                        <span><i class="fas fa-briefcase"></i></span>
                                    </div>
                                    <h3 class="job-card-title">${escapeHtml(job.title || 'Untitled Role')}</h3>
                                    <p class="job-card-company">${escapeHtml(job.company_name || 'MNC')}</p>
                                    <div class="job-card-meta">
                                        <span><i class="fas fa-map-pin"></i> ${escapeHtml(job.location || 'N/A')}</span>
                                        <span><i class="fas fa-clock"></i> ${escapeHtml(job.posted_at_raw || 'Recently')}</span>
                                    </div>
                                    
                                    <a href="${escapeHtml(applyUrl || '#')}" target="_blank" rel="noopener" class="view-details">Apply Now &rarr;</a>
                                    ${saveButtonHtml}
                                </article>
                            `;
                        });
                        mncJobListingsPanel.innerHTML = jobsHtml;
                    } else {
                        mncJobListingsPanel.innerHTML = `
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                    <h4 class="mb-2">No live jobs found</h4>
                                    <p class="text-muted mb-0">No jobs found for ${escapeHtml(data.company || companyName)}</p>
                                </div>
                            </div>`;
                    }
                } else {
                    mncJobListingsPanel.innerHTML = `<div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle me-2"></i>${escapeHtml(data.error || 'Failed to fetch jobs. Please try again later.')}</div>`;
                }
            })
            .catch(error => {
                console.error('MNC Discovery Error:', error);
                mncLoadingSpinner.classList.add('d-none');
                mncSearchButton.disabled = false;
                mncDiscoveryResultsDiv.classList.remove('d-none');
                mncJobListingsPanel.innerHTML = `<div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle me-2"></i>${escapeHtml(error.message || 'Failed to fetch jobs. Please try again later.')}</div>`;
            });
    }

    // Helper functions
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }

    function normalizeUrl(url) {
        const value = String(url || '').trim();
        if (value === '' || value === '#') {
            return '';
        }

        if (/^https?:\/\//i.test(value)) {
            return value;
        }

        return 'https://' + value.replace(/^\/+/, '');
    }

    window.hmCompanyLogoFallback = function(img, initial) {
        const googleLogo = img.dataset.googleLogo || '';
        if (img.src.includes('clearbit.com') && googleLogo && img.src !== googleLogo) {
            img.src = googleLogo;
            return;
        }
        if (googleLogo && img.src !== googleLogo) {
            img.src = googleLogo;
            return;
        }
        img.parentNode.innerHTML = `<span>${escapeHtml(initial || 'C')}</span>`;
    };
});
</script>

<?= view('Layouts/candidate_footer') ?>
