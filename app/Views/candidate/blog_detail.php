<?php
$publishedDate = ($post['published_at'] ?? '') ?: ($post['created_at'] ?? date('Y-m-d H:i:s'));
$authorName = $post['author_name'] ?? $post['author'] ?? 'HireMatrix Team';
$coverImage = $post['cover_image'] ?? $post['featured_image'] ?? '';
$coverImageUrl = $coverImage !== ''
    ? (preg_match('#^https?://#i', $coverImage) ? $coverImage : base_url(ltrim($coverImage, '/')))
    : '';
$plainContent = trim(strip_tags((string) ($post['content'] ?? '')));
$excerpt = trim((string) ($post['excerpt'] ?? ''));
if ($excerpt === '' && $plainContent !== '') {
    $words = preg_split('/\s+/', $plainContent) ?: [];
    $excerpt = implode(' ', array_slice($words, 0, 26));
    if (count($words) > 26) {
        $excerpt .= '...';
    }
}
$wordCount = $plainContent !== '' ? str_word_count($plainContent) : 0;
$readMinutes = max(1, (int) ceil($wordCount / 220));
?>
<?= view('Layouts/candidate_header', ['title' => esc($post['title'])]) ?>

<section class="blog-detail-page">
    <div class="blog-article-hero">
        <div class="container">
            <div class="blog-detail-back-row">
                <a href="<?= base_url('candidate/dashboard') ?>" class="blog-back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>

            <div class="blog-hero-grid">
                <div class="blog-hero-copy">
                    <div class="blog-kicker">
                        <i class="fas fa-newspaper"></i>
                        <span>Career Insight</span>
                    </div>
                    <h1><?= esc($post['title']) ?></h1>
                    <?php if ($excerpt !== ''): ?>
                        <p class="blog-hero-excerpt"><?= esc($excerpt) ?></p>
                    <?php endif; ?>
                    <div class="blog-meta-row" aria-label="Article information">
                        <span><i class="fas fa-calendar-alt"></i><?= esc(date('M d, Y', strtotime($publishedDate))) ?></span>
                        <span><i class="fas fa-user-edit"></i><?= esc($authorName) ?></span>
                        <span><i class="fas fa-clock"></i><?= $readMinutes ?> min read</span>
                    </div>
                </div>

                <div class="blog-hero-panel" aria-label="Candidate resource summary">
                    <div class="blog-hero-panel-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h2>Make this insight actionable</h2>
                    <p>Use the ideas here to sharpen your applications, prepare cleaner answers, and move faster on relevant roles.</p>
                    <a href="<?= base_url('jobs?tab=suggested') ?>" class="blog-primary-action">
                        <span>Explore matched jobs</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="blog-detail-wrap">
        <div class="container">
            <div class="blog-detail-layout">
                <article class="blog-article-card">
                    <?php if ($coverImageUrl !== ''): ?>
                        <figure class="blog-featured-image">
                            <img src="<?= esc($coverImageUrl) ?>" alt="<?= esc($post['title']) ?>">
                        </figure>
                    <?php endif; ?>

                    <div class="blog-article-body">
                        <?= $post['content'] ?>
                    </div>
                </article>

                <aside class="blog-detail-sidebar" aria-label="Article sidebar">
                    <div class="blog-sidebar-card">
                        <div class="blog-sidebar-card-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Quick Career Tip</h3>
                        <p>After reading, note one change you can apply to your resume, profile, or next interview answer today.</p>
                    </div>

                    <?php if (!empty($relatedPosts)): ?>
                        <div class="blog-sidebar-card">
                            <div class="blog-sidebar-head">
                                <span>Continue Reading</span>
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="related-post-list">
                                <?php foreach ($relatedPosts as $relatedPost): ?>
                                    <?php
                                    $relatedDate = ($relatedPost['published_at'] ?? '') ?: ($relatedPost['created_at'] ?? date('Y-m-d H:i:s'));
                                    ?>
                                    <a class="related-post-item" href="<?= base_url('candidate/blog/' . $relatedPost['id']) ?>">
                                        <small><?= esc(date('M d, Y', strtotime($relatedDate))) ?></small>
                                        <h4><?= esc($relatedPost['title']) ?></h4>
                                        <span>Read article <i class="fas fa-arrow-right"></i></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="blog-sidebar-card blog-sidebar-cta">
                        <h3>Ready for the next step?</h3>
                        <p>Turn your reading into momentum with recommended openings and saved searches.</p>
                        <a href="<?= base_url('jobs') ?>" class="blog-secondary-action">
                            <i class="fas fa-search"></i>
                            <span>Browse jobs</span>
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>

<?= view('Layouts/candidate_footer') ?>
