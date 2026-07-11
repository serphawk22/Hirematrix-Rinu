<?= view('Layouts/candidate_header', ['title' => 'Find Jobs']) ?>
<style>
    body.dark .div.job-card.js-clickable-card,
    body.dark.candidate-app .jobs-page-jobboard .job-card {
        background-color: var(--card) !important;
        border: 1px solid #23343A !important;
    }

    .candidate-app .job-card-footer,
    div.jobs-page-jobboard.saved-jobs-jobboard .job-card-footer {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-top: 5px !important;
        padding-top: 5px !important;
        border-top: none !important;
    }

    .candidate-app .job-card-posted {
        font-size: 12.5px !important;
        color: var(--muted-foreground, #8a94a0) !important;
    }

    .candidate-app .job-card-company {
        font-size: 0.94rem !important;
        line-height: 1 !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
    }

    .candidate-app .jobs-page-jobboard .recommended-job-card,
    .candidate-app .jobs-page-jobboard .job-card {
        gap: 2px !important;
        height: 100% !important;
        min-height: 0 !important;
        width: 100% !important;
        position: relative !important;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease !important;
    }

    .jobs-page-jobboard .job-card-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0rem;
    }

    .job-card-tools-wrapper {
        position: absolute;
        bottom: none !important;
        right: 34px !important;
        /* Positioned side-by-side with the save button */
        z-index: 100;
        /* Ensure wrapper is above card content */
        display: block;
    }

    .job-card-saveing-btn {
        background: none;
        border: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--foreground, #12181f);
        cursor: pointer;
        padding: 0;
        outline: none !important;
        box-shadow: none !important;
    }

    .job-card-actions-group {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }

    div.jobs-page-jobboard .recommended-job-pane .row.g-4,
    .row.g-4.mb-4 {
        --bs-gutter-x: 16px !important;
        --bs-gutter-y: 16px !important;
        margin-left: calc(-1 * var(--bs-gutter-x) / 2) !important;
        margin-right: calc(-1 * var(--bs-gutter-x) / 2) !important;
        padding-top: 10px !important;
        /* gives translateY(-2px) room to breathe */
    }

    div.jobs-page-jobboard .recommended-job-pane .row.g-4,
    .row.g-4.mb-4>[class*="col-"] {
        padding-left: calc(var(--bs-gutter-x) / 2) !important;
        padding-right: calc(var(--bs-gutter-x) / 2) !important;
        margin-bottom: 10px !important;
        /* was 24px, and no longer stacked with g-4's own gutter-y */
    }

    .candidate-app .jobs-page-jobboard .recommended-tab-loading {
        align-items: center !important;
        background: var(--candidate-surface, #fff) !important;
        border: 1px solid var(--candidate-line, #d7e5f2) !important;
        border-radius: 8px !important;
        color: var(--candidate-muted, #64748b) !important;
        display: flex !important;
        gap: 10px !important;
        justify-content: center !important;
        min-height: 180px !important;
        padding: 28px !important;
        width: 100% !important;
    }

    .candidate-app .jobs-page-jobboard .recommended-tab-spinner {
        animation: recommendedTabSpin .75s linear infinite !important;
        border: 2px solid rgba(31, 183, 181, .2) !important;
        border-radius: 999px !important;
        border-top-color: var(--primary, #1FB7B5) !important;
        flex: 0 0 20px !important;
        height: 20px !important;
        width: 20px !important;
    }

    @keyframes recommendedTabSpin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
<?php
$allJobsAreExternal = $allJobsAreExternal ?? false;

$recommendationType = $recommendationType ?? 'skills';
$filters = $filters ?? [];
$suggestedJobs = $suggestedJobs ?? [];
$suggestedJobsByApplies = $suggestedJobsByApplies ?? [];
$suggestedJobsBySkills = $suggestedJobsBySkills ?? [];
$suggestedJobsByPreferences = $suggestedJobsByPreferences ?? [];
$suggestedJobsByAi = $suggestedJobsByAi ?? [];
$loadedRecommendationTypes = $loadedRecommendationTypes ?? ['applies', 'skills', 'preferences', 'ai'];
$recommendationCounts = $recommendationCounts ?? [];
$candidateSkills = $candidateSkills ?? [];
$candidateInterests = $candidateInterests ?? [];
$behavior = $behavior ?? [];
$showFilters = $showFilters ?? false;
$totalJobs = $totalJobs ?? 0;
$jobs = $jobs ?? [];
$locations = $locations ?? [];
$categories = $categories ?? [];
$experienceLevels = $experienceLevels ?? [];
$employmentTypes = $employmentTypes ?? [];
$savedJobIds = $savedJobIds ?? [];
$appliedJobMap = $appliedJobMap ?? [];
$salaryRanges = [
    '' => 'Any Salary',
    'under_3' => 'Under 3 LPA',
    '3_5' => '3 - 5 LPA',
    '5_8' => '5 - 8 LPA',
    '8_12' => '8 - 12 LPA',
    '12_plus' => '12+ LPA',
];
$workModes = [
    '' => 'Any mode',
    'remote' => 'Remote',
    'hybrid' => 'Hybrid',
    'onsite' => 'On-site',
];

$recommendationSets = [
    'applies' => $suggestedJobsByApplies,
    'skills' => $suggestedJobsBySkills,
    'preferences' => $suggestedJobsByPreferences,
    'ai' => $suggestedJobsByAi,
];
if (!array_key_exists($recommendationType, $recommendationSets)) {
    $recommendationType = 'skills';
}
$activeRecommendedJobs = $recommendationSets[$recommendationType];
$recommendationLabels = [
    'applies' => 'Based On Applies',
    'skills' => 'Based On Skills',
    'preferences' => 'Preferences / Interests',
    'ai' => 'Other Recommendations',
];
$recommendationCountLabel = static function (string $type, array $jobs): string {
    $visibleCount = count($jobs);
    if ($visibleCount > 0) {
        return (string) $visibleCount;
    }

    return '';
};
$jobsHeroTitle = $showFilters ? 'Browse Jobs' : 'Jobs Matching Your Profile';
$jobsHeroSubtitle = $showFilters
    ? 'Use live filters to narrow roles by company, location, experience, job type, salary, and work mode.'
    : 'Based on your skills, preferences, and application history';

$jobIconSet = [
    'developer' => 'fas fa-code',
    'engineer' => 'fas fa-cogs',
    'designer' => 'fas fa-palette',
    'manager' => 'fas fa-chart-line',
    'data' => 'fas fa-database',
    'marketing' => 'fas fa-bullhorn',
    'product' => 'fas fa-briefcase',
    'frontend' => 'fas fa-code',
    'backend' => 'fas fa-server',
    'full stack' => 'fas fa-layer-group',
];

$pickJobIcon = static function (string $title) use ($jobIconSet): string {
    $needle = strtolower($title);
    foreach ($jobIconSet as $key => $icon) {
        if (str_contains($needle, $key)) {
            return $icon;
        }
    }

    return 'fas fa-briefcase';
};

$resolveAssetUrl = static function (string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
        return $path;
    }
    return base_url(ltrim($path, '/'));
};

$formatPostedMeta = static function (?string $createdAt): ?string {
    $raw = trim((string) $createdAt);
    if ($raw === '') {
        return null;
    }

    try {
        $postedAt = new \DateTime($raw);
        $postedDay = (clone $postedAt)->setTime(0, 0, 0);
        $today = new \DateTime('today');
        $interval = $postedDay->diff($today);
        $days = $interval->invert === 1 ? 0 : (int) $interval->days;
        $relative = $days === 0 ? 'today' : ($days === 1 ? '1 day ago' : $days . ' days ago');
        return 'Posted on ' . $postedAt->format('d M Y') . ' • ' . $relative;
    } catch (\Throwable $e) {
        return null;
    }
};

$formatBadgeText = static function (string $value, int $limit = 22): string {
    $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
    if ($value === '') {
        return '';
    }

    return strlen($value) > $limit ? rtrim(substr($value, 0, $limit - 3)) . '...' : $value;
};

$normaliseBadgeText = static function (string $value): string {
    return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(strip_tags($value))) ?? '');
};

$isRemoteJob = static function (array $job): bool {
    $remoteText = strtolower(trim(implode(' ', [
        (string) ($job['location'] ?? ''),
        (string) ($job['work_mode'] ?? ''),
        (string) ($job['remote'] ?? ''),
        (string) ($job['employment_type'] ?? ''),
    ])));

    return (bool) preg_match('/\b(remote|work from home|wfh|anywhere|distributed)\b/', $remoteText);
};

$pickRequiredSkillBadges = static function (array $job, int $limit = 3) use ($formatBadgeText, $normaliseBadgeText): array {
    $skillSource = trim((string) ($job['required_skills'] ?? $job['skills_required'] ?? $job['skills'] ?? ''));
    $descriptionSource = trim((string) ($job['description'] ?? $job['job_description'] ?? $job['requirements'] ?? ''));
    $parts = $skillSource !== '' ? (preg_split('/[,|\/;]+/', $skillSource) ?: []) : [];

    if ($descriptionSource !== '') {
        $descriptionKey = ' ' . $normaliseBadgeText($descriptionSource) . ' ';
        $knownSkills = [
            'PHP',
            'Laravel',
            'CodeIgniter',
            'JavaScript',
            'TypeScript',
            'React',
            'Vue',
            'Angular',
            'Node.js',
            'Python',
            'Django',
            'Flask',
            'Java',
            'Spring Boot',
            'Kotlin',
            'Swift',
            'SQL',
            'MySQL',
            'PostgreSQL',
            'MongoDB',
            'Redis',
            'AWS',
            'Azure',
            'GCP',
            'Docker',
            'Kubernetes',
            'Git',
            'REST API',
            'GraphQL',
            'HTML',
            'CSS',
            'Tailwind',
            'Bootstrap',
            'Figma',
            'Excel',
            'ETL',
            'Data Analysis',
            'Machine Learning',
            'Communication',
            'Project Management',
            'Customer Support',
            'Salesforce',
            'SEO'
        ];

        foreach ($knownSkills as $knownSkill) {
            $knownSkillKey = $normaliseBadgeText($knownSkill);
            if ($knownSkillKey !== '' && str_contains($descriptionKey, ' ' . $knownSkillKey . ' ')) {
                $parts[] = $knownSkill;
            }
        }
    }

    if (empty($parts)) {
        return [];
    }

    $titleKey = $normaliseBadgeText((string) ($job['title'] ?? ''));
    $badges = [];
    $seen = [];

    foreach ($parts as $part) {
        $badge = $formatBadgeText((string) $part, 20);
        $badgeKey = $normaliseBadgeText($badge);

        if ($badgeKey === '' || isset($seen[$badgeKey])) {
            continue;
        }

        if (
            preg_match('#(?:https?://|www\.|[a-z0-9-]+\.(?:com|in|org|net|io|co|ai|dev|careers|jobs)\b)#i', $badge)
            || str_contains($badgeKey, 'careers ')
            || str_contains($badgeKey, 'career ')
        ) {
            continue;
        }

        if ($titleKey !== '' && (str_contains($titleKey, $badgeKey) || str_contains($badgeKey, $titleKey))) {
            continue;
        }

        if (preg_match('/^\?+/', $badge) || strlen($badgeKey) < 2) {
            continue;
        }

        $seen[$badgeKey] = true;
        $badges[] = $badge;

        if (count($badges) >= $limit) {
            break;
        }
    }

    return $badges;
};

$renderRecommendedPane = static function (string $recType, array $jobs, string $tabLabel) use ($recommendationType, $formatPostedMeta, $pickRequiredSkillBadges, $isRemoteJob, $savedJobIds, $appliedJobMap, $resolveAssetUrl, $hasBaseResume, $primaryResumeId, $loadedRecommendationTypes): string {
    ob_start();
    $isActivePane = $recommendationType === $recType;
    $isLoadedPane = in_array($recType, (array) $loadedRecommendationTypes, true);
    ?>
    <div class="recommended-job-pane <?= $isActivePane ? '' : 'd-none' ?>" data-rec-pane="<?= esc($recType) ?>"
        data-rec-label="<?= esc($tabLabel) ?>" data-rec-loaded="<?= $isLoadedPane ? '1' : '0' ?>">
        <?php if (!empty($jobs)): ?>
            <div class="row g-4 mb-4 alljobs-scope">
                <?php foreach ($jobs as $job): ?>
                    <?php
                    $stripBadChars = static function (string $text): string {
                        // Collapse runs of 2+ literal '?' (typical artifact of lost emoji/unicode chars)
                        $text = preg_replace('/\?{2,}\s*/u', '', $text);
                        return trim($text);
                    };

                    $title = $stripBadChars((string) ($job['title'] ?? 'Untitled Role'));
                    $company = (string) ($job['company'] ?? 'Company');
                    $location = $stripBadChars((string) ($job['location'] ?? 'N/A'));
                    $postedMeta = $formatPostedMeta($job['created_at'] ?? null);
                    $isSaved = in_array((int) ($job['id'] ?? 0), $savedJobIds, true);
                    $appliedStatus = $appliedJobMap[(int) ($job['id'] ?? 0)] ?? null;
                    $type = strtolower((string) ($job['employment_type'] ?? ''));
                    $typeBadge = str_contains($type, 'part') ? 'badge-secondary' : 'badge-primary';
                    $companyInitial = strtoupper(substr($company, 0, 1) ?: 'C');
                    $companyLogo = trim((string) ($job['company_logo'] ?? ''));
                    $score = (int) round((float) ($job['match_score'] ?? 0));
                    $matchPct = max(10, min(100, (int) round($score)));
                    $matchLabel = $score > 0 ? max(10, min(100, $score)) . '% match' : 'Open role';
                    $isExternalJob = (int) ($job['is_external'] ?? 0) === 1;
                    $externalSource = trim((string) ($job['external_source'] ?? ''));
                    $isVisited = (int) ($job['visited_flag'] ?? 0) === 1;
                    $requiredSkillBadges = $pickRequiredSkillBadges($job);
                    $showRemoteBadge = $isRemoteJob($job);
                    ?>
                    <div class="col-md-6">
                        <?php
                        $jobLink = $isExternalJob && !empty($job['external_apply_url'])
                            ? esc($job['external_apply_url'])
                            : base_url('job/' . $job['id']);
                        ?>
                        <div class="job-card js-clickable-card <?= $appliedStatus !== null ? 'is-applied' : '' ?>"
                            data-href="<?= $jobLink ?>" data-job-id="<?= (int) $job['id'] ?>" <?= $isExternalJob ? 'data-external="1"' : '' ?> role="link" tabindex="0" style="cursor:pointer;">

                            <div class="job-card-body">
                                <h3 class="job-card-title"><?= esc($title) ?></h3>
                                <p class="job-card-company"><?= esc($company) ?></p>
                                <div class="job-card-meta">
                                    <span><i class="fas fa-map-pin"></i> <?= esc($location) ?></span>
                                    <?php if ($postedMeta !== null): ?>
                                        <span><i class="fas fa-clock"></i> <?= esc($postedMeta) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="job-card-tags">
                                    <span class="badge <?= $typeBadge ?>"><?= esc($job['employment_type'] ?: 'Full Time') ?></span>
                                    <?php if ($showRemoteBadge): ?>
                                        <span class="badge badge-warning">Remote</span>
                                    <?php endif; ?>
                                    <?php foreach ($requiredSkillBadges as $requiredSkillBadge): ?>
                                        <span class="badge badge-secondary"><?= esc($requiredSkillBadge) ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($appliedStatus !== null): ?>
                                        <span class="badge job-card-applied-badge"><i class="fas fa-check-circle"></i> Applied</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($job['match_reason'])): ?>
                                    <div class="small mb-2"><?= esc($job['match_reason']) ?></div>
                                <?php endif; ?>

                                <div class="progress-container">
                                    <div class="progress-track">
                                        <div class="progress-bar-custom candidate-progress-fill"
                                            style="--candidate-progress: <?= $matchPct ?>%;"></div>
                                    </div>
                                    <span class="progress-label"><?= $matchPct ?>% ATS match</span>
                                </div>
                                <div class="job-card-footer mt-auto">
                                    <span class="job-card-posted">
                                        <span class="saved-job-visited-note">
                                            <a href="<?= $isExternalJob && !empty($job['external_apply_url'])
                                                ? esc($job['external_apply_url'])
                                                : base_url('job/' . $job['id']) ?>"
                                                class="view-details js-mark-visited <?= $isVisited ? 'is-viewed' : 'is-unviewed' ?>"
                                                data-job-id="<?= (int) $job['id'] ?>" <?= $isExternalJob ? 'target="_blank"' : '' ?>
                                                style="text-decoration:none;">
                                                <span class="viewed-action-mark <?= $isVisited ? 'is-viewed' : 'is-unviewed' ?>">
                                                    <i class="<?= $isVisited ? 'fas fa-eye' : 'far fa-eye' ?>"></i>
                                                    <?= $isVisited ? 'Viewed' : 'Not viewed' ?>
                                                </span>
                                                <span> </span>
                                            </a>
                                        </span>
                                    </span>

                                    <div class="job-card-actions-group">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary py-0 px-2 job-card-saveing-btn js-save-job-toggle <?= $isSaved ? 'is-saved' : '' ?>"
                                            aria-label="<?= $isSaved ? 'Saved job' : 'Save job' ?>"
                                            title="<?= $isSaved ? 'Saved' : 'Save Job' ?>"
                                            data-save-url="<?= base_url($isSaved ? 'job/unsave/' . $job['id'] : 'job/save/' . $job['id']) ?>"
                                            data-job-id="<?= (int) $job['id'] ?>" data-saved="<?= $isSaved ? '1' : '0' ?>"
                                            data-save-label-save="Save Job" data-save-label-saved="Saved">
                                            <i class="<?= $isSaved ? 'fas' : 'far' ?> fa-bookmark"></i>
                                        </button>

                                        <div class="job-card-tools-wrapper">
                                            <button type="button" class="btn btn-sm btn-outline-secondary job-card-tools-toggle"
                                                title="Tools" onclick="event.stopPropagation();">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="job-card-tools-dropdown">
                                                <?php if ($primaryResumeId > 0 || ($hasBaseResume ?? false)): ?>
                                                    <button type="button" class="job-card-tools-item js-analyze-ats"
                                                        data-job-id="<?= (int) $job['id'] ?>" data-resume-id="<?= $primaryResumeId ?>">
                                                        Analyze ATS Match
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="job-card-tools-item"
                                                    onclick="event.stopPropagation(); generateCoverLetter(<?= (int) $job['id'] ?>)">
                                                    AI Cover Letter
                                                </button>
                                                <button type="button" class="job-card-tools-item"
                                                    onclick="event.stopPropagation(); shareJob(<?= (int) $job['id'] ?>)">
                                                    Share Job
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </div>


                            </div>
                            <div class="job-card-icon">
                                <?php
                                $displayLogo = "";
                                $useFavicon = false;
                                if (!empty($companyLogo)) {
                                    $displayLogo = $resolveAssetUrl($companyLogo);
                                } elseif (!empty($job['company_website'])) {
                                    $domain = parse_url($job['company_website'], PHP_URL_HOST) ?? $job['company_website'];
                                    $displayLogo = "https://www.google.com/s2/favicons?domain=" . urlencode($domain) . "&sz=96";
                                    $useFavicon = true;
                                }
                                ?>
                                <?php if ($displayLogo !== ''): ?>
                                    <img src="<?= esc($displayLogo) ?>" alt="<?= esc($company) ?>"
                                        class="job-card-logo <?= $useFavicon ? 'is-favicon' : '' ?>"
                                        onerror="this.onerror=null; this.parentElement.innerHTML='<span><?= esc($companyInitial) ?></span>';">
                                <?php else: ?>
                                    <span><?= esc($companyInitial) ?></span>
                                <?php endif; ?>
                            </div>


                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-star"></i>
                <h5>No suitable jobs found</h5>
                <p>No matches available in this recommendation view right now.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
};

$request = service('request');
$queryParams = $request->getGet();
$buildJobsUrl = static function (array $overrides = [], array $remove = []) use ($queryParams): string {
    $params = $queryParams;
    foreach ($remove as $key) {
        unset($params[$key]);
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '' || $value === []) {
            unset($params[$key]);
            continue;
        }
        $params[$key] = $value;
    }

    return base_url('jobs') . (empty($params) ? '' : '?' . http_build_query($params));
};

$activeFilterChips = [];
$addActiveChip = function (string $label, string $url) use (&$activeFilterChips): void {
    $activeFilterChips[] = [
        'label' => $label,
        'url' => $url,
    ];
};

if (!empty($filters['search'])) {
    $addActiveChip('Search: ' . (string) $filters['search'], $buildJobsUrl(['search' => null], ['search']));
}
if (!empty($filters['category'])) {
    $addActiveChip('Category: ' . (string) $filters['category'], $buildJobsUrl(['category' => null], ['category']));
}
if (!empty($filters['location'])) {
    $addActiveChip('Location: ' . (string) $filters['location'], $buildJobsUrl(['location' => null], ['location']));
}
if (!empty($filters['work_mode'])) {
    $workModeLabel = $workModes[(string) $filters['work_mode']] ?? (string) $filters['work_mode'];
    $addActiveChip('Mode: ' . $workModeLabel, $buildJobsUrl(['work_mode' => null], ['work_mode']));
}
if (!empty($filters['salary_range'])) {
    $salaryLabel = $salaryRanges[(string) $filters['salary_range']] ?? (string) $filters['salary_range'];
    $addActiveChip('Salary: ' . $salaryLabel, $buildJobsUrl(['salary_range' => null], ['salary_range']));
}
foreach ((array) ($filters['employment_type'] ?? []) as $employmentType) {
    $employmentType = (string) $employmentType;
    $remaining = array_values(array_filter((array) ($filters['employment_type'] ?? []), static fn($value) => (string) $value !== $employmentType));
    $addActiveChip('Type: ' . $employmentType, $buildJobsUrl(['employment_type' => $remaining], []));
}
foreach ((array) ($filters['experience_level'] ?? []) as $experienceLevel) {
    $experienceLevel = (string) $experienceLevel;
    $remaining = array_values(array_filter((array) ($filters['experience_level'] ?? []), static fn($value) => (string) $value !== $experienceLevel));
    $addActiveChip('Experience: ' . $experienceLevel, $buildJobsUrl(['experience_level' => $remaining], []));
}
if (!empty($filters['posted_within'])) {
    $postedLabels = ['1' => 'Today', '7' => 'This week', '30' => 'Last one month'];
    $postedLabel = $postedLabels[(string) $filters['posted_within']] ?? (string) $filters['posted_within'];
    $addActiveChip('Posted: ' . $postedLabel, $buildJobsUrl(['posted_within' => null], ['posted_within']));
}

$activeFilterCount = count($activeFilterChips);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@700;800&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="jobs-page-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-magic"></i> AI-powered matching</span>
                <h1 class="page-board-title"><?= esc($jobsHeroTitle) ?></h1>
                <p class="page-board-subtitle"><?= esc($jobsHeroSubtitle) ?></p>
                <?php if ($showFilters): ?>
                    <div class="custom-breadcrumbs">
                        <a href="<?= base_url('candidate/dashboard') ?>">Home</a>
                        <span class="mx-2 slash">/</span>
                        <span><strong>Browse Jobs</strong></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="site-section pt-0">
        <div class="container">
            <form method="GET" action="<?= base_url('jobs') ?>" id="filterForm">
                <input type="hidden" name="search" id="hiddenSearch" value="<?= esc($filters['search'] ?? '') ?>">
                <input type="hidden" name="tab" id="activeTabInput" value="<?= esc($activeTab) ?>">
                <input type="hidden" name="rec" id="recommendationTypeInput" value="<?= esc($recommendationType) ?>">
                <?php if (!empty($filters['company'])): ?>
                    <input type="hidden" name="company" value="<?= esc($filters['company']) ?>">
                <?php endif; ?>

                <div class="jobs-layout <?= $showFilters ? '' : 'jobs-layout-no-sidebar' ?>">

                    <?php if ($showFilters): ?>
                        <div class="sidebar">
                            <div class="sidebar-head">
                                <h5><i class="fas fa-sliders-h"></i> Filters</h5>
                                <?php
                                $clearUrl = !empty($filters['company'])
                                    ? base_url('jobs?company=' . urlencode($filters['company']))
                                    : base_url('jobs?tab=all');
                                ?>
                                <?php if ($activeFilterCount > 0): ?>
                                    <a href="<?= esc($clearUrl) ?>" class="clear-link" data-jobs-filter-link="1">Clear all</a>
                                <?php endif; ?>
                            </div>

                            <?php
                            $meaningfulCategories = array_values(array_filter(
                                array_unique(array_column($categories, 'category')),
                                static fn($v) => strtolower(trim($v)) !== 'external' && trim($v) !== ''
                            ));
                            ?>
                            <?php if (!empty($meaningfulCategories)): ?>
                                <div class="filter-section">
                                    <span class="filter-label">Category</span>
                                    <select name="category" onchange="submitFilters()">
                                        <option value="">All Categories</option>
                                        <?php foreach ($meaningfulCategories as $cat): ?>
                                            <option value="<?= esc($cat) ?>" <?= ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                                <?= esc($cat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php
                            $uniqueLocations = array_values(array_unique(array_column($locations, 'location')));
                            ?>
                            <?php if (!empty($uniqueLocations)): ?>
                                <div class="filter-section">
                                    <span class="filter-label">Location</span>
                                    <select name="location" onchange="submitFilters()">
                                        <option value="">All Locations</option>
                                        <?php foreach ($uniqueLocations as $loc): ?>
                                            <option value="<?= esc($loc) ?>" <?= ($filters['location'] ?? '') === $loc ? 'selected' : '' ?>>
                                                <?= esc($loc) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php
                            $jobWorkModes = array_filter(array_unique(array_map(static function ($j) {
                                return strtolower(trim((string) ($j['work_mode'] ?? '')));
                            }, $jobs)));
                            $availableWorkModes = array_filter($workModes, static function ($label, $val) use ($jobWorkModes) {
                                return $val === '' || in_array(strtolower($val), $jobWorkModes, true);
                            }, ARRAY_FILTER_USE_BOTH);
                            ?>
                            <?php if (!empty($availableWorkModes)): ?>
                                <div class="filter-section">
                                    <span class="filter-label">Work Mode</span>
                                    <select name="work_mode" onchange="submitFilters()">
                                        <?php foreach ($availableWorkModes as $modeValue => $modeLabel): ?>
                                            <option value="<?= esc($modeValue) ?>" <?= ($filters['work_mode'] ?? '') === $modeValue ? 'selected' : '' ?>>
                                                <?= esc($modeLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php
                            $jobSalaries = array_filter(array_unique(array_map(static function ($j) {
                                return trim((string) ($j['salary_range'] ?? ''));
                            }, $jobs)));
                            $hasSalaryData = !empty($jobSalaries);
                            ?>
                            <?php if ($hasSalaryData): ?>
                                <div class="filter-section">
                                    <span class="filter-label">Salary Range</span>
                                    <select name="salary_range" onchange="submitFilters()">
                                        <?php foreach ($salaryRanges as $rangeValue => $rangeLabel): ?>
                                            <option value="<?= esc($rangeValue) ?>" <?= ($filters['salary_range'] ?? '') === $rangeValue ? 'selected' : '' ?>>
                                                <?= esc($rangeLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php
                            $uniqueEmploymentTypes = array_values(array_filter(
                                array_unique(array_map(static fn($row) => trim((string) ($row['employment_type'] ?? '')), $employmentTypes)),
                                static fn($type) => $type !== ''
                            ));
                            ?>
                            <?php if (!empty($uniqueEmploymentTypes)): ?>
                                <div class="filter-section">
                                    <span class="filter-label">Job Type</span>
                                    <?php foreach ($uniqueEmploymentTypes as $type): ?>
                                        <label class="check-item">
                                            <input type="checkbox" name="employment_type[]" value="<?= esc($type) ?>"
                                                <?= in_array($type, (array) ($filters['employment_type'] ?? []), true) ? 'checked' : '' ?> onchange="submitFilters()">
                                            <span class="check-box"></span>
                                            <span class="check-text"><?= esc($type) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $uselessExp = ['not specified', 'not_specified', ''];
                            $meaningfulExp = array_values(array_filter(
                                array_unique(array_column($experienceLevels, 'experience_level')),
                                static fn($v) => !in_array(strtolower(trim($v)), $uselessExp, true)
                            ));
                            ?>
                            <?php if (!empty($meaningfulExp)): ?>
                                <div class="filter-section">
                                    <span class="filter-label">Experience</span>
                                    <?php foreach ($meaningfulExp as $exp): ?>
                                        <label class="check-item">
                                            <input type="checkbox" name="experience_level[]" value="<?= esc($exp) ?>"
                                                <?= in_array($exp, (array) ($filters['experience_level'] ?? []), true) ? 'checked' : '' ?> onchange="submitFilters()">
                                            <span class="check-box"></span>
                                            <span class="check-text"><?= esc($exp) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $hasDateData = !empty(array_filter(array_map(static function ($j) {
                                return trim((string) ($j['created_at'] ?? ''));
                            }, $jobs)));
                            ?>
                            <?php if ($hasDateData): ?>
                                <div class="filter-section">
                                    <span class="filter-label">Posted Within</span>
                                    <?php foreach (['1' => 'Today', '7' => 'This week', '30' => 'Last one month', '' => 'Any time'] as $val => $label): ?>
                                        <label class="check-item">
                                            <input type="radio" name="posted_within" value="<?= $val ?>"
                                                <?= ($filters['posted_within'] ?? '') == $val ? 'checked' : '' ?>
                                                onchange="submitFilters()">
                                            <span class="radio-box"></span>
                                            <span class="check-text"><?= $label ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="jobs-main">

                        <?php if ($showFilters): ?>
                            <button type="button" class="mobile-filter-toggle" onclick="toggleMobileFilters()">
                                <span><i class="fas fa-sliders-h mobile-filter-icon"></i>Filters</span>
                                <i class="fas fa-chevron-down" id="mobileFilterIcon"></i>
                            </button>

                            <div class="mobile-filter-drawer" id="mobileFilterDrawer">
                                <div class="mobile-filter-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="mobile-filter-label">Category</label>
                                            <select id="mobileCategory" class="form-control mobile-filter-select">
                                                <option value="">All</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= esc($cat['category']) ?>" <?= ($filters['category'] ?? '') == $cat['category'] ? 'selected' : '' ?>>
                                                        <?= esc($cat['category']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="mobile-filter-label">Location</label>
                                            <select id="mobileLocation" class="form-control mobile-filter-select">
                                                <option value="">All</option>
                                                <?php foreach ($locations as $loc): ?>
                                                    <option value="<?= esc($loc['location']) ?>" <?= ($filters['location'] ?? '') == $loc['location'] ? 'selected' : '' ?>>
                                                        <?= esc($loc['location']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="mobile-filter-label">Experience</label>
                                            <select id="mobileExperience" class="form-control mobile-filter-select">
                                                <option value="">All</option>
                                                <?php foreach ($experienceLevels as $exp): ?>
                                                    <option value="<?= esc($exp['experience_level']) ?>"
                                                        <?= in_array($exp['experience_level'], (array) ($filters['experience_level'] ?? []), true) ? 'selected' : '' ?>>
                                                        <?= esc($exp['experience_level']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="mobile-filter-label">Job Type</label>
                                            <select id="mobileEmploymentType" class="form-control mobile-filter-select">
                                                <option value="">All</option>
                                                <?php foreach ($employmentTypes as $type): ?>
                                                    <option value="<?= esc($type['employment_type']) ?>"
                                                        <?= in_array($type['employment_type'], (array) ($filters['employment_type'] ?? []), true) ? 'selected' : '' ?>>
                                                        <?= esc($type['employment_type']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="mobile-filter-label">Work Mode</label>
                                            <select id="mobileWorkMode" class="form-control mobile-filter-select">
                                                <?php foreach ($workModes as $modeValue => $modeLabel): ?>
                                                    <option value="<?= esc($modeValue) ?>" <?= ($filters['work_mode'] ?? '') === $modeValue ? 'selected' : '' ?>>
                                                        <?= esc($modeLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="mobile-filter-label">Salary Range</label>
                                            <select id="mobileSalaryRange" class="form-control mobile-filter-select">
                                                <?php foreach ($salaryRanges as $rangeValue => $rangeLabel): ?>
                                                    <option value="<?= esc($rangeValue) ?>" <?= ($filters['salary_range'] ?? '') === $rangeValue ? 'selected' : '' ?>>
                                                        <?= esc($rangeLabel) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" onclick="applyMobileFilters()"
                                            class="mobile-filter-action mobile-filter-apply">Apply</button>
                                        <a href="<?= !empty($filters['company']) ? esc(base_url('jobs?company=' . urlencode($filters['company']))) : base_url('jobs?tab=all') ?>"
                                            data-jobs-filter-link="1"
                                            class="mobile-filter-action mobile-filter-clear">Clear</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (session()->getFlashdata('career_suggestion')):
                            $suggestion = session()->getFlashdata('career_suggestion'); ?>
                            <div class="career-suggestion-banner">
                                <div>
                                    <strong class="career-suggestion-title"><i class="fas fa-rocket"></i> Career Transition
                                        Opportunity!</strong>
                                    <p class="career-suggestion-text"><?= esc($suggestion['message']) ?></p>
                                </div>
                                <a href="<?= base_url('career-transition') ?>" class="career-suggestion-link">
                                    <i class="fas fa-graduation-cap"></i> Get Roadmap
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($showFilters): ?>
                            <div class="<?= $activeTab !== 'all' ? 'd-none' : '' ?>">
                                <?php if (!$showFilters): ?>
                                    <div class="results-bar">
                                        <span class="results-count">Use the header search bar to open filters for refining job
                                            results.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="jobs-results-summary">
                                    <div class="jobs-results-summary-copy">
                                        <div class="jobs-results-summary-kicker">Browse jobs</div>
                                        <h2 class="jobs-results-summary-title">
                                            <?php if (!empty($filters['search'])): ?>
                                                Results for "<?= esc($filters['search']) ?>"
                                            <?php else: ?>
                                                <?= $activeFilterCount > 0 ? 'Filtered jobs' : 'All jobs' ?>
                                            <?php endif; ?>
                                        </h2>
                                        <p class="jobs-results-summary-text">
                                            <strong><?= $totalJobs ?></strong> job<?= $totalJobs != 1 ? 's' : '' ?>
                                            found<?= $activeFilterCount > 0 ? ' with ' . $activeFilterCount . ' active filter' . ($activeFilterCount === 1 ? '' : 's') : '' ?>.
                                        </p>
                                    </div>
                                    <?php
                                    $clearAllUrl = !empty($filters['company'])
                                        ? base_url('jobs?company=' . urlencode($filters['company']))
                                        : base_url('jobs?tab=all');
                                    ?>
                                    <?php if ($activeFilterCount > 0): ?>
                                        <a href="<?= esc($clearAllUrl) ?>"
                                            class="btn btn-outline-secondary btn-sm jobs-results-clear"
                                            data-jobs-filter-link="1">Clear all filters</a>
                                    <?php endif; ?>
                                    <?php if ($activeFilterCount > 0): ?>
                                        <div class="active-filter-chips">
                                            <?php foreach ($activeFilterChips as $chip): ?>
                                                <a href="<?= esc($chip['url']) ?>" class="active-filter-chip"
                                                    data-jobs-filter-link="1">
                                                    <span><?= esc($chip['label']) ?></span>
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($jobs)): ?>
                                    <div class="row g-4 mb-4">
                                        <?php foreach ($jobs as $job): ?>
                                            <?php
                                            $stripBadChars = static function (string $text): string {
                                                // Collapse runs of 2+ literal '?' (typical artifact of lost emoji/unicode chars)
                                                $text = preg_replace('/\?{2,}\s*/u', '', $text);
                                                return trim($text);
                                            };

                                            $title = $stripBadChars((string) ($job['title'] ?? 'Untitled Role'));
                                            $company = (string) ($job['company'] ?? 'Company');
                                            $location = (string) ($job['location'] ?? 'N/A');
                                            $postedMeta = $formatPostedMeta($job['created_at'] ?? null);
                                            $isSaved = in_array((int) ($job['id'] ?? 0), $savedJobIds, true);
                                            $appliedStatus = $appliedJobMap[(int) ($job['id'] ?? 0)] ?? null;
                                            $type = strtolower((string) ($job['employment_type'] ?? ''));
                                            $typeBadge = str_contains($type, 'part') ? 'badge-secondary' : 'badge-primary';
                                            $companyInitial = strtoupper(substr($company, 0, 1) ?: 'C');
                                            $companyLogo = trim((string) ($job['company_logo'] ?? ''));
                                            $score = (int) round((float) ($job['match_score'] ?? 0));
                                            $matchPct = max(10, min(100, (int) round($score)));
                                            $matchLabel = $score > 0 ? max(10, min(100, $score)) . '% match' : 'Open role';
                                            $isExternalJob = (int) ($job['is_external'] ?? 0) === 1;
                                            $externalSource = trim((string) ($job['external_source'] ?? ''));
                                            $isVisited = (int) ($job['visited_flag'] ?? 0) === 1;
                                            $requiredSkillBadges = $pickRequiredSkillBadges($job);
                                            $showRemoteBadge = $isRemoteJob($job);
                                            ?>
                                            <div class="col-md-12">
                                                <?php
                                                $jobLink = $isExternalJob && !empty($job['external_apply_url'])
                                                    ? esc($job['external_apply_url'])
                                                    : base_url('job/' . $job['id']);
                                                ?>
                                                <div class="job-card js-clickable-card <?= $appliedStatus !== null ? 'is-applied' : '' ?>"
                                                    data-href="<?= $jobLink ?>" data-job-id="<?= (int) $job['id'] ?>"
                                                    <?= $isExternalJob ? 'data-external="1"' : '' ?> role="link" tabindex="0"
                                                    style="cursor:pointer;">

                                                    <div class="job-card-body">
                                                        <h3 class="job-card-title"><?= esc($title) ?></h3>
                                                        <p class="job-card-company"><?= esc($company) ?></p>
                                                        <div class="job-card-meta">
                                                            <span><i class="fas fa-map-pin"></i> <?= esc($location) ?></span>
                                                            <?php if ($postedMeta !== null): ?>
                                                                <span><i class="fas fa-clock"></i> <?= esc($postedMeta) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="job-card-tags">
                                                            <span
                                                                class="badge <?= $typeBadge ?>"><?= esc($job['employment_type'] ?: 'Full Time') ?></span>
                                                            <?php if ($showRemoteBadge): ?>
                                                                <span class="badge badge-warning">Remote</span>
                                                            <?php endif; ?>
                                                            <?php foreach ($requiredSkillBadges as $requiredSkillBadge): ?>
                                                                <span
                                                                    class="badge badge-secondary"><?= esc($requiredSkillBadge) ?></span>
                                                            <?php endforeach; ?>
                                                            <?php if ($appliedStatus !== null): ?>
                                                                <span class="badge job-card-applied-badge"><i
                                                                        class="fas fa-check-circle"></i> Applied</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="job-card-footer mt-auto">
                                                            <span class="job-card-posted">
                                                                <span class="saved-job-visited-note">
                                                                    <a href="<?= $isExternalJob && !empty($job['external_apply_url'])
                                                                        ? esc($job['external_apply_url'])
                                                                        : base_url('job/' . $job['id']) ?>" class="view-details js-mark-visited <?= $isVisited ? 'is-viewed' : 'is-unviewed' ?>"
                                                                        data-job-id="<?= (int) $job['id'] ?>" <?= $isExternalJob ? 'target="_blank"' : '' ?> style="text-decoration:none;">
                                                                        <span
                                                                            class="viewed-action-mark <?= $isVisited ? 'is-viewed' : 'is-unviewed' ?>">
                                                                            <i
                                                                                class="<?= $isVisited ? 'fas fa-eye' : 'far fa-eye' ?>"></i>
                                                                            <?= $isVisited ? 'Viewed' : 'Not viewed' ?>
                                                                        </span>
                                                                        <span> </span>
                                                                    </a>
                                                                </span>
                                                            </span>

                                                            <div class="job-card-actions-group">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary py-0 px-2 job-card-saveing-btn js-save-job-toggle <?= $isSaved ? 'is-saved' : '' ?>"
                                                                    aria-label="<?= $isSaved ? 'Saved job' : 'Save job' ?>"
                                                                    title="<?= $isSaved ? 'Saved' : 'Save Job' ?>"
                                                                    data-save-url="<?= base_url($isSaved ? 'job/unsave/' . $job['id'] : 'job/save/' . $job['id']) ?>"
                                                                    data-job-id="<?= (int) $job['id'] ?>"
                                                                    data-saved="<?= $isSaved ? '1' : '0' ?>"
                                                                    data-save-label-save="Save Job" data-save-label-saved="Saved">
                                                                    <i class="<?= $isSaved ? 'fas' : 'far' ?> fa-bookmark"></i>
                                                                </button>

                                                                <div class="job-card-tools-wrapper">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-secondary job-card-tools-toggle"
                                                                        title="Tools" onclick="event.stopPropagation();">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </button>
                                                                    <div class="job-card-tools-dropdown">
                                                                        <?php if ($primaryResumeId > 0 || ($hasBaseResume ?? false)): ?>
                                                                            <button type="button"
                                                                                class="job-card-tools-item js-analyze-ats"
                                                                                data-job-id="<?= (int) $job['id'] ?>"
                                                                                data-resume-id="<?= $primaryResumeId ?>">
                                                                                Analyze ATS Match
                                                                            </button>
                                                                        <?php endif; ?>
                                                                        <button type="button" class="job-card-tools-item"
                                                                            onclick="event.stopPropagation(); generateCoverLetter(<?= (int) $job['id'] ?>)">
                                                                            AI Cover Letter
                                                                        </button>
                                                                        <button type="button" class="job-card-tools-item"
                                                                            onclick="event.stopPropagation(); shareJob(<?= (int) $job['id'] ?>)">
                                                                            Share Job
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                    <div class="job-card-icon">
                                                        <?php
                                                        $displayLogo = "";
                                                        $useFavicon = false;
                                                        if (!empty($companyLogo)) {
                                                            $displayLogo = $resolveAssetUrl($companyLogo);
                                                        } elseif (!empty($job['company_website'])) {
                                                            $domain = parse_url($job['company_website'], PHP_URL_HOST) ?? $job['company_website'];
                                                            $displayLogo = "https://www.google.com/s2/favicons?domain=" . urlencode($domain) . "&sz=96";
                                                            $useFavicon = true;
                                                        }
                                                        ?>
                                                        <?php if ($displayLogo !== ''): ?>
                                                            <img src="<?= esc($displayLogo) ?>" alt="<?= esc($company) ?>"
                                                                class="job-card-logo <?= $useFavicon ? 'is-favicon' : '' ?>"
                                                                onerror="this.onerror=null; this.parentElement.innerHTML='<span><?= esc($companyInitial) ?></span>';">
                                                        <?php else: ?>
                                                            <span><?= esc($companyInitial) ?></span>
                                                        <?php endif; ?>
                                                    </div>

                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
                                        <?= $pager->links('default', 'portal_full') ?>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-search"></i>
                                        <h5>No jobs found</h5>
                                        <p>Try adjusting your search or clearing filters</p>
                                        <a href="<?= base_url('jobs') ?>" class="candidate-primary-link">Clear All</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$showFilters): ?>
                            <div id="tab-recommended" class="<?= $activeTab !== 'recommended' ? 'd-none' : '' ?>">

                                <?php if (!empty($candidateSkills) || !empty($candidateInterests)): ?>
                                    <div class="profile-strip">
                                        <?php if (!empty($candidateSkills)): ?>
                                            <div class="profile-strip-section">
                                                <div class="strip-label">Your Skills</div>
                                                <?php foreach (array_slice($candidateSkills, 0, 5) as $sk): ?>
                                                    <span class="skill-chip"><?= esc($sk) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($candidateInterests)): ?>
                                            <div class="profile-strip-section">
                                                <div class="strip-label">Your Interests</div>
                                                <?php foreach (array_slice($candidateInterests, 0, 5) as $int): ?>
                                                    <span class="interest-chip"><?= esc($int) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="tabs-row candidate-tabs-offset">
                                    <div class="tab-pills">
                                        <button type="button"
                                            class="tab-pill <?= $recommendationType === 'applies' ? 'active' : '' ?>"
                                            data-rec-type="applies" onclick="switchRecommendation('applies', event)">
                                            <i class="fas fa-history"></i> Based On Applies
                                            <?php $countLabel = $recommendationCountLabel('applies', $suggestedJobsByApplies); ?>
                                            <?php if ($countLabel !== ''): ?><span
                                                    class="pill-count"><?= esc($countLabel) ?></span><?php endif; ?>
                                        </button>
                                        <button type="button"
                                            class="tab-pill <?= $recommendationType === 'skills' ? 'active' : '' ?>"
                                            data-rec-type="skills" onclick="switchRecommendation('skills', event)">
                                            <i class="fas fa-tools"></i> Based On Skills
                                            <?php $countLabel = $recommendationCountLabel('skills', $suggestedJobsBySkills); ?>
                                            <?php if ($countLabel !== ''): ?><span
                                                    class="pill-count"><?= esc($countLabel) ?></span><?php endif; ?>
                                        </button>
                                        <button type="button"
                                            class="tab-pill <?= $recommendationType === 'preferences' ? 'active' : '' ?>"
                                            data-rec-type="preferences"
                                            onclick="switchRecommendation('preferences', event)">
                                            <i class="fas fa-heart"></i> Preferences / Interests
                                            <?php $countLabel = $recommendationCountLabel('preferences', $suggestedJobsByPreferences); ?>
                                            <?php if ($countLabel !== ''): ?><span
                                                    class="pill-count"><?= esc($countLabel) ?></span><?php endif; ?>
                                        </button>
                                        <button type="button"
                                            class="tab-pill <?= $recommendationType === 'ai' ? 'active' : '' ?>"
                                            data-rec-type="ai" onclick="switchRecommendation('ai', event)">
                                            <i class="fas fa-brain"></i> Other Recommendations
                                            <?php $countLabel = $recommendationCountLabel('ai', $suggestedJobsByAi); ?>
                                            <?php if ($countLabel !== ''): ?><span
                                                    class="pill-count"><?= esc($countLabel) ?></span><?php endif; ?>
                                        </button>
                                    </div>
                                </div>

                                <div class="recommended-jobs-stage">
                                    <?= $renderRecommendedPane($recommendationType, $activeRecommendedJobs, $recommendationLabels[$recommendationType] ?? 'Recommended Jobs') ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<!-- AI Cover Letter Modal -->
<div class="modal fade" id="coverLetterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header text-white" style="background:#1FB7B5 !important;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-magic mr-2"></i>AI Cover Letter Draft</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                <div id="coverLetterLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 font-weight-bold">Our AI is analyzing the job and your profile...</p>
                </div>
                <div id="coverLetterContent">
                    <div class="form-group">
                        <label class="font-weight-bold small text-muted text-uppercase">Targeting:</label>
                        <div id="jobTargetDisplay" class="h6 font-weight-bold"></div>
                        <hr>
                        <textarea id="coverLetterTextArea"
                            class="form-control border-0 shadow-none candidate-cover-letter-input" rows="15"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-dismiss="modal">Discard</button>
                <button type="button" class="btn btn-primary px-4" id="copyLetterBtn" onclick="copyCoverLetter()">
                    <i class="far fa-copy mr-1"></i> Copy to Clipboard
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    async function generateCoverLetter(jobId) {
        const modal = $('#coverLetterModal');
        const contentArea = $('#coverLetterContent');
        const loadingArea = $('#coverLetterLoading');
        const textArea = $('#coverLetterTextArea');
        const targetDisplay = $('#jobTargetDisplay');

        textArea.val('');
        contentArea.addClass('d-none');
        loadingArea.removeClass('d-none');
        modal.modal('show');

        try {
            const response = await fetch(`<?= base_url('candidate/generate-ai-cover-letter') ?>?job_id=${jobId}`);
            const data = await response.json();

            if (data.success) {
                targetDisplay.text(`${data.job_title} at ${data.company}`);
                textArea.val(data.cover_letter);
                loadingArea.addClass('d-none');
                contentArea.removeClass('d-none');
            } else {
                alert('Error: ' + (data.error || 'Failed to generate cover letter'));
                modal.modal('hide');
            }
        } catch (error) {
            console.error('AI Error:', error);
            modal.modal('hide');
        }
    }

    function copyCoverLetter() {
        const textArea = document.getElementById('coverLetterTextArea');
        textArea.select();
        document.execCommand('copy');
        alert('Cover letter copied to clipboard!');
    }
</script>

<script>
    /**
     * Copies the job detail URL to the clipboard.
     * @param {number} jobId - The ID of the job to share.
     */
    function shareJob(jobId) {
        const jobUrl = `<?= base_url('job/') ?>${jobId}`;
        navigator.clipboard.writeText(jobUrl).then(() => {
            alert('Job link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy job link: ', err);
            alert('Failed to copy link. Please try again.');
        });
    }
</script>

<!-- ATS Analysis Modal -->
<div class="modal fade" id="atsAnalysisModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-robot mr-2"></i>AI ATS Match Analysis</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="atsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">AI is analyzing your match against <strong><span
                                id="atsJobTitle"></span></strong>...</p>
                </div>
                <div id="atsResults" class="d-none">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-4 text-center">
                            <div id="atsScoreCircle" class="c100 p0 big center">
                                <span id="atsScoreText">0%</span>
                                <div class="slice">
                                    <div class="bar"></div>
                                    <div class="fill"></div>
                                </div>
                            </div>
                            <div class="mt-2 font-weight-bold">ATS Match Score</div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i> Critical Gap</h6>
                            <p id="atsGap" class="text-muted small"></p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6><i class="fas fa-tags mr-1"></i> Missing Keywords</h6>
                        <div id="atsKeywords" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div>
                        <h6><i class="fas fa-lightbulb mr-1"></i> Suggestions to Improve Score</h6>
                        <ul id="atsSuggestions" class="list-unstyled small"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= base_url('candidate/resume-studio') ?>" class="btn btn-primary">Improve in Resume
                    Studio</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = $('#atsAnalysisModal');

        $(document).on('click', '.js-analyze-ats', function () {
            const jobId = $(this).data('job-id');
            const resumeId = $(this).data('resume-id');
            const jobTitle = $(this).closest('.job-card').find('.job-card-title').text();

            $('#atsJobTitle').text(jobTitle);
            $('#atsLoading').removeClass('d-none');
            $('#atsResults').addClass('d-none');
            modal.modal('show');

            fetch(`<?= base_url('candidate/analyze-ats-match') ?>?job_id=${jobId}`)
                .then(async res => {
                    const responseText = await res.text();
                    let responseData;
                    try { responseData = JSON.parse(responseText); } catch (e) { responseData = null; }
                    if (!res.ok) { throw new Error(responseData?.error || responseText.substring(0, 100) || `HTTP ${res.status}`); }
                    return responseData;
                })
                .then(data => {
                    if (data.success) {
                        $('#atsScoreCircle').attr('class', 'c100 p' + data.score + ' big center');
                        $('#atsScoreText').text(data.score + '%');
                        $('#atsGap').text(data.gap);

                        let keywordsHtml = '';
                        data.keywords.forEach(kw => {
                            keywordsHtml += `<span class="badge badge-light border px-2 py-1 mr-1 mb-1">${kw}</span>`;
                        });
                        $('#atsKeywords').html(keywordsHtml || '<small class="text-muted">None identified.</small>');

                        let suggestionsHtml = '';
                        data.suggestions.forEach(s => {
                            suggestionsHtml += `<li class="mb-2"><i class="fas fa-arrow-right text-primary mr-2"></i>${s}</li>`;
                        });
                        $('#atsSuggestions').html(suggestionsHtml);

                        $('#atsLoading').addClass('d-none');
                        $('#atsResults').removeClass('d-none');
                    } else {
                        alert('Error: ' + (data.error || 'Failed to analyze match'));
                        modal.modal('hide');
                    }
                })
                .catch(err => {
                    console.error('ATS Analysis Error:', err);
                    alert('Error: ' + err.message);
                    modal.modal('hide');
                });
        });
    });
</script>
<script>
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.js-mark-visited');
        if (!link) return;

        e.preventDefault(); // ✅ stop navigation

        const jobId = link.dataset.jobId;
        const url = link.href;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch("<?= base_url('job/mark-visited/') ?>" + jobId, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrfToken
            }
        })
            .then(res => res.json())
            .then(data => {
                console.log("Visited saved:", data);
                if (data && data.csrf) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.setAttribute('content', data.csrf);
                    }
                }

                if (!data || data.success !== true) {
                    return;
                }

                link.classList.remove('is-unviewed');
                link.classList.add('is-viewed');

                const viewedMark = link.querySelector('.viewed-action-mark');
                if (viewedMark) {
                    viewedMark.classList.remove('is-unviewed');
                    viewedMark.classList.add('is-viewed');
                    viewedMark.innerHTML = '<i class="fas fa-eye"></i> Viewed';
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                if (link.target === "_blank") {
                    window.open(url, "_blank");
                } else {
                    window.location.href = url;
                }
            });
    });
</script>
<script>
    document.addEventListener('click', function (e) {
        const card = e.target.closest('.js-clickable-card');
        if (!card) return;
        // ignore clicks on buttons, dropdowns, or anything that already stopped propagation
        if (e.target.closest('button') || e.target.closest('.job-card-tools-dropdown')) return;

        const href = card.dataset.href;
        if (!href) return;

        if (card.dataset.external === '1') {
            window.open(href, '_blank');
        } else {
            window.location.href = href;
        }
    });

    // keyboard accessibility (Enter key) since article has role="link" tabindex="0"
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        const card = e.target.closest('.js-clickable-card');
        if (!card) return;
        const href = card.dataset.href;
        if (!href) return;
        if (card.dataset.external === '1') {
            window.open(href, '_blank');
        } else {
            window.location.href = href;
        }
    });
</script>
<?= view('Layouts/candidate_footer') ?>