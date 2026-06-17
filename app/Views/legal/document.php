<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= esc($pageTitle ?? 'Legal') ?> - HireMatrix</title>
    <link rel="icon" type="image/png" href="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/custom-bs.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('jobboard/css/hirematrix-style.css?v=' . @filemtime(FCPATH . 'jobboard/css/hirematrix-style.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('custom/public-pages.css?v=' . @filemtime(FCPATH . 'custom/public-pages.css')) ?>">
    <style>
        :root {
            color-scheme: light;
            --legal-bg: #f7fbfa;
            --legal-surface: #ffffff;
            --legal-text: #16212b;
            --legal-muted: #5f7288;
            --legal-border: #d9ece5;
            --legal-accent: #1fb7b5;
            --legal-accent-soft: #eef9f5;
        }

        body {
            margin: 0;
            background: var(--legal-bg);
            color: var(--legal-text);
        }

        .legal-shell {
            min-height: 100vh;
            padding: 40px 20px 56px;
        }

        .legal-frame {
            max-width: 1040px;
            margin: 0 auto;
        }

        .legal-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 28px;
        }

        .legal-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none !important;
            color: var(--legal-text);
            font-weight: 700;
            font-size: 1rem;
        }

        .legal-brand img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .legal-backlink {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid var(--legal-border);
            border-radius: 10px;
            background: var(--legal-surface);
            color: var(--legal-text);
            font-weight: 600;
            text-decoration: none !important;
        }

        .legal-hero,
        .legal-article,
        .legal-bottom {
            background: var(--legal-surface);
            border: 1px solid var(--legal-border);
            border-radius: 16px;
        }

        .legal-hero {
            padding: 32px;
            margin-bottom: 18px;
        }

        .legal-eyebrow {
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

        .legal-hero h1 {
            margin: 0 0 14px;
            font-size: clamp(2rem, 4vw, 3.1rem);
            line-height: 1.08;
            font-weight: 700;
            color: #0f172a;
        }

        .legal-summary {
            max-width: 760px;
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--legal-muted);
        }

        .legal-meta {
            margin-top: 18px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--legal-accent-soft);
            color: #0d8a90;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .legal-article {
            padding: 10px 32px;
        }

        .legal-section {
            padding: 28px 0;
            border-bottom: 1px solid #e8f3ef;
        }

        .legal-section:last-child {
            border-bottom: 0;
        }

        .legal-section h2 {
            margin: 0 0 14px;
            font-size: 1.3rem;
            line-height: 1.35;
            font-weight: 700;
            color: #10213b;
        }

        .legal-section p {
            margin: 0 0 14px;
            font-size: 1rem;
            line-height: 1.88;
            color: var(--legal-muted);
        }

        .legal-section p:last-child {
            margin-bottom: 0;
        }

        .legal-bottom {
            margin-top: 18px;
            padding: 18px 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 14px;
            color: var(--legal-muted);
            font-size: 0.95rem;
        }

        .legal-bottom-links {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .legal-bottom a {
            color: #0d8a90;
            font-weight: 600;
            text-decoration: none !important;
        }

        @media (max-width: 767.98px) {
            .legal-shell {
                padding: 24px 14px 42px;
            }

            .legal-topbar {
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 18px;
            }

            .legal-hero,
            .legal-article {
                padding-left: 18px;
                padding-right: 18px;
            }

            .legal-hero {
                padding-top: 24px;
                padding-bottom: 24px;
            }

            .legal-article {
                padding-top: 4px;
                padding-bottom: 4px;
            }

            .legal-bottom {
                padding: 16px 18px;
            }
        }

        @media (prefers-color-scheme: dark) {
            :root {
                color-scheme: dark;
                --legal-bg: #0d1117;
                --legal-surface: #11161d;
                --legal-text: #e8eef5;
                --legal-muted: #9cb0c2;
                --legal-border: #23343a;
                --legal-accent-soft: rgba(31, 183, 181, 0.12);
            }

            .legal-hero h1,
            .legal-section h2,
            .legal-brand,
            .legal-backlink {
                color: #f8fafc;
            }

            .legal-backlink {
                background: #11161d;
            }

            .legal-section {
                border-bottom-color: #1b2731;
            }
        }
    </style>
</head>
<body>
    <main class="legal-shell">
        <div class="legal-frame">
            <div class="legal-topbar">
                <a href="<?= base_url('/') ?>" class="legal-brand">
                    <img src="<?= base_url('jobboard/images/Serp Hwak Logo.png') ?>" alt="HireMatrix">
                    <span>HireMatrix</span>
                </a>
                <a href="<?= base_url('/') ?>" class="legal-backlink">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to portal</span>
                </a>
            </div>

            <section class="legal-hero">
                <div class="legal-eyebrow">
                    <i class="fas fa-shield-alt"></i>
                    <span><?= esc($pageEyebrow ?? 'Legal') ?></span>
                </div>
                <h1><?= esc($pageHeading ?? 'Legal Document') ?></h1>
                <p class="legal-summary"><?= esc($pageSummary ?? '') ?></p>
                <div class="legal-meta">
                    <i class="far fa-calendar-alt"></i>
                    <span>Effective date: <?= esc($effectiveDate ?? '') ?></span>
                </div>
            </section>

            <article class="legal-article">
                <?php foreach (($sections ?? []) as $section): ?>
                    <section class="legal-section">
                        <h2><?= esc($section['title'] ?? '') ?></h2>
                        <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
                            <p><?= esc($paragraph) ?></p>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            </article>

            <div class="legal-bottom">
                <span>These pages should be reviewed and tailored for your business, jurisdiction, and compliance requirements before production use.</span>
                <div class="legal-bottom-links">
                    <a href="<?= base_url('privacy-policy') ?>">Privacy Policy</a>
                    <a href="<?= base_url('terms-of-service') ?>">Terms of Service</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
