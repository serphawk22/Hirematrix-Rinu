<?php
$platformStats = $platformStats ?? [];
$featuredJobs = $featuredJobs ?? [];

$jobsPostedCount = (int) ($platformStats['jobs_posted'] ?? count($featuredJobs));
$candidateCount  = (int) ($platformStats['candidates'] ?? 0);
$interviewCount  = (int) ($platformStats['interviews_booked'] ?? 0);
$recruiterCount  = (int) ($platformStats['recruiters'] ?? 0);

$jobIconSet = [
    'developer' => 'fas fa-code',
    'engineer'  => 'fas fa-cogs',
    'designer'  => 'fas fa-palette',
    'manager'   => 'fas fa-chart-line',
    'data'      => 'fas fa-database',
    'marketing' => 'fas fa-bullhorn',
    'product'   => 'fas fa-briefcase',
];

$pickJobIcon = static function (string $title) use ($jobIconSet): string {
    $needle = strtolower($title);
    foreach ($jobIconSet as $key => $icon) {
        if (str_contains($needle, $key)) return $icon;
    }
    return 'fas fa-briefcase';
};

$formatAge = static function ($value): string {
    if ($value === null || $value === '') return 'Recently';
    $date = strtotime((string) $value);
    if ($date === false) return 'Recently';
    return date('M d, Y', $date);
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>HireMatrix | Home</title>
    <meta name="description" content="AI Job Portal home page">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/jquery.fancybox.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/bootstrap-select.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/icomoon/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/fonts/line-icons/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/owl.carousel.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/animate.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.css?v='       . @filemtime(FCPATH . 'jobboard/css/responsive.css')) ?>">
<style>
/* ================================================
   HERO
================================================ */
.hero {
    position: relative; overflow: hidden;
    min-height: 100vh; display: flex; align-items: center;
    padding: 100px 24px 72px; text-align: center;
    background: #ffffff;
}
#heroCanvas {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    pointer-events: none; z-index: 0;
}
.hero .container { position: relative; z-index: 1; }

.hero-h1 {
    font-size: 56px; font-weight: 600 !important;
    letter-spacing: -.03em; line-height: 1.1;
    color: var(--foreground); margin-bottom: 18px;
    min-height: unset !important;
}
.hero-h1 .grad-text {
    background: linear-gradient(135deg, #1FB7B5 0%, #53B86C 55%, #B5D84E 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero-sub {
    font-size: 20px !important; font-weight: 500;
    color: var(--muted-foreground); line-height: 1.75;
    max-width: 520px; margin: 0 auto 32px;
    min-height: unset !important;
}
.tw-cursor {
    display: inline-block; width: 2.5px; height: .85em;
    background: var(--primary); border-radius: 1px;
    margin-left: 2px; vertical-align: middle;
    animation: blink-cur .9s step-end infinite;
}
@keyframes blink-cur { 0%,100%{opacity:1} 50%{opacity:0} }

.hero-search {
    display: flex; align-items: center;
    background: #fff; border: 1.5px solid var(--border);
    border-radius: 14px; padding: 7px 7px 7px 14px;
    gap: 6px; max-width: 640px; margin: 0 auto 14px;
    box-shadow: 0 2px 12px rgba(31,183,181,.06);
    transition: border-color .2s, box-shadow .2s;
}
.hero-search:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(31,183,181,.12);
}
.search-field {
    flex: 1; display: flex; align-items: center; gap: 8px;
    padding: 4px 8px; border-radius: 9px; transition: .18s; min-width: 0;
}
.search-field:focus-within { background: var(--muted); }
.search-field i { color: var(--primary); font-size: 15px; flex-shrink: 0; }
.search-field input {
    flex: 1; background: transparent; border: none; outline: none;
    font-size: 14px; color: var(--foreground); font-family: inherit; min-width: 0;
}
.search-field input::placeholder { color: var(--text-light); }
.search-divider { width: 1px; height: 24px; background: var(--border); flex-shrink: 0; }
.search-btn {
    background: transparent !important; border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important; padding: 8px 20px;
    border-radius: 6px !important; font-size: 14px; font-weight: 600;
    transition: all 0.2s ease;
}
.search-btn:hover { background: #1FB7B5 !important; color: #ffffff !important; transform: translateY(-1px); }
.hero-hint { font-size: 12.5px; color: var(--text-light); margin-top: 8px; margin-bottom: 0; }

@media (max-width: 768px) { .hero-h1 { font-size: 38px; } .hero { padding: 90px 16px 56px; min-height: 100svh; } }
@media (max-width: 640px) {
    .hero-h1 { font-size: 32px; }
    .hero-search { flex-direction: column; align-items: stretch; padding: 10px; }
    .search-divider { display: none; }
    .search-btn { width: 100%; justify-content: center; }
}

/* ================================================
   WAVE SECTION
================================================ */
.wave-section {
    width: 100vw; margin-left: calc(-50vw + 50%);
    background: #fff; padding: 20px 0 36px;
    border-bottom: none !important; overflow: hidden; position: relative;
}
.wave-section::before, .wave-section::after {
    content: ''; position: absolute; top: 0; bottom: 0;
    width: 80px; z-index: 2; pointer-events: none;
}
.wave-section::before { left: 0;  background: linear-gradient(to right, #fff, transparent); }
.wave-section::after  { right: 0; background: linear-gradient(to left,  #fff, transparent); }
.wave-track-outer { overflow: visible; width: 100%; }
.wave-track { position: relative; height: 260px; width: 100%; }

.cat-bubble {
    position: absolute;
    display: flex; flex-direction: column; align-items: center;
    text-decoration: none !important;
}
.bubble-circle {
    width: 130px !important; height: 130px !important;
    border-radius: 50%;
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%);
    border: 1.5px solid #D9ECE5;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 6px; font-size: 28px !important; color: #0D8A90;
    will-change: transform;
}
.bubble-label {
    font-size: 11px; font-weight: 600;
    color: #0D8A90; white-space: nowrap; line-height: 1;
}

@media (max-width: 640px) {
    .bubble-circle { width: 80px !important; height: 80px !important; font-size: 18px !important; }
    .bubble-label  { font-size: 10px; }
    .wave-track    { height: 200px; }
}

/* ================================================
   FEATURED JOBS SECTION
================================================ */
.featured-jobs-section {
    padding: 64px 0 72px;
    background: transparent;
}
.featured-jobs-section .section-head-title {
    font-size: 56px !important; font-weight: 500 !important;
    color: var(--foreground); margin-bottom: 6px;
}
.featured-jobs-section .section-head-sub {
    font-size: 15px; color: var(--muted-foreground);
    max-width: 480px; margin: 0 auto 40px;
}

/* Individual job card — completely separate */
.fj-card {
    display: flex; align-items: center; gap: 16px;
    padding: 20px 24px;
    background: white !important;
    border: 1px solid #D9ECE5;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(13,138,144,0.04);
    /* animation start state */
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.55s ease, transform 0.55s ease, box-shadow 0.25s ease;
}
.fj-card.fj-visible {
    opacity: 1;
    transform: translateY(0);
}
/* gentle float once visible */
.fj-card.fj-float { animation: fjFloat 3.8s ease-in-out infinite; }
.fj-card.fj-float:nth-child(2) { animation-delay: 0.3s; }
.fj-card.fj-float:nth-child(3) { animation-delay: 0.6s; }
.fj-card.fj-float:nth-child(4) { animation-delay: 0.9s; }
.fj-card.fj-float:nth-child(5) { animation-delay: 1.2s; }
.fj-card.fj-float:nth-child(6) { animation-delay: 1.5s; }

@keyframes fjFloat {
    0%,100% { transform: translateY(0px); }
    50%      { transform: translateY(-6px); }
}

.fj-card:hover {
    box-shadow: none !important;
    transform: translateY(-3px) !important;
    animation-play-state: paused;
}

.fj-main   { flex: 1; min-width: 0; }
.fj-top    { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.fj-title  { font-size: 18px !important; font-weight: 500 !important; color: var(--foreground); letter-spacing: -.1px; }
.fj-company { font-size: 15px !important; color: var(--muted-foreground); font-weight: 500; margin-bottom: 7px; }
.fj-meta   { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.fj-meta-item { font-size: 13px !important; color: var(--text-light); display: flex; align-items: center; gap: 4px; }

.fj-tags   { flex-shrink: 0; display: flex; gap: 6px; flex-wrap: wrap; }
.fj-tag {
    font-size: 13px !important; font-weight: 500; padding: 4px 9px;
    border-radius: 6px; background: rgba(31,183,181,.08);
    color: #0D8A90; border: 1px solid #D9ECE5;
}

.fj-right  { flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; min-width: 120px; }
.fj-salary { font-size: 14px; font-weight: 700; color: var(--foreground); }

.badge-featured {
    font-size: 10px; font-weight: 700; padding: 3px 8px;
    border-radius: 50px; background: rgba(31,183,181,.12);
    color: #0D8A90; border: 1px solid #D9ECE5; letter-spacing: .03em;
}

@media (max-width: 768px) {
    .fj-card { flex-wrap: wrap; }
    .fj-right { align-items: flex-start; min-width: unset; width: 100%; flex-direction: row; justify-content: space-between; }
    .fj-tags  { width: 100%; }
}

/* ================================================
   CAREER TRANSITION
================================================ */  

.landing-career-transition{
    position:relative;
    overflow:hidden;

    padding:50px 0 !important;

    background:
    radial-gradient(
        circle at top right,
        rgba(31,183,181,.12),
        transparent 35%
    ),
    radial-gradient(
        circle at bottom left,
        rgba(181,216,78,.10),
        transparent 35%
    ),
    #ffffff;
}

.career-bg-glow{
    position:absolute;
    inset:0;
    pointer-events:none;
}

.career-content{
    max-width:1100px;
    margin:auto;
    position:relative;
    z-index:2;
}

.career-badge{
    display:inline-flex;
    align-items:center;

    padding:10px 18px;

    border-radius:999px;

    background:
    rgba(31,183,181,.08);

    color:#0D8A90;

    font-size:14px;
    font-weight:700;

    margin-bottom:32px;
}

.career-title{
    font-size:clamp(25px,3vw,55px) !important;
    line-height:0.8 !important;

    font-weight:500 !important;

    letter-spacing:-.06em;

    color:#111827;

    margin-bottom:32px;
} 
.career-title span{
    display:inline !important;

    background:
    linear-gradient(
        135deg,
        #1FB7B5,
        #53B86C,
        #B5D84E
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.career-description{
    max-width:760px;

    margin:auto;

    font-size:20px !important;

    line-height:1.9;

    color:#6b7280;

    margin-bottom:50px;
}

.career-actions{
    display:flex;
    justify-content:center;
    gap:16px;
    flex-wrap:wrap;

    margin-bottom:40px !important;
}
 
@media(max-width:768px){

    .landing-career-transition{
        padding:100px 0;
    }

    .career-title{
        font-size:52px;
    }

    .career-description{
        font-size:17px;
    }

    .career-stats{
        gap:40px;
    }

    .career-stat strong{
        font-size:38px;
    }
}
/* ================================================
   GET STARTED
================================================ */
.landing-choices{
    padding:0px 0 !important;
    overflow:hidden;
}

.choices-header{
    text-align:center;
    margin-bottom:40px !important;
}

.choices-label{
    display:inline-block;

    padding:8px 16px;

    border-radius:999px;

    background:rgba(31,183,181,.08);

    color:#0D8A90;

    font-size:12px;
    font-weight:700;

    letter-spacing:.15em;

    margin-bottom:24px;
}

.choices-title{
    font-size:clamp(25px,3vw,55px) !important;

    font-weight:500 !important;

    letter-spacing:-.04em;

    color:#111827;

    margin-bottom:16px;
}

.choices-subtitle{
    color:#6b7280;
    font-size:18px;
}

.choices-wrap{
    display:flex;
    flex-direction:column;
    gap:24px;

    max-width:1000px;
    margin:auto;
}

.choice-card{

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:45px 50px;

    background:#fff;

    border:1px solid #D9ECE5;

    border-radius:28px;

    text-decoration:none;

    color:inherit;

    box-shadow:
    0 10px 30px rgba(13, 138, 144, 0.01) !important;

    opacity:0;
    transform:translateY(80px);

    transition:
    transform 0.25s ease, 
    opacity .6s ease;
}

.choice-card.show{
    opacity:1;
    transform:translateY(0);
}

.choice-card.float{
    animation:choiceFloat 5s ease-in-out infinite;
}

.choice-card:last-child.float{
    animation-delay:1s;
}

.choice-card:hover{
    transform:translateY(-1px)!important;
text-decoration:none !important;
    border-color:#1FB7B5;

    box-shadow:
   none !important;
}

.choice-content h3{
    font-size:26px !important;
    font-weight:500 !important;
    margin-bottom:10px !important;
    color:#111827;
}

.choice-content p{
    margin:0;
    color:#6b7280;
    font-size:16px;
}
 

@keyframes choiceFloat{

    0%,100%{
        transform:translateY(0);
    }

    50%{
        transform:translateY(-10px);
    }
}

@media(max-width:768px){

    .choice-card{
        padding:32px 28px;
    }

    .choice-content h3{
        font-size:28px;
    }

    .choice-arrow{
        font-size:34px;
    }

}
</style>
</head>
<?= view('Layouts/public_header', ['body_class' => 'landing-page']) ?>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="hero py-5">
  <canvas id="heroCanvas"></canvas>
  <div class="container">
    <div class="hero-inner">
      
      <h1 class="hero-h1" id="heroH1" aria-label="Find Your Dream Job Today"></h1>
      <p class="hero-sub" id="heroSub" aria-label="Connect with top companies and discover opportunities that match your skills. AI-powered recommendations to fast-track your career."></p>
      <form action="<?= base_url('jobs') ?>" method="get" class="hero-search">
        <div class="search-field">
          <i class="fas fa-search"></i>
          <input type="text" name="search" placeholder="Job title, skills or company">
        </div>
        <div class="search-divider"></div>
        <div class="search-field">
          <i class="fas fa-map-pin"></i>
          <input type="text" name="location" placeholder="City or Remote">
        </div>
        <button type="submit" class="search-btn">Search Jobs</button>
      </form>
      <p class="hero-hint">Sign in to view AI match score, complete listings &amp; application status.</p>
    </div>
  </div>
</section>

<!-- ═══════════════ WAVE BUBBLES ═══════════════ -->
<section class="wave-section">
  <div class="wave-track-outer">
    <div class="wave-track" id="waveTrack"></div>
  </div>
</section>

<!-- ═══════════════ FEATURED JOBS ═══════════════ -->
<section class="featured-jobs-section">
  <div class="container">

    <div class="text-center mb-5">
      <h2 class="section-head-title">Featured Jobs</h2>
      <p class="section-head-sub">Hand-picked opportunities from top companies</p>
    </div>

    <div class="d-flex flex-column" style="gap: 16px;" id="fjList">
      <?php foreach (array_slice($featuredJobs, 0, 6) as $i => $job): ?>
        <?php
          $title    = (string) ($job['title']    ?? 'Untitled Role');
          $company  = trim((string) ($job['company']  ?? 'Company'));
          $location = trim((string) ($job['location'] ?? 'N/A'));
          $postedAt = $formatAge($job['created_at'] ?? $job['posted_at'] ?? null);
          $salary   = $job['salary'] ?? '';
          $tags     = $job['tags']   ?? [];
          $isFeat   = $job['is_featured'] ?? false;
        ?>
        <div class="fj-card" data-index="<?= $i ?>">
          <div class="fj-main">
            <div class="fj-top">
              <span class="fj-title"><?= esc($title) ?></span>
              <?php if ($isFeat): ?><span class="badge-featured">Featured</span><?php endif; ?>
            </div>
            <div class="fj-company"><?= esc($company) ?></div>
            <div class="fj-meta">
              <span class="fj-meta-item"><i class="fas fa-map-pin"></i> <?= esc($location) ?></span>
              <span class="fj-meta-item"><i class="fas fa-briefcase"></i> Full-time</span>
              <span class="fj-meta-item"><i class="fas fa-clock"></i> <?= esc($postedAt) ?></span>
            </div>
          </div>
          <div class="fj-tags">
            <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
              <span class="fj-tag"><?= esc($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="fj-right">
            <?php if ($salary): ?><span class="fj-salary"><?= esc($salary) ?></span><?php endif; ?>
            <a href="<?= base_url('login') ?>" class="btn btn-primary">Apply Now</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-4">
      <a href="<?= base_url('jobs') ?>" class="btn btn-outline-primary px-4">View All Jobs</a>
    </div>

  </div>
</section>

<!-- ═══════════════ CAREER TRANSITION ═══════════════ -->
<section class="landing-career-transition">

    <div class="career-bg-glow"></div>

    <div class="container-fluid px-lg-5">

        <div class="career-content text-center">
 
            <h2 class="career-title">
                Transition Into Your&#160;<span>Dream Career</span>&#160;With AI Guidance
            </h2>

            <p class="career-description">
                Discover skill gaps, build personalized learning paths,
                identify certifications, and generate a complete roadmap
                to move from your current role into the career you want.
            </p>

            <div class="career-actions">
                <a href="<?= base_url('career-transition') ?>"
                   class="btn btn-outline-primary">
                    Generate My Roadmap
                </a>

                <a href="<?= base_url('jobs') ?>"
                   class="btn btn-outline-primary">
                    Explore Careers
                </a>
            </div>

        </div>

    </div>

</section>

<!-- ═══════════════ GET STARTED ═══════════════ -->
<section class="landing-choices" id="get-started">

    <div class="container">

        <div class="choices-header"> 
            <h2 class="choices-title">
                Who Are You?
            </h2>

            <p class="choices-subtitle">
                Choose how you want to use HireMatrix.
            </p>

        </div>

        <div class="choices-wrap">

            <a href="<?= base_url('register') ?>"
               class="choice-card">

                <div class="choice-content">

                    <h3>
                        I Want To Find A Job
                    </h3>

                    <p>
                        AI matching, career roadmaps,
                        resume analysis and interview preparation.
                    </p>

                </div>

               
            </a>

            <a href="<?= base_url('recruiter/register') ?>"
               class="choice-card">

                <div class="choice-content">

                    <h3>
                        I Want To Hire Talent
                    </h3>

                    <p>
                        Candidate sourcing, AI screening
                        and recruitment automation.
                    </p>

                </div>

               

            </a>

        </div>

    </div>
<br/><br/>
</section>

<?= view('Layouts/public_footer') ?>

<!-- ═══════════════ SCRIPTS ═══════════════ -->

<!-- Hero canvas particle animation -->
<script>
(function () {
  const canvas = document.getElementById('heroCanvas');
  const hero   = canvas.parentElement;
  const ctx    = canvas.getContext('2d');
  const COLORS = ['#1FB7B5','#53B86C','#B5D84E','#0D8A90'];
  let pts = [], W, H;
  function resize() { W = canvas.width = hero.offsetWidth; H = canvas.height = hero.offsetHeight; }
  function init() {
    resize();
    pts = Array.from({length:55}, () => ({
      x: Math.random()*W, y: Math.random()*H,
      vx: (Math.random()-.5)*.35, vy: (Math.random()-.5)*.35,
      r: 2+Math.random()*2, color: COLORS[Math.floor(Math.random()*COLORS.length)]
    }));
  }
  function draw() {
    ctx.clearRect(0,0,W,H);
    pts.forEach(p => {
      p.x+=p.vx; p.y+=p.vy;
      if(p.x<0||p.x>W) p.vx*=-1;
      if(p.y<0||p.y>H) p.vy*=-1;
    });
    for(let i=0;i<pts.length;i++) for(let j=i+1;j<pts.length;j++) {
      const dx=pts[i].x-pts[j].x, dy=pts[i].y-pts[j].y, d=Math.sqrt(dx*dx+dy*dy);
      if(d<90){
        ctx.beginPath();
        ctx.strokeStyle=`rgba(31,183,181,${(1-d/90)*.28})`;
        ctx.lineWidth=1; ctx.moveTo(pts[i].x,pts[i].y); ctx.lineTo(pts[j].x,pts[j].y); ctx.stroke();
      }
    }
    pts.forEach(p => {
      ctx.beginPath(); ctx.arc(p.x,p.y,p.r,0,Math.PI*2); ctx.fillStyle=p.color+'55'; ctx.fill();
      ctx.beginPath(); ctx.arc(p.x,p.y,p.r*.5,0,Math.PI*2); ctx.fillStyle=p.color+'cc'; ctx.fill();
    });
    requestAnimationFrame(draw);
  }
  init(); draw();
  window.addEventListener('resize', resize);
})();
</script>

<!-- Hero typewriter -->
<script>
(function () {
  var GRAD_START = 'Find Your ', GRAD_WORD = 'Dream Job', FULL_PLAIN = 'Find Your Dream Job Today';
  var SUB_TEXT = 'Connect with top companies and discover opportunities that match your skills. AI-powered recommendations to fast-track your career.';
  var h1El = document.getElementById('heroH1'), subEl = document.getElementById('heroSub');
  var charIdx = 0, subIdx = 0;
  function buildH1(n) {
    var plain=FULL_PLAIN.slice(0,n), gs=GRAD_START.length, gw=GRAD_WORD.length, cursor='<span class="tw-cursor"></span>';
    if(n<=gs) return plain+cursor;
    if(n<=gs+gw) return GRAD_START+'<span class="grad-text">'+plain.slice(gs)+'</span>'+cursor;
    return GRAD_START+'<span class="grad-text">'+GRAD_WORD+'</span>'+plain.slice(gs+gw)+(n<FULL_PLAIN.length?cursor:'');
  }
  function typeH1() {
    if(charIdx>FULL_PLAIN.length){h1El.innerHTML=buildH1(FULL_PLAIN.length);setTimeout(typeSub,380);return;}
    h1El.innerHTML=buildH1(charIdx++); setTimeout(typeH1,52+Math.random()*28);
  }
  function typeSub() {
    if(subIdx>SUB_TEXT.length){subEl.innerHTML=SUB_TEXT;return;}
    subEl.innerHTML=SUB_TEXT.slice(0,subIdx++)+'<span class="tw-cursor"></span>';
    setTimeout(typeSub,18+Math.random()*12);
  }
  setTimeout(typeH1,300);
})();
</script>

<!-- Wave bubble animation -->
<script>
(function () {
  const cats = [
    {icon:'fa-code',           label:'Developer'},
    {icon:'fa-bullhorn',       label:'Marketing'},
    {icon:'fa-home',           label:'Remote'},
    {icon:'fa-clock',          label:'Full-time'},
    {icon:'fa-database',       label:'Data & AI'},
    {icon:'fa-users',          label:'HR'},
    {icon:'fa-palette',        label:'Design'},
    {icon:'fa-chart-line',     label:'Finance'},
    {icon:'fa-handshake',      label:'Sales'},
    {icon:'fa-heartbeat',      label:'Healthcare'},
    {icon:'fa-graduation-cap', label:'Education'},
    {icon:'fa-cogs',           label:'Operations'},
  ];
  const track      = document.getElementById('waveTrack');
  const ITEM_W     = 150;
  const AMPLITUDE  = 80;
  const WAVE_SPEED = 0.00022;
  const SCROLL_SPD = 0.55;
  const totalW     = cats.length * ITEM_W;
  const bubbles    = [];

  for (let i = 0; i < cats.length * 3; i++) {
    const c  = cats[i % cats.length];
    const el = document.createElement('a');
    el.href      = '<?= base_url("jobs") ?>';
    el.className = 'cat-bubble';
    el.style.cssText = 'position:absolute; pointer-events:none;';
    el.innerHTML = `<div class="bubble-circle"><i class="fas ${c.icon}"></i><span class="bubble-label">${c.label}</span></div>`;
    track.appendChild(el);
    bubbles.push(el);
  }

  const centerY = 90;
  let offset = 0, t = 0;

  function animate() {
    offset += SCROLL_SPD;
    if (offset >= totalW) offset -= totalW;
    t += WAVE_SPEED;

    bubbles.forEach((el, i) => {
      let x = i * ITEM_W - offset;
      x = ((x % (totalW * 3)) + totalW * 3) % (totalW * 3) - totalW;
      const phase = (x / (ITEM_W * cats.length)) * Math.PI * 2;
      const y = centerY + Math.sin(phase - t * Math.PI * 2 * cats.length) * AMPLITUDE;
      el.style.left      = x + 'px';
      el.style.top       = y + 'px';
      el.style.transform = 'translateX(-50%)';
    });
    requestAnimationFrame(animate);
  }
  animate();
})();
</script>

<!-- Featured jobs: unfold one by one then float forever -->
<script>
window.addEventListener('load', function () {
  var cards = document.querySelectorAll('#fjList .fj-card');
  if (!cards.length) return;

  cards.forEach(function (card) {
    var i = parseInt(card.getAttribute('data-index'), 10) || 0;

    // Step 1 — reveal (slide up + fade in), staggered
    setTimeout(function () {
      card.classList.add('fj-visible');

      // Step 2 — once transition finishes, start the float animation permanently
      card.addEventListener('transitionend', function onDone(e) {
        if (e.propertyName !== 'opacity') return;
        card.removeEventListener('transitionend', onDone);
        card.classList.add('fj-float');
      });
    }, 200 + i * 220);
  });
});
</script>
<script>

document.addEventListener('DOMContentLoaded',()=>{

    const section=document.querySelector('.landing-choices');

    const cards=document.querySelectorAll('.choice-card');

    const observer=new IntersectionObserver(

        entries=>{

            entries.forEach(entry=>{

                if(entry.isIntersecting){

                    cards.forEach((card,index)=>{

                        setTimeout(()=>{

                            card.classList.add('show');

                            setTimeout(()=>{

                                card.classList.add('float');

                            },500);

                        },index*250);

                    });

                    observer.disconnect();

                }

            });

        },

        {
            threshold:.25
        }

    );

    observer.observe(section);

});

</script>

</body>
</html>