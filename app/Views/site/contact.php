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
    <link rel="stylesheet" href="<?= base_url('custom/public-information-dark.css?v=' . @filemtime(FCPATH . 'custom/public-information-dark.css')) ?>">
</head>
<body class="public-information-page">
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
                    <h2 class="info-section-title">What to contact us about</h2>
                    <p>We can help with account questions, recruiter onboarding, candidate experience issues, AI interview workflow questions, subscription matters, and platform feedback.</p>
                    <div class="contact-points">
                        <div class="contact-point">
                            <h3>Support</h3>
                            <p>Questions about access, profile issues, applications, or interview flow.</p>
                            <p>Mail ID : info.serphawk@gmail.com</p>
                            <p>Contact No : +91 97477 51235</p>
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

                    <h2 class="info-section-title">Send a message</h2>
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
