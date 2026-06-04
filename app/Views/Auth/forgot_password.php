                        <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Forgot Password - HireMatrix</title>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">

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
    <link rel="stylesheet" href="<?= base_url('custom/public-pages.css?v=' . @filemtime(FCPATH . 'custom/public-pages.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.css?v=' . @filemtime(FCPATH . 'jobboard/css/responsive.css')) ?>">
 <style>
/* ===============================
🔥 LOGIN PAGE BACKGROUND (HERO STYLE)
================================= */
.auth-page-shell {
  position: relative;
  min-height: 100vh;
  overflow: hidden;

  background: linear-gradient(
    120deg,
    #dbe6ff,
    #eef2ff,
    #fd6c0555,
    #f84b073f,
    #dbe6ff
  );
  background-size: 300% 300%;
  animation: gradientMove 6s ease-in-out infinite;
}

/* Gradient animation */
@keyframes gradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
/* ===============================
🔥 FLOATING BLOBS
================================= */
.auth-page-shell::before,
.auth-page-shell::after {
  content: "";
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.7;
  z-index: 0;
}

.auth-page-shell::before {
  width: 420px;
  height: 420px;
  background: #ac75ffac;
  top: -100px;
  left: -100px;
  animation: blobMove1 5s ease-in-out infinite alternate;
}

.auth-page-shell::after {
  width: 420px;
  height: 420px;
  background: #6b95ffe5;
  bottom: -100px;
  right: -100px;
  animation: blobMove2 6s ease-in-out infinite alternate;
}

@keyframes blobMove1 {
  0% { transform: translate(0, 0); }
  100% { transform: translate(120px, 80px); }
}

@keyframes blobMove2 {
  0% { transform: translate(0, 0); }
  100% { transform: translate(-120px, -80px); }
}
/* ===============================
✨ LIGHT SWEEP
================================= */
.auth-page-shell .light-sweep {
  position: absolute;
  top: 0;
  left: -120%;
  width: 60%;
  height: 100%;
  z-index: 1;

  background: linear-gradient(
    120deg,
    transparent,
    rgba(255,255,255,0.5),
    transparent
  );

  transform: skewX(-20deg);
  animation: sweepMove 4s linear infinite;
}

@keyframes sweepMove {
  0% { left: -120%; }
  100% { left: 130%; }
}
/* ===============================
💎 LOGIN CARD ANIMATION
================================= */
.auth-page-card {
  position: relative;
  z-index: 2;

  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.3);

  box-shadow: 0 20px 60px rgba(0,0,0,0.15);

  animation: cardFadeUp 0.8s ease;
  overflow: hidden;
}

/* Card fade animation */
@keyframes cardFadeUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
/* ===============================
✨ INNER CARD LIGHT EFFECT
================================= */
.auth-page-card::before {
  content: "";
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;

  background: linear-gradient(
    120deg,
    transparent,
    rgba(255,255,255,0.3),
    transparent
  );

  transform: rotate(25deg);
  animation: cardLightMove 6s linear infinite;
}

@keyframes cardLightMove {
  0% { transform: translateX(-100%) rotate(25deg); }
  100% { transform: translateX(100%) rotate(25deg); }
}
/* ===============================
🌙 DARK MODE
================================= */
body.dark .auth-page-shell {
  background: linear-gradient(
    120deg,
    #2214336c,
        #44261c,
        #2a215553
  );
}

body.dark .auth-page-shell::before {
  background: rgba(119, 0, 255, 0.24);
}

body.dark .auth-page-shell::after {
  background: rgba(8, 0, 255, 0.13);
}

/* Dark card */
body.dark .auth-page-card {
  background: rgba(20, 20, 30, 0.6);
  border: 1px solid rgba(255,255,255,0.1);
}

/* softer inner light */
body.dark .auth-page-card::before {
  background: linear-gradient(
    120deg,
    transparent,
    rgba(0,150,255,0.2),
    transparent
  );
}
body.dark .auth-page-shell .light-sweep {
  background: linear-gradient(
    120deg,
    transparent,
    rgba(139, 92, 246, 0.25),
    rgba(59, 130, 246, 0.18),
    transparent
  );

  opacity: 0.6;
  filter: blur(0.5px);
}
body.dark .auth-page-card::before {
  background: linear-gradient(
    120deg,
    transparent,
    rgba(139, 92, 246, 0.18),
    transparent
  );

  opacity: 0.7;
}
  /* 🚫 Prevent animation layers from blocking clicks */
.auth-page-shell::before,
.auth-page-shell::after,
.auth-page-card::before,
.auth-page-shell .light-sweep {
  pointer-events: none;
}
/* 🌙 Fix Chrome autofill white background */
body.dark input:-webkit-autofill,
body.dark input:-webkit-autofill:hover,
body.dark input:-webkit-autofill:focus,
body.dark textarea:-webkit-autofill,
body.dark select:-webkit-autofill {
  -webkit-box-shadow: 0 0 0px 1000px rgba(20,20,30,0.9) inset !important;
  box-shadow: 0 0 0px 1000px rgba(20,20,30,0.9) inset !important;

  -webkit-text-fill-color: #ffffff !important;
  caret-color: #ffffff !important;

  transition: background-color 5000s ease-in-out 0s;
}
  </style>
</head>
<?= view('Layouts/public_header', ['body_class' => 'public-auth-page']) ?>

  <section class="auth-page-shell">
    <div class="auth-page-column auth-page-column--sm">
      <div class="auth-page-head">
        <div class="auth-page-brand">
          <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix Logo">
          <span class="auth-page-brand-text">HireMatrix</span>
        </div>
        <h1 class="auth-page-title">Reset Your Password</h1>
        <p class="auth-page-subtitle">Enter your email address and we'll send you a reset link.</p>
      </div>

      <div class="card rounded-5 border-1 auth-page-card">
        <div class="card-body p-4 p-md-5">
          <?php if (session()->getFlashdata('error')): ?>
              <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('success')): ?>
              <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
          <?php endif; ?>

          <?php $mailPreview = session()->getFlashdata('mail_preview'); ?>
          <?php if (!empty($mailPreview['url'])): ?>
              <div class="alert alert-info">
                  <strong><?= esc($mailPreview['title'] ?? 'Email preview') ?>:</strong>
                  testing mode is enabled, so no real email was sent.<br>
                  Recipient: <code><?= esc($mailPreview['recipient'] ?? '') ?></code><br>
                  Link: <a href="<?= esc($mailPreview['url']) ?>"><?= esc($mailPreview['url']) ?></a>
              </div>
          <?php endif; ?>

          <form method="post" action="<?= base_url('forgot-password') ?>" class="auth-form">
              <?= csrf_field() ?>

              <div>
                <label class="form-label auth-field-label">Email Address</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-envelope auth-field-icon"></i>
                  <input type="email" id="email" name="email" placeholder="your@email.com" class="form-control auth-input" value="<?= esc(old('email')) ?>" required>
                </div>
                <p class="auth-helper-text">We'll send you an email with instructions to reset your password.</p>
              </div>

              <button type="submit" class="btn btn-primary btn-lg auth-primary-btn">
                Send Reset Link
              </button>
          </form>
        </div>
      </div>

      <div class="auth-footer-copy">
        <a href="<?= base_url('login') ?>" class="auth-footer-link auth-back-link">
          <i class="fas fa-arrow-left"></i>
          Back to Sign In
        </a>
      </div>
    </div>
  </section>

<?= view('Layouts/public_footer') ?>
</body>
</html>
            