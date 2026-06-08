<?php
$role = (string) (session()->get('role') ?? 'candidate');
$isRecruiter = $role === 'recruiter';
$backUrl = $isRecruiter ? base_url('recruiter/dashboard') : base_url('candidate/profile');
$backLabel = $isRecruiter ? 'Back to Dashboard' : 'Back to Profile';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Change Password | HireMatrix</title>
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
         .btn-outline-primary {  
        background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

  .btn-outline-primary:focus, .btn-outline-primary:hover {
 background:  #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);

}
    </style>
</head>
<?= view('Layouts/auth_header', ['body_class' => 'public-auth-page']) ?>

<section class="auth-page-shell">
    <div class="auth-page-column auth-page-column--md">
        <div class="auth-page-head">
            <div class="auth-page-brand"> 
        <a class="auth-page-title" href="https://hirematrix.serphawk.in" style="font-size:22px;text-decoration:none;">Hire Matrix</a>
        </div>
            <h1 class="auth-page-title" style="font-weight:normal;">Change Password</h1>
            <p class="auth-page-subtitle">Update your password securely to keep your account protected.</p>
        </div>

        <div class="card rounded-5 border-1 auth-page-card">
            <div class="card-body p-4 p-md-5">
                <?php if (session()->getFlashdata('error')) : ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')) : ?>
                    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <?php $validation = session()->getFlashdata('validation'); ?>
                <?php if ($validation): ?>
                    <div class="alert alert-danger"><?= $validation->listErrors() ?></div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('account/change-password') ?>" class="auth-form">
                    <?= csrf_field() ?>

                    <div>
                        <label class="form-label auth-field-label">Current Password</label>
                        <div class="auth-field-wrap">
                            <i class="fas fa-lock auth-field-icon"></i>
                            <input type="password" id="current_password" name="current_password" class="form-control auth-input auth-input--password" required>
                            <button type="button" class="auth-password-toggle" data-password-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label auth-field-label">New Password</label>
                        <div class="auth-field-wrap">
                            <i class="fas fa-key auth-field-icon"></i>
                            <input type="password" id="password" name="password" class="form-control auth-input auth-input--password" required>
                            <button type="button" class="auth-password-toggle" data-password-target="password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label auth-field-label">Confirm New Password</label>
                        <div class="auth-field-wrap">
                            <i class="fas fa-shield-alt auth-field-icon"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control auth-input auth-input--password" required>
                            <button type="button" class="auth-password-toggle" data-password-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg auth-secondary-btn">Change Password</button>
                    <a href="<?= $backUrl ?>" class="btn btn-primary btn-lg auth-secondary-btn"><?= esc($backLabel) ?></a>
                </form>
            </div>
        </div>

        <div class="auth-footer-copy">
            <p>
                Need to return later?
                <a href="<?= $backUrl ?>" class="auth-footer-link">Go back</a>
            </p>
        </div>
    </div>
</section>

<?= view('Layouts/auth_footer') ?>
</body>
</html>
    