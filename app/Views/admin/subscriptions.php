<?= view('Layouts/admin_header', ['title' => 'Subscription Management']) ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Subscription Management</h3>
        <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">All Subscriptions</div>
        <div class="card-body">
            <?php if (!empty($subscriptions)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td><?= esc($sub['id']) ?></td>
                                    <td><?= esc($sub['user_name'] ?: 'N/A') ?></td>
                                    <td><?= esc($sub['user_email'] ?: 'N/A') ?></td>
                                    <td><?= esc($sub['plan_name'] ?: 'N/A') ?></td>
                                    <td>₹<?= number_format((float) $sub['amount_paid'], 2) ?></td>
                                    <td><?= esc(date('Y-m-d', strtotime($sub['start_date']))) ?></td>
                                    <td><?= esc(date('Y-m-d', strtotime($sub['end_date']))) ?></td>
                                    <td><span class="badge bg-<?= $sub['status'] === 'active' ? 'success' : ($sub['status'] === 'cancelled' ? 'danger' : 'warning') ?>"><?= esc(ucfirst($sub['status'])) ?></span></td>
                                    <td>
                                        <a href="<?= base_url('admin/subscription/' . $sub['id']) ?>" class="btn btn-sm btn-info">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted py-3">No subscriptions found.</p>
            <?php endif; ?>
        </div>
    </div>

<?= view('Layouts/admin_footer') ?>