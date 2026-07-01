<?= view('Layouts/candidate_header', ['title' => $title]) ?>

<?php
$companyName = (string) ($company_name ?? 'Company');
$companyInitial = strtoupper(substr($companyName, 0, 1) ?: 'C');
$portalJobs = is_array($internal_jobs ?? null) ? $internal_jobs : [];
$externalJobs = is_array($external_jobs ?? null) ? $external_jobs : [];
$portalCount = count($portalJobs);
$externalCount = count($externalJobs);
$renderedJobCount = $portalCount + $externalCount;
$totalCount = max((int) ($total_jobs ?? 0), $renderedJobCount);
$company = is_array($company ?? null) ? $company : [];
$companyLogo = trim((string) ($company['logo'] ?? ''));
$companyWebsite = trim((string) ($company['website'] ?? ''));
$companyCareerPage = trim((string) ($company['career_page'] ?? ''));
$companyHq = trim((string) ($company['hq'] ?? ''));
$companyIndustry = trim((string) ($company['industry'] ?? ''));
$companyType = trim((string) ($company['company_type'] ?? ''));
$companySize = trim((string) ($company['size'] ?? ''));
$companyFounded = trim((string) ($company['founded_year'] ?? ''));
$companyDescription = trim((string) ($company['short_description'] ?? 'Explore company details, hiring locations, and available jobs in one place.'));
$companyWhatWeDo = trim((string) ($company['what_we_do'] ?? ''));
$websiteHost = $companyWebsite !== '' ? (parse_url($companyWebsite, PHP_URL_HOST) ?: $companyWebsite) : '';
$websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
$logoUrl = '';
if ($companyLogo !== '') {
    $logoUrl = preg_match('/^https?:\/\//i', $companyLogo) ? $companyLogo : base_url($companyLogo);
} elseif ($websiteHost !== '') {
    $logoUrl = 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96';
}
$discoverUrl = base_url('mnc/discover');
$profileUrl = !empty($company['id']) ? base_url('company/' . (int) $company['id']) : '';
$discoveryUrl = base_url('candidate/company-job-discovery');

$companyTags = [];
foreach (preg_split('/[,|;\n]+/', (string) ($company['company_tags'] ?? '')) ?: [] as $tag) {
    $tag = trim($tag);
    if ($tag !== '') {
        $companyTags[] = $tag;
    }
}
$companyTags = array_slice(array_values(array_unique(array_filter(array_merge([$companyType, $companyIndustry], $companyTags)))), 0, 7);

$formatDate = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('M d, Y', $timestamp) : 'Recently';
};

$jobExcerpt = static function ($value): string {
    $text = trim(strip_tags((string) $value));
    if ($text === '') {
        return 'Open role listed by the employer.';
    }
    return mb_substr($text, 0, 170) . (mb_strlen($text) > 170 ? '...' : '');
};
?>

<div class="jobs-page-jobboard company-jobs-page">
    <div class="container company-jobs-shell">
        <div class="page-board-header company-jobs-header">
            <div class="company-jobs-identity">
                <div class="company-jobs-logo" aria-hidden="true">
                    <?php if ($logoUrl !== ''): ?>
                        <img src="<?= esc($logoUrl) ?>" alt="" onerror="this.parentNode.innerHTML='<span><?= esc($companyInitial) ?></span>';">
                    <?php else: ?>
                        <span><?= esc($companyInitial) ?></span>
                    <?php endif; ?>
                </div>
                <div class="page-board-copy">
                    <h1><?= esc($companyName) ?></h1>
                    <span id="companyJobsTotalCount" hidden><?= (int) $totalCount ?></span>
                    <div class="company-jobs-meta">
                        <?php if ($companyHq !== ''): ?><span><i class="fas fa-map-marker-alt"></i><?= esc($companyHq) ?></span><?php endif; ?>
                        <?php if ($companyIndustry !== ''): ?><span><i class="fas fa-building"></i><?= esc($companyIndustry) ?></span><?php endif; ?>
                        <?php if ($companyFounded !== ''): ?><span><i class="fas fa-calendar"></i>Founded <?= esc($companyFounded) ?></span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="page-board-actions">
                <a href="<?= esc($discoveryUrl) ?>" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Companies</a>
                <?php if ($companyWebsite !== ''): ?>
                    <a href="<?= esc($companyWebsite) ?>" target="_blank" rel="noopener" class="btn btn-primary">Website <i class="fas fa-external-link-alt"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="company-jobs-tabs tab-pills" role="tablist" aria-label="Company sections">
            <button type="button" class="tab-pill" data-company-tab="overview" role="tab" aria-selected="false">
                <i class="fas fa-building"></i> Overview
            </button>
            <button type="button" class="tab-pill active" data-company-tab="jobs" role="tab" aria-selected="true">
                <i class="fas fa-briefcase"></i> Jobs <span id="companyJobsTabCount" class="pill-count"><?= (int) $totalCount ?></span>
            </button>
        </div>

        <section class="company-jobs-panel" data-company-panel="overview" hidden>
            <div class="company-jobs-overview-grid">
                <article class="company-jobs-card company-jobs-card-wide">
                    <h2>About <?= esc($companyName) ?></h2>
                    <p><?= esc($companyDescription) ?></p>
                    <?php if ($companyWhatWeDo !== ''): ?>
                        <p><?= esc($companyWhatWeDo) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($companyTags)): ?>
                        <div class="company-jobs-pills">
                            <?php foreach ($companyTags as $tag): ?>
                                <span class="skill-chip"><?= esc($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="company-jobs-card">
                    <h2>Company Snapshot</h2>
                    <dl class="company-jobs-snapshot">
                        <dt>Industry</dt><dd><?= esc($companyIndustry ?: 'Not specified') ?></dd>
                        <dt>Location</dt><dd><?= esc($companyHq ?: 'Not specified') ?></dd>
                        <dt>Type</dt><dd><?= esc($companyType ?: 'Not specified') ?></dd>
                        <dt>Size</dt><dd><?= esc($companySize ?: 'Not specified') ?></dd>
                        <dt>Founded</dt><dd><?= esc($companyFounded ?: 'Not specified') ?></dd>
                    </dl>
                </article>

                <article class="company-jobs-card">
                    <h2>Career Links</h2>
                    <div class="company-jobs-links">
                        <?php if ($companyCareerPage !== ''): ?>
                            <a href="<?= esc($companyCareerPage) ?>" target="_blank" rel="noopener">Career page <i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <?php if ($companyWebsite !== ''): ?>
                            <a href="<?= esc($companyWebsite) ?>" target="_blank" rel="noopener">Company website <i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <?php if ($profileUrl !== ''): ?>
                            <a href="<?= esc($profileUrl) ?>">HireMatrix profile <i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </section>

        <section class="company-jobs-panel is-active" data-company-panel="jobs">
            <div class="company-jobs-content-grid">
                <div class="company-jobs-list">
                    <?php if ($portalCount > 0): ?>
                        <div class="company-jobs-section-label">HireMatrix Posted Jobs</div>
                    <?php endif; ?>
                    <?php foreach ($portalJobs as $job): ?>
                        <article class="company-job-card">
                            <div class="company-job-main">
                                <div class="company-job-source">HireMatrix</div>
                                <h3><a href="<?= base_url('job/' . (int) $job['id']) ?>"><?= esc($job['title'] ?? 'Untitled role') ?></a></h3>
                                <div class="company-job-meta">
                                    <span><i class="fas fa-map-marker-alt"></i><?= esc($job['location'] ?? 'N/A') ?></span>
                                    <span><i class="fas fa-briefcase"></i><?= esc($job['experience_level'] ?? 'Not specified') ?></span>
                                    <span><i class="fas fa-calendar"></i><?= esc($formatDate($job['created_at'] ?? '')) ?></span>
                                </div>
                                <p><?= esc($jobExcerpt($job['description'] ?? '')) ?></p>
                                <?php if (!empty($job['employment_type'])): ?>
                                    <div class="company-jobs-pills">
                                        <span class="skill-chip"><?= esc($job['employment_type']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= base_url('job/' . (int) $job['id']) ?>" class="company-job-action">View Details</a>
                        </article>
                    <?php endforeach; ?>

                     <div id="cachedExternalJobsList" class="company-jobs-list-inner">
                        <?php foreach ($externalJobs as $job): ?>
                            <?php
                            $isStale = !empty($job['is_stale']);
                            $applyUrl = (string) ($job['apply_url'] ?? '#');
                            ?>
                            <article class="company-job-card company-job-card-external">
                                <div class="company-job-main">
                                    <h3><a href="<?= esc($applyUrl) ?>" target="_blank" rel="noopener"><?= esc($job['title'] ?? 'Untitled role') ?></a></h3>
                                    <div class="company-job-meta">
                                        <span><i class="fas fa-map-marker-alt"></i><?= esc($job['location'] ?? 'Remote/Multiple') ?></span>
                                        <span><i class="fas fa-layer-group"></i><?= esc($job['source_platform'] ?? 'Official/public source') ?></span>
                                        <span><i class="fas fa-clock"></i><?= esc($job['posted_at_raw'] ?? 'Recently') ?></span>
                                    </div>
                                    <?php if (!empty($job['employment_type'])): ?>
                                        <div class="company-jobs-pills">
                                            <span class="skill-chip"><?= esc($job['employment_type']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <a href="<?= esc($applyUrl) ?>" target="_blank" rel="noopener" class="company-job-action">Apply <i class="fas fa-external-link-alt"></i></a>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div id="companyJobsEmptyState" class="company-jobs-empty-state" <?= $totalCount > 0 ? 'hidden' : '' ?>>
                        <i class="fas fa-search"></i>
                        <div>
                            <strong>Finding current openings at <?= esc($companyName) ?></strong>
                            <p>We are checking public career sources. Results will appear here as soon as they are available.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    const companyName = <?= json_encode($companyName) ?>;
    const discoverUrl = <?= json_encode($discoverUrl) ?>;
    const portalCount = <?= (int) $portalCount ?>;
    const externalList = document.getElementById('cachedExternalJobsList');
    const emptyState = document.getElementById('companyJobsEmptyState');
    const totalCountEl = document.getElementById('companyJobsTotalCount');
    const tabCountEl = document.getElementById('companyJobsTabCount');
    const initialExternalCount = <?= (int) $externalCount ?>;
    const initialTotalCount = <?= (int) $totalCount ?>;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const renderedJobCardCount = () => document.querySelectorAll('.company-jobs-list .company-job-card').length;

    const syncEmptyStateVisibility = () => {
        if (!emptyState) {
            return;
        }
        if (renderedJobCardCount() > 0) {
            emptyState.hidden = true;
        }
    };

    document.querySelectorAll('[data-company-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.companyTab;
            document.querySelectorAll('[data-company-tab]').forEach((item) => {
                item.classList.toggle('active', item === tab);
                item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
            });
            document.querySelectorAll('[data-company-panel]').forEach((panel) => {
                const active = panel.dataset.companyPanel === target;
                panel.hidden = !active;
                panel.classList.toggle('is-active', active);
            });
        });
    });

    const setCounts = (externalCount) => {
        const total = portalCount + externalCount;
        if (totalCountEl) {
            totalCountEl.textContent = String(total);
        }
        if (tabCountEl) {
            tabCountEl.textContent = String(total);
        }
        if (emptyState) {
            emptyState.hidden = total > 0;
        }
        syncEmptyStateVisibility();
    };

    const setEmptyStateContent = (title, message, iconClass = 'fas fa-search') => {
        if (!emptyState) {
            return;
        }
        emptyState.innerHTML = '<i class="' + escapeHtml(iconClass) + '"></i>' +
            '<div><strong>' + escapeHtml(title) + '</strong><p>' + escapeHtml(message) + '</p></div>';
    };

    const showNoResultsState = () => {
        setEmptyStateContent(
            'No current openings found at ' + companyName,
            'We checked public career sources and did not find active roles for this company right now.'
        );
        if (emptyState) {
            emptyState.hidden = portalCount > 0;
        }
    };

    const jobCardHtml = (job) => {
        const url = escapeHtml(job.apply_url || job.url || '#');
        const title = escapeHtml(job.title || 'Untitled role');
        const location = escapeHtml(job.location || 'Remote/Multiple');
        const source = escapeHtml(job.source_platform || job.source || 'Official/public source');
        const posted = escapeHtml(job.posted_at_raw || job.posted_date || 'Recently');
        const employmentType = escapeHtml(job.employment_type || '');

        return `
            <article class="company-job-card company-job-card-external">
                <div class="company-job-main">
                    <h3><a href="${url}" target="_blank" rel="noopener">${title}</a></h3>
                    <div class="company-job-meta">
                        <span><i class="fas fa-map-marker-alt"></i>${location}</span>
                        <span><i class="fas fa-layer-group"></i>${source}</span>
                        <span><i class="fas fa-clock"></i>${posted}</span>
                    </div>
                    ${employmentType ? `<div class="company-jobs-pills"><span class="skill-chip">${employmentType}</span></div>` : ''}
                </div>
                <a href="${url}" target="_blank" rel="noopener" class="company-job-action">Apply <i class="fas fa-external-link-alt"></i></a>
            </article>
        `;
    };

    const renderExternalJobs = (jobs) => {
        const validJobs = Array.isArray(jobs) ? jobs.filter((job) => job && (job.apply_url || job.url)) : [];
        setCounts(validJobs.length);

        if (!externalList) {
            return;
        }

        if (validJobs.length === 0) {
            externalList.innerHTML = '';
            showNoResultsState();
            return;
        }

        if (emptyState) {
            emptyState.hidden = true;
        }
        externalList.innerHTML = '';
        validJobs.forEach((job, index) => {
            window.setTimeout(() => {
                externalList.insertAdjacentHTML('beforeend', jobCardHtml(job));
                syncEmptyStateVisibility();
            }, index * 90);
        });
    };

    const discoverJobs = (isAutomatic = false) => {
        if (!externalList || !emptyState) {
            return;
        }

        const previousHtml = externalList.innerHTML;
        emptyState.hidden = true;
        setEmptyStateContent(
            'Finding current openings at ' + companyName,
            'We are checking public career sources. Results will appear here as soon as they are available.'
        );
        externalList.innerHTML = '<div class="company-jobs-note"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Finding current openings at ' + escapeHtml(companyName) + '...</span></div>';

        fetch(discoverUrl + '?' + new URLSearchParams({ company: companyName, limit: '20' }).toString(), {
            headers: { 'Accept': 'application/json' }
        })
            .then((response) => response.text().then((text) => {
                if (!text.trim()) {
                    throw new Error('Could not check public openings right now.');
                }

                let payload = null;
                try {
                    payload = JSON.parse(text);
                } catch (error) {
                    throw new Error('Could not check public openings right now.');
                }

                if (!response.ok) {
                    throw new Error(payload && payload.error ? payload.error : 'Could not check latest jobs.');
                }

                return payload;
            }))
            .then((payload) => {
                if (!payload || payload.success === false || payload.error) {
                    throw new Error(payload && payload.error ? payload.error : 'Could not check latest jobs.');
                }
                renderExternalJobs(payload.jobs || []);
            })
            .catch((error) => {
                externalList.innerHTML = (previousHtml && !isAutomatic ? previousHtml : '') + '<div class="company-jobs-note"><i class="fas fa-exclamation-circle"></i><span>' + escapeHtml(error.message || 'Could not check public openings right now.') + '</span></div>';
                setCounts(initialExternalCount);
                if (emptyState) {
                    emptyState.hidden = true;
                }
            });
    };

    if (initialTotalCount === 0 && emptyState) {
        emptyState.hidden = false;
        window.setTimeout(() => discoverJobs(true), 250);
    }
    syncEmptyStateVisibility();
})();
</script>

<?= view('Layouts/candidate_footer') ?>
