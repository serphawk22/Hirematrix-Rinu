<?= view('Layouts/admin_header', ['title' => 'Jobs Management']) ?>

<h3 class="mb-4 fw-bold">Jobs</h3>

<?php $stats = $stats ?? []; ?>
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small">Total</div>
                <div class="h4 mb-0"><?= (int) ($stats['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small">Open</div>
                <div class="h4 mb-0"><?= (int) ($stats['open_jobs'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small">External</div>
                <div class="h4 mb-0"><?= (int) ($stats['external_jobs'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="text-muted small">Today</div>
                <div class="h4 mb-0"><?= (int) ($stats['imported_today'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 align-items-center">
                    <div>
                        <div class="text-muted small">External imports this week</div>
                        <div class="h4 mb-0"><?= (int) ($stats['imported_week'] ?? 0) ?></div>
                    </div>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                        Import Jobs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="get" class="row g-2 mb-3 align-items-stretch">
    <div class="col-md-3 position-relative">
        <input type="text"
               name="search"
               id="searchInput"
               value="<?= esc($search) ?>"
               class="form-control"
               placeholder="Search title, company, location...">

        <div id="suggestionsBox"
             class="list-group position-absolute w-100"
             style="z-index:9999; display:none;">
        </div>
    </div>

    <div class="col-md-2">
        <select name="type" class="form-select">
            <option value="">All types</option>
            <option value="external" <?= ($type ?? '') === 'external' ? 'selected' : '' ?>>External</option>
            <option value="internal" <?= ($type ?? '') === 'internal' ? 'selected' : '' ?>>Internal</option>
        </select>
    </div>

    <div class="col-md-2">
        <select name="source" class="form-select">
            <option value="">All sources</option>
            <?php foreach (($sources ?? []) as $sourceName): ?>
                <option value="<?= esc($sourceName) ?>" <?= ($source ?? '') === $sourceName ? 'selected' : '' ?>>
                    <?= esc(ucfirst($sourceName)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="open" <?= ($status ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
            <option value="closed" <?= ($status ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
    </div>

    <div class="col-md-2 d-flex">
        <button class="btn btn-primary w-100">Search</button>
    </div>
</form>

<div class="card shadow-sm">
<div class="table-responsive" style="max-height:450px; overflow-y:auto;">
<table class="table mb-0">
<thead class="table-light sticky-header">
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Company</th>
    <th>Location</th>
    <th>Source</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($jobs as $j): ?>
<tr class="job-row" data-id="<?= (int) $j['id'] ?>" style="cursor:pointer;">
    <td><?= (int) $j['id'] ?></td>
    <td><?= esc($j['title']) ?></td>
    <td><?= esc($j['company']) ?></td>
    <td><?= esc($j['location']) ?></td>
    <td>
        <?php if ((int) ($j['is_external'] ?? 0) === 1): ?>
            <span class="badge bg-info text-dark"><?= esc($j['external_source'] ?: 'External') ?></span>
        <?php else: ?>
            <span class="badge bg-light text-dark">Internal</span>
        <?php endif; ?>
    </td>
    <td>
        <span class="badge bg-<?= ($j['status'] ?? '') === 'open' ? 'success' : 'secondary' ?>">
            <?= esc(ucfirst((string) ($j['status'] ?? 'unknown'))) ?>
        </span>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="modal fade" id="importModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="importForm">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Import External Jobs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Daily limit</label>
                        <input type="number" name="limit" value="50" min="1" max="100" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sources</label>
                        <input type="text" name="sources" value="remotive,remoteok,arbeitnow" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keyword</label>
                        <input type="text" name="keyword" class="form-control" placeholder="php, react, data analyst">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="india, remote, bengaluru">
                    </div>
                    <div id="importResult" class="alert d-none mb-0"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="importButton">Run Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="jobModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="jobDetails">
                Loading...
            </div>
        </div>
    </div>
</div>

<script>
const input = document.getElementById('searchInput');
const box = document.getElementById('suggestionsBox');
const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.innerText = value ?? '';
    return div.innerHTML;
};

input.addEventListener('keyup', function() {
    let val = this.value;

    if (val.length < 2) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }

    fetch("<?= base_url('admin/jobs/suggestions') ?>?term=" + encodeURIComponent(val))
    .then(res => res.json())
    .then(data => {
        box.innerHTML = '';

        if (data.length === 0) {
            box.style.display = 'none';
            return;
        }

        box.style.display = 'block';

        data.forEach(item => {
            let el = document.createElement('a');
            el.className = 'list-group-item list-group-item-action';
            el.innerText = item.title;

            el.onclick = () => {
                input.value = item.title;
                box.style.display = 'none';
            };

            box.appendChild(el);
        });
    });
});

document.addEventListener('click', function(e) {
    if (!input.contains(e.target)) {
        box.style.display = 'none';
    }
});

document.querySelectorAll('.job-row').forEach(row => {
    row.addEventListener('click', function() {
        let id = this.dataset.id;

        fetch("<?= base_url('admin/job') ?>/" + id)
        .then(res => res.json())
        .then(data => {
            let applyLink = data.external_apply_url
                ? `<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="${escapeHtml(data.external_apply_url)}">Open Apply Link</a>`
                : '';

            let html = `
                <h4 class="fw-bold mb-2">${escapeHtml(data.title)}</h4>
                <p class="text-muted">${escapeHtml(data.company)} &bull; ${escapeHtml(data.location)}</p>
                <hr>
                <p><b>Experience:</b> ${escapeHtml(data.experience_level ?? '-')}</p>
                <p><b>Salary:</b> ${escapeHtml(data.salary_range ?? '-')}</p>
                <p><b>Status:</b> ${escapeHtml(data.status ?? '-')}</p>
                <p><b>Source:</b> ${data.is_external == 1 ? escapeHtml(data.external_source ?? 'External') : 'Internal'}</p>
                <p class="mt-3"><b>Description:</b><br>${escapeHtml(data.description ?? '-')}</p>
                <p><b>Skills:</b><br>${escapeHtml(data.required_skills ?? '-')}</p>
                ${applyLink}
            `;

            document.getElementById('jobDetails').innerHTML = html;
            new bootstrap.Modal(document.getElementById('jobModal')).show();
        });
    });
});

document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const button = document.getElementById('importButton');
    const result = document.getElementById('importResult');
    button.disabled = true;
    button.innerText = 'Importing...';
    result.className = 'alert alert-info mb-0';
    result.innerText = 'Fetching and deduping jobs. This can take a moment.';

    fetch("<?= base_url('admin/jobs/import-manual') ?>", {
        method: 'POST',
        body: new FormData(this),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Import failed');
        }
        return data;
    })
    .then(data => {
        result.className = 'alert alert-success mb-0';
        result.innerText = `Imported ${data.imported}, skipped ${data.skipped}, fetched ${data.fetched}. Refreshing...`;
        setTimeout(() => window.location.reload(), 1200);
    })
    .catch(error => {
        result.className = 'alert alert-danger mb-0';
        result.innerText = error.message;
    })
    .finally(() => {
        button.disabled = false;
        button.innerText = 'Run Import';
    });
});
</script>
<?= view('Layouts/admin_footer') ?>
