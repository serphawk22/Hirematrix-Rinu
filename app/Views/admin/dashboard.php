<?= view('Layouts/admin_header', ['title' => 'Admin Analytics']) ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Admin Analytics</h3>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="<?= base_url('admin/company-ats-mappings') ?>">
                Company ATS
            </a>
            <a class="btn btn-outline-primary" href="<?= base_url('admin/blogs') ?>">
                Manage Blogs
            </a>
            <a class="btn btn-outline-primary" href="<?= base_url('admin/subscriptions') ?>">
                Subscriptions
            </a>
            <form method="get" class="d-flex align-items-center gap-2">
                <label class="small text-muted">Last</label>
                <div class="input-group" style="width:150px;">
                    <input type="number" name="days" value="<?= esc($days) ?>" class="form-control">
                    <span class="input-group-text">days</span>
                </div>
                <button class="btn btn-outline-secondary">Update</button>
            </form>
        </div>
    </div>

    <!-- STATS -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0 bg-light">
                <div class="text-muted small">API Calls</div>
                <div class="fs-4 fw-bold text-primary"><?= $apiTotals['total_calls'] ?? 0 ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0 bg-light">
                <div class="text-muted small">Cost (USD)</div>
                <div class="fs-4 fw-bold text-success">$<?= number_format($apiTotals['total_cost'] ?? 0,4) ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0 bg-light">
                <div class="text-muted small">Total Revenue (INR)</div>
                <div class="fs-4 fw-bold text-dark">₹<?= number_format($totalRevenue ?? 0, 2) ?></div>
        </div>
    </div>
        
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card p-3">
                <div class="text-muted small">Tracked Days</div>
                <div class="fs-4 fw-bold"><?= $days ?></div>
        
        </div>

        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <div class="text-muted small">Active Subscriptions</div>
                <div class="fs-4 fw-bold text-primary"><?= $activeSubscriptionsCount ?? 0 ?></div>
            </div>
        </div>

    

    <!-- TABLES -->
        <div class="col-md-4">
            <div class="card p-3">
                <div class="text-muted small">Churned Subscriptions</div>
                <div class="fs-4 fw-bold text-danger">
                    <?= $cancelledSubscriptionsCount ?? 0 ?> <span class="fs-6 fw-normal">(<?= number_format($churnRate ?? 0, 1) ?>%)</span>
                </div>
            </div>
        </div>
    </div>
<!-- INFO -->
        <div class="alert alert-info mb-4">
            Cost = estimated API spend
        </div>
      <!-- SUBSCRIPTION CHARTS SECTION -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-bold">Revenue Trend (Last 6 Months)</div>
                <div class="card-body">
                    <?php if (!empty($monthlyRevenueTrend)): ?>
                        <canvas id="revenueChart" height="100"></canvas>
                        <script>
                            const ctx = document.getElementById('revenueChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: [<?php foreach($monthlyRevenueTrend as $t) echo "'" . $t['month'] . "',"; ?>],
                                    datasets: [{
                                        label: 'Revenue (₹)',
                                        data: [<?php foreach($monthlyRevenueTrend as $t) echo $t['revenue'] . ","; ?>],
                                        borderColor: '#2446c0',
                                        backgroundColor: 'rgba(36, 70, 192, 0.1)',
                                        fill: true,
                                        tension: 0.3
                                    }]
                                },
                                options: { responsive: true, plugins: { legend: { display: false } } }
                            });
                        </script>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No revenue data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white fw-bold">Plan Breakdown</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach($subscriptionBreakdown as $plan): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= esc($plan['plan_name'] ?: 'Unknown Plan') ?>
                                <span class="badge bg-primary rounded-pill"><?= $plan['count'] ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($subscriptionBreakdown)): ?>
                            <li class="list-group-item text-muted text-center">No subscriptions yet</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLES -->
    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Users by Day</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Users</th></tr></thead>
                        <tbody>
                        <?php foreach($dailyUsers as $row): ?>
                            <tr>
                                <td><?= $row['day'] ?></td>
                                <td><?= $row['users_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">API Usage</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Calls</th><th>Cost</th></tr></thead>
                        <tbody>
                        <?php foreach($dailyApi as $row): ?>
                            <tr>
                                <td><?= $row['day'] ?></td>
                                <td><?= $row['calls_count'] ?></td>
                                <td>$<?= number_format($row['cost_usd'],4) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- SCROLL TABLE -->
    <div class="card">
        <div class="card-header">Login Duration</div>
        <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
            <table class="table table-sm mb-0">
                <thead class="table-light" style="position:sticky; top:0;">
                    <tr>
                        <th>Login</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Page</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($firstPageDurations as $row): ?>
                    <tr>
                        <td><?= $row['login_at'] ?></td>
                        <td><?= $row['user_name'] ?></td>
                        <td><?= $row['user_role'] ?></td>
                        <td><?= $row['first_page_path'] ?></td>
                        <td><?= $row['duration_ms'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?= view('Layouts/admin_footer') ?>