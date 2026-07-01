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

        <form method="get" action="<?= esc($baseDiscoveryUrl) ?>" class="companies-filter-card company-discovery-search">
            <div class="company-discovery-search-grid">
                <div>
                    <label for="companyDiscoveryQ">Company, skill, or keyword</label>
                    <input id="companyDiscoveryQ" type="text" name="q" class="form-control" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Zoho, fintech, PHP, Bangalore">
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
                    <label for="companyDiscoveryJobs">Hiring status</label>
                    <select id="companyDiscoveryJobs" name="jobs" class="form-control">
                        <option value="">All companies</option>
                        <option value="active" <?= ($filters['jobs'] ?? '') === 'active' ? 'selected' : '' ?>>Actively hiring</option>
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
                <small><?= $allCompanyCount ?> profiles</small>
            </a>
            <?php foreach ($segments as $segment): ?>
                <?php
                $segmentKey = (string) ($segment['key'] ?? '');
                $segmentUrl = $baseDiscoveryUrl . '?' . http_build_query(['segment' => $segmentKey]);
                ?>
                <a href="<?= esc($segmentUrl) ?>" class="company-segment-card <?= $activeSegmentKey === $segmentKey ? 'is-active' : '' ?>">
                    <strong><?= esc($segment['label'] ?? 'Companies') ?></strong>
                    <small><?= (int) ($segment['count'] ?? 0) ?> profiles</small>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($companies)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h4 class="mb-2">No companies found</h4>
                    <p class="text-muted mb-0">Try another category, city, industry, or company name.</p>
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

            <?php if (!($viewAll ?? false) && isset($pager) && $pager->getPageCount() > 1): ?>
                <div class="text-center mt-4">
                    <?php $viewMoreUrl = $baseDiscoveryUrl . '?' . http_build_query(array_merge($activeFilters, $activeSegmentKey !== '' ? ['segment' => $activeSegmentKey] : [], ['view_all' => 1])); ?>
                    <a href="<?= esc($viewMoreUrl) ?>" class="btn btn-primary px-4">View More Companies <i class="fas fa-plus ml-2"></i></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= view('Layouts/candidate_footer') ?>
