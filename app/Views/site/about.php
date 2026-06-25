<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= esc($pageTitle ?? 'About') ?></title>
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
                    <i class="fas fa-circle-info"></i>
                    <span>About</span>
                </div>
                <h1>Built to make hiring clearer, faster, and more human.</h1>
                <p>HireMatrix is a job portal designed to support both sides of the hiring process: candidates who want sharper direction and recruiters who need better signal. We bring together job discovery, structured profiles, AI-assisted screening, and workflow tools in one connected platform.</p>
            </section>

            <section class="story-grid">
                <div class="story-copy">
                    <h2 class="info-section-title">What HireMatrix is for</h2>
                    <p>Modern hiring often breaks down because information is scattered, screening is inconsistent, and both candidates and recruiters lose time moving between disconnected tools. HireMatrix is built to reduce that friction.</p>
                    <p>For candidates, the platform supports profile building, job applications, resume-led workflows, and assessment experiences that help them present their strengths more clearly. For recruiters, it supports structured job posting, application review, AI interview policy controls, and better visibility into applicant activity.</p>
                    <p>The goal is not just automation. It is a hiring experience that feels more organized, more transparent, and easier to act on.</p>
                </div>
                <aside class="story-side">
                    <h2 class="info-section-title">Who it serves</h2>
                    <p><strong>Candidates:</strong> People exploring roles, building stronger profiles, and moving through applications with better guidance.</p>
                    <p><strong>Recruiters:</strong> Hiring teams who need cleaner workflows, faster shortlisting, and more confidence in early-stage screening.</p>
                    <p><strong>Employers:</strong> Companies that want a practical recruitment platform with room for AI-assisted evaluation without losing human judgment.</p>
                </aside>
            </section>

            <section class="values-grid">
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-compass"></i></div>
                    <h3>Clarity first</h3>
                    <p>We focus on interfaces and workflows that make the next step obvious for both candidates and recruiters.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-sitemap" aria-hidden="true"></i></div>
                    <h3>Useful structure</h3>
                    <p>Profiles, jobs, assessments, and communication are organized so decisions can happen with less back-and-forth.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Responsible automation</h3>
                    <p>AI features are used to support workflows, not to replace human accountability in hiring decisions.</p>
                </div>
            </section>

            <section class="cta-card">
                <div>
                    <h2>Want to learn more or get in touch?</h2>
                    <p>Use the contact page for product questions, partnerships, support requests, or business enquiries.</p>
                </div>
                <div class="cta-actions">
                    <a href="<?= base_url('contact') ?>" class="cta-btn">
                        <i class="fas fa-paper-plane"></i>
                        <span>Contact us</span>
                    </a>
                    <a href="<?= base_url('privacy-policy') ?>" class="cta-btn-secondary">
                        <i class="fas fa-shield-alt"></i>
                        <span>Privacy Policy</span>
                    </a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
