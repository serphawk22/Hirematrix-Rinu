<?php
$company = is_array($company ?? null) ? $company : [];
$name = trim((string) ($company['name'] ?? 'Company'));
$location = trim((string) ($company['location'] ?? 'India'));
$industry = trim((string) ($company['industry'] ?? 'Hiring company'));
$description = trim((string) ($company['description'] ?? 'Explore this company and review current opportunities.'));
$openJobs = (int) ($company['open_jobs'] ?? 0);
$website = trim((string) ($company['website'] ?? ''));
$logo = trim((string) ($company['logo'] ?? ''));
$initial = strtoupper(substr($name, 0, 1) ?: 'C');
$logoUrl = $logo !== '' && preg_match('/^https?:\/\//i', $logo) ? $logo : ($logo !== '' ? base_url($logo) : '');
?>

<article class="local-company-card">
    <div class="local-company-card-top">
        <div class="local-company-logo">
            <?php if ($logoUrl !== ''): ?>
                <img src="<?= esc($logoUrl) ?>" alt="<?= esc($name) ?>" onerror="this.parentElement.textContent='<?= esc($initial) ?>';">
            <?php else: ?>
                <?= esc($initial) ?>
            <?php endif; ?>
        </div>
        <div class="local-company-name">
            <h3 title="<?= esc($name) ?>"><?= esc($name) ?></h3>
            <span><?= esc($industry) ?></span>
        </div>
    </div>
    <div class="local-company-meta">
        <span class="local-company-pill"><i class="fas fa-map-marker-alt"></i> <?= esc($location) ?></span>
        <span class="local-company-pill"><i class="fas fa-briefcase"></i> <?= $openJobs > 0 ? esc((string) $openJobs) . ' open' : 'Verify openings' ?></span>
    </div>
    <p class="local-company-description"><?= esc($description) ?></p>
    <?php if ($website !== ''): ?>
        <div class="local-company-actions">
            <a href="<?= esc($website) ?>" target="_blank" rel="noopener" class="local-company-primary"><i class="fas fa-external-link-alt"></i> Website</a>
        </div>
    <?php endif; ?>
</article>
