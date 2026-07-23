<?= view('Layouts/candidate_header', ['title' => 'Company Discovery']) ?>

<?php
$filters = is_array($filters ?? null) ? $filters : [];
$segments = is_array($segments ?? null) ? $segments : [];
$companies = is_array($companies ?? null) ? $companies : [];
$industries = is_array($industries ?? null) ? $industries : [];
$allCompanyCount = (int) ($allCompanyCount ?? 0);
$activeSegmentKey = (string) ($activeSegmentKey ?? ($filters['segment'] ?? ''));
$baseDiscoveryUrl = base_url('candidate/company-job-discovery');
$activeFilters = array_filter([
    'q' => $filters['q'] ?? '',
    'industry' => $filters['industry'] ?? '',
    'location' => $filters['location'] ?? '',
    'jobs' => $filters['jobs'] ?? '',
], static fn ($value): bool => trim((string) $value) !== '');
?>

<div class="companies-directory-jobboard company-discovery-hub">
    <div class="container-fluid company-discovery-shell">
        <div class="page-board-header page-board-header-tight company-discovery-hero">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-building"></i> Company Intelligence</span>
                <h1 class="page-board-title">Discover companies before you apply</h1>
                <p class="page-board-subtitle">Explore Indian MNCs, corporate employers, global Indian companies, startups, and their portal-posted jobs.</p>
            </div>
        </div>

        <form id="companyDiscoveryForm" method="get" action="<?= esc($baseDiscoveryUrl) ?>" class="companies-filter-card company-discovery-search">
            <div class="company-discovery-search-grid">
                <div class="company-discovery-autocomplete">
                    <label for="companyDiscoveryQ">Company, skill, or keyword</label>
                    <input
                        id="companyDiscoveryQ"
                        type="text"
                        name="q"
                        class="form-control"
                        value="<?= esc($filters['q'] ?? '') ?>"
                        placeholder="Zoho, fintech, PHP, Bangalore"
                        autocomplete="off"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="companyDiscoverySuggestions"
                    >
                    <div id="companyDiscoverySuggestions" class="company-discovery-suggestions" role="listbox" hidden></div>
                </div>
                <div>
                    <label for="companyDiscoveryIndustry">Industry</label>
                    <select id="companyDiscoveryIndustry" name="industry" class="form-control">
                        <option value="">All industries</option>
                        <?php foreach ($industries as $industry): ?>
                            <?php $industry = trim((string) $industry); ?>
                            <?php if ($industry !== ''): ?>
                                <option value="<?= esc($industry) ?>" <?= ($filters['industry'] ?? '') === $industry ? 'selected' : '' ?>><?= esc($industry) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="companyDiscoveryLocation">Location</label>
                    <input id="companyDiscoveryLocation" type="text" name="location" class="form-control" value="<?= esc($filters['location'] ?? '') ?>" placeholder="Bangalore, Kochi, Chennai">
                </div>
                <div>
                    <label for="companyDiscoveryJobs">Availability</label>
                    <select id="companyDiscoveryJobs" name="jobs" class="form-control">
                        <option value="active" selected>Companies with openings</option>
                    </select>
                </div>
                <?php if ($activeSegmentKey !== ''): ?>
                    <input type="hidden" name="segment" value="<?= esc($activeSegmentKey) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>

        <div class="company-discovery-segments">
            <a href="<?= esc($baseDiscoveryUrl) ?>" class="company-segment-card <?= $activeSegmentKey === '' ? 'is-active' : '' ?>">
                <strong>All Companies</strong>
            </a>
            <?php foreach ($segments as $segment): ?>
                <?php
                $segmentKey = (string) ($segment['key'] ?? '');
                $segmentUrl = $baseDiscoveryUrl . '?' . http_build_query(['segment' => $segmentKey]);
                ?>
                <a href="<?= esc($segmentUrl) ?>" class="company-segment-card <?= $activeSegmentKey === $segmentKey ? 'is-active' : '' ?>">
                    <strong><?= esc($segment['label'] ?? 'Companies') ?></strong>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($companies)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h4 class="mb-2">Checking company openings</h4>
                    <p class="text-muted mb-0">Try another category, city, or industry while current openings are being refreshed.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="company-directory-grid company-discovery-grid mb-4">
                <?php foreach ($companies as $company): ?>
                    <?php
                    $companyName = (string) ($company['name'] ?? 'Company');
                    $companyInitial = strtoupper(substr($companyName, 0, 1) ?: 'C');
                    $companyLogo = trim((string) ($company['logo'] ?? ''));
                    $companyWebsite = trim((string) ($company['website'] ?? ''));
                    $companyHq = trim((string) ($company['hq'] ?? ''));
                    $companyFounded = trim((string) ($company['founded_year'] ?? ''));
                    $cardUrl = (string) ($company['jobs_url'] ?? base_url('candidate/company-jobs/' . rawurlencode($companyName)));
                    $tags = is_array($company['discovery_tags'] ?? null) ? $company['discovery_tags'] : [];
                    $websiteHost = $companyWebsite !== '' ? (parse_url($companyWebsite, PHP_URL_HOST) ?: $companyWebsite) : '';
                    $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                    $logoUrl = $companyLogo !== '' ? base_url($companyLogo) : ($websiteHost !== '' ? 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96' : '');
                    ?>
                    <a href="<?= esc($cardUrl) ?>" class="job-card company-directory-card company-discovery-card company-discovery-card-link" aria-label="Load jobs at <?= esc($companyName) ?>">
                        <div class="company-directory-card-head">
                            <div class="job-card-icon company-directory-logo">
                                <?php if ($logoUrl !== ''): ?>
                                    <img src="<?= esc($logoUrl) ?>" alt="<?= esc($companyName) ?>" onerror="this.parentNode.innerHTML='<span><?= esc($companyInitial) ?></span>';">
                                <?php else: ?>
                                    <span><?= esc($companyInitial) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="company-directory-title-wrap">
                                <h3 class="job-card-title"><?= esc($companyName) ?></h3>
                                <?php if ($companyHq !== ''): ?><p class="job-card-company mb-0"><?= esc($companyHq) ?></p><?php endif; ?>
                            </div>
                            <span class="company-directory-card-arrow" aria-hidden="true">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        </div>

                        <div class="company-discovery-tags">
                            <?php foreach ($tags as $tag): ?>
                                <span><?= esc($tag) ?></span>
                            <?php endforeach; ?>
                            <?php if ($companyFounded !== ''): ?>
                                <span>Founded <?= esc($companyFounded) ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="company-discovery-summary"><?= esc(mb_substr(trim((string) ($company['short_description'] ?? 'Explore company details, hiring locations, and portal-posted jobs.')), 0, 135)) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (!($viewAll ?? false) && !empty($hasMoreCompanies)): ?>
                <div class="text-center mt-4">
                    <?php $viewMoreUrl = $baseDiscoveryUrl . '?' . http_build_query(array_merge($activeFilters, $activeSegmentKey !== '' ? ['segment' => $activeSegmentKey] : [], ['view_all' => 1])); ?>
                    <a href="<?= esc($viewMoreUrl) ?>" class="btn btn-primary px-4">View More Companies <i class="fas fa-plus ml-2"></i></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('companyDiscoveryForm');
    const input = document.getElementById('companyDiscoveryQ');
    const list = document.getElementById('companyDiscoverySuggestions');
    const suggestionsUrl = <?= json_encode(base_url('candidate/company-jobs/suggestions')) ?>;

    if (!form || !input || !list) {
        return;
    }

    let debounceTimer = null;
    let requestController = null;
    let activeIndex = -1;
    let suggestions = [];

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[character]));

    const closeSuggestions = () => {
        suggestions = [];
        activeIndex = -1;
        list.hidden = true;
        list.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    };

    const setActiveSuggestion = (index) => {
        const options = Array.from(list.querySelectorAll('[role="option"]'));
        if (options.length === 0) {
            return;
        }

        activeIndex = Math.max(0, Math.min(index, options.length - 1));
        options.forEach((option, optionIndex) => {
            const isActive = optionIndex === activeIndex;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        input.setAttribute('aria-activedescendant', options[activeIndex].id);
        options[activeIndex].scrollIntoView({ block: 'nearest' });
    };

    const chooseSuggestion = (index) => {
        const suggestion = suggestions[index];
        if (!suggestion || !suggestion.name) {
            return;
        }

        input.value = suggestion.name;
        closeSuggestions();
        form.requestSubmit();
    };

    const renderSuggestions = (items) => {
        suggestions = Array.isArray(items)
            ? items.filter((item) => item && String(item.name || '').trim() !== '').slice(0, 8)
            : [];
        activeIndex = -1;

        if (suggestions.length === 0) {
            closeSuggestions();
            return;
        }

        list.innerHTML = suggestions.map((suggestion, index) => `
            <button
                id="companyDiscoverySuggestion${index}"
                type="button"
                class="company-discovery-suggestion"
                role="option"
                aria-selected="false"
                data-suggestion-index="${index}"
            >
                <span class="company-discovery-suggestion-icon" aria-hidden="true"><i class="fas fa-building"></i></span>
                <span>${escapeHtml(suggestion.name)}</span>
            </button>
        `).join('');
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const loadSuggestions = () => {
        const query = input.value.trim();
        if (query.length < 2) {
            closeSuggestions();
            return;
        }

        if (requestController) {
            requestController.abort();
        }
        requestController = new AbortController();

        fetch(suggestionsUrl + '?' + new URLSearchParams({ q: query }).toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: requestController.signal
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Unable to load company suggestions');
                }
                return response.json();
            })
            .then((payload) => renderSuggestions(payload && payload.suggestions))
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    closeSuggestions();
                }
            });
    };

    input.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(loadSuggestions, 220);
    });

    input.addEventListener('keydown', (event) => {
        if (list.hidden || suggestions.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveSuggestion(activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveSuggestion(activeIndex <= 0 ? suggestions.length - 1 : activeIndex - 1);
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            chooseSuggestion(activeIndex);
        } else if (event.key === 'Escape') {
            closeSuggestions();
        }
    });

    list.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });

    list.addEventListener('click', (event) => {
        const option = event.target.closest('[data-suggestion-index]');
        if (option) {
            chooseSuggestion(Number(option.dataset.suggestionIndex));
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.company-discovery-autocomplete')) {
            closeSuggestions();
        }
    });
}());
</script>

<?= view('Layouts/candidate_footer') ?>
