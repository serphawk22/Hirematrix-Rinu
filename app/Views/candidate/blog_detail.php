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

<style>
.blog-detail-jobboard {
    background: #f7f8fa;
}

.blog-header-card {
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 14px;
    padding: 24px 28px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    flex-wrap: wrap;
}

.blog-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #0d9488;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 16px;
}

.blog-back-link:hover {
    text-decoration: underline;
    color: #0d9488;
}

.blog-kicker {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(13, 148, 136, 0.1);
    color: #0d9488;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 999px;
    margin-bottom: 12px;
}

.blog-header-title {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    line-height: 1.3;
    margin: 0 0 10px;
    max-width: 720px;
}

.blog-header-subtitle {
    font-size: 15px;
    color: #6b7280;
    line-height: 1.6;
    max-width: 640px;
    margin: 0 0 16px;
}

.blog-header-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.blog-header-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    color: #6b7280;
    background: #f3f5f8;
    padding: 5px 12px;
    border-radius: 999px;
}

.blog-header-actions {
    flex-shrink: 0;
}

.blog-hero-wrap {
    position: relative;
    width: 100%;
    height: 320px;
    background: #1a1a1a;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 24px;
}

.blog-hero-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.blog-hero-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.25);
    font-size: 48px;
}

.blog-detail-layout {
    display: grid;
    grid-template-columns: minmax(0, 2.3fr) minmax(260px, 1fr);
    gap: 28px;
    align-items: start;
}

.blog-article-card {
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 14px;
    padding: 32px 36px;
}

.blog-article-body {
    font-size: 15.5px;
    line-height: 1.8;
    color: #374151;
}

.blog-article-body h2 {
    font-size: 22px;
    font-weight: 700;
    margin: 28px 0 14px;
    color: #111827;
}

.blog-article-body p {
    margin: 0 0 16px;
}

.blog-article-body p:empty {
    display: none;
}

.blog-article-body pre {
    background: #f7f8fa;
    border-radius: 8px;
    padding: 14px 16px;
    margin: 0 0 16px;
    overflow-x: auto;
}

.blog-article-body code {
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    font-size: 14px;
    color: #0d9488;
    white-space: pre-wrap;
}

.blog-detail-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 24px;
}

.blog-sidebar-card {
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 14px;
    padding: 20px 22px;
}

.blog-sidebar-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(13, 148, 136, 0.1);
    color: #0d9488;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-bottom: 12px;
}

.blog-sidebar-card h3 {
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 8px;
    color: #111827;
}

.blog-sidebar-card p {
    font-size: 13.5px;
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
}

.blog-sidebar-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 14px;
}

.related-post-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.related-post-item {
    display: block;
    text-decoration: none;
    color: inherit;
    padding-bottom: 14px;
    border-bottom: 1px solid #f0f1f4;
}

.related-post-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.related-post-item small {
    display: block;
    font-size: 11.5px;
    color: #9ca3af;
    margin-bottom: 4px;
}

.related-post-item h4 {
    font-size: 13.5px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 6px;
    line-height: 1.4;
}

.related-post-item span {
    font-size: 12px;
    color: #0d9488;
    font-weight: 600;
}

.related-post-item:hover h4 {
    color: #0d9488;
}

.blog-sidebar-cta {
    background: #0d9488;
}

.blog-sidebar-cta h3,
.blog-sidebar-cta p {
    color: #fff;
}

.blog-sidebar-cta p {
    opacity: 0.9;
}

.blog-secondary-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    color: #0d9488;
    font-size: 13.5px;
    font-weight: 600;
    padding: 9px 16px;
    border-radius: 8px;
    text-decoration: none;
    margin-top: 14px;
}

.blog-secondary-action:hover {
    text-decoration: none;
    color: #0d9488;
    opacity: 0.9;
}

@media (max-width: 992px) {
    .blog-detail-layout {
        grid-template-columns: 1fr;
    }
    .blog-detail-sidebar {
        position: static;
    }
    .blog-header-card {
        flex-direction: column;
    }
    .blog-header-title {
        font-size: 22px;
    }
    .blog-hero-wrap {
        height: 200px;
    }
}
</style>

<div class="blog-detail-jobboard">
    <section class="content-wrap blog-detail-content-canvas">
        <div class="container-fluid">

            <div class="blog-header-card">
                <div> 
                    <div class="blog-kicker">
                        <i class="fas fa-newspaper"></i>
                        <span>Career insight</span>
                    </div>

                    <h1 class="blog-header-title"><?= esc($post['title']) ?></h1>

                    <?php if ($excerpt !== ''): ?>
                        <p class="blog-header-subtitle"><?= esc($excerpt) ?></p>
                    <?php endif; ?>

                    <div class="blog-header-meta" aria-label="Article information">
                        <span><i class="fas fa-calendar-alt"></i><?= esc(date('M d, Y', strtotime($publishedDate))) ?></span>
                        <span><i class="fas fa-user-edit"></i><?= esc($authorName) ?></span>
                        <span><i class="fas fa-clock"></i><?= $readMinutes ?> min read</span>
                    </div>
                </div>

                <div class="blog-header-actions">
                    <a href="<?= base_url('jobs?tab=suggested') ?>" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i>
                        Explore matched jobs
                    </a>
                    <a href="<?= base_url('candidate/dashboard') ?>" class="btn btn-primary"> 
                        Back To Dashboard
                    </a>
                </div>
            </div>

            <div class="blog-hero-wrap">
                <?php if ($coverImageUrl !== ''): ?>
                    <img src="<?= esc($coverImageUrl) ?>" alt="<?= esc($post['title']) ?>" class="blog-hero-img">
                <?php else: ?>
                    <div class="blog-hero-fallback"><i class="fas fa-newspaper"></i></div>
                <?php endif; ?>
            </div>

            <div class="blog-detail-layout">
                <article class="blog-article-card">
                    <div class="blog-article-body" id="blogArticleBody">
                        <?= $post['content'] ?>
                    </div>
                </article>

                <aside class="blog-detail-sidebar" aria-label="Article sidebar">
                    <div class="blog-sidebar-card">
                        <div class="blog-sidebar-card-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Quick career tip</h3>
                        <p>After reading, note one change you can apply to your resume, profile, or next interview answer today.</p>
                    </div>

                    <?php if (!empty($relatedPosts)): ?>
                        <div class="blog-sidebar-card">
                            <div class="blog-sidebar-head">
                                <span>Continue reading</span>
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
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var body = document.getElementById('blogArticleBody');
    if (!body) return;

    // Remove pasted UI chrome (copy-button toolbars, sticky wrappers)
    body.querySelectorAll('[class*="pointer-events-none"], [class*="sticky"][class*="z-"]')
        .forEach(function (el) { el.remove(); });

    // Extracts text from a block while preserving line breaks — plain
    // textContent concatenates every child with no separator, which mashes
    // "npm init", "npm install express", etc. (each its own inner <div>/<p>
    // in the pasted block) into a single run-on line. Inserting a newline
    // after every block-level child before reading textContent keeps each
    // command on its own line.
    function blockAwareText(el) {
        var clone = el.cloneNode(true);
        clone.querySelectorAll('div, p, li, br').forEach(function (node) {
            node.insertAdjacentText('afterend', '\n');
        });
        return clone.textContent.replace(/\n{2,}/g, '\n').trim();
    }

    // Unwrap the pasted code-block container so only its text remains,
    // as a clean <pre><code> instead of the messy nested divs
    body.querySelectorAll('[class*="border-token-border-light"], [class*="corner-superellipse"]')
        .forEach(function (el) {
            var pre = document.createElement('pre');
            var code = document.createElement('code');
            code.textContent = blockAwareText(el);
            pre.appendChild(code);
            el.replaceWith(pre);
        });

    // Clean up now-empty leftover divs (e.g. <div>&nbsp;</div>), innermost first
    var changed = true;
    while (changed) {
        changed = false;
        body.querySelectorAll('div').forEach(function (el) {
            if (!el.children.length && el.textContent.replace(/\u00a0/g, ' ').trim() === '') {
                el.remove();
                changed = true;
            }
        });
    }
});
</script>

<?= view('Layouts/candidate_footer') ?>