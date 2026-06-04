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
                <div class="results-bar">
                    <span class="results-count"><strong><?= count($jobs) ?></strong> saved job<?= count($jobs) !== 1 ? 's' : '' ?></span>
                </div>

                <div class="saved-job-grid mb-4">
                    <?php foreach ($jobs as $job): ?>
                        <?php
                            $title = (string) ($job['title'] ?? 'Untitled Role');
                            $company = (string) ($job['company'] ?? 'Company');
                            $location = (string) ($job['location'] ?? 'N/A');
                            $experience = trim((string) ($job['experience_level'] ?? ''));
                            $salary = trim((string) ($job['salary_range'] ?? ''));
                            $type = strtolower((string) ($job['employment_type'] ?? ''));
                            $typeBadge = str_contains($type, 'part') ? 'badge-secondary' : 'badge-primary';
                            $isExternal = !empty($job['is_external']);
                            $initial = strtoupper(substr($company, 0, 1) ?: 'J');
                            $postedAt = !empty($job['created_at']) ? date('d M Y', strtotime((string) $job['created_at'])) : null;
                            $companyLogo = trim((string) ($job['company_logo'] ?? ''));
                            $isVisited = (int)($job['visited_flag'] ?? 0) === 1;
                            $detailsUrl = trim((string) ($job['details_url'] ?? ''));
                            $unsaveUrl = trim((string) ($job['unsave_url'] ?? ''));
                            if ($detailsUrl === '') {
                                $detailsUrl = base_url('job/' . (int) ($job['id'] ?? 0));
                            }
                            if ($unsaveUrl === '') {
                                $unsaveUrl = base_url('job/unsave/' . (int) ($job['id'] ?? 0));
                            }
                        ?>
                        <article class="job-card saved-job-card">
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
                            <div class="job-card-icon saved-job-logo">
                                <?php if ($companyLogo !== ''): ?>
                                    <img src="<?= esc($resolveAssetUrl($companyLogo)) ?>" alt="<?= esc($company) ?>">
                                <?php else: ?>
                                    <span><?= esc($initial) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="job-card-title"><?= esc($title) ?></h3>
                            <p class="job-card-company"><?= esc($company) ?></p>
                            <div class="job-card-meta">
                                <span><i class="fas fa-map-pin"></i> <?= esc($location) ?></span>
                                <?php if ($experience !== ''): ?>
                                    <span><i class="fas fa-briefcase"></i> <?= esc($experience) ?></span>
                                <?php endif; ?>
                                <?php if ($salary !== ''): ?>
                                    <span><i class="fas fa-rupee-sign"></i> <?= esc($salary) ?></span>
                                <?php endif; ?>
                                <?php if ($postedAt !== null): ?>
                                    <span><i class="fas fa-clock"></i> <?= esc($postedAt) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="job-card-tags">
                                <span class="badge <?= $typeBadge ?>"><?= esc($job['employment_type'] ?: ($isExternal ? 'External' : 'Full Time')) ?></span>
                                <span class="badge badge-secondary"><?= $isExternal ? 'MNC Discovery' : esc(substr($title, 0, 15) ?: 'Role') ?></span>
                                 <?php if ($isVisited): ?>       <span class="badge badge-success">
         Visited
    </span>  <?php endif; ?>
                            </div>
                            <a href="<?= esc($detailsUrl) ?>" class="view-details js-mark-visited" <?= $isExternal ? 'target="_blank" rel="noopener"' : '' ?>  data-job-id="<?= (int) $job['id'] ?>">
                                <?= $isExternal ? 'Apply Now' : 'View Details' ?> &rarr;
                            </a>
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

    e.preventDefault(); // ✅ stop navigation

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

        // ✅ Add badge instantly
        const card = link.closest('.job-card');
        if (card) {
            const tagsDiv = card.querySelector('.job-card-tags');
            if (tagsDiv && !tagsDiv.querySelector('.badge-success')) {
                const badge = document.createElement('span');
                badge.className = 'badge badge-success';
                badge.innerHTML = 'Visited';
                tagsDiv.appendChild(badge);
            }
        }
    })
    .catch(err => console.error(err))
    .finally(() => {
        // ✅ navigate AFTER API call
        if (link.target === "_blank") {
            window.open(url, "_blank");
        } else {
            window.location.href = url;
        }
    });
});
</script>
<?= view('Layouts/candidate_footer') ?>

