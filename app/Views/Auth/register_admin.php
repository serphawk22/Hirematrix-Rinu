                        <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Recruiter Registration | HireMatrix</title>
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
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.min.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('custom/public-pages.css?v=' . @filemtime(FCPATH . 'custom/public-pages.css')) ?>">
    <style>
    /* ===============================
   LOGIN PAGE SHELL
================================= */
.auth-page-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;

    /* Same colors, calmer angle + tighter spread = no glow */
    background:white !important;

    transform: translateZ(0);
    backface-visibility: hidden;
}

.auth-page-shell::before,
.auth-page-shell::after {
    display: none;
}
/* ===============================
   LOGIN CARD
================================= */
.auth-page-card {
    position: relative;
    z-index: 2;

    background: #ffffff !important;

    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);

    /* Softer border — less glassy */
    border: 1px solid #D9ECE5;

    animation: cardFadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;

     transition: all .25s ease;
}

.auth-page-card:hover {
    transform: translateY(-1px);
}

@keyframes cardFadeUp {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
  </head>
<?= view('Layouts/public_header', ['body_class' => 'public-auth-page']) ?>

  <section class="auth-page-shell"> 
    <div class="auth-page-column auth-page-column--md">
      <div class="auth-page-head">
        <h1 class="auth-page-title" style="font-weight:normal;">Create Recruiter Account</h1>
        <p class="auth-page-subtitle">Create your recruiter account to post jobs and manage applications.</p>
      </div>

      <div class="card rounded-5 border-1 auth-page-card">
        <div class="card-body p-4 p-md-5">
          <?php if (session()->getFlashdata('error')) : ?>
              <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
          <?php endif; ?>

          <form method="post" action="<?= base_url('recruiter/register') ?>" class="auth-form">
              <?= csrf_field() ?>

              <div>
                <label class="form-label auth-field-label">Company Name</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-building auth-field-icon"></i>
                  <input type="text" id="company_name" name="company_name" placeholder="Your company name" class="form-control auth-input" value="<?= old('company_name') ?>" required>
                </div>
              </div>

              <div>
                <label class="form-label auth-field-label">Recruiter Type</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-user-tie auth-field-icon"></i>
                  <?php $recruiterType = old('recruiter_type', 'direct_employer'); ?>
                  <select id="recruiter_type" name="recruiter_type" class="form-control auth-input" required>
                    <option value="direct_employer" <?= $recruiterType === 'direct_employer' ? 'selected' : '' ?>>Direct employer</option>
                    <option value="consultancy" <?= $recruiterType === 'consultancy' ? 'selected' : '' ?>>Consultancy / staffing agency</option>
                  </select>
                </div>
                <small class="text-muted">Consultancy accounts can register here, but job posting starts after admin verification.</small>
                <?php if (session('validation') && session('validation')->hasError('recruiter_type')): ?>
                    <small class="text-danger"><?= session('validation')->getError('recruiter_type') ?></small>
                <?php endif; ?>
              </div>

              <div>
                <label class="form-label auth-field-label">Recruiter Name</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-user auth-field-icon"></i>
                  <input type="text" id="name" name="name" placeholder="Your full name" class="form-control auth-input" value="<?= old('name') ?>" required>
                </div>
                <?php if (session('validation') && session('validation')->hasError('name')): ?>
                    <small class="text-danger"><?= session('validation')->getError('name') ?></small>
                <?php endif; ?>
              </div>

              <div>
                <label class="form-label auth-field-label">Designation</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-id-badge auth-field-icon"></i>
                  <input type="text" id="designation" name="designation" placeholder="e.g., Talent Acquisition Specialist" class="form-control auth-input" value="<?= old('designation') ?>" required>
                </div>
                <?php if (session('validation') && session('validation')->hasError('designation')): ?>
                    <small class="text-danger"><?= session('validation')->getError('designation') ?></small>
                <?php endif; ?>
              </div>

              <div>
                <label class="form-label auth-field-label">Email Address</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-envelope auth-field-icon"></i>
                  <input type="email" id="email" name="email" placeholder="you@company.com" class="form-control auth-input" value="<?= old('email') ?>" required>
                </div>
                <small class="text-muted">Use company domain email only (free providers are blocked).</small>
                <?php if (session('validation') && session('validation')->hasError('email')): ?>
                    <small class="text-danger"><?= session('validation')->getError('email') ?></small>
                <?php endif; ?>
              </div>

              <div>
                <label class="form-label auth-field-label">Official Verification Email</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-at auth-field-icon"></i>
                  <input type="email" id="official_email" name="official_email" placeholder="verification@company.com" class="form-control auth-input" value="<?= old('official_email') ?>">
                </div>
                <small class="text-muted">Leave blank to use the login email above.</small>
                <?php if (session('validation') && session('validation')->hasError('official_email')): ?>
                    <small class="text-danger"><?= session('validation')->getError('official_email') ?></small>
                <?php endif; ?>
              </div>

              <div>
                <label class="form-label auth-field-label">Website</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-globe auth-field-icon"></i>
                  <input type="text" id="website" name="website" placeholder="https://company.com" class="form-control auth-input" value="<?= old('website') ?>">
                </div>
              </div>

              <div>
                <label class="form-label auth-field-label">Agency Registration Number</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-certificate auth-field-icon"></i>
                  <input type="text" id="agency_registration_number" name="agency_registration_number" placeholder="Required for consultancies if available" class="form-control auth-input" value="<?= old('agency_registration_number') ?>">
                </div>
              </div>

              <div>
                <label class="form-label auth-field-label">GST Number</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-receipt auth-field-icon"></i>
                  <input type="text" id="gst_number" name="gst_number" placeholder="GSTIN" class="form-control auth-input" value="<?= old('gst_number') ?>">
                </div>
              </div>

              <div>
                <label class="form-label auth-field-label">Phone Number</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-phone auth-field-icon"></i>
                  <input type="text" id="phone" name="phone" placeholder="Phone number" class="form-control auth-input" value="<?= old('phone') ?>" required>
                </div>
                <?php if (session('validation') && session('validation')->hasError('phone')): ?>
                    <small class="text-danger"><?= session('validation')->getError('phone') ?></small>
                <?php endif; ?>
              </div>

              <div>
                <label class="form-label auth-field-label">Password</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-lock auth-field-icon"></i>
                  <input type="password" id="passwordInput" name="password" placeholder="Password" class="form-control auth-input auth-input--password" required>
                  <button type="button" class="auth-password-toggle" data-password-target="passwordInput">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <?php if (session('validation') && session('validation')->hasError('password')): ?>
                    <small class="text-danger"><?= session('validation')->getError('password') ?></small>
                <?php endif; ?>
              </div>

              <div class="mb-1">
                <label class="form-label auth-field-label">Re-Type Password</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-lock auth-field-icon"></i>
                  <input type="password" id="confirmPasswordInput" name="confirm_password" placeholder="Password" class="form-control auth-input auth-input--password" required>
                  <button type="button" class="auth-password-toggle" data-password-target="confirmPasswordInput">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <?php if (session('validation') && session('validation')->hasError('confirm_password')): ?>
                    <small class="text-danger"><?= session('validation')->getError('confirm_password') ?></small>
                <?php endif; ?>
              </div>

              <button type="submit" class="btn btn-primary btn-lg auth-secondary-btn">
                Register as Recruiter
              </button>
          </form>
        </div>
      </div>

      <div class="auth-footer-copy">
        <p>
          Already have an account?
          <a href="<?= base_url('login') ?>" class="auth-footer-link">Login</a>
        </p>
      </div>
    </div>
  </section>

<?= view('Layouts/auth_footer') ?>
</body>
</html>
            
