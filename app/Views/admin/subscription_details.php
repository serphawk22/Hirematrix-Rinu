<?= view('Layouts/admin_header', ['title' => 'Subscription Details']) ?>

<?php
$status = strtolower((string) ($subscription['status'] ?? ''));
$statusClass = $status === 'active' ? 'success' : ($status === 'cancelled' ? 'danger' : 'warning');
$amountPaid = (float) ($subscription['amount_paid'] ?? 0);
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-muted small fw-semibold">Admin</div>
        <h3 class="fw-bold mb-1">Subscription Details</h3>
        <div class="text-muted">Review plan, payment, and validity information for this subscription.</div>
    </div>
    <a href="<?= base_url('admin/subscriptions') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-light">
            <div class="text-muted small">Subscription ID</div>
            <div class="fs-4 fw-bold text-primary">#<?= esc((string) ($subscription['id'] ?? '')) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-light">
            <div class="text-muted small">Amount Paid</div>
            <div class="fs-4 fw-bold text-success">₹<?= number_format($amountPaid, 2) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-0 bg-light">
            <div class="text-muted small">Status</div>
            <div class="fs-5 fw-bold">
                <span class="badge bg-<?= esc($statusClass) ?>"><?= esc(strtoupper($status !== '' ? $status : 'unknown')) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Detailed Subscription Information</div>
            <div class="card-body">
                <table class="table table-borderless align-middle mb-0">
                    <tr>
                        <th class="text-muted" style="width: 32%;">User</th>
                        <td>
                            <div class="fw-semibold"><?= esc((string) ($subscription['user_name'] ?? 'N/A')) ?></div>
                            <div class="text-muted small"><?= esc((string) ($subscription['user_email'] ?? 'N/A')) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Plan Name</th>
                        <td><span class="badge bg-primary"><?= esc((string) (($subscription['plan_name'] ?? '') ?: 'N/A')) ?></span></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Validity</th>
                        <td><?= esc((string) ($subscription['start_date'] ?? 'N/A')) ?> to <?= esc((string) ($subscription['end_date'] ?? 'N/A')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Order ID</th>
                        <td><code><?= esc((string) (($subscription['order_id'] ?? '') ?: 'N/A')) ?></code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Payment ID</th>
                        <td><code><?= esc((string) (($subscription['payment_id'] ?? '') ?: 'N/A')) ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">Payment Summary</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Plan</div>
                    <div class="fw-bold"><?= esc((string) (($subscription['plan_name'] ?? '') ?: 'N/A')) ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Paid Amount</div>
                    <div class="h4 text-success mb-0">₹<?= number_format($amountPaid, 2) ?></div>
                </div>
                <div class="mb-0">
                    <div class="text-muted small">Current Status</div>
                    <span class="badge bg-<?= esc($statusClass) ?>"><?= esc(ucfirst($status !== '' ? $status : 'unknown')) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?= view('Layouts/admin_footer') ?>
