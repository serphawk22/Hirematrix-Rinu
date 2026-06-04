<?php
// File: app/Views/admin/external_jobs_dashboard.php
?>

<?= view('Layouts/admin_header', ['title' => 'External Jobs Management']) ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-globe"></i> External Job Feed Management
            </h1>
            <p class="text-muted small mt-1">Monitor and manage external job imports from public sources</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-primary text-uppercase mb-1 small font-weight-bold">Total External Jobs</div>
                    <div class="h3 mb-0" id="totalExternalJobs">-</div>
                    <small class="text-muted">Last 30 days</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-success text-uppercase mb-1 small font-weight-bold">Remotive</div>
                    <div class="h3 mb-0" id="remotiveCount">-</div>
                    <small class="text-muted">Jobs imported</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-info text-uppercase mb-1 small font-weight-bold">RemoteOK</div>
                    <div class="h3 mb-0" id="remoteokCount">-</div>
                    <small class="text-muted">Jobs imported</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-warning text-uppercase mb-1 small font-weight-bold">ArbeitNow</div>
                    <div class="h3 mb-0" id="arbeitnowCount">-</div>
                    <small class="text-muted">Jobs imported</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Manual Import Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-download"></i> Manual Import</h5>
                </div>
                <div class="card-body">
                    <form id="manualImportForm">
                        <div class="form-group">
                            <label for="importLimit">Number of Jobs</label>
                            <input type="number" class="form-control" id="importLimit" name="limit" 
                                   value="50" min="1" max="100" required>
                            <small class="form-text text-muted">Between 1 and 100 jobs</small>
                        </div>

                        <div class="form-group">
                            <label for="importSources">Sources</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="sourceRemotive" 
                                       name="sources" value="remotive" checked>
                                <label class="custom-control-label" for="sourceRemotive">Remotive</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="sourceRemoteOK" 
                                       name="sources" value="remoteok" checked>
                                <label class="custom-control-label" for="sourceRemoteOK">RemoteOK</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="sourceArbeitNow" 
                                       name="sources" value="arbeitnow" checked>
                                <label class="custom-control-label" for="sourceArbeitNow">ArbeitNow</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="importKeyword">Keyword Filter (Optional)</label>
                            <input type="text" class="form-control" id="importKeyword" name="keyword" 
                                   placeholder="e.g., php, laravel, react">
                        </div>

                        <div class="form-group">
                            <label for="importLocation">Location Filter (Optional)</label>
                            <input type="text" class="form-control" id="importLocation" name="location" 
                                   placeholder="e.g., india, us, remote">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" id="importBtn">
                            <i class="fas fa-play"></i> Start Import
                        </button>
                    </form>

                    <div id="importStatus" class="mt-3" style="display: none;">
                        <div class="alert alert-info">
                            <div class="spinner-border spinner-border-sm mr-2" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <span id="importStatusText">Importing jobs...</span>
                        </div>
                    </div>

                    <div id="importResult" class="mt-3" style="display: none;">
                        <div class="alert" id="importResultAlert">
                            <h6 id="importResultTitle"></h6>
                            <ul id="importResultList" class="mb-0"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Import Statistics</h5>
                </div>
                <div class="card-body">
                    <div id="statsLoading" class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div id="statsContent" style="display: none;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Jobs</th>
                                    <th>External</th>
                                </tr>
                            </thead>
                            <tbody id="statsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Information</h5>
                </div>
                <div class="card-body small">
                    <p><strong>Supported Sources:</strong></p>
                    <ul class="mb-3">
                        <li><strong>Remotive</strong> - Remote job board</li>
                        <li><strong>RemoteOK</strong> - Remote work jobs</li>
                        <li><strong>ArbeitNow</strong> - Job aggregator</li>
                    </ul>
                    <p><strong>Automatic Import:</strong></p>
                    <p class="text-muted mb-0">Set up a cron job to run daily imports automatically. See documentation for details.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Imports Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Recent External Jobs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="recentJobsTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Company</th>
                                    <th>Source</th>
                                    <th>Location</th>
                                    <th>Imported</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="recentJobsBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    loadRecentJobs();

    // Manual import form
    document.getElementById('manualImportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        performManualImport();
    });
});

function loadStatistics() {
    fetch('<?= base_url('admin/jobs/import-stats') ?>?days=30')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update summary cards
                const summary = data.summary || {};
                document.getElementById('totalExternalJobs').textContent = summary.external_jobs || 0;

                // Update source breakdown
                const bySource = data.by_source || [];
                bySource.forEach(item => {
                    const sourceId = item.external_source + 'Count';
                    const element = document.getElementById(sourceId);
                    if (element) {
                        element.textContent = item.count || 0;
                    }
                });

                // Update daily stats table
                const dailyStats = data.daily_breakdown || [];
                const tbody = document.getElementById('statsTableBody');
                tbody.innerHTML = '';
                dailyStats.forEach(stat => {
                    const row = `<tr>
                        <td>${stat.date}</td>
                        <td>${stat.count}</td>
                        <td><span class="badge badge-primary">${stat.external_count}</span></td>
                    </tr>`;
                    tbody.innerHTML += row;
                });

                document.getElementById('statsLoading').style.display = 'none';
                document.getElementById('statsContent').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
            document.getElementById('statsLoading').innerHTML = '<p class="text-danger">Failed to load statistics</p>';
        });
}

function loadRecentJobs() {
    fetch('<?= base_url('jobs') ?>?external=1&limit=10')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('recentJobsBody');
            tbody.innerHTML = '';
            
            if (data.jobs && data.jobs.length > 0) {
                data.jobs.forEach(job => {
                    const row = `<tr>
                        <td><strong>${escapeHtml(job.title)}</strong></td>
                        <td>${escapeHtml(job.company)}</td>
                        <td><span class="badge badge-info">${escapeHtml(job.external_source)}</span></td>
                        <td>${escapeHtml(job.location)}</td>
                        <td><small class="text-muted">${formatDate(job.created_at)}</small></td>
                        <td>
                            <a href="<?= base_url('job/') ?>${job.id}" class="btn btn-sm btn-outline-primary" target="_blank">
                                View
                            </a>
                        </td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No external jobs found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading jobs:', error);
            document.getElementById('recentJobsBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load jobs</td></tr>';
        });
}

function performManualImport() {
    const form = document.getElementById('manualImportForm');
    const limit = document.getElementById('importLimit').value;
    const keyword = document.getElementById('importKeyword').value;
    const location = document.getElementById('importLocation').value;
    
    const sources = Array.from(document.querySelectorAll('input[name="sources"]:checked'))
        .map(cb => cb.value)
        .join(',');

    if (!sources) {
        alert('Please select at least one source');
        return;
    }

    document.getElementById('importStatus').style.display = 'block';
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importBtn').disabled = true;

    const formData = new FormData();
    formData.append('limit', limit);
    formData.append('sources', sources);
    formData.append('keyword', keyword);
    formData.append('location', location);

    fetch('<?= base_url('admin/jobs/import-manual') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('importStatus').style.display = 'none';
        document.getElementById('importResult').style.display = 'block';

        const resultAlert = document.getElementById('importResultAlert');
        const resultTitle = document.getElementById('importResultTitle');
        const resultList = document.getElementById('importResultList');

        if (data.status === 'success') {
            resultAlert.className = 'alert alert-success';
            resultTitle.textContent = '✓ Import Completed Successfully';
            resultList.innerHTML = `
                <li>Imported: <strong>${data.imported}</strong> jobs</li>
                <li>Skipped: <strong>${data.skipped}</strong> jobs</li>
                <li>Fetched: <strong>${data.fetched}</strong> jobs</li>
            `;
            loadStatistics();
            loadRecentJobs();
        } else {
            resultAlert.className = 'alert alert-danger';
            resultTitle.textContent = '✗ Import Failed';
            resultList.innerHTML = `<li>${escapeHtml(data.message)}</li>`;
        }

        document.getElementById('importBtn').disabled = false;
    })
    .catch(error => {
        document.getElementById('importStatus').style.display = 'none';
        document.getElementById('importResult').style.display = 'block';
        
        const resultAlert = document.getElementById('importResultAlert');
        resultAlert.className = 'alert alert-danger';
        document.getElementById('importResultTitle').textContent = '✗ Error';
        document.getElementById('importResultList').innerHTML = `<li>${escapeHtml(error.message)}</li>`;
        document.getElementById('importBtn').disabled = false;
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}
</script>

<?= view('Layouts/admin_footer') ?>
