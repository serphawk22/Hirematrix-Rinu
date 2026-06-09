<?= view('Layouts/candidate_header', ['title' => 'Saved Jobs']) ?>
<?php $jobs = $jobs ?? []; ?>
<?php
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

$formatAge = static function (?string $date): ?string {
    if (empty($date)) {
        return null;
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return null;
    }

    $days = max(0, (int) floor((time() - $timestamp) / 86400));
    if ($days === 0) {
        return 'today';
    }

    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
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

$isEmploymentBadgeValue = static function (string $value) use ($normaliseBadgeText): bool {
    $value = trim($value);
    $key = $normaliseBadgeText($value);
    if ($value === '' || $key === '') {
        return false;
    }

    if (
        preg_match('#(?:https?://|www\.|[a-z0-9-]+\.(?:com|in|org|net|io|co|ai|dev|careers|jobs)\b)#i', $value)
        || in_array($key, ['external', 'linkedin', 'indeed', 'glassdoor', 'naukri', 'monster'], true)
    ) {
        return false;
    }

    return (bool) preg_match('/\b(full time|fulltime|part time|parttime|contract|intern|internship|temporary|freelance|permanent)\b/', $key);
};

$pickRequiredSkillBadges = static function (array $job, int $limit = 3) use ($formatBadgeText, $normaliseBadgeText): array {
    $skillSource = trim((string) ($job['required_skills'] ?? $job['skills_required'] ?? $job['skills'] ?? ''));
    $descriptionSource = trim((string) ($job['description'] ?? $job['job_description'] ?? $job['requirements'] ?? ''));
    $parts = $skillSource !== '' ? (preg_split('/[,|\/;]+/', $skillSource) ?: []) : [];

    if ($descriptionSource !== '') {
        $descriptionKey = ' ' . $normaliseBadgeText($descriptionSource) . ' ';
        $knownSkills = [
            'PHP', 'Laravel', 'CodeIgniter', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Angular', 'Node.js',
            'Python', 'Django', 'Flask', 'Java', 'Spring Boot', 'Kotlin', 'Swift', 'SQL', 'MySQL', 'PostgreSQL',
            'MongoDB', 'Redis', 'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Git', 'REST API', 'GraphQL',
            'HTML', 'CSS', 'Tailwind', 'Bootstrap', 'Figma', 'Excel', 'ETL', 'Data Analysis', 'Machine Learning',
            'Communication', 'Project Management', 'Customer Support', 'Salesforce', 'SEO'
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
?>

<div class="jobs-page-jobboard saved-jobs-jobboard">
    <div class="container">
        <div class="results-bar saved-jobs-summary-bar">
            <span class="results-count">
                <i class="fas fa-bookmark"></i>
                <strong><?= count($jobs) ?></strong>
                saved job<?= count($jobs) !== 1 ? 's' : '' ?>
            </span>
        </div>
    </div>

    <section class="site-section pt-0">
        <div class="container">
            <?php if (!empty($jobs)): ?>
                <div class="recommended-job-grid saved-job-grid mb-4">
                    <?php foreach ($jobs as $job): ?>
                        <?php
                            $title = (string) ($job['title'] ?? 'Untitled Role');
                            $company = (string) ($job['company'] ?? 'Company');
                            $location = (string) ($job['location'] ?? 'N/A');
                            $experience = trim((string) ($job['experience_level'] ?? ''));
                            $salary = trim((string) ($job['salary_range'] ?? ''));
                            $employmentType = trim((string) ($job['employment_type'] ?? ''));
                            $showEmploymentBadge = $isEmploymentBadgeValue($employmentType);
                            $type = strtolower((string) ($job['employment_type'] ?? ''));
                            $typeBadge = str_contains($type, 'part') ? 'badge-secondary' : 'badge-primary';
                            $isExternal = !empty($job['is_external']);
                            $isExternalJob = (int) ($job['is_external'] ?? 0) === 1;
                            $initial = strtoupper(substr($company, 0, 1) ?: 'J');
                            $postedDateSource = (string) ($job['created_at'] ?? $job['posted_at_raw'] ?? $job['saved_at'] ?? '');
                            $postedTimestamp = $postedDateSource !== '' ? strtotime($postedDateSource) : false;
                            $postedAt = $postedTimestamp !== false ? date('d M Y', $postedTimestamp) : null;
                            $postedAge = $formatAge($postedDateSource);
                            $companyLogo = trim((string) ($job['company_logo'] ?? ''));
                            $score = (float) ($job['match_score'] ?? 0);
                            $hasMatchScore = $score > 0;
                            $matchPct = $hasMatchScore ? max(10, min(100, (int) round($score))) : 0;
                            $requiredSkillBadges = $pickRequiredSkillBadges($job);
                            $showRemoteBadge = $isRemoteJob($job);
                            $isVisited = (int) ($job['visited_flag'] ?? 0) === 1;
                            $detailsUrl = trim((string) ($job['details_url'] ?? ''));
                            $unsaveUrl = trim((string) ($job['unsave_url'] ?? ''));
                            if ($detailsUrl === '') {
                                $detailsUrl = base_url('job/' . (int) ($job['id'] ?? 0));
                            }
                            if ($unsaveUrl === '') {
                                $unsaveUrl = base_url('job/unsave/' . (int) ($job['id'] ?? 0));
                            }
                        ?>
                        <article class="job-card recommended-job-card saved-job-card">
                            <div class="job-card-icon saved-job-logo">
                                <?php if ($companyLogo !== ''): ?>
                                    <img src="<?= esc($resolveAssetUrl($companyLogo)) ?>" alt="<?= esc($company) ?>">
                                <?php else: ?>
                                    <span><?= esc($initial) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="job-card-body">
                                <h3 class="job-card-title"><?= esc($title) ?></h3>
                                <p class="job-card-company"><?= esc($company) ?></p>
                                <div class="job-card-meta">
                                    <span><i class="fas fa-map-pin"></i> <?= esc($location) ?></span>
                                    <?php if ($postedAt !== null): ?>
                                        <span>
                                            <i class="fas fa-clock"></i>
                                            Posted on <?= esc($postedAt) ?><?= $postedAge !== null ? ' - ' . esc($postedAge) : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="job-card-tags">
                                    <?php if ($showEmploymentBadge): ?>
                                        <span class="badge <?= $typeBadge ?>"><?= esc($employmentType) ?></span>
                                    <?php endif; ?>
                                    <?php if ($showRemoteBadge): ?>
                                        <span class="badge badge-warning">Remote</span>
                                    <?php endif; ?>
                                    <?php foreach ($requiredSkillBadges as $requiredSkillBadge): ?>
                                        <span class="badge badge-secondary"><?= esc($requiredSkillBadge) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (!empty($job['match_reason'])): ?>
                                    <div class="small text-muted mb-2"><?= esc($job['match_reason']) ?></div>
                                <?php elseif ($experience !== '' || $salary !== ''): ?>
                                    <div class="small text-muted mb-2">
                                        <?= esc(trim($experience . ($experience !== '' && $salary !== '' ? ' - ' : '') . $salary)) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($hasMatchScore): ?>
                                    <div class="progress-container saved-job-status">
                                        <div class="progress-track">
                                            <div class="progress-bar-custom candidate-progress-fill" style="--candidate-progress: <?= $matchPct ?>%;"></div>
                                        </div>
                                        <span class="progress-label"><?= $matchPct ?>% match</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($isVisited): ?>
                                    <span class="saved-job-visited-note"><i class="fas fa-eye"></i> Viewed</span>
                                <?php endif; ?>
                                <div class="job-card-tools-wrapper">
                                    <button type="button" class="btn btn-sm btn-outline-secondary job-card-tools-toggle" title="Tools" aria-label="Job tools">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="job-card-tools-dropdown">
                                        <a
                                            href="<?= esc($detailsUrl) ?>"
                                            class="job-card-tools-item js-mark-visited"
                                            <?= $isExternal ? 'target="_blank" rel="noopener"' : '' ?>
                                            data-job-id="<?= (int) $job['id'] ?>"
                                        >
                                            <i class="fas fa-external-link-alt"></i>
                                            <?= $isExternal ? 'Apply now' : 'Open details' ?>
                                        </a>
                                        <a href="<?= base_url('jobs?search=' . urlencode($title)) ?>" class="job-card-tools-item">
                                            <i class="fas fa-search"></i>
                                            Similar jobs
                                        </a>
                                    </div>
                                </div>
                                <a href="<?= esc($detailsUrl) ?>" class="view-details js-mark-visited" <?= $isExternal ? 'target="_blank" rel="noopener"' : '' ?>  data-job-id="<?= (int) $job['id'] ?>">
                                    <?= $isExternal ? 'Apply Now' : 'View Details' ?> &rarr;
                                </a>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary py-0 px-2 job-card-save js-save-job-toggle is-saved"
                                aria-label="Remove saved job"
                                title="Remove"
                                data-save-url="<?= esc($unsaveUrl) ?>"
                                <?php if (!$isExternal): ?>
                                    data-job-id="<?= (int) $job['id'] ?>"
                                <?php endif; ?>
                                data-saved="1"
                                data-save-label-save="Save Job"
                                data-save-label-saved="Saved"
                            >
                                <i class="js-save-icon fas fa-bookmark"></i>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bookmark"></i>
                    <h5>No saved jobs yet</h5>
                    <p>Save jobs from listings and they will appear here.</p>
                    <a href="<?= base_url('jobs') ?>" class="candidate-primary-link">Browse Jobs</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<script>
document.addEventListener('click', function (e) {
    const link = e.target.closest('.js-mark-visited');
    if (!link) return;

    e.preventDefault();

    const jobId = link.dataset.jobId;
    const url   = link.href;

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
<?= view('Layouts/candidate_footer') ?>

