<?php
$resolveAssetUrl = static function (string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
        return $path;
    }
    return base_url(ltrim($path, '/'));
};
?>
<section class="dashboard-section pt-0 dashboard-blog-section">
    <div class="container-fluid px-lg-5">
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
            <div>
                <h2 class="section-title">Latest Career Insights</h2>
                <p class="section-subtitle">Expert advice and industry trends for your next move.</p>
            </div>
        </div>
        <div class="row g-4">
            <?php if (!empty($blogPosts)): ?>
                <?php foreach ($blogPosts as $post): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="job-card h-100 dashboard-card dashboard-blog-card">
                            <div class="job-card-icon">
                                <?php if (!empty($post['cover_image'])): ?>
                                    <img src="<?= esc($resolveAssetUrl($post['cover_image'])) ?>" alt="<?= esc($post['title']) ?>">
                                <?php else: ?>
                                    <span><i class="fas fa-newspaper"></i></span>
                                <?php endif; ?>
                            </div>
                            <div class="dashboard-blog-meta-row d-flex justify-content-between align-items-center mb-3 mt-2">
                                <span class="badge badge-primary"><?= !empty($post['featured']) ? 'Featured' : 'Blog' ?></span>
                                <span class="dashboard-blog-date small"><?= esc(date('M d, Y', strtotime((string) ($post['published_at'] ?: $post['created_at'])))) ?></span>
                            </div>
                            <h3 class="job-card-title"><?= esc($post['title']) ?></h3>
                            <div class="dashboard-blog-author mb-2 small">
                                <i class="fas fa-user-edit me-1"></i> By <?= esc($post['author_name'] ?? 'HireMatrix Team') ?>
                            </div>
                            <p class="dashboard-blog-excerpt mb-4"><?= esc(strip_tags((string) $post['excerpt'])) ?></p>
                            <a href="<?= base_url('candidate/blog/' . $post['id']) ?>" class="view-details mt-auto">Read Article &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="job-card text-center">
                        <h3 class="job-card-title">No blog posts available yet.</h3>
                        <p class="text-muted mb-0">Check back soon for career advice and hiring insights!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>                
