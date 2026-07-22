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
            
