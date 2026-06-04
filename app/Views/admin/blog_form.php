<?= view('Layouts/admin_header', [
    'title' => isset($post) && $post ? 'Edit Blog Post' : 'Create New Blog Post'
]) ?>

    <!-- Include TinyMCE for rich text editing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
            toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            height: 400,
            menubar: false,
            statusbar: false,
            content_css: [
                '<?= base_url('jobboard/css/custom-bs.css') ?>', // Adjust path if needed
                '<?= base_url('jobboard/css/style.css') ?>' // Adjust path if needed
            ]
        });
    </script>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><?= isset($post) && $post ? 'Edit Blog Post' : 'Create New Blog Post' ?></h3>
        <a href="<?= base_url('admin/blogs') ?>" class="btn btn-outline-secondary">Back to Blog List</a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <?php if (!empty($errors = session()->getFlashdata('errors'))): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="<?= isset($post) && $post ? base_url('admin/blogs/update/' . $post['id']) : base_url('admin/blogs/store') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= old('title', $post['title'] ?? '') ?>" required>
                    <?php if (isset($errors['title'])): ?><div class="text-danger small mt-1"><?= esc($errors['title']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug (URL friendly name)</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?= old('slug', $post['slug'] ?? '') ?>" placeholder="auto-generated if left empty">
                    <?php if (isset($errors['slug'])): ?><div class="text-danger small mt-1"><?= esc($errors['slug']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="author_name" class="form-label">Author Name</label>
                    <input type="text" class="form-control" id="author_name" name="author_name" value="<?= old('author_name', $post['author_name'] ?? $author_name ?? '') ?>" required>
                    <?php if (isset($errors['author_name'])): ?><div class="text-danger small mt-1"><?= esc($errors['author_name']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="author_email" class="form-label">Author Email</label>
                    <input type="email" class="form-control" id="author_email" name="author_email" value="<?= old('author_email', $post['author_email'] ?? $author_email ?? '') ?>" required>
                    <?php if (isset($errors['author_email'])): ?><div class="text-danger small mt-1"><?= esc($errors['author_email']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="excerpt" class="form-label">Excerpt (Short summary)</label>
                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3" required><?= old('excerpt', $post['excerpt'] ?? '') ?></textarea>
                    <?php if (isset($errors['excerpt'])): ?><div class="text-danger small mt-1"><?= esc($errors['excerpt']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control" id="content" name="content"><?= old('content', $post['content'] ?? '') ?></textarea>
                    <?php if (isset($errors['content'])): ?><div class="text-danger small mt-1"><?= esc($errors['content']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="cover_image" class="form-label">Cover Image URL</label>
                    <input type="text" class="form-control" id="cover_image" name="cover_image" value="<?= old('cover_image', $post['cover_image'] ?? '') ?>">
                    <?php if (isset($errors['cover_image'])): ?><div class="text-danger small mt-1"><?= esc($errors['cover_image']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="published_at" class="form-label">Published At</label>
                    <input type="datetime-local" class="form-control" id="published_at" name="published_at" value="<?= old('published_at', isset($post['published_at']) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '') ?>">
                    <div class="form-text">Leave empty to set as draft or publish immediately.</div>
                    <?php if (isset($errors['published_at'])): ?><div class="text-danger small mt-1"><?= esc($errors['published_at']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1" <?= old('featured', $post['featured'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="featured">Featured Post</label>
                    <?php if (isset($errors['featured'])): ?><div class="text-danger small mt-1"><?= esc($errors['featured']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="draft" <?= old('status', $post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= old('status', $post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                    <?php if (isset($errors['status'])): ?><div class="text-danger small mt-1"><?= esc($errors['status']) ?></div><?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i><?= isset($post) && $post ? 'Update Post' : 'Create Post' ?></button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= view('Layouts/footer') ?>
</body>
</html>            