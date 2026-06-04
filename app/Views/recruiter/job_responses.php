<?= view('Layouts/recruiter_header', [
    'title' => 'Jobs & Responses',
    'pageStyles' => [base_url('jobboard/css/recruiter-jobs.css?v=' . @filemtime(FCPATH . 'jobboard/css/recruiter-jobs.css'))],
]) ?>



<div
    id="recruiterJobsPage"
    class="recruiter-jobs-jobboard"
    data-status-url-base="<?= base_url('recruiter/applications/update-status/') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 font-weight-bold">Jobs Management</h1>
        <a href="<?= base_url('recruiter/post_job') ?>" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Post New Job</a>
    </div>

    <div id="jobs-list">
            <div class="card mb-4 bg-light recruiter-filter-card">
                <div class="card-body">
                    <form action="<?= base_url('recruiter/jobs') ?>" method="get" class="row align-items-end">
                        <div class="col-md-5 mb-2">
                            <label class="small font-weight-bold text-muted">Search Jobs</label>
                            <input type="text" name="q" class="form-control" placeholder="Search by title..." value="<?= esc($filters['q']) ?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="small font-weight-bold text-muted">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active Jobs</option>
                                <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>Closed Jobs</option>
                                <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>All Jobs</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="alert alert-info">No jobs found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive recruiter-table-card">
                    <table class="table table-hover bg-white border rounded recruiter-jobs-table">
                        <thead class="bg-light">
                            <tr>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Applicants</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-dark job-title"><?= esc($job['title']) ?></div>
                                        <small class="text-muted"><?= esc($job['location']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $job['status'] === 'open' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($job['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $job['applicant_count'] ?> (<?= $job['shortlisted_count'] ?> Shortlisted)</td>
                                    <td class="text-right">
                                        <a href="<?= base_url('recruiter/jobs/view/' . $job['id']) ?>" class="btn btn-sm btn-outline-primary mr-1">
                                            <i class="fas fa-users mr-1"></i> Pipeline
                                        </a>
                                        <a href="<?= base_url('recruiter/jobs/edit/' . $job['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pager->getTotal() > 10): ?>
                    <div class="mt-4">
                        <?= $pager->links() ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer', [
    'pageScripts' => [base_url('jobboard/js/recruiter-jobs.js?v=' . @filemtime(FCPATH . 'jobboard/js/recruiter-jobs.js'))],
]) ?>


