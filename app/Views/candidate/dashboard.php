<?= view('Layouts/candidate_header', ['title' => 'Dashboard']) ?>


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
        'eyebrow' => 'Job Search Strategy Coach',
        'title' => 'Search Smarter, Not Harder',
        'rows' => [
            'Weekly application priorities',
            'Post-application follow-up plan',
            'Traction-focused role targeting',
        ],
        'cta_label' => 'Open Full Strategy',
        'cta_url' => base_url('candidate/job-search-strategy'),
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
<style>
/* Reset any inherited white "card" styling on the ancestor section/container
   so the gradient panel below is the ONLY visible box — no white halo. */
.dash-pro-wrap{
  background:transparent !important;
  box-shadow:none !important;
  border:none !important;
  padding-top:0 !important;
  padding-bottom:0 !important;
}
.dash-pro-wrap .container-fluid{
  background:transparent !important;
  box-shadow:none !important;
  border:none !important;
}

.dash-pro-promo{ margin: 8px 0 32px; }

/* ── Spotlight panel (single box, no nested white card) ───────── */
.dash-pro-panel{
  position:relative;
  overflow:hidden;
  border-radius:24px;
  padding:36px 32px;
  background:
    linear-gradient( rgba(31, 183, 181, 0.19), transparent 135%), 
    var(--gradient-soft);
  border:1px solid var(--border);
}

.dash-pro-panel-head{
  position:relative;z-index:1;
  display:flex;justify-content:space-between;align-items:flex-end;
  gap:16px;margin-bottom:26px;flex-wrap:wrap;
}
.dash-pro-badge{
  display:inline-flex;align-items:center;gap:6px;
  font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;
  color:#fff;background:var(--gradient-primary);
  padding:5px 12px;border-radius:999px;margin-bottom:10px;
}
.dash-pro-panel-head h2{
  font-size:22px;font-weight:800;color:var(--foreground);margin:0 0 4px;
}
.dash-pro-panel-head p{margin:0;color:var(--muted-foreground);font-size:14px;}
.dash-pro-promo-cta{
  background:var(--gradient-primary);color:#fff !important;border:none;
  border-radius:99px !important;padding:10px 24px;font-size:13px;font-weight:700;
  text-decoration:none !important;white-space:nowrap;
  box-shadow:0 4px 14px rgba(31,183,181,.25);
  transition:transform .15s,box-shadow .15s;
}
.dash-pro-promo-cta:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(31,183,181,.35);color:#fff !important;}

/* ── Card grid ───────────────────────────────────────────────── */
/* ── Carousel wrapper ───────────────────────────────────────── */
.dash-pro-carousel{ position:relative; }

.dash-pro-grid{
  position:relative;z-index:1;
  display:flex;
  gap:18px;
  overflow-x:auto;
  overflow-y:visible;
  scroll-snap-type:x proximity;   /* was: mandatory — was fighting JS scroll */
  -webkit-overflow-scrolling:touch;
  padding:8px 2px 10px;
  margin:-8px -2px -10px;
}
.dash-pro-grid::-webkit-scrollbar{ display:none; }
.dash-pro-grid{ scrollbar-width:none; -ms-overflow-style:none; }

/* Gradient-ring wrapper: thin rainbow border via padding trick */
.dash-pro-ring{
  flex:0 0 calc(25% - 13.5px);
  scroll-snap-align:start;
  border-radius:16px;
  padding:1.5px;
  background: linear-gradient(
      135deg,
      #1FB7B5 0%,
      #53B86C 55%,
      #B5D84E 100%
    ) !important;
  transition:transform .2s ease, box-shadow .2s ease;
}
.dash-pro-ring:hover{
  transform:translateY(-1px) !important;
  box-shadow:0 10px 24px rgba(31,183,181,.18);
}
@media (max-width: 1199px){ .dash-pro-ring{ flex:0 0 calc(50% - 9px); } }
@media (max-width: 575px){ .dash-pro-ring{ flex:0 0 85%; } }

.dash-pro-carousel-nav{
  position:absolute;top:50%;transform:translateY(-50%);
  width:38px;height:38px;border-radius:50%;
  background:var(--card);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;z-index:5;
  box-shadow:0 4px 12px rgba(0,0,0,.10);
  color:var(--foreground);
  transition:transform .15s,box-shadow .15s;
  pointer-events:auto;
  outline:none !important;
  -webkit-tap-highlight-color:transparent;
}
.dash-pro-carousel-nav:hover{ transform:translateY(-50%) scale(1.07); }
.dash-pro-carousel-prev{ left:-16px; }
.dash-pro-carousel-next{ right:-16px; }
@media (max-width: 575px){ .dash-pro-carousel-nav{ display:none; } }

body.dark .dash-pro-carousel-nav{
  box-shadow:0 4px 14px rgba(0,0,0,.4);
}
.dash-pro-carousel-nav:focus,
.dash-pro-carousel-nav:focus-visible,
.dash-pro-carousel-nav:active{
  outline:none !important;
  box-shadow:0 4px 12px rgba(0,0,0,.10);
}
.dash-pro-card{
   background: linear-gradient(
      135deg,
      #F4FBFA 0%,
      #EEF9F2 100%
    );
  border-radius:14.5px;
  padding:22px;
  display:flex;flex-direction:column;
  position:relative;
  overflow:hidden;
  cursor:pointer;
  height:100%;
}

.dash-pro-card-lock{
  position:absolute;top:16px;right:16px;
  font-size:12px;color:var(--text-light);
}

.dash-pro-card-icon{
  width:44px;height:44px;border-radius:12px;
  background:var(--gradient-soft);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:14px;
  position:relative;
}
.dash-pro-card-icon i{
  font-size:18px;
  background:var(--gradient-primary);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.dash-pro-card-play-badge{
  position:absolute;top:-6px;right:-6px;
  width:20px;height:20px;border-radius:50%;
  background:var(--gradient-primary);
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 2px 4px rgba(31,183,181,.04) !important;
  border:2px solid var(--card);
}
.dash-pro-card-play-badge i{
  font-size:8px;color:#fff;background:none;-webkit-text-fill-color:#fff;margin-left:1px;
}

.dash-pro-card-eyebrow{
  display:inline-block;font-size:10.5px;font-weight:700;letter-spacing:.05em;
  text-transform:uppercase;color:var(--primary-dark);
  background:var(--muted);border:1px solid var(--border);
  padding:3px 10px;border-radius:999px;margin-bottom:10px;align-self:flex-start;
}
.dash-pro-card-title{
  font-size:15px;font-weight:700;color:var(--foreground);margin:0 0 12px;line-height:1.35;
}
.dash-pro-card-rows{list-style:none;margin:0 0 16px;padding:0;flex:1;}
.dash-pro-card-rows li{
  display:flex;align-items:flex-start;gap:8px;font-size:12.5px;
  color:var(--muted-foreground);margin-bottom:8px;line-height:1.4;
}
.dash-pro-card-rows li i{
  color:var(--secondary);margin-top:2px;font-size:12px;flex-shrink:0;
}
.dash-pro-card-watch,
.dash-pro-card-cta{
  display:inline-flex;align-items:center;gap:6px;
  font-size:12.5px;font-weight:700;
  margin-top:auto;
  background:var(--gradient-primary);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.dash-pro-card-watch i,
.dash-pro-card-cta i{font-size:11px;color:var(--primary-dark);-webkit-text-fill-color:var(--primary-dark);}

/* Video modal */
.dash-pro-video-modal{
  display:none;position:fixed;inset:0;z-index:1080;
  align-items:center;justify-content:center;
  background:rgba(15, 23, 28, 0.72);
  padding:20px;
}
.dash-pro-video-modal.is-open{display:flex;}
.dash-pro-video-modal-box{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:16px;
  width:100%;max-width:1020px !important;
  overflow:hidden;
  box-shadow:0 20px 60px rgba(0,0,0,.35);
}
.dash-pro-video-modal-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 20px;border-bottom:1px solid var(--border);
  gap:12px;
}
.dash-pro-video-modal-head h4{
  margin:0;font-size:15px;font-weight:700;color:var(--foreground);
}
.dash-pro-video-modal-close{
  background:var(--muted);border:none !important;
  color:var(--foreground);width:30px;height:30px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;flex-shrink:0;font-size:13px;line-height:1;
  transition:background .15s,color .15s,transform .15s;
  outline:none !important;
  box-shadow:none !important;
  -webkit-tap-highlight-color:transparent;
}
.dash-pro-video-modal-close:focus,
.dash-pro-video-modal-close:active,
.dash-pro-video-modal-close:focus-visible{
  outline:none !important;
  box-shadow:none !important;
  border:none !important;
}
.dash-pro-video-modal-close:hover{
  transform:translateY(-1px) !important;
}
.dash-pro-video-modal-body{ background:#000; }
.dash-pro-video-modal-body video{
  display:block;width:100%;max-height:70vh;background:#000;
} 
/* ── Dark theme boost ───────────────────────────────────────────
   Tokens already flip via var(--foreground)/var(--card)/etc, but the
   glow + shadows below are tuned for a light backdrop and go flat
   against a near-black page — brighten/re-tune them here. */
body.dark .dash-pro-panel{
  border-color:rgba(31,183,181,.35);
  background:
    radial-gradient(circle at top right, rgba(31,183,181,.24), transparent 50%),
    radial-gradient(circle at bottom left, rgba(181,216,78,.18), transparent 50%),
    var(--gradient-soft);
  box-shadow:0 0 0 1px rgba(31,183,181,.10), 0 28px 64px rgba(31,183,181,.12);
}
 
body.dark .dash-pro-badge{
  box-shadow:0 2px 6px rgba(31,183,181,.20) !important;
}
 
body.dark .dash-pro-promo-cta{
  box-shadow:0 2px 9px rgba(31,183,181,.04) !important;
}
body.dark .dash-pro-promo-cta:hover{
  box-shadow:0 6px 22px rgba(31,183,181,.55);
}
 
body.dark .dash-pro-ring{
  background:linear-gradient(135deg, #24D9D6, #6BD886, #C7EB6B);
}
body.dark .dash-pro-ring:hover{
  box-shadow:0 4px 12px rgba(31,183,181,.04) !important;
}
 
body.dark .dash-pro-card{
  background:var(--card);
}
 
body.dark .dash-pro-card-icon{
  background:linear-gradient(135deg, rgba(31,183,181,.22), rgba(181,216,78,.20));
}
body.dark .dash-pro-card-icon i{
  background:linear-gradient(135deg, #24D9D6, #6BD886, #C7EB6B);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
 
body.dark .dash-pro-card-play-badge{
  box-shadow:0 2px 10px rgba(31,183,181,.55);
}
 
body.dark .dash-pro-card-eyebrow{
  background:var(--muted);
  border-color:rgba(31,183,181,.30);
}
 
body.dark .dash-pro-card-watch,
body.dark .dash-pro-card-cta{
  background:linear-gradient(135deg, #24D9D6, #6BD886, #C7EB6B);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
/* Jobs Matching Your Profile: vertical stacked cards */
 .candidate-app .job-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--border, #f0f1f3);
}
.candidate-app .job-card-posted {
    font-size: 12.5px;
    color: var(--muted-foreground, #8a94a0);
}
.candidate-app .job-card-save {
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
.candidate-app .job-card-save i { font-size: 14px; }
.candidate-app .job-card-save i.fas { color: var(--primary, #1FB7B5); }
.candidate-app .job-card-save.is-saving { opacity: .6; pointer-events: none; }

@media (max-width: 575.98px) {
    .candidate-app .dashboard-jobs-grid .job-card.dashboard-card {
        padding: 16px 18px;
    }
}
body.dark .job-card.dashboard-card{
    background-color:var(--card) !important;
 }
</style>

 
      <div class="dash-pro-panel">

        <div class="dash-pro-panel-head">
          <div>
            <div class="dash-pro-badge"><i class="fas fa-crown"></i> Pro tools</div>
          <h2 style="font-weight: bold; background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent;">
  Unlock more with PRO
</h2>
            <p>AI-powered tools to help you land your next role faster.</p>
          </div>
          <a href="<?= base_url('premium/plans') ?>" class="btn btn-primary dash-pro-promo-cta">
            Become a Pro
          </a>
        </div>

        <div class="dash-pro-carousel" id="dashProCarousel">
          <button type="button" class="dash-pro-carousel-nav dash-pro-carousel-prev" id="dashProPrev" aria-label="Previous features">
              <i class="fas fa-chevron-left"></i>
          </button>
          <button type="button" class="dash-pro-carousel-nav dash-pro-carousel-next" id="dashProNext" aria-label="Next features">
              <i class="fas fa-chevron-right"></i>
          </button>

          <div class="dash-pro-grid" id="dashProGrid">
          <?php foreach ($proFeatureSlides as $slide):
              $hasVideo = !empty($slide['video_url']);
              $cardTag  = $hasVideo ? 'div' : 'a';
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
                      href="<?= esc($slide['cta_url'] ?? base_url('premium/plans')) ?>" style="text-decoration:none;"
                  <?php endif; ?>
                  class="dash-pro-card"
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
            <video id="dashProVideoModalPlayer" controls controlsList="nofullscreen noremoteplayback" preload="none"></video>
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

window.addEventListener('load', function(){
    var track = document.getElementById('dashProGrid');
    var prev  = document.getElementById('dashProPrev');
    var next  = document.getElementById('dashProNext');
    var wrap  = document.getElementById('dashProCarousel');
    if (!track || !prev || !next || !wrap) return;

    function cardStep(){
        var card = track.querySelector('.dash-pro-ring');
        var gap = 18;
        return card ? (card.getBoundingClientRect().width + gap) : 300;
    }

    function maxScroll(){
        return Math.max(0, track.scrollWidth - track.clientWidth);
    }

    var animId = null;
    function animateTo(target){
        if (animId) cancelAnimationFrame(animId);
        var start = track.scrollLeft;
        var change = target - start;
        var duration = 650;
        var startTime = null;

        function step(ts){
            if (!startTime) startTime = ts;
            var progress = Math.min(1, (ts - startTime) / duration);
            var eased = 1 - Math.pow(1 - progress, 3); // ease-out
            track.scrollLeft = start + change * eased;
            if (progress < 1) {
                animId = requestAnimationFrame(step);
            } else {
                animId = null;
            }
        }
        animId = requestAnimationFrame(step);
    }

    function goNext(){
        var max = maxScroll();
        if (max <= 0) return;
        var target = track.scrollLeft >= max - 4 ? 0 : Math.min(max, track.scrollLeft + cardStep());
        animateTo(target);
    }

    function goPrev(){
        var max = maxScroll();
        if (max <= 0) return;
        var target = track.scrollLeft <= 4 ? max : Math.max(0, track.scrollLeft - cardStep());
        animateTo(target);
    }

    next.addEventListener('click', function(e){ e.preventDefault(); stopAuto(); goNext(); startAuto(); });
    prev.addEventListener('click', function(e){ e.preventDefault(); stopAuto(); goPrev(); startAuto(); });

    var autoTimer = null;
    var AUTO_DELAY = 4000;

    function startAuto(){
        stopAuto();
        autoTimer = setInterval(goNext, AUTO_DELAY);
    }
    function stopAuto(){
        if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    }

    wrap.addEventListener('mouseenter', stopAuto);
    wrap.addEventListener('mouseleave', startAuto);
    wrap.addEventListener('touchstart', stopAuto, { passive: true });
    wrap.addEventListener('touchend', function(){ setTimeout(startAuto, AUTO_DELAY); }, { passive: true });

    startAuto();
});
</script>
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

            <div class="dashboard-jobs-grid">
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
            <div class="job-card dashboard-card">
                <a href="<?= base_url('job/' . $jobId) ?>" class="job-card-link" style="text-decoration:none;color:inherit;display:block;">
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
                    <?php else: ?>
                        <div class="job-card-desc job-card-desc--empty" aria-hidden="true"></div>
                    <?php endif; ?>

                    <?php if (!empty($tags)): ?>
                        <div class="job-card-tags">
                            <?php foreach (array_slice($tags, 0, 6) as $i => $tag): ?>
                                <span class="job-card-tag"><?= esc($tag) ?></span><?php if ($i < min(count($tags), 6) - 1): ?><span class="job-card-tag-dot">·</span><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="job-card-tags job-card-tags--empty" aria-hidden="true"></div>
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
        <?php endforeach; ?>
    <?php else: ?>
        <div class="dashboard-panel" style="grid-column:1/-1">
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
</div><!-- /.dashboard-jobboard --> 
 

<?= view('Layouts/candidate_footer') ?>
