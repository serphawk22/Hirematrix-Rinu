<?= view('Layouts/candidate_header', ['title' => 'Company Profile']) ?>
<?php
$brandingPhotos = [];
$brandingPhotosRaw = $company['workplace_photos'] ?? '';
if (is_string($brandingPhotosRaw) && trim($brandingPhotosRaw) !== '') {
    $decodedBrandingPhotos = json_decode($brandingPhotosRaw, true);
    if (is_array($decodedBrandingPhotos)) {
        $brandingPhotos = array_values(array_filter(array_map('strval', $decodedBrandingPhotos)));
    }
}

$benefits = [];
$benefitsRaw = trim((string) ($company['employee_benefits'] ?? ''));
if ($benefitsRaw !== '') {
    $benefits = preg_split('/[\r\n,]+/', $benefitsRaw) ?: [];
    $benefits = array_values(array_filter(array_map('trim', $benefits)));
}
$cultureText = trim((string) ($company['culture_summary'] ?? ''));
if ($cultureText === '') {
    $cultureText = trim((string) ($company['mission_values'] ?? ''));
}
$aboutCompanyText = trim((string) ($company['what_we_do'] ?? ''));
$hasBrandingSection = ($cultureText !== '') || !empty($benefits) || !empty($brandingPhotos);
$officeTourUrl = trim((string) ($company['office_tour_url'] ?? ''));
$officeTourTitle = trim((string) ($company['office_tour_title'] ?? ''));
$officeTourSummary = trim((string) ($company['office_tour_summary'] ?? ''));
if ($officeTourTitle === '') {
    $officeTourTitle = 'Take a Virtual Tour';
}

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

$averageRating = (float) ($reviewSummary['average_rating'] ?? 0);
$totalReviews = (int) ($reviewSummary['total_reviews'] ?? 0);
$reviewEligibility = is_array($reviewEligibility ?? null) ? $reviewEligibility : ['canInterviewReview' => false, 'canEmployeeReview' => false];
$companyName = (string) ($company['name'] ?? 'Company Profile');
$companyShortDescription = trim((string) ($company['short_description'] ?? ''));
$companyInitial = strtoupper(substr($companyName, 0, 1) ?: 'C');
$companyMeta = [
    !empty($company['industry'])     ? ['Industry',  (string) ($company['industry'] ?? '')]     : null,
    !empty($company['size'])         ? ['Size',      (string) ($company['size'] ?? '')]         : null,
    !empty($company['hq'])           ? ['HQ',        (string) ($company['hq'] ?? '')]           : null,
    !empty($company['founded_year']) ? ['Founded',   (string) ($company['founded_year'] ?? '')] : null,
    !empty($company['branches'])     ? ['Branches',  (string) ($company['branches'] ?? '')]     : null,
];
$companyMeta = array_values(array_filter($companyMeta));
$companySource = strtolower(trim((string) ($company['source'] ?? '')));
$companySourceLabel = $companySource === 'official_career_page'
    ? 'Verified from careers page'
    : ($companySource === 'auto_discovered' ? 'Auto-discovered' : '');
$companyCoreLinks = array_filter([
    !empty($company['website']) ? ['label' => 'Website', 'url' => $company['website'], 'icon' => 'fas fa-globe'] : null,
    !empty($company['career_page']) ? ['label' => 'Careers', 'url' => $company['career_page'], 'icon' => 'fas fa-briefcase'] : null,
]);
$companySocialLinks = array_filter([
    !empty($company['linkedin']) ? ['label' => 'LinkedIn', 'url' => $company['linkedin'], 'icon' => 'fab fa-linkedin-in'] : null,
    !empty($company['twitter']) ? ['label' => 'X / Twitter', 'url' => $company['twitter'], 'icon' => 'fab fa-x-twitter'] : null,
    !empty($company['facebook']) ? ['label' => 'Facebook', 'url' => $company['facebook'], 'icon' => 'fab fa-facebook-f'] : null,
    !empty($company['instagram']) ? ['label' => 'Instagram', 'url' => $company['instagram'], 'icon' => 'fab fa-instagram'] : null,
    !empty($company['youtube']) ? ['label' => 'YouTube', 'url' => $company['youtube'], 'icon' => 'fab fa-youtube'] : null,
]);
?>

<div class="company-profile-jobboard">
    <style>
        .company-social-card {
            border: 1px solid #e8edf8;
            border-radius: 18px;
            background:
                radial-gradient(circle at top right, rgba(79, 109, 202, 0.07), transparent 38%),
                linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.045);
        }
        .company-social-card .detail-card-title {
            margin-bottom: 1rem;
        }
        .company-social-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .company-social-link {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.7rem 1rem;
            border-radius: 999px;
            border: 1px solid #dce5fb;
            background: #fff;
            color: #2446c0;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            box-shadow: 0 6px 18px rgba(36, 70, 192, 0.06);
        }
        .company-social-link i {
            font-size: 1rem;
        }
        .company-social-link:hover {
            transform: translateY(-1px);
            border-color: #b8c9f8;
            box-shadow: 0 10px 24px rgba(36, 70, 192, 0.12);
            text-decoration: none;
            color: #1f3ea8;
        }
        .company-social-link--linkedin { color: #0a66c2; border-color: rgba(10, 102, 194, 0.18); }
        .company-social-link--twitter { color: #111827; border-color: rgba(17, 24, 39, 0.12); }
        .company-social-link--facebook { color: #1877f2; border-color: rgba(24, 119, 242, 0.18); }
        .company-social-link--instagram { color: #c13584; border-color: rgba(193, 53, 132, 0.18); }
        .company-social-link--youtube { color: #ff0000; border-color: rgba(255, 0, 0, 0.18); }
        .company-social-link--linkedin:hover,
        .company-social-link--twitter:hover,
        .company-social-link--facebook:hover,
        .company-social-link--instagram:hover,
        .company-social-link--youtube:hover {
            color: inherit;
        }
        @media (max-width: 575px) {
            .company-social-grid {
                gap: 0.6rem;
            }
            .company-social-link {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <div class="container">
        <div class="company-profile-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-building"></i> Company profile</span>
                <h1 class="page-board-title"><?= esc($companyName) ?></h1>
                <p class="page-board-subtitle">
                    <?= $companyShortDescription !== '' ? esc($companyShortDescription) : 'Explore the company overview, open roles, workplace culture, and reviews before you apply.' ?>
                </p>
            </div>
            <div class="company-profile-actions">
                <?php if (!empty($company['website'])): ?>
                    <a href="<?= esc($company['website']) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                        <i class="fas fa-globe mr-1"></i> Company Website
                    </a>
                <?php endif; ?>
                <?php if (!empty($company['career_page'])): ?>
                    <a href="<?= esc($company['career_page']) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                        <i class="fas fa-briefcase mr-1"></i> Careers Page
                    </a>
                <?php endif; ?>
                <?php if (!empty($openJobs)): ?>
                    <a href="#company-open-jobs" class="btn btn-primary">
                        <i class="fas fa-suitcase mr-1"></i> Open Jobs
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <div class="company-profile-layout">
                <div class="company-profile-main">
                    <div class="detail-card company-overview-card">
                        <div class="detail-card-title">
                            <span class="detail-card-icon"><i class="fas fa-id-badge"></i></span>
                            <span>Company Overview</span>
                        </div>
                        <div class="company-overview-top">
                            <div class="company-overview-logo">
                                <?php if (!empty($company['logo'])): ?>
                                    <img src="<?= base_url($company['logo']) ?>" alt="Company Logo">
                                <?php else: ?>
                                    <span><?= esc($companyInitial) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="company-overview-copy">
                                <h2><?= esc($companyName) ?></h2>
                                <?php if ($companyShortDescription !== ''): ?>
                                    <p><?= esc($companyShortDescription) ?></p>
                                <?php endif; ?>
                                <?php if ($companySourceLabel !== ''): ?>
                                    <div class="job-details-chip-list">
                                        <span class="summary-chip"><?= esc($companySourceLabel) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($company['contact_public']) && (int) $company['contact_public'] === 1): ?>
                                    <div class="job-details-chip-list">
                                        <span class="summary-chip">Contact public</span>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                        <div class="company-overview-grid">
                            <?php foreach ($companyMeta as $meta): ?>
                                <div><strong><?= esc($meta[0]) ?></strong><span><?= esc($meta[1]) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($companySocialLinks)): ?>
                        <div class="detail-card company-social-card">
                            <div class="detail-card-title">
                                <span class="detail-card-icon"><i class="fas fa-share-nodes"></i></span>
                                <span>Social Profiles</span>
                            </div>
                            <div class="company-social-grid">
                                <?php foreach ($companySocialLinks as $link): ?>
                                    <?php
                                        $socialClass = match ($link['label']) {
                                            'LinkedIn' => 'company-social-link--linkedin',
                                            'X / Twitter' => 'company-social-link--twitter',
                                            'Facebook' => 'company-social-link--facebook',
                                            'Instagram' => 'company-social-link--instagram',
                                            'YouTube' => 'company-social-link--youtube',
                                            default => '',
                                        };
                                    ?>
                                    <a href="<?= esc($link['url']) ?>" target="_blank" rel="noopener" class="company-social-link <?= esc($socialClass) ?>">
                                        <i class="<?= esc($link['icon']) ?>"></i>
                                        <span><?= esc($link['label']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($aboutCompanyText !== ''): ?>
                        <div class="detail-card">
                            <div class="detail-card-title">
                                <span class="detail-card-icon"><i class="fas fa-align-left"></i></span>
                                <span>About Company</span>
                            </div>
                            <p class="job-details-section-text mb-0"><?= nl2br(esc($aboutCompanyText)) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($company['mission_values'])): ?>
                        <div class="detail-card">
                            <div class="detail-card-title">
                                <span class="detail-card-icon"><i class="fas fa-bullseye"></i></span>
                                <span>Mission / Values</span>
                            </div>
                            <p class="job-details-section-text mb-0"><?= nl2br(esc($company['mission_values'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasBrandingSection): ?>
                        <div class="detail-card">
                            <div class="detail-card-title">
                                <span class="detail-card-icon"><i class="fas fa-users"></i></span>
                                <span>Life at <?= esc($companyName) ?></span>
                            </div>
                            <p class="job-details-section-text">Explore the team environment, employee perks, and the workplace setup.</p>

                            <div class="company-branding-grid">
                                <?php if ($cultureText !== ''): ?>
                                    <div class="company-branding-panel">
                                        <h4>Team Culture</h4>
                                        <p class="mb-0"><?= esc($cultureText) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($benefits)): ?>
                                    <div class="company-branding-panel">
                                        <h4>Perks & Benefits</h4>
                                        <div class="company-benefit-list">
                                            <?php foreach ($benefits as $benefit): ?>
                                                <span class="summary-chip"><?= esc($benefit) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($brandingPhotos)): ?>
                                <div class="company-branding-panel mt-4">
                                    <div class="branding-gallery-head">
                                        <h4>Office & Team Gallery</h4>
                                        <span><?= count($brandingPhotos) . ' photos' ?></span>
                                    </div>
                                    <div class="company-photo-grid">
                                        <?php foreach ($brandingPhotos as $photo): ?>
                                            <?php $photoUrl = $resolveAssetUrl($photo); ?>
                                            <a href="<?= esc($photoUrl) ?>" target="_blank" rel="noopener" class="company-photo-tile">
                                                <img src="<?= esc($photoUrl) ?>" alt="<?= esc($companyName . ' workplace photo') ?>" loading="lazy">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($officeTourUrl !== ''): ?>
                        <div class="detail-card">
                            <div class="office-tour-card">
                                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                                    <div>
                                        <h5 class="mb-1">Office Tour</h5>
                                        <p class="text-muted mb-0">Explore the workplace before you apply.</p>
                                    </div>
                                    <span class="badge badge-info">Virtual Tour</span>
                                </div>
                                <h4 class="office-tour-title"><?= esc($officeTourTitle) ?></h4>
                                <?php if ($officeTourSummary !== ''): ?>
                                    <p class="office-tour-summary"><?= esc($officeTourSummary) ?></p>
                                <?php endif; ?>
                                <a href="<?= esc($officeTourUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary">
                                    <i class="fas fa-play-circle mr-1"></i> Watch Tour
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($openJobs)): ?>
                        <div class="detail-card" id="company-open-jobs">
                            <div class="detail-card-title">
                                <span class="detail-card-icon"><i class="fas fa-suitcase"></i></span>
                                <span>Open Jobs (<?= (int) $openJobsCount ?>)</span>
                            </div>
                            <div class="company-open-job-list">
                                <?php foreach ($openJobs as $job): ?>
                                    <a href="<?= base_url('job/' . $job['id']) ?>" class="company-open-job-item">
                                        <div>
                                            <h6><?= esc($job['title']) ?></h6>
                                            <span><?= esc($job['location']) ?></span>
                                        </div>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="detail-card" id="company-reviews">
                        <div class="detail-card-title">
                            <span class="detail-card-icon"><i class="fas fa-star"></i></span>
                            <span>Company Reviews</span>
                        </div>
                        <div class="company-review-summary">
                            <strong><?= $totalReviews > 0 ? number_format($averageRating, 1) : 'N/A' ?></strong>
                            <span><?= $totalReviews ?> review<?= $totalReviews === 1 ? '' : 's' ?></span>
                        </div>

                        <?php if (!empty($reviews)): ?>
                            <div class="company-review-list">
                                <?php foreach ($reviews as $review): ?>
                                    <article class="company-review-item">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                            <div>
                                                <h6 class="mb-1"><?= esc($review['headline'] ?: 'Candidate Review') ?></h6>
                                                <small class="text-muted">
                                                    <?= esc($review['candidate_name'] ?: 'Candidate') ?> | <?= date('M d, Y', strtotime((string) ($review['updated_at'] ?? $review['created_at'] ?? 'now'))) ?>
                                                </small>
                                                <div class="mt-1">
                                                    <?php $reviewType = (string) ($review['review_type'] ?? 'interview'); ?>
                                                    <?php if ($reviewType === 'employee'): ?>
                                                        <span class="badge badge-success">Verified Employee</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-info">Verified Interview Candidate</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span class="company-review-rating">
                                                <?= str_repeat('*', max(0, min(5, (int) ($review['rating'] ?? 0)))) ?>
                                            </span>
                                        </div>
                                        <p class="mb-2"><?= nl2br(esc($review['review_text'] ?? '')) ?></p>
                                        <div class="company-review-pros-cons">
                                            <?php if (!empty($review['pros'])): ?>
                                                <div><strong>Pros:</strong> <?= esc($review['pros']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($review['cons'])): ?>
                                                <div><strong>Cons:</strong> <?= esc($review['cons']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="mb-0 text-muted">No reviews yet. Be the first candidate to write one.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="company-profile-side">
                    <div class="summary-card company-contact-card">
                        <div class="detail-card-title mb-3">
                            <span class="detail-card-icon"><i class="fas fa-phone"></i></span>
                            <span>Contact</span>
                        </div>
                        <?php if ((int) ($company['contact_public'] ?? 0) === 1): ?>
                            <p class="mb-2"><strong>Email:</strong> <?= esc($company['contact_email'] ?: 'Not specified') ?></p>
                            <p class="mb-0"><strong>Phone:</strong> <?= esc($company['contact_phone'] ?: 'Not specified') ?></p>
                        <?php else: ?>
                            <p class="mb-0 text-muted">Contact details are not public for this company.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ((string) session()->get('role') === 'candidate'): ?>
                        <div class="summary-card" id="write-review">
                            <div class="detail-card-title mb-3">
                                <span class="detail-card-icon"><i class="fas fa-comment-dots"></i></span>
                                <span><?= !empty($currentUserReview) ? 'Update Your Review' : 'Write a Review' ?></span>
                            </div>
                            <p class="text-muted small mb-3">Interview reviews are default. Employee reviews require selected/hired status.</p>

                            <?php if (!($reviewEligibility['canInterviewReview'] ?? false)): ?>
                                <div class="alert alert-warning py-2">
                                    You can write a review after applying/interviewing with this company.
                                </div>
                            <?php endif; ?>

                            <form method="post" action="<?= base_url('company/' . (int) ($company['id'] ?? 0) . '/review') ?>">
                                <?= csrf_field() ?>
                                <?php $selectedReviewType = (string) old('review_type', $currentUserReview['review_type'] ?? 'interview'); ?>
                                <div class="form-group">
                                    <label for="review_type">Review Type</label>
                                    <select name="review_type" id="review_type" class="form-control" required>
                                        <option value="interview" <?= $selectedReviewType === 'interview' ? 'selected' : '' ?> <?= !($reviewEligibility['canInterviewReview'] ?? false) ? 'disabled' : '' ?>>
                                            Interview Experience
                                        </option>
                                        <option value="employee" <?= $selectedReviewType === 'employee' ? 'selected' : '' ?> <?= !($reviewEligibility['canEmployeeReview'] ?? false) ? 'disabled' : '' ?>>
                                            Work Experience (Employee)
                                        </option>
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        Employee reviews are available only for selected/hired candidates.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="review_rating">Overall Rating</label>
                                    <select name="rating" id="review_rating" class="form-control" required>
                                        <option value="">Select rating</option>
                                        <?php $selectedRating = old('rating', $currentUserReview['rating'] ?? ''); ?>
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <option value="<?= $i ?>" <?= (string) $selectedRating === (string) $i ? 'selected' : '' ?>><?= $i ?> / 5</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="review_headline">Headline</label>
                                    <input type="text" name="headline" id="review_headline" class="form-control" maxlength="180" value="<?= esc(old('headline', $currentUserReview['headline'] ?? '')) ?>" placeholder="e.g. Strong interview process and clear communication" required>
                                </div>

                                <div class="form-group">
                                    <label for="review_text">Review</label>
                                    <textarea name="review_text" id="review_text" class="form-control" rows="4" placeholder="Describe your experience with this company." required><?= esc(old('review_text', $currentUserReview['review_text'] ?? '')) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="review_pros">Pros</label>
                                    <textarea name="pros" id="review_pros" class="form-control" rows="2" placeholder="What stood out positively?"><?= esc(old('pros', $currentUserReview['pros'] ?? '')) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="review_cons">Cons</label>
                                    <textarea name="cons" id="review_cons" class="form-control" rows="2" placeholder="Anything candidates should know?"><?= esc(old('cons', $currentUserReview['cons'] ?? '')) ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block" <?= !($reviewEligibility['canInterviewReview'] ?? false) ? 'disabled' : '' ?>>
                                    <?= !empty($currentUserReview) ? 'Update Review' : 'Publish Review' ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </section>
</div>

<?= view('Layouts/candidate_footer') ?>
