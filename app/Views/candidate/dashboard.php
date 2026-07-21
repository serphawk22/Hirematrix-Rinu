<?= view('Layouts/candidate_header', ['title' => 'Dashboard']) ?>

<style>
/* ================================================
   FEATURE HIGHLIGHTS POPUP (dashboard)
================================================ */
.hm-modal-overlay {
    position: fixed; inset: 0; background: rgba(17,24,39,.55);
    display: none; align-items: center; justify-content: center;
    z-index: 9999; padding: 20px; backdrop-filter: blur(2px);
}
.hm-modal-overlay.active { display: flex; }

.hm-modal {
    background: #fff; border-radius: 20px; max-width: 480px; width: 100%;
    max-height: 90vh; overflow-y: auto; padding: 32px 32px 28px;
    box-shadow: 0 30px 80px rgba(0,0,0,.25);
    position: relative; animation: hmPop .25s ease;
}
@keyframes hmPop { from{opacity:0; transform:translateY(12px) scale(.98);} to{opacity:1; transform:none;} }

.hm-modal-close {
    position: absolute; top: 18px; right: 18px;
    width: 34px; height: 34px; border-radius: 9px; border: 1px solid #E5E7EB;
    background: #fff; display: flex; align-items: center; justify-content: center;
    color: #6b7280; cursor: pointer; transition: .15s; font-size: 14px;
}
.hm-modal-close:hover { background: #F9FAFB; color: #111827; }

.hm-modal-eyebrow { font-size: 13px; font-weight: 800; letter-spacing: .08em; color: #0D8A90; text-transform: uppercase; margin-bottom: 4px; }
.hm-modal-sub { font-size: 13px; color: #9CA3AF; margin-bottom: 16px; }
.hm-modal-divider { height: 2px; background: linear-gradient(90deg,#1FB7B5,#53B86C,#B5D84E); border-radius: 2px; margin-bottom: 22px; }

.hm-modal-title { font-size: 26px; font-weight: 600; color: #111827; margin-bottom: 12px; line-height: 1.25; }
.hm-modal-title span { background: linear-gradient(135deg,#1FB7B5,#53B86C,#B5D84E); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

.hm-intent-options { display: flex; flex-direction: column; gap: 14px; margin-top: 6px; }
.hm-intent-btn {
    display: flex; align-items: center; gap: 16px;
    padding: 20px 22px; border-radius: 14px;
    border: 1.5px solid #D9ECE5; background: #fff;
    text-decoration: none !important; color: inherit;
    transition: .2s;
}
.hm-intent-btn:hover {
    border-color: #1FB7B5;
    box-shadow: 0 6px 20px rgba(31,183,181,.10);
    transform: translateY(-2px);
    text-decoration: none !important;
}
.hm-intent-icon {
    width: 52px; height: 52px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #0D8A90;
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%);
    border: 1px solid #D9ECE5;
}
.hm-intent-text h4 { font-size: 17px; font-weight: 600; color: #111827; margin-bottom: 3px; }
.hm-intent-text p { font-size: 13.5px; color: #6b7280; margin: 0; line-height: 1.4; }
.hm-intent-arrow { margin-left: auto; color: #9CA3AF; font-size: 16px; flex-shrink: 0; }
.hm-intent-skip {
    display: block; text-align: center; margin-top: 18px;
    font-size: 13.5px; color: #9CA3AF; background: none; border: none;
    cursor: pointer; text-decoration: underline; outline: none;
    -webkit-tap-highlight-color: transparent;
}
.hm-intent-skip:focus { outline: none; box-shadow: none; }
.hm-intent-skip:hover { color: #6b7280; }

@media (max-width: 600px) {
    .hm-modal { padding: 26px 20px 22px; }
    .hm-modal-title { font-size: 22px; }
}

@media (prefers-color-scheme: dark) {
    .hm-modal { background: #0B0B0B !important; border: 1px solid #23343A; }
    .hm-modal-close { background: #0B0B0B !important; border-color: #23343A !important; color: #94A3B8 !important; }
    .hm-modal-close:hover { background: #1B2A2F !important; color: #fff !important; }
    .hm-modal-title { color: #F8FAFC !important; }
    .hm-modal-sub { color: #7A8B96 !important; }
    .hm-intent-btn { background: #0B0B0B !important; border-color: #23343A !important; }
    .hm-intent-btn:hover { border-color: #1FB7B5 !important; }
    .hm-intent-icon { background: linear-gradient(135deg,#162327 0%,#1B2A2F 100%) !important; border-color: #23343A !important; }
    .hm-intent-text h4 { color: #F8FAFC !important; }
    .hm-intent-text p { color: #94A3B8 !important; }
    .hm-intent-skip { color: #7A8B96 !important; }
    .hm-intent-skip:hover { color: #CBD5E1 !important; }
}
    </style>
<?php
$applicationCount = count($applications ?? []);
$recentApps = array_slice($applications ?? [], 0, 5);
$topSuggestedJobs = $topSuggestedJobs ?? [];
$avgScore = (int) round((float) ($stats['average_ai_score'] ?? 0));
$profileStrength = (int) ($profileStrength ?? 0);
$activeMatches = count($topSuggestedJobs);
$candidateId = (int) (session()->get('user_id') ?? 0);
$activeSuggestions = session()->get('career_suggestions') ?? [];
$activeSuggestions = array_filter($activeSuggestions, static function ($suggestion): bool {
    return isset($suggestion['expires_at']) && time() < (int) $suggestion['expires_at'];
});
$activeSuggestionsCount = count($activeSuggestions);
$topRecommendedCount = count($topSuggestedJobs);
$savedJobsCount = $candidateId > 0
    ? (int) model('SavedJobModel')->where('candidate_id', $candidateId)->countAllResults()
    : 0;
$jobAlertsCount = $candidateId > 0
    ? (int) model('JobAlertModel')->where('candidate_id', $candidateId)->where('is_active', 1)->countAllResults()
    : 0;
$unreadNotificationCount = $candidateId > 0
    ? (int) model('NotificationModel')->getUnreadCount($candidateId)
    : 0;
$profilePrompt = $profileStrength >= 80
    ? 'Recruiter-ready profile. Keep momentum with fresh applications.'
    : ($profileStrength >= 50
        ? 'Complete a few more profile details to get sharper matches.'
        : 'Complete your profile to unlock stronger matches and recruiter visibility.');
$nextActionUrl = $topRecommendedCount > 0 ? base_url('jobs?tab=suggested') : base_url('jobs');
$nextActionCta = $topRecommendedCount > 0 ? 'View matches' : 'Browse jobs';
$formatCompactCount = static function (int $count): string {
    return $count > 99 ? '99+' : (string) $count;
};
$dailyReminder = is_array($dailyReminder ?? null) ? $dailyReminder : [];

$pickJobIcon = static function (string $title): string {
    $needle = strtolower($title);
    if (str_contains($needle, 'data')) {
        return 'fas fa-database';
    }
    if (str_contains($needle, 'design')) {
        return 'fas fa-pencil-ruler';
    }
    if (str_contains($needle, 'manager') || str_contains($needle, 'product')) {
        return 'fas fa-chart-line';
    }
    if (str_contains($needle, 'engineer') || str_contains($needle, 'developer') || str_contains($needle, 'backend')) {
        return 'fas fa-code';
    }

    return 'fas fa-briefcase';
};

$formatDate = static function ($value, string $fallback = 'Recently'): string {
    if (empty($value)) {
        return $fallback;
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('M d, Y', $timestamp) : $fallback;
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
// ── PRO feature card data ────────────────────────────────────────────
// Topics mirror the former 3-slide tour popup (AI Interview, Career
// Transition AI, Resume Studio) plus an extra slide pulled from the
// portal-trailer feature showcase (Job Search Strategy Coach).
$candidateName = trim((string) (session()->get('user_name') ?? ''));
$candidateName = $candidateName !== '' ? $candidateName : 'Your Profile';
$candidateAvatar = trim((string) ($candidateAvatar ?? ''));
$candidateInitial = strtoupper(substr($candidateName, 0, 1) ?: 'C');
$profileHeadline = trim((string) ($profileHeadline ?? ''));
$profileUpdatedAgo = trim((string) ($profileUpdatedAgo ?? ''));

$proFeatureSlides = [
    [
        'eyebrow' => 'AI Interview Practice',
        'title' => 'Practice with the AI Interview',
        'rows' => [
            'Role-specific mock interview rounds',
            'Structured answer frameworks',
            'Instant post-round feedback',
        ],
        'cta_label' => 'Start practising',
        'cta_url' => base_url('candidate/applications'),
        'video_url' => base_url('videos/ai-interview-demo.mp4'),
        'video_title' => 'Your Guide to Acing the AI Interview',
    ],
    [
        'eyebrow' => 'Career Transition AI',
        'title' => 'Plan Your Next Career Move',
        'rows' => [
            'Personalised role-change roadmap',
            'Skill gap analysis vs target role',
            'Certification & learning path guide',
        ],
        'cta_label' => 'Generate my roadmap',
        'cta_url' => base_url('career-transition'),
    ],
    [
        'eyebrow' => 'Resume Studio',
        'title' => 'Build a Resume That Gets Noticed',
        'rows' => [
            'Role-targeted resume per job',
            'ATS-friendly formatting checks',
            'AI rewrite & positioning tips',
        ],
        'cta_label' => 'Build my resume',
        'cta_url' => base_url('candidate/resume-studio'),
    ], 
     [
        'eyebrow' => 'AI Career Mentor',
        'title' => 'Get Guidance, Anytime You Need It',
        'rows' => [
            'Unlimited mentor chat sessions',
            'Personalised career guidance',
            'Interview & negotiation tips',
        ],
        'cta_label' => 'Chat with mentor',
        'cta_url' => base_url('candidate/ai-mentor'),
    ],
];
?>

<div class="dashboard-jobboard">
<div class="dash-grid">

    <!-- CENTER: main content -->
    <div class="dash-grid__main">
          <!-- ═══════════════ PRO FEATURE PROMO (top of page) ═══════════════ -->
  <!-- ═══════════════ PRO FEATURE PROMO (top of page) ═══════════════ -->
<?php if (empty($premiumSubscription ?? null)): ?>
<div class="dash-pro-panel">

        <div class="dash-pro-panel-head">
          <div>
            <div class="dash-pro-badge"><i class="fas fa-crown"></i> Pro tools</div>
          <h2 class="candidate-gradient-text">
  Unlock more with PRO
</h2>
            <p>AI-powered tools to help you land your next role faster.</p>
          </div>
          <a href="<?= base_url('premium/plans') ?>" class="btn btn-primary dash-pro-promo-cta">
            Become a Pro
          </a>
        </div>

        <div class="dash-pro-carousel" id="dashProCarousel">
         
          <div class="dash-pro-grid" id="dashProGrid">
          <?php foreach ($proFeatureSlides as $slide):
              $hasVideo = !empty($slide['video_url']);
              $cardTag  = $hasVideo ? 'div' : 'a';
              $cardClass = $hasVideo ? 'dash-pro-card' : 'dash-pro-card candidate-link-plain';
          ?>
            <div class="dash-pro-ring">
              <<?= $cardTag ?>
                  <?php if ($hasVideo): ?>
                      role="button" tabindex="0"
                      data-video-src="<?= esc($slide['video_url'], 'attr') ?>"
                      data-video-title="<?= esc($slide['video_title'] ?? ($slide['title'] ?? ''), 'attr') ?>"
                      onclick="dashProOpenVideo(this)"
                      onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();dashProOpenVideo(this);}"
                  <?php else: ?>
                      href="<?= esc($slide['cta_url'] ?? base_url('premium/plans')) ?>"
                  <?php endif; ?>
                  class="<?= esc($cardClass, 'attr') ?>"
              >
                 <?php if (!$hasVideo && ($slide['eyebrow'] ?? '') !== 'Job Search Strategy Coach'): ?>
    <i class="fas fa-lock dash-pro-card-lock" aria-hidden="true"></i>
<?php endif; ?>

                  <div class="dash-pro-card-icon">
                      <i class="<?= esc($pickJobIcon($slide['eyebrow'] ?? '')) ?>" aria-hidden="true"></i>
                      <?php if ($hasVideo): ?>
                          <span class="dash-pro-card-play-badge"><i class="fas fa-play"></i></span>
                      <?php endif; ?>
                  </div>

                  <span class="dash-pro-card-eyebrow"><?= esc($slide['eyebrow'] ?? '') ?></span>
                  <h3 class="dash-pro-card-title"><?= esc($slide['title'] ?? ($slide['eyebrow'] ?? '')) ?></h3>

                  <ul class="dash-pro-card-rows">
                      <?php foreach (array_slice((array) ($slide['rows'] ?? []), 0, 3) as $row): ?>
                          <li><i class="fas fa-check-circle"></i> <?= esc($row) ?></li>
                      <?php endforeach; ?>
                  </ul>

                  <?php if ($hasVideo): ?>
                      <span class="dash-pro-card-watch"><i class="fas fa-circle-play"></i> Watch the guide</span>
                  <?php else: ?>
                      <span class="dash-pro-card-cta"><?= esc($slide['cta_label'] ?? 'Learn more') ?> <i class="fas fa-arrow-right"></i></span>
                  <?php endif; ?>
              </<?= $cardTag ?>>
            </div>
          <?php endforeach; ?>
        </div></div>

      </div> 

<!-- Video guide modal (shared by any PRO card with a video) -->
<div class="dash-pro-video-modal" id="dashProVideoModal" aria-hidden="true">
    <div class="dash-pro-video-modal-box" role="dialog" aria-modal="true" aria-labelledby="dashProVideoModalTitle">
        <div class="dash-pro-video-modal-head">
            <h4 id="dashProVideoModalTitle">Your Guide to Acing the AI Interview</h4>
            <button type="button" class="dash-pro-video-modal-close" onclick="dashProCloseVideo()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="dash-pro-video-modal-body">
            <video id="dashProVideoModalPlayer" controls data-ai-tour-bgm controlsList="nofullscreen noremoteplayback" preload="none"></video>
        </div>
    </div>
</div>

<script>
function dashProOpenVideo(el){
    var modal = document.getElementById('dashProVideoModal');
    var player = document.getElementById('dashProVideoModalPlayer');
    var titleEl = document.getElementById('dashProVideoModalTitle');
    var src = el.getAttribute('data-video-src');
    var title = el.getAttribute('data-video-title') || 'Video guide';

    if (!src) { return; }

    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    titleEl.textContent = title;
    player.src = src;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    player.play().catch(function(){ /* autoplay blocked, user can press play */ });
}

function dashProCloseVideo(){
    var modal = document.getElementById('dashProVideoModal');
    var player = document.getElementById('dashProVideoModalPlayer');

    player.pause();
    player.removeAttribute('src');
    player.load();
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}
 
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { dashProCloseVideo(); }
});

document.getElementById('dashProVideoModal').addEventListener('click', function(e){
    if (e.target === this) { dashProCloseVideo(); }
});
 
</script>
<script src="<?= base_url('jobboard/js/ai-tour-bgm.js') ?>"></script>
<?php endif; ?>
<!-- ═══════════════ END PRO FEATURE PROMO ═══════════════ -->

   <section class="dashboard-section pt-0">
    <div class="container-fluid px-lg-5">
            <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="section-title">Jobs Matching Your Profile</h2>
                    <p class="section-subtitle">Based on your skills, target roles, and work preferences</p>
                </div>
                <a href="<?= base_url('jobs?tab=suggested') ?>" class="btn btn-primary">View all jobs</a>
            </div>

            <div class="row g-4">
    <?php if (!empty($topSuggestedJobs)): ?>
        <?php foreach (array_slice($topSuggestedJobs, 0, 4) as $job): ?>
            <?php
            $score = (int) round((float) ($job['match_score'] ?? 0));
            $title = (string) ($job['title'] ?? 'Untitled Role');
            $company = (string) ($job['company'] ?? 'Company');
            $location = (string) ($job['location'] ?? 'N/A');
            $experience = trim((string) ($job['experience_level'] ?? ''));
            $salary = trim((string) ($job['salary_range'] ?? ''));
            $postedAgo = isset($job['posted_at']) ? $formatTimeAgo($job['posted_at']) : 'Recently';
            $companyInitial = strtoupper(substr($company, 0, 1) ?: 'C');
            $companyLogo = trim((string) ($job['company_logo'] ?? ''));
            $website = trim((string) ($job['company_website'] ?? ''));
            $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : '';
            $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
            $googleLogoUrl = $websiteHost !== '' ? 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96' : '';
            $companyLogoResolved = $companyLogo !== '' ? $resolveAssetUrl($companyLogo) : $googleLogoUrl;

            $fallbackHtml = '<span>' . esc($companyInitial) . '</span>';
            $logoErrorJs = "if(this.dataset.googleLogo&&this.src!==this.dataset.googleLogo){this.src=this.dataset.googleLogo;}else{this.parentNode.innerHTML='" . $fallbackHtml . "';}";

            $matchPct = max(10, min(100, $score));
            $matchLabel = $score > 0 ? $matchPct . '% match' : 'Open role';
            $isExternalJob = (int) ($job['is_external'] ?? 0) === 1;
            $externalSource = trim((string) ($job['external_source'] ?? ''));

            // Rating / reviews
            $rating = isset($job['rating']) && $job['rating'] !== null ? round((float) $job['rating'], 1) : null;
            $reviewCount = (int) ($job['review_count'] ?? 0);
$stripBadChars = static function (string $text): string {
    // Collapse runs of 2+ literal '?' (typical artifact of lost emoji/unicode chars)
    $text = preg_replace('/\?{2,}\s*/u', '', $text);
    return trim($text);
};
            // Short description snippet
            $description = trim(strip_tags((string) ($job['description'] ?? '')));
            if ($description !== '' && mb_strlen($description) > 100) {
                $description = mb_substr($description, 0, 100) . '…';
            }
         
$description = $stripBadChars($description); 

$title = $stripBadChars((string) ($job['title'] ?? 'Untitled Role'));

            // Tags: prefer explicit tags field, fall back to skills/category
            $rawTags = (string) ($job['tags'] ?? $job['skills'] ?? '');
            $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags))));

            $isSaved = !empty($job['is_saved']);
            $jobId = (int) ($job['id'] ?? 0);

            // Truncate location to keep the top row tidy (e.g. "Pune, Mumbai, Nag...")
            $locationDisplay = $location;
            if (mb_strlen($locationDisplay) > 24) {
                $locationDisplay = mb_substr($locationDisplay, 0, 24) . '…';
            }
            ?>
            <div class="col-md-6">
            <div class="job-card dashboard-card h-100">
                <a href="<?= base_url('job/' . $jobId) ?>" class="job-card-link candidate-card-link">
                    <div class="job-card-top">
                        <div class="job-card-heading">
                            <h3 class="job-card-title"><?= esc($title) ?></h3>
                            <div class="job-card-company-row">
                                <span class="job-card-company"><?= esc($company) ?></span>
                                <?php if ($rating !== null): ?>
                                    <span class="job-card-rating"><i class="fas fa-star"></i> <?= esc((string) $rating) ?></span>
                                    <?php if ($reviewCount > 0): ?>
                                        <span class="job-card-reviews">| <?= (int) $reviewCount ?> Review<?= $reviewCount === 1 ? '' : 's' ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="job-card-icon">
                            <?php if ($companyLogoResolved !== ''): ?>
                                <img src="<?= esc($companyLogoResolved) ?>" alt="<?= esc($company) ?>" data-google-logo="<?= esc($googleLogoUrl) ?>" onerror="<?= esc($logoErrorJs, 'attr') ?>">
                            <?php else: ?>
                                <span><?= esc($companyInitial) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="job-card-meta">
                        <?php if ($experience !== ''): ?>
                            <span><i class="fas fa-briefcase"></i> <?= esc($experience) ?></span>
                        <?php endif; ?>
                        <span><i class="fas fa-map-marker-alt"></i> <?= esc($locationDisplay) ?></span>
                        <?php if ($salary !== ''): ?>
                            <span><i class="fas fa-rupee-sign"></i> <?= esc($salary) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($description !== ''): ?>
                        <div class="job-card-desc">
                            <i class="fas fa-align-left"></i> <span><?= esc($description) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($tags)): ?>
                        <div class="job-card-tags">
                            <?php foreach (array_slice($tags, 0, 6) as $i => $tag): ?>
                                <span class="job-card-tag"><?= esc($tag) ?></span><?php if ($i < min(count($tags), 6) - 1): ?><span class="job-card-tag-dot">·</span><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </a>
                
 <div class="job-card-footer">
                    <span class="job-card-posted"><?= esc($postedAgo) ?></span>
                <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary py-0 px-2 job-card-save js-save-job-toggle <?= $isSaved ? 'is-saved' : '' ?>"
                        aria-label="<?= $isSaved ? 'Saved job' : 'Save job' ?>"
                        title="<?= $isSaved ? 'Saved' : 'Save Job' ?>"
                        data-save-url="<?= base_url($isSaved ? 'job/unsave/' . $job['id'] : 'job/save/' . $job['id']) ?>"
                        data-job-id="<?= (int) $job['id'] ?>"
                        data-saved="<?= $isSaved ? '1' : '0' ?>"
                        data-save-label-save="Save Job"
                        data-save-label-saved="Saved"
                    >
                        <i class="<?= $isSaved ? 'fas' : 'far' ?> fa-bookmark"></i>
                    </button>
                </div>

            </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="dashboard-panel candidate-grid-full">
            <div class="panel-body text-center py-5">
                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                <h4 class="mb-2">No recommended jobs yet</h4>
                <p class="text-muted mb-0">Once your profile matches live openings, they will appear here automatically.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
        </div>
    </section>

    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <?php if (!empty($jobCategories)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-title mb-0">Explore by Role</h2>
                    <p class="section-subtitle mb-0">Quickly find openings in your preferred specialized domains.</p>
                </div>
            </div>
            <div class="top-companies-grid dashboard-tile-grid dashboard-role-grid mb-5">
                <?php foreach (array_slice($jobCategories, 0, 10) as $category): ?>
                    <a href="<?= base_url('jobs?category=' . urlencode((string)($category['name'] ?? ''))) ?>" class="top-company-card">
                        <div class="top-company-logo">
                            <i class="<?= esc((string)($category['icon'] ?? 'fas fa-briefcase')) ?> text-primary candidate-icon-lg"></i>
                        </div>
                        <div class="top-company-info">
                            <div class="top-company-name"><?= esc((string)($category['name'] ?? 'Role')) ?></div>
                            <div class="top-company-jobs"><?= (int)($category['job_count'] ?? 0) ?> <?= (int)($category['job_count'] ?? 0) === 1 ? 'opening' : 'openings' ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
</div>
    </section>
    
    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <?php
            $topHiringCompanies = $topHiringCompanies ?? [];
            ?>
            <?php if (!empty($topHiringCompanies)): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title mb-0">Top Companies Hiring Now</h2>
                <a href="<?= base_url('candidate/company-job-discovery') ?>" class="btn btn-ghost text-primary">View all</a>
            </div>
            <div class="top-companies-grid dashboard-tile-grid dashboard-company-grid">
                <?php foreach (array_slice($topHiringCompanies, 0, 10) as $co): ?>
                    <?php
                    $coName    = trim((string) ($co['name'] ?? 'Company'));
                    $dbLogo    = trim((string) ($co['logo'] ?? ''));
                    $website   = trim((string) ($co['website'] ?? ''));
                    $websiteHost = $website !== '' ? (parse_url($website, PHP_URL_HOST) ?: $website) : '';
                    $websiteHost = preg_replace('/^www\./i', '', (string) $websiteHost) ?? '';
                    $googleLogoUrl = $websiteHost !== '' ? 'https://www.google.com/s2/favicons?domain=' . rawurlencode($websiteHost) . '&sz=96' : '';
                    $coLogoResolved = $dbLogo !== '' ? $resolveAssetUrl($dbLogo) : $googleLogoUrl;

                    $coIndustry = trim((string) ($co['industry'] ?? ''));
                    $coJobs    = (int) ($co['job_count'] ?? 0);
                    $coInitial = strtoupper(substr($coName, 0, 1) ?: 'C');
                    $coId      = (int) ($co['company_id'] ?? 0);
                    $coUrl     = $coId > 0 ? base_url('company/' . $coId) : base_url('jobs?company=' . urlencode($coName));

                    $fallbackHtml = '<span>' . esc($coInitial) . '</span>';
                    $logoErrorJs = "if(this.dataset.googleLogo&&this.src!==this.dataset.googleLogo){this.src=this.dataset.googleLogo;}else{this.parentNode.innerHTML='" . $fallbackHtml . "';}";
                    ?>
                    <a href="<?= esc($coUrl) ?>" class="top-company-card">
                        <div class="top-company-logo">
                            <?php if ($coLogoResolved !== ''): ?>
                                <img src="<?= esc($coLogoResolved) ?>" alt="<?= esc($coName) ?>" data-google-logo="<?= esc($googleLogoUrl) ?>" onerror="<?= esc($logoErrorJs, 'attr') ?>">
                            <?php else: ?>
                                <span><?= esc($coInitial) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="top-company-info">
                            <div class="top-company-name"><?= esc($coName) ?></div>
                            <?php if ($coIndustry !== ''): ?>
                                <div class="top-company-industry"><?= esc($coIndustry) ?></div>
                            <?php endif; ?>
                            <div class="top-company-jobs"><?= $coJobs ?> <?= $coJobs === 1 ? 'opening' : 'openings' ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($blogPosts)): ?>
        <?= view('candidate/dashboard_blog_section', ['blogPosts' => $blogPosts]) ?>
    <?php endif; ?>

    <section class="dashboard-section pt-0">
        <div class="container-fluid px-lg-5">
            <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="section-title">Recent Applications</h2>
                    <p class="section-subtitle">Track your application status and next steps</p>
                </div>
                <a href="<?= base_url('candidate/applications') ?>" class="btn btn-ghost text-primary">View all applications</a>
            </div>

            <div class="dashboard-panel dashboard-table-wrap">
                <div class="panel-body">
                    <?php if (empty($recentApps)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="mb-2">No applications yet</h4>
                            <p class="text-muted mb-4">Start exploring opportunities and submit your first application.</p>
                            <a href="<?= base_url('jobs') ?>" class="btn btn-primary btn-lg">Browse Jobs</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentApps as $application): ?>
                                        <tr>
                                            <td><strong><?= esc($application['job_title'] ?? '-') ?></strong></td>
                                            <td><?= esc($application['company'] ?? $application['company_name'] ?? '-') ?></td>
                                            <td><?= !empty($application['applied_at']) ? $formatDate($application['applied_at']) : '-' ?></td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    <?= esc(ucwords(str_replace('_', ' ', (string) ($application['status'] ?? 'applied')))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= base_url('job/' . (int) ($application['job_id'] ?? 0)) ?>" class="dashboard-table-link dashboard-table-icon-link" aria-label="View <?= esc((string) ($application['job_title'] ?? 'application'), 'attr') ?>">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    </div><!-- /.dash-grid__main -->

</div><!-- /.dash-grid -->

<!-- ═══════════════ FEATURE HIGHLIGHTS POPUP (fires 7s after load) ═══════════════ -->
<?php if (empty($premiumSubscription ?? null)): ?>
<div class="hm-modal-overlay" id="dashFeaturePopupModal">
  <div class="hm-modal" role="dialog" aria-modal="true" aria-labelledby="dashFeaturePopupTitle">
    <button type="button" class="hm-modal-close" onclick="hmCloseDashFeaturePopup()" aria-label="Close"><i class="fas fa-times"></i></button>

    <div class="hm-modal-eyebrow">Why HireMatrix</div>
    <div class="hm-modal-sub">Tools built to get you hired faster</div>
    <div class="hm-modal-divider"></div>

    <h3 class="hm-modal-title" id="dashFeaturePopupTitle">Explore what makes us <span>different</span></h3>

    <div class="hm-intent-options">
      <?php foreach ($proFeatureSlides as $slide): ?>
        <a href="<?= esc($slide['cta_url'] ?? base_url('premium/plans')) ?>" class="hm-intent-btn">
          <div class="hm-intent-icon"><i class="<?= esc($pickJobIcon($slide['eyebrow'] ?? '')) ?>"></i></div>
          <div class="hm-intent-text">
            <h4><?= esc($slide['title'] ?? ($slide['eyebrow'] ?? '')) ?></h4>
            <p><?= esc($slide['rows'][0] ?? '') ?></p>
          </div>
          <i class="fas fa-chevron-right hm-intent-arrow"></i>
        </a>
      <?php endforeach; ?>
    </div>

    <button type="button" class="hm-intent-skip" onclick="hmCloseDashFeaturePopup()">Maybe later</button>
  </div>
</div>
<?php endif; ?>
</div><!-- /.dashboard-jobboard --> 
 

<?= view('Layouts/candidate_footer') ?>
<script>
(function () {
  var STORAGE_KEY = 'hm_dash_feature_popup_shown';
  var DELAY_MS = 7000;

  function showPopup() {
    if (sessionStorage.getItem(STORAGE_KEY)) return;
    var modal = document.getElementById('dashFeaturePopupModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  window.hmCloseDashFeaturePopup = function () {
    var modal = document.getElementById('dashFeaturePopupModal');
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }
    sessionStorage.setItem(STORAGE_KEY, '1');
  };

  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('dashFeaturePopupModal');
    if (modal) {
      modal.addEventListener('click', function (e) {
        if (e.target === modal) window.hmCloseDashFeaturePopup();
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') window.hmCloseDashFeaturePopup();
    });
    setTimeout(showPopup, DELAY_MS);
  });
})();
</script>
