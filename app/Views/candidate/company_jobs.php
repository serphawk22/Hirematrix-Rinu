<?= view('Layouts/candidate_header', ['title' => $title]) ?>

<?php
use App\Libraries\ExternalJobTextNormalizer;

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
    $text = ExternalJobTextNormalizer::normalize((string) $value);
    if ($text === '') {
        return 'Open role listed by the employer.';
    }
    return mb_substr($text, 0, 170) . (mb_strlen($text) > 170 ? '...' : '');
};

$formatExternalSource = static function ($source, $applyUrl = '') use ($companyName, $websiteHost): string {
    $source = trim(ExternalJobTextNormalizer::normalize((string) $source));
    $applyUrl = trim((string) $applyUrl);
    $sourceHost = strtolower((string) (parse_url($source, PHP_URL_HOST) ?: ''));
    $applyHost = strtolower((string) (parse_url($applyUrl, PHP_URL_HOST) ?: ''));
    $host = preg_replace('/^www\./i', '', $sourceHost ?: $applyHost) ?? ($sourceHost ?: $applyHost);

    $platforms = [
        'linkedin.' => 'LinkedIn',
        'indeed.' => 'Indeed',
        'glassdoor.' => 'Glassdoor',
        'remotive.' => 'Remotive',
        'remoteok.' => 'Remote OK',
        'arbeitnow.' => 'Arbeitnow',
    ];
    foreach ($platforms as $domainPart => $label) {
        if (str_contains(strtolower($source), rtrim($domainPart, '.')) || str_contains($host, $domainPart)) {
            return $label;
        }
    }

    $officialHost = strtolower(preg_replace('/^www\./i', '', $websiteHost) ?? $websiteHost);
    if ($host !== '' && ($officialHost === '' || $host === $officialHost || str_ends_with($host, '.' . $officialHost))) {
        return $companyName . ' Careers';
    }

    if ($host !== '') {
        return $host;
    }

    return $source !== '' && !filter_var($source, FILTER_VALIDATE_URL) ? $source : 'External source';
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
                    </div>
                </article>
            </div>
        </section>

        <section class="company-jobs-panel is-active" data-company-panel="jobs">
            <div class="company-jobs-content-grid">
                <div class="company-jobs-list">
                    <?php if ($portalCount > 0): ?>
                        <div class="company-jobs-section-label">Available Jobs</div>
                    <?php endif; ?>
                    <?php foreach ($portalJobs as $job): ?>
                        <?php
                        $isExternalPortalJob = (int) ($job['is_external'] ?? 0) === 1;
                        $externalApplyUrl = trim((string) ($job['external_apply_url'] ?? ''));
                        $jobDetailsUrl = $isExternalPortalJob && filter_var($externalApplyUrl, FILTER_VALIDATE_URL)
                            ? $externalApplyUrl
                            : base_url('job/' . (int) $job['id']);
                        $sourceLabel = $isExternalPortalJob
                            ? $formatExternalSource($job['external_source'] ?? '', $externalApplyUrl)
                            : 'HireMatrix';
                        ?>
                        <article class="company-job-card"
                            data-href="<?= esc($jobDetailsUrl) ?>"
                            <?= $isExternalPortalJob ? 'data-target="_blank"' : '' ?>
                            role="link"
                            tabindex="0"
                            aria-label="<?= $isExternalPortalJob ? 'Apply to' : 'View details for' ?> <?= esc(ExternalJobTextNormalizer::normalize((string) ($job['title'] ?? 'this job'))) ?>">
                            <div class="company-job-main">
                                <div class="company-job-source"><?= esc($sourceLabel ?: 'External source') ?></div>
                                <h3><?= esc(ExternalJobTextNormalizer::normalize((string) ($job['title'] ?? 'Untitled role'))) ?></h3>
                                <div class="company-job-meta">
                                    <span><i class="fas fa-map-marker-alt"></i><?= esc(ExternalJobTextNormalizer::normalize((string) ($job['location'] ?? 'N/A'))) ?></span>
                                    <span><i class="fas fa-briefcase"></i><?= esc(ExternalJobTextNormalizer::normalize((string) ($job['experience_level'] ?? 'Not specified'))) ?></span>
                                    <span><i class="fas fa-calendar"></i><?= esc($formatDate($job['created_at'] ?? '')) ?></span>
                                </div>
                                <p><?= esc($jobExcerpt($job['description'] ?? '')) ?></p>
                                <?php if (!empty($job['employment_type'])): ?>
                                    <div class="company-jobs-pills">
                                        <span class="skill-chip"><?= esc(ExternalJobTextNormalizer::normalize((string) $job['employment_type'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>

                     <div id="cachedExternalJobsList" class="company-jobs-list-inner">
                        <?php foreach ($externalJobs as $job): ?>
                            <?php
                            $isStale = !empty($job['is_stale']);
                            $applyUrl = (string) ($job['apply_url'] ?? '#');
                            ?>
                            <article class="company-job-card company-job-card-external"
                                data-href="<?= esc($applyUrl) ?>"
                                data-target="_blank"
                                role="link"
                                tabindex="0"
                                aria-label="Apply to <?= esc($job['title'] ?? 'this job') ?>">
                                <div class="company-job-main">
                                    <h3><?= esc($job['title'] ?? 'Untitled role') ?></h3>
                                    <div class="company-job-meta">
                                        <span><i class="fas fa-map-marker-alt"></i><?= esc($job['location'] ?? 'Remote/Multiple') ?></span>
                                        <span><i class="fas fa-layer-group"></i><?= esc($formatExternalSource($job['source_platform'] ?? '', $applyUrl)) ?></span>
                                        <span><i class="fas fa-clock"></i><?= esc($job['posted_at_raw'] ?? 'Recently') ?></span>
                                    </div>
                                    <?php if (!empty($job['employment_type'])): ?>
                                        <div class="company-jobs-pills">
                                            <span class="skill-chip"><?= esc($job['employment_type']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div id="companyJobsEmptyState" class="company-jobs-empty-state" <?= $totalCount > 0 ? 'hidden' : '' ?>>
                        <span class="company-jobs-empty-icon"><i class="fas fa-search"></i></span>
                        <div>
                            <strong>Loading current openings at <?= esc($companyName) ?></strong>
                            <p>Matching roles will appear here as soon as they are available.</p>
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
    const cachedJobsUrl = <?= json_encode(base_url('candidate/company-jobs/cache')) ?>;
    const companyWebsite = <?= json_encode($companyWebsite) ?>;
    const companyCareerPage = <?= json_encode($companyCareerPage) ?>;
    const portalCount = <?= (int) $portalCount ?>;
    const externalList = document.getElementById('cachedExternalJobsList');
    const emptyState = document.getElementById('companyJobsEmptyState');
    const totalCountEl = document.getElementById('companyJobsTotalCount');
    const tabCountEl = document.getElementById('companyJobsTabCount');
    const initialExternalCount = <?= (int) $externalCount ?>;
    const initialTotalCount = <?= (int) $totalCount ?>;
    let cachePollTimer = null;
    let cachePollAttempts = 0;
    let lastPolledJobCount = 0;
    let stableCachePolls = 0;
    const maxCachePollAttempts = 12;

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

    document.addEventListener('click', (event) => {
        const card = event.target.closest('.company-job-card[data-href]');
        if (!card || event.target.closest('a, button')) {
            return;
        }

        const href = card.dataset.href || '';
        if (href && card.dataset.target === '_blank') {
            window.open(href, '_blank', 'noopener');
        } else if (href) {
            window.location.href = href;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const card = event.target.closest('.company-job-card[data-href]');
        if (!card) {
            return;
        }

        const href = card.dataset.href || '';
        if (href && card.dataset.target === '_blank') {
            event.preventDefault();
            window.open(href, '_blank', 'noopener');
        } else if (href) {
            event.preventDefault();
            window.location.href = href;
        }
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

        emptyState.classList.remove('is-discovering');

        emptyState.innerHTML = [
            '<span class="company-jobs-empty-icon"><i class="' + escapeHtml(iconClass) + '"></i></span>',
            '<div>',
            '  <strong>' + escapeHtml(title) + '</strong>',
            '  <p>' + escapeHtml(message) + '</p>',
            '</div>'
        ].join('');
    };

    const showDiscoveryLoading = () => {
        if (!emptyState) {
            return;
        }

        emptyState.classList.add('is-discovering');
        emptyState.innerHTML = [
            '<div class="company-discovery-animation" role="status" aria-live="polite">',
            '  <div class="company-discovery-heading">',
            '    <span class="company-discovery-icon" aria-hidden="true"><i class="fas fa-search"></i></span>',
            '    <div class="company-discovery-copy">',
            '      <strong>Finding current openings at ' + escapeHtml(companyName) + '</strong>',
            '      <span>Checking official careers and trusted job sources...</span>',
            '    </div>',
            '  </div>',
            '  <div class="company-discovery-progress" aria-hidden="true"><span></span></div>',
            '  <div class="company-discovery-placeholders" aria-hidden="true">',
            '    <div><i></i><span><b></b><em></em></span></div>',
            '    <div><i></i><span><b></b><em></em></span></div>',
            '    <div><i></i><span><b></b><em></em></span></div>',
            '  </div>',
            '</div>'
        ].join('');
    };

    const showNoResultsState = () => {
        setEmptyStateContent(
            'No openings available at ' + companyName,
            'There are no matching roles available here right now. Please check again later.',
            'fas fa-briefcase'
        );
        if (emptyState) {
            emptyState.hidden = portalCount > 0;
        }
    };

    const cleanSourceLabel = (sourceValue, applyUrl) => {
        const source = String(sourceValue || '').trim();
        const getHost = (value) => {
            try {
                return value && /^https?:\/\//i.test(value) ? new URL(value).hostname.toLowerCase().replace(/^www\./, '') : '';
            } catch (error) {
                return '';
            }
        };
        const host = getHost(source) || getHost(applyUrl);
        const platforms = [
            ['linkedin.', 'LinkedIn'],
            ['indeed.', 'Indeed'],
            ['glassdoor.', 'Glassdoor'],
            ['remotive.', 'Remotive'],
            ['remoteok.', 'Remote OK'],
            ['arbeitnow.', 'Arbeitnow']
        ];

        for (const [domainPart, label] of platforms) {
            if (source.toLowerCase().includes(domainPart.replace('.', '')) || host.includes(domainPart)) {
                return label;
            }
        }

        const officialHost = getHost(companyWebsite);
        if (host && (!officialHost || host === officialHost || host.endsWith('.' + officialHost))) {
            return companyName + ' Careers';
        }

        if (host) {
            return host;
        }

        return source && !/^https?:\/\//i.test(source) ? source : 'External source';
    };

    const jobCardHtml = (job) => {
        const url = escapeHtml(job.apply_url || job.url || '#');
        const title = escapeHtml(job.title || 'Untitled role');
        const location = escapeHtml(job.location || 'Remote/Multiple');
        const source = escapeHtml(cleanSourceLabel(job.source_platform || job.source || '', job.apply_url || job.url || ''));
        const posted = escapeHtml(job.posted_at_raw || job.posted_date || 'Recently');
        const employmentType = escapeHtml(job.employment_type || '');

        return `
            <article class="company-job-card company-job-card-external" data-href="${url}" data-target="_blank" role="link" tabindex="0" aria-label="Apply to ${title}">
                <div class="company-job-main">
                    <h3>${title}</h3>
                    <div class="company-job-meta">
                        <span><i class="fas fa-map-marker-alt"></i>${location}</span>
                        <span><i class="fas fa-layer-group"></i>${source}</span>
                        <span><i class="fas fa-clock"></i>${posted}</span>
                    </div>
                    ${employmentType ? `<div class="company-jobs-pills"><span class="skill-chip">${employmentType}</span></div>` : ''}
                </div>
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

    const stopCachePolling = () => {
        if (cachePollTimer) {
            window.clearTimeout(cachePollTimer);
            cachePollTimer = null;
        }
    };

    const pollCachedJobs = () => {
        if (document.hidden) {
            cachePollTimer = window.setTimeout(pollCachedJobs, 2000);
            return;
        }

        cachePollAttempts += 1;
        const params = new URLSearchParams({ company: companyName });

        fetch(cachedJobsUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((response) => response.json())
            .then((payload) => {
                const jobs = payload && Array.isArray(payload.jobs) ? payload.jobs : [];
                if (jobs.length > 0) {
                    renderExternalJobs(jobs);
                    if (jobs.length === lastPolledJobCount) {
                        stableCachePolls += 1;
                    } else {
                        lastPolledJobCount = jobs.length;
                        stableCachePolls = 0;
                    }

                    if (stableCachePolls >= 3 || cachePollAttempts >= maxCachePollAttempts) {
                        stopCachePolling();
                    } else {
                        cachePollTimer = window.setTimeout(pollCachedJobs, 2000);
                    }
                    return;
                }

                if (cachePollAttempts < maxCachePollAttempts) {
                    cachePollTimer = window.setTimeout(pollCachedJobs, 2000);
                } else {
                    showNoResultsState();
                }
            })
            .catch(() => {
                if (cachePollAttempts < maxCachePollAttempts) {
                    cachePollTimer = window.setTimeout(pollCachedJobs, 2000);
                } else if (renderedJobCardCount() === 0) {
                    showNoResultsState();
                }
            });
    };

    const startCachePolling = () => {
        stopCachePolling();
        cachePollAttempts = 0;
        lastPolledJobCount = 0;
        stableCachePolls = 0;
        cachePollTimer = window.setTimeout(pollCachedJobs, 1200);
    };

    const discoverJobs = (isAutomatic = false) => {
        if (!externalList || !emptyState) {
            return;
        }

        const previousHtml = externalList.innerHTML;
        showDiscoveryLoading();
        if (emptyState) {
            emptyState.hidden = portalCount > 0;
        }
        if (renderedJobCardCount() === 0) {
            externalList.innerHTML = '';
        }
        startCachePolling();

        const discoverParams = new URLSearchParams({
            company: companyName,
            limit: '10'
        });
        if (companyWebsite) {
            discoverParams.set('website', companyWebsite);
        }
        if (companyCareerPage) {
            discoverParams.set('career_url', companyCareerPage);
        }

        const discoveryController = new AbortController();
        const discoveryTimeout = window.setTimeout(() => discoveryController.abort(), 30000);

        fetch(discoverUrl + '?' + discoverParams.toString(), {
            headers: { 'Accept': 'application/json' },
            signal: discoveryController.signal
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
                window.clearTimeout(discoveryTimeout);
                if (!payload || payload.success === false || payload.error) {
                    throw new Error(payload && payload.error ? payload.error : 'Could not check latest jobs.');
                }
                if (Array.isArray(payload.jobs) && payload.jobs.length > 0) {
                    renderExternalJobs(payload.jobs);
                    stopCachePolling();
                }
            })
            .catch((error) => {
                window.clearTimeout(discoveryTimeout);
                if (previousHtml && !externalList.innerHTML) {
                    externalList.innerHTML = previousHtml;
                }
                setCounts(initialExternalCount);
                if (emptyState) {
                    setEmptyStateContent(
                        'Current openings are still being checked',
                        'This company did not return jobs quickly. Please check again shortly.',
                        'fas fa-exclamation-circle'
                    );
                    emptyState.hidden = renderedJobCardCount() > 0;
                }
            });
    };

    if (initialTotalCount === 0 && emptyState) {
        emptyState.hidden = false;
        window.setTimeout(() => discoverJobs(true), 250);
    }
    syncEmptyStateVisibility();
    window.addEventListener('beforeunload', stopCachePolling);
})();
</script>

<?= view('Layouts/candidate_footer') ?>
