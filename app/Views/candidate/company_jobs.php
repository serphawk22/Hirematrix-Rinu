<?= view('Layouts/candidate_header', ['title' => $title]) ?>

<?php
$companyName = (string) ($company_name ?? 'Company');
$companyInitial = strtoupper(substr($companyName, 0, 1) ?: 'C');
$portalJobs = is_array($internal_jobs ?? null) ? $internal_jobs : [];
$externalJobs = is_array($external_jobs ?? null) ? $external_jobs : [];
$portalCount = count($portalJobs);
$externalCount = count($externalJobs);
$totalCount = (int) ($total_jobs ?? ($portalCount + $externalCount));
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
$logoUrl = $companyLogo !== '' ? base_url($companyLogo) : '';
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

$lastCheckedTimes = [];
foreach ($externalJobs as $job) {
    $timestamp = strtotime((string) ($job['last_sync_at'] ?? ''));
    if ($timestamp) {
        $lastCheckedTimes[] = $timestamp;
    }
}
$lastCheckedLabel = !empty($lastCheckedTimes) ? date('M d, Y h:i A', max($lastCheckedTimes)) : 'Not checked yet';

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
                        <img src="<?= esc($logoUrl) ?>" alt="">
                    <?php else: ?>
                        <span><?= esc($companyInitial) ?></span>
                    <?php endif; ?>
                </div>
                <div class="page-board-copy">
                    <h1><?= esc($companyName) ?></h1>
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

        <div class="company-jobs-tabs" role="tablist" aria-label="Company sections">
            <button type="button" class="company-jobs-tab" data-company-tab="overview" role="tab" aria-selected="false">Overview</button>
            <button type="button" class="company-jobs-tab is-active" data-company-tab="jobs" role="tab" aria-selected="true">Jobs <span id="companyJobsTabCount"><?= (int) $totalCount ?></span></button>
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
                                <span><?= esc($tag) ?></span>
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
                                <div class="company-jobs-pills">
                                    <span><?= esc($job['employment_type'] ?? 'Full-time') ?></span>
                                    <span>Portal posted</span>
                                </div>
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
                                    <div class="company-jobs-pills">
                                        <span><?= $isStale ? 'Needs refresh' : 'Available' ?></span>
                                        <?php if (!empty($job['last_sync_at'])): ?>
                                            <span>Checked <?= esc($formatDate($job['last_sync_at'])) ?></span>
                                        <?php endif; ?>
                                    </div>
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

                <aside class="company-jobs-side">
                    <article class="company-jobs-card">
                        <h2>Job Sources</h2>
                        <dl class="company-jobs-snapshot">
                            <dt>Portal</dt><dd><?= (int) $portalCount ?> roles</dd>
                            <dt>Discovered</dt><dd id="companyJobsCachedCount"><?= (int) $externalCount ?> roles</dd>
                            <dt>Status</dt><dd>Active links only</dd>
                            <dt>Window</dt><dd>Last 30 days</dd>
                        </dl>
                    </article>
                    <article class="company-jobs-card">
                        <h2>Before You Apply</h2>
                        <p><?= esc($companyDescription) ?></p>
                        <?php if (!empty($companyTags)): ?>
                            <div class="company-jobs-pills">
                                <?php foreach (array_slice($companyTags, 0, 5) as $tag): ?>
                                    <span><?= esc($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </aside>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    const companyName = <?= json_encode($companyName) ?>;
    const discoverUrl = <?= json_encode($discoverUrl) ?>;
    const portalCount = <?= (int) $portalCount ?>;
    const refreshBtn = document.getElementById('refreshDiscoveredJobsBtn');
    const externalList = document.getElementById('cachedExternalJobsList');
    const emptyState = document.getElementById('companyJobsEmptyState');
    const totalCountEl = document.getElementById('companyJobsTotalCount');
    const tabCountEl = document.getElementById('companyJobsTabCount');
    const cachedCountEl = document.getElementById('companyJobsCachedCount');
    const lastCheckedEl = document.getElementById('companyJobsLastChecked');
    const initialExternalCount = <?= (int) $externalCount ?>;
    const initialTotalCount = <?= (int) $totalCount ?>;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    document.querySelectorAll('[data-company-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.companyTab;
            document.querySelectorAll('[data-company-tab]').forEach((item) => {
                item.classList.toggle('is-active', item === tab);
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
        totalCountEl.textContent = String(total);
        tabCountEl.textContent = String(total);
        cachedCountEl.textContent = externalCount + (externalCount === 1 ? ' role' : ' roles');
        emptyState.hidden = total > 0;
    };

    const jobCardHtml = (job) => {
        const url = escapeHtml(job.apply_url || job.url || '#');
        const title = escapeHtml(job.title || 'Untitled role');
        const location = escapeHtml(job.location || 'Remote/Multiple');
        const source = escapeHtml(job.source_platform || job.source || 'Official/public source');
        const posted = escapeHtml(job.posted_at_raw || job.posted_date || 'Recently');

        return `
            <article class="company-job-card company-job-card-external">
                <div class="company-job-main">
                    <div class="company-job-source">Discovered</div>
                    <h3><a href="${url}" target="_blank" rel="noopener">${title}</a></h3>
                    <div class="company-job-meta">
                        <span><i class="fas fa-map-marker-alt"></i>${location}</span>
                        <span><i class="fas fa-layer-group"></i>${source}</span>
                        <span><i class="fas fa-clock"></i>${posted}</span>
                    </div>
                    <div class="company-jobs-pills">
                        <span>Available</span>
                        <span>Checked now</span>
                    </div>
                </div>
                <a href="${url}" target="_blank" rel="noopener" class="company-job-action">Apply <i class="fas fa-external-link-alt"></i></a>
            </article>
        `;
    };

    const renderExternalJobs = (jobs) => {
        const validJobs = Array.isArray(jobs) ? jobs.filter((job) => job && (job.apply_url || job.url)) : [];
        setCounts(validJobs.length);
        lastCheckedEl.textContent = 'Last checked: Just now';

        if (validJobs.length === 0) {
            externalList.innerHTML = '<div class="company-jobs-note"><i class="fas fa-search"></i><span>No active public openings found right now.</span></div>';
            return;
        }

        externalList.innerHTML = '';
        validJobs.forEach((job, index) => {
            window.setTimeout(() => {
                externalList.insertAdjacentHTML('beforeend', jobCardHtml(job));
            }, index * 90);
        });
    };

    const discoverJobs = (isAutomatic = false) => {
        const previousHtml = externalList.innerHTML;
        const previousLastChecked = lastCheckedEl.textContent;
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking...';
        }
        lastCheckedEl.textContent = 'Checking public sources...';
        emptyState.hidden = true;
        externalList.innerHTML = '<div class="company-jobs-note"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Finding current openings at ' + escapeHtml(companyName) + '...</span></div>';

        fetch(discoverUrl + '?' + new URLSearchParams({ company: companyName, limit: '20' }).toString(), {
            headers: { 'Accept': 'application/json' }
        })
            .then((response) => response.json())
            .then((payload) => {
                if (!payload || payload.success === false || payload.error) {
                    throw new Error(payload && payload.error ? payload.error : 'Could not check latest jobs.');
                }
                renderExternalJobs(payload.jobs || []);
            })
            .catch((error) => {
                externalList.innerHTML = (previousHtml && !isAutomatic ? previousHtml : '') + '<div class="company-jobs-note"><i class="fas fa-exclamation-circle"></i><span>' + escapeHtml(error.message || 'Could not check public openings right now.') + '</span></div>';
                lastCheckedEl.textContent = previousLastChecked;
                setCounts(initialExternalCount);
            })
            .finally(() => {
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh openings';
                }
            });
    };

    refreshBtn?.addEventListener('click', () => discoverJobs(false));

    if (initialExternalCount === 0) {
        emptyState.hidden = initialTotalCount > 0;
        window.setTimeout(() => discoverJobs(true), 250);
    }
})();
</script>

<?= view('Layouts/candidate_footer') ?>
