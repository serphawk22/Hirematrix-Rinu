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
    <link rel="stylesheet" href="<?= base_url('custom/public-information-dark.css?v=' . @filemtime(FCPATH . 'custom/public-information-dark.css')) ?>">
</head>
<body class="public-information-page">
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
