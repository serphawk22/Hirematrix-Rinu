                        <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reset Password | HireMatrix</title>
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
    <style>@media (prefers-color-scheme: dark) {
    .auth-page-shell {
        background: #111111 !important;
    }

    .auth-page-card {
        background: #111111 !important;
        border-color: #23343A !important;
    }

    .auth-page-card:hover {
        transform: translateY(-1px);
    }

    /* Title & subtitle */
    .auth-page-title,
    .auth-page-title a {
        color: #F8FAFC !important;
    }

    .auth-page-subtitle {
        color: #7A8B96 !important;
    }

    /* Google button */
  .btn-google-auth {
    background: #111111 !important;
    border: 1px solid #23343A  !important;
    color: #E2E8F0 !important;
    outline: none !important;
    box-shadow: none !important;
}

.btn-google-auth:hover,
.btn-google-auth:focus,
.btn-google-auth:active {
    background: #111111 !important;
    border: 1px solid #23343A  !important;
    color: #E2E8F0 !important;
    box-shadow: none !important;
}

    /* Divider */
    .auth-divider-line {
        background: #23343A !important;
    }
    .auth-divider-text {
        color: #7A8B96 !important;
        background: #111111 !important;
    }

    /* Labels */
    .auth-field-label {
        color: #94A3B8 !important;
    }

    /* Inputs */
    .auth-input {
        background: #111111 !important;
        border-color: #23343A !important;
        color: #E2E8F0 !important;
    }
    .auth-input::placeholder {
        color: #3D5560 !important;
    }
    .auth-input:focus {
        border-color: #0D8A90 !important;
        box-shadow: none !important;
    }

    /* Field icon */
    .auth-field-icon {
        color: #3D5560 !important;
    }

    /* Password toggle */
    .auth-password-toggle {
        color: #3D5560 !important;
    }

    /* Remember me & links */
    .auth-remember {
        color: #94A3B8 !important;
    }
    .auth-footer-link {
        color: #1FB7B5 !important;
    }

    /* Submit button */
    .auth-primary-btn {
        background: transparent !important;
        border: 1.5px solid #1FB7B5 !important;
        color: #1FB7B5 !important;
    }
    .auth-primary-btn:hover {
        background: #1FB7B5 !important;
        color: #ffffff !important;
    }

    /* Footer text */
    .auth-footer-copy p {
        color: #7A8B96 !important;
    }

    /* Alerts */
    .alert-danger {
        background: #2D1515 !important;
        border-color: #7F1D1D !important;
        color: #FCA5A5 !important;
    }
    .alert-success {
        background: #052e16 !important;
        border-color: #166534 !important;
        color: #86efac !important;
    }
}</style>
</head>
<?= view('Layouts/public_header', ['body_class' => 'public-auth-page']) ?>

  <section class="auth-page-shell">
    <div class="auth-page-column auth-page-column--sm">
      <div class="auth-page-head">
        <div class="auth-page-brand"> 
        <a class="auth-page-title" href="https://hirematrix.serphawk.in" style="font-size:22px;text-decoration:none;">Hire Matrix</a>
        </div>
        <h1 class="auth-page-title" style="font-weight:normal;">Reset Password</h1>
        <p class="auth-page-subtitle">Choose a new password for your account.</p>
      </div>

      <div class="card rounded-5 border-1 auth-page-card">
        <div class="card-body p-4 p-md-5">
          <h2 class="h5 mb-3">Choose a new password</h2>

          <form method="post" action="<?= base_url('reset-password/' . $token) ?>" class="auth-form">
              <?= csrf_field() ?>

              <?php if (session()->getFlashdata('error')): ?>
                  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
              <?php endif; ?>

              <?php $validation = session()->getFlashdata('validation'); ?>
              <?php if ($validation): ?>
                  <div class="alert alert-danger"><?= $validation->listErrors() ?></div>
              <?php endif; ?>

              <div>
                <label class="form-label auth-field-label">New Password</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-lock auth-field-icon"></i>
                  <input type="password" id="password" name="password" class="form-control auth-input auth-input--password" required>
                </div>
              </div>

              <div>
                <label class="form-label auth-field-label">Confirm Password</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-lock auth-field-icon"></i>
                  <input type="password" id="confirm_password" name="confirm_password" class="form-control auth-input auth-input--password" required>
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-lg auth-primary-btn">Reset password</button>
          </form>
        </div>
      </div>
    </div>
  </section>

<?= view('Layouts/auth_footer') ?>
</body>
</html>
            