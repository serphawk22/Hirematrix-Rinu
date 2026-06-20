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
        .story-grid,
        .values-grid,
        .cta-card {
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
        .story-grid {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr;
            gap: 0;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .story-copy,
        .story-side {
            padding: 28px 30px;
        }
        .story-side {
            background: linear-gradient(180deg, #f6fcfb 0%, #edf8f5 100%);
            border-left: 1px solid var(--page-border);
        }
        .section-title {
            margin: 0 0 12px;
            font-size: 1.3rem;
            line-height: 1.35;
            color: #10213b;
            font-weight: 700;
        }
        .story-copy p,
        .story-side p {
            margin: 0 0 14px;
            font-size: 1rem;
            line-height: 1.85;
            color: var(--page-muted);
        }
        .story-copy p:last-child,
        .story-side p:last-child {
            margin-bottom: 0;
        }
        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .value-card {
            padding: 28px;
            border-right: 1px solid var(--page-border);
        }
        .value-card:last-child {
            border-right: 0;
        }
        .value-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--page-soft);
            color: #0d8a90;
            margin-bottom: 16px;
        }
        .value-card h3 {
            margin: 0 0 10px;
            font-size: 1.06rem;
            font-weight: 700;
            color: #10213b;
        }
        .value-card p {
            margin: 0;
            font-size: 0.98rem;
            line-height: 1.8;
            color: var(--page-muted);
        }
        .cta-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 24px 28px;
        }
        .cta-card h2 {
            margin: 0 0 8px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #10213b;
        }
        .cta-card p {
            margin: 0;
            color: var(--page-muted);
            line-height: 1.8;
        }
        .cta-actions {
            display: inline-flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cta-btn,
        .cta-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none !important;
        }
        .cta-btn {
            background: #1fb7b5;
            border: 1px solid #1fb7b5;
            color: #ffffff;
        }
        .cta-btn-secondary {
            background: transparent;
            border: 1px solid var(--page-border);
            color: var(--page-text);
        }
        @media (max-width: 900px) {
            .story-grid,
            .values-grid {
                grid-template-columns: 1fr;
            }
            .story-side {
                border-left: 0;
                border-top: 1px solid var(--page-border);
            }
            .value-card {
                border-right: 0;
                border-bottom: 1px solid var(--page-border);
            }
            .value-card:last-child {
                border-bottom: 0;
            }
            .cta-card {
                flex-direction: column;
                align-items: flex-start;
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
            .story-copy,
            .story-side,
            .value-card,
            .cta-card {
                padding-left: 18px;
                padding-right: 18px;
            }
        }
        @media (prefers-color-scheme: dark) {
            :root {
                color-scheme: dark;
                --page-bg: #000000 !important;
                --page-surface: #11161d;
                --page-border: #23343a;
                --page-text: #ffffff;
                --page-muted: #9cb0c2;
                --page-soft: rgba(31, 183, 181, 0.12);
            }
            .hero-card,.story-grid,.story-side,.values-grid,.cta-card,.cta-actions{
                background:#000000 !important;
            }
            .story-copy h2 .section-title,.story-side h2 .section-title{
                 color: #ffffff !important;
            }
            .hero-card h1,
            .section-title,
            .value-card h3,
            .cta-card h2,
            .page-brand,
            .page-link,
            .cta-btn-secondary {
                color: #ffffff;
                
            }
            .story-side {
                background: #10171e;
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
                    <i class="fas fa-circle-info"></i>
                    <span>About</span>
                </div>
                <h1>Built to make hiring clearer, faster, and more human.</h1>
                <p>HireMatrix is a job portal designed to support both sides of the hiring process: candidates who want sharper direction and recruiters who need better signal. We bring together job discovery, structured profiles, AI-assisted screening, and workflow tools in one connected platform.</p>
            </section>

            <section class="story-grid">
                <div class="story-copy">
                    <h2 class="section-title">What HireMatrix is for</h2>
                    <p>Modern hiring often breaks down because information is scattered, screening is inconsistent, and both candidates and recruiters lose time moving between disconnected tools. HireMatrix is built to reduce that friction.</p>
                    <p>For candidates, the platform supports profile building, job applications, resume-led workflows, and assessment experiences that help them present their strengths more clearly. For recruiters, it supports structured job posting, application review, AI interview policy controls, and better visibility into applicant activity.</p>
                    <p>The goal is not just automation. It is a hiring experience that feels more organized, more transparent, and easier to act on.</p>
                </div>
                <aside class="story-side">
                    <h2 class="section-title">Who it serves</h2>
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
                    <div class="value-icon"><i class="fas fa-layer-group"></i></div>
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
