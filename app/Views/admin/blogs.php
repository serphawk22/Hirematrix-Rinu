<?= view('Layouts/admin_header', ['title' => 'Blog Management']) ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Blog Management</h3>
            <p class="text-muted small mb-0">Manage career insights and hiring advice articles.</p>
        </div>
        <a href="<?= base_url('admin/blogs/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Post
        </a>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" value="<?= esc($search) ?>" class="form-control" placeholder="Search by title or slug...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-secondary me-2">Filter</button>
                    <a href="<?= base_url('admin/blogs') ?>" class="btn btn-link text-decoration-none">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 45%;">Post Details</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Published Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold mb-0"><?= esc($post['title']) ?></div>
                                    <div class="text-muted small"><?= esc($post['slug']) ?></div>
                                    <?php if ($post['featured']): ?>
                                        <span class="badge bg-warning text-dark small mt-1"><i class="fas fa-star me-1"></i>Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-bold"><?= esc($post['author_name'] ?? 'Admin') ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= esc($post['author_email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $post['status'] === 'published' ? 'success' : 'secondary' ?> px-3">
                                        <?= ucfirst($post['status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= esc(date('M d, Y', strtotime($post['published_at'] ?? $post['created_at']))) ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group shadow-sm" role="group">
                                        <a href="<?= base_url('blog/' . $post['slug']) ?>" class="btn btn-sm btn-outline-info" target="_blank" title="View Public Post">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= base_url('admin/blogs/edit/' . $post['id']) ?>" class="btn btn-sm btn-outline-warning" title="Edit Post">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?= base_url('admin/blogs/delete/' . $post['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Post">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">No blog posts found. Start by creating your first insight!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= view('Layouts/admin_footer') ?>