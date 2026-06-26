<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Recruiter Verification | HireMatrix</title>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">

    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.min.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.min.css')) ?>">

    <style>
        .otp-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 24px 0;
        }
        .otp-input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            transition: all 0.2s;
        }
        .otp-input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .otp-input::-webkit-inner-spin-button,
        .otp-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>
<body class="hirematrix-app public-auth-page">
<div class="site-wrap">
    <?= view('Layouts/public_header', ['body_class' => 'public-auth-page']) ?>

    <section class="site-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <h2 class="mb-4">Recruiter Verification</h2>
                    <p class="text-muted">Please verify your company email address to activate your recruiter account.</p>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
                    <?php endif; ?>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>Company Email Verification</h5>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="text" class="form-control" value="<?= esc($email ?? 'Not available') ?>" readonly>
                            </div>

                            <?php if (!($isEmailVerified ?? false)): ?>
                                <p class="text-center mt-4">Enter the 6-digit verification code sent to your professional email address.</p>
                                
                                <form method="post" action="<?= base_url('recruiter/verify-email-code') ?>" id="otpForm">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="email" value="<?= esc($email ?? '') ?>">
                                    <input type="hidden" name="code" id="verification_code">
                                    
                                    <div class="otp-container">
                                        <input type="tel" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                                        <input type="tel" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                                        <input type="tel" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                                        <input type="tel" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                                        <input type="tel" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                                        <input type="tel" class="otp-input" maxlength="1" pattern="\d*" inputmode="numeric" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-block mb-4">Verify Account</button>
                                </form>

                                <hr>
                                <form method="post" action="<?= base_url('recruiter/resend-verification-email') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="email" value="<?= esc($email ?? '') ?>">
                                    <button type="submit" class="btn btn-link btn-sm p-0">Didn't receive the code? Resend Email</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="mt-3"><a href="<?= base_url('login') ?>">Back to login</a></p>
                </div>
            </div>
        </div>
    </section>
    <?= view('Layouts/public_footer') ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupOtpInputs('.otp-input', 'verification_code');

    function setupOtpInputs(selector, hiddenInputId) {
        const inputs = document.querySelectorAll(selector);
        const hiddenCode = document.getElementById(hiddenInputId);

        if (!inputs.length || !hiddenCode) return;

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.inputType === "deleteContentBackward") return;
                input.value = input.value.replace(/\D/g, '').slice(0, 1);
                if (input.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateHiddenCode();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const data = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                data.split('').forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                updateHiddenCode();
                if (data.length > 0) inputs[Math.min(data.length, inputs.length - 1)].focus();
            });
        });

        function updateHiddenCode() {
            let code = '';
            inputs.forEach(input => code += input.value);
            hiddenCode.value = code;
        }
    }
});
</script>
</body>
</html>
