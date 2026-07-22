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
</head>
<?= view('Layouts/public_header', ['body_class' => 'public-auth-page']) ?>

  <section class="auth-page-shell">
    <div class="auth-page-column auth-page-column--sm">
      <div class="auth-page-head">
        <div class="auth-page-brand"> 
        <a class="auth-page-title" href="https://hirematrix.serphawk.in" style="font-size:22px;text-decoration:none;">Hire Matrix</a>
        </div>
        <h1 class="auth-page-title" style="font-weight:normal;">Reset Your Password</h1>
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
          Back to Sign In
        </a>
      </div>
    </div>
  </section>

<?= view('Layouts/auth_footer') ?>
</body>
</html>
            
