<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= esc($pageTitle ?? 'Contact') ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('custom/public-pages.css?v=' . @filemtime(FCPATH . 'custom/public-pages.css')) ?>">
    <style>
        :root {
            color-scheme: light;
            --page-bg: #f7fbfa;
            --page-surface: #ffffff;
            --page-border: #d9ece5;
            --page-text: #16212b;
            --page-muted: #5f7288;
            --page-accent: #1fb7b5;
            --page-soft: #eef9f5;
            --page-danger-bg: #fff5f5;
            --page-danger-border: #ffd4d4;
            --page-danger-text: #c24141;
            --page-success-bg: #eefaf5;
            --page-success-border: #cdeedf;
            --page-success-text: #0f8a5f;
        }
        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--page-text);
        }
        .page-shell {
            min-height: 100vh;
            padding: 40px 20px 56px;
        }
        .page-frame {
            max-width: 1120px;
            margin: 0 auto;
        }
        .page-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 28px;
        }
        .page-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none !important;
            color: var(--page-text);
            font-weight: 700;
        }
        .page-brand img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }
        .page-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid var(--page-border);
            border-radius: 10px;
            background: var(--page-surface);
            color: var(--page-text);
            font-weight: 600;
            text-decoration: none !important;
        }
        .hero-card,
        .contact-grid,
        .contact-note {
            border: 1px solid var(--page-border);
            border-radius: 16px;
            background: var(--page-surface);
        }
        .hero-card {
            padding: 34px;
            margin-bottom: 18px;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: #0d8a90;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .hero-card h1 {
            margin: 0 0 14px;
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.08;
            color: #0f172a;
            font-weight: 700;
        }
        .hero-card p {
            max-width: 780px;
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.85;
            color: var(--page-muted);
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .contact-side,
        .contact-form-wrap {
            padding: 28px 30px;
        }
        .contact-side {
            background: linear-gradient(180deg, #f6fcfb 0%, #edf8f5 100%);
            border-right: 1px solid var(--page-border);
        }
        .section-title {
            margin: 0 0 12px;
            font-size: 1.3rem;
            line-height: 1.35;
            color: #10213b;
            font-weight: 700;
        }
        .contact-side p,
        .contact-note p {
            margin: 0 0 14px;
            font-size: 1rem;
            line-height: 1.85;
            color: var(--page-muted);
        }
        .contact-side p:last-child,
        .contact-note p:last-child {
            margin-bottom: 0;
        }
        .contact-points {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }
        .contact-point {
            border: 1px solid var(--page-border);
            border-radius: 12px;
            background: rgba(255,255,255,0.7);
            padding: 16px;
        }
        .contact-point h3 {
            margin: 0 0 6px;
            font-size: 1rem;
            font-weight: 700;
            color: #10213b;
        }
        .contact-point p {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.75;
        }
        .flash-list,
        .flash-success {
            margin-bottom: 18px;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .flash-list {
            background: var(--page-danger-bg);
            border: 1px solid var(--page-danger-border);
            color: var(--page-danger-text);
        }
        .flash-list ul {
            margin: 0;
            padding-left: 18px;
        }
        .flash-success {
            background: var(--page-success-bg);
            border: 1px solid var(--page-success-border);
            color: var(--page-success-text);
            font-weight: 600;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .form-field {
            display: grid;
            gap: 8px;
        }
        .form-field-full {
            grid-column: 1 / -1;
        }
        .form-field label {
            margin: 0;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #0d8a90;
        }
        .form-field input,
        .form-field textarea {
            width: 100%;
            border: 1px solid var(--page-border);
            border-radius: 12px;
            background: #ffffff;
            color: var(--page-text);
            padding: 13px 15px;
            font-size: 1rem;
            line-height: 1.5;
            outline: none;
        }
        .form-field textarea {
            min-height: 180px;
            resize: vertical;
        }
        .form-field input:focus,
        .form-field textarea:focus {
            border-color: #91ddd9;
            box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.12);
        }
        .form-actions {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .form-help {
            color: var(--page-muted);
            font-size: 0.95rem;
            line-height: 1.7;
        }
        .submit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 48px;
            padding: 0 18px;
            border: 1px solid #1fb7b5;
            border-radius: 10px;
            background: #1fb7b5;
            color: #ffffff;
            font-weight: 600;
            text-decoration: none !important;
            cursor: pointer;
        }
        .contact-note {
            padding: 18px 22px;
        }
        @media (max-width: 900px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            .contact-side {
                border-right: 0;
                border-bottom: 1px solid var(--page-border);
            }
        }
        @media (max-width: 767.98px) {
            .page-shell {
                padding: 24px 14px 42px;
            }
            .page-topbar {
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 18px;
            }
            .hero-card,
            .contact-side,
            .contact-form-wrap,
            .contact-note {
                padding-left: 18px;
                padding-right: 18px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (prefers-color-scheme: dark) {
            :root {
                color-scheme: dark;
                --page-bg: #0d1117;
                --page-surface: #11161d;
                --page-border: #23343a;
                --page-text: #e8eef5;
                --page-muted: #9cb0c2;
                --page-soft: rgba(31, 183, 181, 0.12);
                --page-danger-bg: rgba(127, 29, 29, 0.22);
                --page-danger-border: rgba(248, 113, 113, 0.28);
                --page-danger-text: #fca5a5;
                --page-success-bg: rgba(6, 78, 59, 0.24);
                --page-success-border: rgba(52, 211, 153, 0.26);
                --page-success-text: #86efac;
            }
            .hero-card h1,
            .section-title,
            .contact-point h3,
            .page-brand,
            .page-link {
                color: #f8fafc;
            }
            .contact-side {
                background: #10171e;
            }
            .contact-point {
                background: #11161d;
            }
            .form-field input,
            .form-field textarea {
                background: #0f141a;
                color: #e8eef5;
            }
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <div class="page-frame">
            <div class="page-topbar">
                <a href="<?= base_url('/') ?>" class="page-brand">
                    <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix">
                    <span>HireMatrix</span>
                </a>
                <a href="<?= base_url('/') ?>" class="page-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to portal</span>
                </a>
            </div>

            <section class="hero-card">
                <div class="eyebrow">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Contact</span>
                </div>
                <h1>Get in touch with the HireMatrix team.</h1>
                <p>Use this page for product questions, support requests, partnership enquiries, recruiter onboarding, or anything else related to the portal. We have kept the flow simple so messages can be captured directly from the site.</p>
            </section>

            <section class="contact-grid">
                <aside class="contact-side">
                    <h2 class="section-title">What to contact us about</h2>
                    <p>We can help with account questions, recruiter onboarding, candidate experience issues, AI interview workflow questions, subscription matters, and platform feedback.</p>
                    <div class="contact-points">
                        <div class="contact-point">
                            <h3>Support</h3>
                            <p>Questions about access, profile issues, applications, or interview flow.</p>
                        </div>
                        <div class="contact-point">
                            <h3>Business enquiries</h3>
                            <p>Recruiter onboarding, partnerships, or platform adoption discussions.</p>
                        </div>
                        <div class="contact-point">
                            <h3>Feedback</h3>
                            <p>Suggestions, bug reports, and ideas that can improve the hiring experience.</p>
                        </div>
                    </div>
                </aside>

                <div class="contact-form-wrap">
                    <?php $contactErrors = session('contact_errors') ?? []; ?>
                    <?php if (!empty($contactErrors)): ?>
                        <div class="flash-list">
                            <ul>
                                <?php foreach ($contactErrors as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (session('contact_success')): ?>
                        <div class="flash-success"><?= esc(session('contact_success')) ?></div>
                    <?php endif; ?>

                    <h2 class="section-title">Send a message</h2>
                    <form method="post" action="<?= base_url('contact') ?>">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="contact-name">Full Name</label>
                                <input type="text" id="contact-name" name="name" value="<?= esc(old('name')) ?>" placeholder="Your name">
                            </div>
                            <div class="form-field">
                                <label for="contact-email">Email Address</label>
                                <input type="email" id="contact-email" name="email" value="<?= esc(old('email')) ?>" placeholder="you@example.com">
                            </div>
                            <div class="form-field form-field-full">
                                <label for="contact-subject">Subject</label>
                                <input type="text" id="contact-subject" name="subject" value="<?= esc(old('subject')) ?>" placeholder="What do you need help with?">
                            </div>
                            <div class="form-field form-field-full">
                                <label for="contact-message">Message</label>
                                <textarea id="contact-message" name="message" placeholder="Share the details and we will review your message."><?= esc(old('message')) ?></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <p class="form-help">Messages submitted here are captured by the platform for follow-up. Add enough detail so the team can respond efficiently.</p>
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                <span>Send message</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="contact-note">
                <p>This public contact form is intended for legitimate business and support communication. Please avoid sharing sensitive credentials, payment details, or confidential data in plain text.</p>
            </section>
        </div>
    </main>
</body>
</html>
