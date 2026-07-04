<?= view('Layouts/candidate_header', ['title' => 'Saved Jobs']) ?> 
<style id="saved-jobs-vertical-override">
/* ============================================
   SAVED JOBS — vertical stacked layout, tight spacing
   ============================================ */

div.jobs-page-jobboard.saved-jobs-jobboard div.recommended-job-grid.saved-job-grid {
    display: flex !important;
    flex-direction: column !important;
    flex-wrap: nowrap !important;
    grid-template-columns: none !important;
    gap: 10px !important;
}

/* Card shell */
div.jobs-page-jobboard.saved-jobs-jobboard article.job-card.saved-job-card {
    display: block !important;
    position: relative !important;
    background: #fff !important;
    border: 1px solid #e8edf3 !important;
    border-radius: var(--candidate-card-radius) !important;
    box-shadow: none !important;
    padding: 12px 16px 12px 16px !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    flex: none !important;
    text-align: left !important;
        min-height: 128px !important;
}
body.dark div.jobs-page-jobboard.saved-jobs-jobboard article.job-card.saved-job-card {
    background: var(--card) !important;
}
div.jobs-page-jobboard.saved-jobs-jobboard article.job-card.saved-job-card:hover {
  border-color:none !important;
    box-shadow:none !important;
    transform: translateY(-2px) !important;
}
/* Top row: title/company on the left, logo on the right */
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-top {
   display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

/* Title + company heading block */
 
body.candidate-app .saved-jobs-jobboard .recommended-job-card.saved-job-card .job-card-title {
    text-transform: none !important;
    letter-spacing: 0 !important;
        font-size: 0.98rem !important;
    line-height: 1.15 !important;
    font-weight: 700 !important;
        font-family: var(--portal-font-family) !important;
            min-height: 1.65em !important;
}

div.jobs-page-jobboard.saved-jobs-jobboard .job-card-company {
    font-size: 13px !important;
    line-height: 1.0 !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    opacity: 0.75 !important;
    min-height: 1.65em !important;
}
body.candidate-app .saved-jobs-jobboard .recommended-job-card.saved-job-card .job-card-icon {
      width: 3rem;
    height: 3rem; 
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem; 
} 
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-icon img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

/* Body */
div.jobs-page-jobboard.saved-jobs-jobboard .job-card.saved-job-card .job-card-body {
    display: block !important;
    width: 100% !important;
    max-width: none !important;
    padding-right: 0 !important;
    padding-bottom: 0 !important;
    margin: 0 !important;
    text-align: left !important;
}

/* Meta row — location + posted date on one line */
body.candidate-app .saved-jobs-jobboard .recommended-job-card.saved-job-card .job-card-meta {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: flex-start !important;
    text-align: left !important;
    gap: 1rem !important;
    width: 100% !important;
    margin-bottom: 0px !important;
    font-size: 12.5px !important;
    line-height: 1 !important;
    overflow-x: auto !important;
}
body.candidate-app .saved-jobs-jobboard .recommended-job-card.saved-job-card .job-card-meta span {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    flex: 0 0 auto !important;
    white-space: nowrap !important;
    margin: 0 !important;
    line-height: 1 !important;
}

/* Tags */
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-tags {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6px !important;
    margin: 6px 0 0 !important;
}
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-tags .badge {
    padding: 3px 8px !important;
    font-size: 11.5px !important;
}

div.jobs-page-jobboard.saved-jobs-jobboard .job-card .small.text-muted {
    font-size: 12.5px !important;
    margin: 4px 0 0 !important;
}

div.jobs-page-jobboard.saved-jobs-jobboard .progress-container.saved-job-status {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    max-width: 320px !important;
    margin: 4px 0 0 !important;
}

/* Viewed pill — flows inline before footer */
div.jobs-page-jobboard.saved-jobs-jobboard .saved-job-visited-note {
    display: inline-flex !important;
    align-items: center !important;
    position: static !important;
    margin: 0 !important;
    padding: 4px 10px !important;
    gap: 5px !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    color: #1FB7B5 !important;
    background: rgba(31,183,181,0.10) !important;
    border: 1px solid rgba(31,183,181,0.35) !important;
    border-radius: 999px !important;
    white-space: nowrap !important;
}

/* Footer row — posted date / viewed pill left, save button right */
.candidate-app .job-card-footer,
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-footer {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    margin-top: 8px !important;
    padding-top: 8px !important;
    border-top: none !important;
}
.candidate-app .job-card-posted {
    font-size: 12.5px !important;
    color: var(--muted-foreground, #8a94a0) !important;
}
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-footer .job-card-save {
    position: static !important;
    background: none !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: var(--foreground, #12181f) !important;
    cursor: pointer !important;
    padding: 4px 8px !important;
    outline: none !important;
    box-shadow: none !important;
}
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-footer .job-card-save i { font-size: 14px !important; }
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-footer .job-card-save i.fas { color: var(--primary, #1FB7B5) !important; }
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-footer .job-card-save.is-saving { opacity: .6 !important; pointer-events: none !important; }

/* ── Grid: tight gutter, no extra bottom margin stacking ── */
div.jobs-page-jobboard.saved-jobs-jobboard .row.g-4 {
    --bs-gutter-x: 16px !important;
    --bs-gutter-y: 16px !important;
    margin-left: calc(-1 * var(--bs-gutter-x) / 2) !important;
    margin-right: calc(-1 * var(--bs-gutter-x) / 2) !important;
    padding-top: 4px !important; /* gives translateY(-2px) room to breathe */
}
div.jobs-page-jobboard.saved-jobs-jobboard .row.g-4 > [class*="col-"] {
    padding-left: calc(var(--bs-gutter-x) / 2) !important;
    padding-right: calc(var(--bs-gutter-x) / 2) !important;
    margin-bottom: 16px !important; /* was 24px, and no longer stacked with g-4's own gutter-y */
}

/* Remove trailing empty-line gap when experience/salary block is absent */
div.jobs-page-jobboard.saved-jobs-jobboard .job-card-body > *:last-child.job-card-footer {
    margin-top: 8px !important;
}
</style>
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

/* Robust "not specified" detector — handles case, punctuation, and
   invisible/whitespace characters (nbsp, tabs, etc.) that strcasecmp misses */
$isNotSpecified = static function (?string $value): bool {
    $value = (string) $value;
    $value = preg_replace('/[\x{00A0}\s]+/u', ' ', $value) ?? $value; // collapse nbsp + whitespace
    $value = trim($value);
    $value = rtrim($value, ".!"); // strip trailing punctuation
    if ($value === '') {
        return true;
    }
    $key = strtolower($value);
    return in_array($key, ['not specified', 'n a', 'n/a', 'na', 'none', 'not disclosed', '-'], true);
};
?>

<div class="jobs-page-jobboard saved-jobs-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-bookmark"></i> Your shortlist</span>
                <h1 class="page-board-title">Saved Jobs</h1>
                <p class="page-board-subtitle">Jobs you bookmarked for later. Open a card to review the details or remove it from your saved list.</p>
            </div>
            <div class="job-details-header-actions">
                <a href="<?= base_url('jobs') ?>" class="btn btn-primary">
                    <i class="fas fa-search mr-1"></i> Browse Jobs
                </a>
            </div>
        </div>
    </div>

<section class="site-section pt-0">
    <div class="container">
        <?php if (!empty($jobs)): ?>
            <div class="row g-4">
                <?php foreach ($jobs as $job): ?>
                    <?php
                    $stripBadChars = static function (string $text): string {
                        $text = preg_replace('/\?{2,}\s*/u', '', $text);
                        return trim($text);
                    };

                    $title = $stripBadChars((string) ($job['title'] ?? 'Untitled Role'));
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
                    $companyWebsite = trim((string) ($job['company_website'] ?? ''));
                    $displayLogo = '';
                    $useFavicon = false;
                    if ($companyLogo !== '') {
                        $displayLogo = $resolveAssetUrl($companyLogo);
                    } elseif ($companyWebsite !== '') {
                        $websiteHost = parse_url($companyWebsite, PHP_URL_HOST) ?: $companyWebsite;
                        $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                        if ($websiteHost !== '') {
                            $displayLogo = 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96';
                            $useFavicon = true;
                        }
                    }
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

                    $postedFooterLabel = $postedAt !== null
                        ? ('Posted on ' . $postedAt . ($postedAge !== null ? ' - ' . $postedAge : ''))
                        : '';

                    $isExternalAttr = $isExternal ? 'data-external="1"' : '';

                    $expClean = $isNotSpecified($experience) ? '' : $experience;
                    $salaryClean = $isNotSpecified($salary) ? '' : $salary;
                    $expSalaryText = trim($expClean . ($expClean !== '' && $salaryClean !== '' ? ' - ' : '') . $salaryClean);
                    ?>

                    <div class="col-md-6">
                        <article class="job-card recommended-job-card saved-job-card js-clickable-card"
                            data-href="<?= esc($detailsUrl) ?>"
                            data-job-id="<?= (int) $job['id'] ?>"
                            <?= $isExternalAttr ?>
                            role="link"
                            tabindex="0"
                            style="cursor:pointer;">

                            <div class="job-card-top">
                                <div class="job-card-heading">
                                    <h3 class="job-card-title"><?= esc($title) ?></h3>
                                    <div class="job-card-company-row">
                                <?= esc($company) ?> 
                               
                            </div>
                                </div>
                                <div class="job-card-icon">
                                    <?php if ($displayLogo !== ''): ?>
                                        <img src="<?= esc($displayLogo) ?>" alt="<?= esc($company) ?>" class="job-card-logo <?= $useFavicon ? 'is-favicon' : '' ?>" onerror="this.onerror=null; this.parentElement.innerHTML='<span><?= esc($initial) ?></span>';">
                                    <?php else: ?>
                                        <span><?= esc($initial) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div> 
                                <div class="job-card-meta">
                                    <span>
                                        <i class="fas fa-map-pin"></i>
                                        <?= esc($location) ?>
                                    </span>

                                    <?php if ($postedAt !== null): ?>
                                        <span>
                                            <i class="fas fa-clock"></i>
                                            Posted on <?= esc($postedAt) ?>
                                            <?= $postedAge !== null ? ' - ' . esc($postedAge) : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="job-card-tags">
                                    <?php if ($showEmploymentBadge): ?>
                                        <span class="badge <?= $typeBadge ?>">
                                            <?= esc($employmentType) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($showRemoteBadge): ?>
                                        <span class="badge badge-warning">
                                            Remote
                                        </span>
                                    <?php endif; ?>

                                    <?php foreach ($requiredSkillBadges as $requiredSkillBadge): ?>
                                        <span class="badge badge-secondary">
                                            <?= esc($requiredSkillBadge) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($expSalaryText !== ''): ?>
                                    <div class="small text-muted mb-2">
                                        <?= esc($expSalaryText) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasMatchScore): ?>
                                    <div class="progress-container saved-job-status">
                                        <div class="progress-track">
                                            <div class="progress-bar-custom candidate-progress-fill"
                                                style="--candidate-progress: <?= $matchPct ?>%;">
                                            </div>
                                        </div>

                                        <span class="progress-label">
                                            <?= $matchPct ?>% match
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="job-card-footer mt-auto">
                                    <span class="job-card-posted">
                                        <?php if ($isVisited){ ?>
                                            <span class="saved-job-visited-note">
                                                <i class="fas fa-eye"></i>
                                                Viewed
                                            </span>
                                             <?php } else { ?>
                                            <span class="saved-job-visited-note">
                                                <i class="fas fa-eye"></i>
                                                Not Viewed
                                            </span>
                                        <?php } ?>
                                    </span>

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
                                        data-save-label-saved="Saved">

                                        <i class="js-save-icon fas fa-bookmark"></i>
                                    </button>
                                </div>
 
                        </article>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="empty-state">
                <i class="fas fa-bookmark"></i>
                <h5>No saved jobs yet</h5>
                <p>Save jobs from listings and they will appear here.</p>
                <a href="<?= base_url('jobs') ?>" class="candidate-primary-link">
                    Browse Jobs
                </a>
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
<script>
document.addEventListener('click', function (e) {
    const card = e.target.closest('.js-clickable-card');
    if (!card) return;
    if (e.target.closest('button') || e.target.closest('.job-card-tools-dropdown')) return;

    const href = card.dataset.href;
    if (!href) return;

    if (card.dataset.external === '1') {
        window.open(href, '_blank');
    } else {
        window.location.href = href;
    }
});

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
<script>
document.addEventListener("DOMContentLoaded", function () {

    document.addEventListener("click", function(e){

        const toggle = e.target.closest(".job-card-tools-toggle");

        document.querySelectorAll(".job-card-tools-dropdown").forEach(function(menu){
            if(!toggle || menu !== toggle.nextElementSibling){
                menu.classList.remove("show");
            }
        });

        if(toggle){
            e.preventDefault();
            e.stopPropagation();
            const menu = toggle.nextElementSibling;
            if(menu){
                menu.classList.toggle("show");
            }
        }
    });

    document.querySelectorAll(".job-card-tools-dropdown").forEach(function(menu){
        menu.addEventListener("click", function(e){
            e.stopPropagation();
        });
    });

    document.querySelectorAll(".job-card-tools-toggle").forEach(function(btn){
        btn.addEventListener("click", function(e){
            e.stopPropagation();
        });
    });

});
</script>
<?= view('Layouts/candidate_footer') ?>
