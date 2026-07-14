<?= view('Layouts/recruiter_header', ['title' => 'Interview Slots']) ?>
<div class="recruiter-slots-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Interview Slots</h1>
            <p class="page-board-subtitle">Create, review, and manage slots before candidates book interview windows.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/slots/create') ?>" class="btn btn-outline-primary">Create New Slots</a>
            <a href="<?= base_url('recruiter/slots/bookings') ?>" class="btn btn-outline-primary">View All Bookings</a>
        </div>
    </div>

    <div class="card shadow-sm recruiter-table-card recruiter-rounded-visible">

        <!-- Inline stat strip + filters in one header row -->
        <div class="slots-toolbar">
            <div class="slots-stats">
                <div class="slots-stat">
                    <span class="slots-stat-value recruiter-brand-icon"><?= $stats['upcoming_available'] ?? 0 ?></span>
                    <span class="slots-stat-label">Upcoming Available</span>
                </div>
                <div class="slots-stat-divider"></div>
                <div class="slots-stat">
                    <span class="slots-stat-value recruiter-indigo-text"><?= $stats['booked_upcoming'] ?? 0 ?></span>
                    <span class="slots-stat-label">Booked Upcoming</span>
                </div>
                <div class="slots-stat-divider"></div>
                <div class="slots-stat">
                    <span class="slots-stat-value recruiter-amber-text"><?= $stats['needs_review'] ?? 0 ?></span>
                    <span class="slots-stat-label">Needs Review</span>
                </div>
                <div class="slots-stat-divider"></div>
                <div class="slots-stat">
                    <span class="slots-stat-value"><?= $stats['past_slots'] ?? 0 ?></span>
                    <span class="slots-stat-label">Past Slots</span>
                </div>
            </div>

            <form method="get" action="<?= base_url('recruiter/slots') ?>" class="slots-filters">
                <select name="job_id" class="slots-filter-input">
                    <option value="">All Jobs</option>
                    <?php foreach ($jobs as $job): ?>
                        <option value="<?= $job['id'] ?>" <?= ($filters['job_id'] ?? '') == $job['id'] ? 'selected' : '' ?>><?= esc($job['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date" class="slots-filter-input" value="<?= esc($filters['date'] ?? '') ?>">
                <select name="status" class="slots-filter-input">
                    <option value="upcoming" <?= ($filters['status'] ?? 'upcoming') === 'upcoming' ? 'selected' : '' ?>>Upcoming Slots</option>
                    <option value="available" <?= ($filters['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="booked" <?= ($filters['status'] ?? '') === 'booked' ? 'selected' : '' ?>>Booked Upcoming</option>
                    <option value="full" <?= ($filters['status'] ?? '') === 'full' ? 'selected' : '' ?>>Full Upcoming</option>
                    <option value="needs_review" <?= ($filters['status'] ?? '') === 'needs_review' ? 'selected' : '' ?>>Needs Review</option>
                    <option value="past" <?= ($filters['status'] ?? '') === 'past' ? 'selected' : '' ?>>Past Archive</option>
                    <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Slots</option>
                </select>
                <button type="submit" class="slots-filter-btn"><i class="fas fa-search"></i></button>
                <?php if (!empty($filters['job_id']) || !empty($filters['date']) || (($filters['status'] ?? 'upcoming') !== 'upcoming')): ?>
                    <a href="<?= base_url('recruiter/slots') ?>" class="slots-filter-clear">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover recruiter-slots-table mb-0">
                <thead class="thead-light">
                    <tr>
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
                            <td colspan="8">
                                <div class="slot-empty-state">
                                    <?php if (($filters['status'] ?? 'upcoming') === 'upcoming'): ?>
                                        <h6>No upcoming slots available</h6>
                                        <p>Create interview slots so shortlisted candidates can book a time.</p>
                                        <a href="<?= base_url('recruiter/slots/create') ?>" class="btn btn-outline-primary">Create Slots</a>
                                    <?php else: ?>
                                        <h6>No slots found</h6>
                                        <p>Try changing the job, date, or status filter.</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($slots as $slot): ?>
                            <?php
                            $isPast = strtotime($slot['slot_datetime']) < time();
                            $isAvailable = $slot['is_available'] && !$isPast;
                            $isFull = $slot['booked_count'] >= $slot['capacity'];
                            ?>
                            <tr class="<?= $isPast ? 'slot-row-past' : ($isFull ? 'table-warning' : '') ?>">
                                <td><?= esc($slot['job_title']) ?></td>
                                <td><?= date('M d, Y', strtotime($slot['slot_date'])) ?></td>
                                <td><strong><?= date('h:i A', strtotime($slot['slot_time'])) ?></strong></td>
                                <td><?= $slot['capacity'] ?></td>
                                <td><?= $slot['booked_count'] ?></td>
                                <td>
                                    <?php if ($isPast): ?>
                                        <span class="status-pill">Past</span>
                                    <?php elseif ($isFull): ?>
                                        <span class="status-pill recruiter-amber-text">Full</span>
                                    <?php elseif ($isAvailable): ?>
                                        <span class="status-pill">Available</span>
                                    <?php else: ?>
                                        <span class="status-pill">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($slot['created_by_name']) ?></td>
                                <td>
                                    <?php if ($slot['booked_count'] == 0): ?>
                                        <?php if (!$isPast): ?>
                                            <a href="<?= base_url('recruiter/slots/edit/' . $slot['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <form method="post" action="<?= base_url('recruiter/slots/delete/' . $slot['id']) ?>" class="d-inline" onsubmit="return confirm('Delete this slot?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-primary recruiter-danger-outline">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Archived</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?= base_url('recruiter/slots/bookings?slot_id=' . $slot['id']) ?>" class="btn btn-sm btn-outline-primary">View bookings</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pager) && is_object($pager) && method_exists($pager, 'links') && $pager->getPageCount() > 1): ?>
            <div class="px-3 py-2">
                <?= $pager->links('default', 'portal_full') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
