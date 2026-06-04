<?= view('Layouts/recruiter_header', ['title' => 'Interview Slots']) ?>

<div class="recruiter-slots-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-calendar-alt"></i> Recruiter scheduling</span>
            <h1 class="page-board-title">Interview Slots Management</h1>
            <p class="page-board-subtitle">Create, review, and manage slots before candidates book interview windows.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/slots/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Slots
            </a>
            <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-list"></i> View All Bookings
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card recruiter-stat-card recruiter-stat-applications shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Slots</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_slots'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card recruiter-stat-card recruiter-stat-openjobs shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Available Slots</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['available_slots'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card recruiter-stat-card recruiter-stat-conversion shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Fully Booked</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['fully_booked'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card recruiter-stat-card recruiter-stat-bookings shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Bookings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_bookings'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm recruiter-filter-card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                    <p class="text-muted mb-0">Narrow the schedule by job, date, and slot availability.</p>
                </div>
            </div>
            <form method="get" action="<?= base_url('recruiter/slots') ?>" class="recruiter-slot-filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Job</label>
                            <select name="job_id" class="form-control">
                                <option value="">All Jobs</option>
                                <?php foreach ($jobs as $job): ?>
                                    <option value="<?= $job['id'] ?>" <?= ($filters['job_id'] ?? '') == $job['id'] ? 'selected' : '' ?>>
                                        <?= esc($job['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control" value="<?= esc($filters['date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="available" <?= ($filters['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="full" <?= ($filters['status'] ?? '') === 'full' ? 'selected' : '' ?>>Fully Booked</option>
                                <option value="past" <?= ($filters['status'] ?? '') === 'past' ? 'selected' : '' ?>>Past Slots</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm recruiter-table-card">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Interview Slots</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover recruiter-slots-table">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Job</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Capacity</th>
                            <th>Booked</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slots)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">No slots found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($slots as $slot): ?>
                                <?php
                                $isPast = strtotime($slot['slot_datetime']) < time();
                                $isAvailable = $slot['is_available'] && !$isPast;
                                $isFull = $slot['booked_count'] >= $slot['capacity'];
                                ?>
                                <tr class="<?= $isPast ? 'table-secondary' : ($isFull ? 'table-warning' : '') ?>">
                                    <td><?= $slot['id'] ?></td>
                                    <td><?= esc($slot['job_title']) ?></td>
                                    <td><?= date('M d, Y', strtotime($slot['slot_date'])) ?></td>
                                    <td><strong><?= date('h:i A', strtotime($slot['slot_time'])) ?></strong></td>
                                    <td><?= $slot['capacity'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= $slot['booked_count'] > 0 ? 'primary' : 'secondary' ?>">
                                            <?= $slot['booked_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isPast): ?>
                                            <span class="badge badge-secondary">Past</span>
                                        <?php elseif ($isFull): ?>
                                            <span class="badge badge-danger">Full</span>
                                        <?php elseif ($isAvailable): ?>
                                            <span class="badge badge-dark">Available</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($slot['created_by_name']) ?></td>
                                    <td>
                                        <?php if ($slot['booked_count'] == 0): ?>
                                            <a href="<?= base_url('recruiter/slots/edit/' . $slot['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('recruiter/slots/delete/' . $slot['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this slot?')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Has bookings</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links')): ?>
                <div class="mt-3">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
