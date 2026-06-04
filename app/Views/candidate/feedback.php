        <?= view('Layouts/candidate_header', ['title' => 'Feedback']) ?>

<div class="book-slot-jobboard">
    <div class="container">
<br/>
        <!-- HEADER -->
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker">
                    <i class="fas fa-comment-dots"></i> Feedback
                </span>
                <h1 class="page-board-title">Share Your Experience</h1>
                <p class="page-board-subtitle">
                    Your feedback helps us improve the platform experience.
                </p>
            </div>
        </div>

        <!-- SUCCESS -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <!-- CARD -->
        <div class="feedback-wrapper">

            <form method="post" action="<?= base_url('feedback/save') ?>" class="feedback-card">
                <?= csrf_field() ?>

                <!-- USER INFO -->
                <div class="user-info mb-4">
                    <div>
                        <strong><?= esc($user['name']) ?></strong><br>
                        <small class="text-muted"><?= esc($user['email']) ?></small>
                    </div>
                    <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
                </div>

                <!-- RATING -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Rating</label>

                    <div class="rating-stars">
                        <?php for($i=5;$i>=1;$i--): ?>
                            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- MESSAGE -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Your Feedback</label>
                    <textarea name="message" rows="4" class="form-control"
                        placeholder="Write your feedback..." required></textarea>
                </div>

                <!-- SUBMIT -->
                <div class="text-center">
                    <button type="submit" class="btn submit-btn">
                        <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                    </button>
                </div>

            </form>

        </div>
<br/>
    </div>
</div>

<?= view('Layouts/candidate_footer') ?>
