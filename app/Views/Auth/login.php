<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign In - HireMatrix</title>
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
     <link rel="stylesheet" href="<?= base_url('jobboard/css/dark.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('custom/public-pages.css?v=' . @filemtime(FCPATH . 'custom/public-pages.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/responsive.css?v=' . @filemtime(FCPATH . 'jobboard/css/responsive.css')) ?>"> 
    <style>
        .auth-back-link {
            position: absolute;
            top: 24px;
            left: 24px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #53565A;
            text-decoration: none;
            z-index: 5;
        }

        .auth-back-link i {
            font-size: 12px;
        }

        .auth-back-link:hover {
            color: #1FB7B5;
            text-decoration: none;
        }

        .auth-page-shell {
            position: relative;
        }

        @media (prefers-color-scheme: dark) {
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

    .auth-back-link {
        color: #7A8B96 !important;
    }

    .auth-back-link:hover {
        color: #1FB7B5 !important;
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
}
    </style>
  </head>
<?= view('Layouts/public_header', ['body_class' => 'public-auth-page login-auth-page']) ?>

  <section class="auth-page-shell"> 
  

    <div class="auth-page-column auth-page-column--sm">
        
      <div class="auth-page-head">
        <h1 class="auth-page-title" style="font-weight:normal;">Welcome Back</h1>
        <p class="auth-page-subtitle">Sign in to your account to continue</p>
      </div>

      <div class="card rounded-5 border-1 auth-page-card">
        <div class="card-body p-4 p-md-5">
          <?php if (session()->getFlashdata('error')) : ?>
              <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('success')) : ?>
              <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
          <?php endif; ?>

          <a href="<?= base_url('auth/google') ?>" class="btn btn-google-auth btn-block mb-3">
              <span class="google-g-icon" aria-hidden="true">
                  <svg viewBox="0 0 18 18" width="18" height="18" xmlns="http://www.w3.org/2000/svg">
                      <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.7-1.56 2.68-3.86 2.68-6.62z"/>
                      <path fill="#34A853" d="M9 18c2.43 0 4.46-.8 5.95-2.18l-2.92-2.26c-.81.54-1.84.86-3.03.86-2.33 0-4.3-1.57-5-3.68H1v2.31A9 9 0 0 0 9 18z"/>
                      <path fill="#FBBC05" d="M4 10.74a5.41 5.41 0 0 1 0-3.48V4.95H1a9 9 0 0 0 0 8.1l3-2.31z"/>
                      <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.43 1.34l2.57-2.57A8.98 8.98 0 0 0 1 4.95l3 2.31c.7-2.11 2.67-3.68 5-3.68z"/>
                  </svg>
              </span>
              <span>Continue with Google</span>
          </a>

          <div class="auth-divider">
            <div class="auth-divider-line"></div>
            <span class="auth-divider-text">OR</span>
            <div class="auth-divider-line"></div>
          </div>

          <form method="post" action="<?= base_url('login') ?>" class="auth-form">
              <?= csrf_field() ?>
              <input type="hidden" name="next" value="<?= esc($next ?? '') ?>">

              <div>
                <label class="form-label auth-field-label">Email Address</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-envelope auth-field-icon"></i>
                  <input type="email" id="email" name="email" placeholder="your@email.com" class="form-control auth-input" required>
                </div>
              </div>

              <div>
                <label class="form-label auth-field-label">Password</label>
                <div class="auth-field-wrap">
                  <i class="fas fa-lock auth-field-icon"></i>
                  <input type="password" id="passwordInput" name="password" placeholder="Password" class="form-control auth-input auth-input--password" required>
                  <button type="button" class="auth-password-toggle" data-password-target="passwordInput">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                  </button>
                </div>
              </div>

              <div class="auth-meta-row">
                <label class="auth-remember">
                  <input type="checkbox" name="remember_me" value="1" class="form-check-input" <?= old('remember_me') ? 'checked' : '' ?>>
                  Remember me
                </label>
                <a href="<?= base_url('forgot-password') ?>" class="auth-footer-link">Forgot password?</a>
              </div>

              <button type="submit" class="btn btn-primary btn-lg auth-primary-btn">
                Sign In
              </button>
          </form>
        </div>
      </div>

      <div class="auth-footer-copy">
        <p>
          Don't have an account?
          <a href="<?= base_url('register') ?>" class="auth-footer-link" id="createAccountLink">Create one</a>
        </p>
      </div>
    </div>
  </section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var referrer   = document.referrer || '';
    var backLink   = document.getElementById('authBackLink');
    var createLink = document.getElementById('createAccountLink');

    var candidateRegisterUrl = "<?= base_url('register') ?>";
    var recruiterRegisterUrl = "<?= base_url('recruiter/register') ?>";

    if (!referrer) {
        return;
    }

    // Route the "Create one" link based on where the user came from
    if (referrer.indexOf('/recruiter/register') !== -1) {
        createLink.setAttribute('href', recruiterRegisterUrl);
    } else if (referrer.indexOf('/register') !== -1) {
        createLink.setAttribute('href', candidateRegisterUrl);
    }

    // Make the Back link go straight to the referrer if it's same-origin,
    // otherwise fall back to history.back() (already set via onclick)
    if (referrer.indexOf(window.location.origin) === 0) {
        backLink.setAttribute('href', referrer);
        backLink.removeAttribute('onclick');
    }
});
</script>

<?= view('Layouts/auth_footer') ?>
</body>
</html>