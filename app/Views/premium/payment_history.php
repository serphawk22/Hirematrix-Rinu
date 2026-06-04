<?= view('Layouts/candidate_header', ['title' => $title ?? 'Payment History']) ?>

<div class="container content-wrap py-5">
    <div class="page-board-header page-board-header-tight mb-4">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-receipt"></i> Billing</span>
            <h1 class="page-board-title">Payment History</h1>
        </div>
        <a href="<?= base_url('premium/plans') ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-crown"></i> View Plans
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center bg-white rounded shadow-sm p-5">
            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No payments yet</h5>
            <p class="text-muted mb-4">Subscribe to a premium plan to unlock all AI features.</p>
            <a href="<?= base_url('premium/plans') ?>" class="btn btn-primary">
                <i class="fas fa-crown"></i> See Plans
            </a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Payment ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                <td><?= esc($order['plan_name'] ?? '—') ?></td>
                                <td>&#8377;<?= number_format((float) $order['amount']) ?></td>
                                <td>
                                    <small class="text-muted font-monospace">
                                        <?= esc($order['razorpay_payment_id'] ?? '—') ?>
                                    </small>
                                </td>
                                <td>
                                    <?php $s = $order['status'] ?? 'created'; ?>
                                    <span class="badge badge-<?= $s === 'paid' ? 'success' : ($s === 'created' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($s) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= view('Layouts/candidate_footer') ?>
