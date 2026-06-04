<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #eef2ff, #f8fafc);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }

        .login-card h4 {
            font-weight: 700;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79,70,229,0.1);
        }

        .btn-primary {
            background: #4f46e5;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .logo {
            font-size: 20px;
            font-weight: 700;
            color: #4f46e5;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>

<body>

<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-5">

            <div class="card login-card">
                <div class="card-body p-4 p-md-5">

                    <!-- Logo / Title -->
                    <div class="text-center mb-4">
                        <div class="logo">HireMatrix</div>
                        <div class="subtitle">Admin Analytics Panel</div>
                    </div>

                    <!-- Error -->
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger text-center">
                            <?= esc(session()->getFlashdata('error')) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="post" action="<?= base_url('admin/login') ?>">

                        <div class="mb-3">
                            <label class="form-label small text-muted">Email</label>
                            <input type="email"
                                   class="form-control"
                                   name="email"
                                   placeholder="Enter your email"
                                   value="<?= esc(old('email') ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-muted">Password</label>
                            <input type="password"
                                   class="form-control"
                                   name="password"
                                   placeholder="Enter your password"
                                   required>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            Login to Dashboard
                        </button>

                    </form>

                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-4 text-muted small">
                © 2026 HireMatrix. All rights reserved.
            </div>

        </div>
    </div>
</div>

</body>
</html>