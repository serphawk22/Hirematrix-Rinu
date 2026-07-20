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
<style>
.blog-card-thumb {
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 14px;
    padding: 20px 22px 22px;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
}

.blog-card-thumb:hover {
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
    border-color: #e2e5ea;
    color: inherit;
    text-decoration: none;
}

.blog-card-thumb-top {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.blog-card-thumb-img {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #f3f5f8;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9aa3af;
    font-size: 16px;
    overflow: hidden;
}

.blog-card-thumb-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.blog-card-thumb-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.blog-card-badge {
    display: inline-block;
    width: fit-content;
    background: rgba(13, 148, 136, 0.1);
    color: #0d9488;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
}

.blog-card-badge-default {
    background: rgba(107, 114, 128, 0.1);
    color: #6b7280;
}

.blog-card-date {
    font-size: 12px;
    color: #9ca3af;
}

.blog-card-thumb-title {
    font-size: 16px;
    font-weight: 700;
    line-height: 1.4;
    margin: 0 0 8px;
    color: #111827;
}

.blog-card-thumb-excerpt {
    font-size: 13.5px;
    line-height: 1.55;
    color: #6b7280;
    margin: 0 0 16px;
    flex: 1;
}

.blog-card-thumb-footer {
    display: flex;
    align-items: center;
    border-top: 1px solid #f0f1f4;
    padding-top: 12px;
    font-size: 13px;
    color: #6b7280;
}
</style>
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
                        <a href="<?= base_url('candidate/blog/' . $post['id']) ?>" class="blog-card-thumb h-100">
                            <div class="blog-card-thumb-top">
                                <div class="blog-card-thumb-img">
                                    <?php if (!empty($post['cover_image'])): ?>
                                        <img src="<?= esc($resolveAssetUrl($post['cover_image'])) ?>" alt="<?= esc($post['title']) ?>">
                                    <?php else: ?>
                                        <i class="fas fa-newspaper"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="blog-card-thumb-meta">
                                    <?php if (!empty($post['featured'])): ?>
                                        <span class="blog-card-badge">Featured</span>
                                    <?php else: ?>
                                        <span class="blog-card-badge blog-card-badge-default">Blog</span>
                                    <?php endif; ?>
                                    <span class="blog-card-date">
                                        <?= esc(date('M d, Y', strtotime((string) ($post['published_at'] ?: $post['created_at'])))) ?>
                                    </span>
                                </div>
                            </div>

                            <h3 class="blog-card-thumb-title"><?= esc($post['title']) ?></h3>
                            <p class="blog-card-thumb-excerpt"><?= esc(strip_tags((string) $post['excerpt'])) ?></p>

                            <div class="blog-card-thumb-footer">
                                <span class="blog-card-thumb-author">
                                    By <?= esc($post['author_name'] ?? 'HireMatrix Team') ?>
                                </span>
                            </div>
                        </a>
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