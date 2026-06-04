<?= view('Layouts/admin_header', ['title' => 'Companies']) ?>

<h3 class="mb-4 fw-bold">Companies</h3>

<!-- FILTER -->
<form method="get" class="row g-2 mb-3 align-items-stretch">

    <div class="col-md-4 position-relative">
        <input type="text" 
               name="search" 
               id="searchInput"
               value="<?= esc($search) ?>"
               class="form-control custom-input"
               placeholder="Search company...">

        <div id="suggestionsBox" 
     class="list-group position-absolute w-100 shadow"
     style="z-index:9999;background:#fff;">
</div>
    </div>

    <div class="col-md-2 d-flex">
        <button class="btn btn-primary w-100">Search</button>
    </div>

</form>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="table-responsive" style="max-height:450px; overflow-y:auto;">

<table class="table mb-0 align-middle">
<thead class="table-light" style="position:sticky; top:0; z-index:2;">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Website</th>
    <th>Industry</th>
    <th>HQ</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach($companies as $c): ?>
<tr class="company-row" data-id="<?= $c['id'] ?>" style="cursor:pointer;">
    <td><?= $c['id'] ?></td>
    <td class="fw-semibold"><?= esc($c['name']) ?></td>
    <td class="text-primary"><?= esc($c['website']) ?></td>
    <td><?= esc($c['industry']) ?></td>
    <td><?= esc($c['hq']) ?></td>
    <td>
        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $c['id'] ?>">
            Delete
        </button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>
</div>

<!-- ✅ PROFESSIONAL MODAL -->
<div class="modal fade" id="companyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">

            <!-- HEADER -->
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="companyTitle">Company Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body pt-2" id="companyDetails">
                Loading...
            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-0">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

<!-- JS -->
<script>
const input = document.getElementById('searchInput');
const box = document.getElementById('suggestionsBox');

/* =========================
   SEARCH SUGGESTIONS
========================= */
input.addEventListener('keyup', function() {
    let val = this.value;

    if(val.length < 2){
        box.innerHTML = '';
        box.classList.remove('active'); // 👈 hide
        return;
    }

    fetch("<?= base_url('admin/companies/suggestions') ?>?term=" + val)
    .then(res => res.json())
    .then(data => {

        box.innerHTML = '';

        if(data.length === 0){
            box.classList.remove('active'); // 👈 hide if empty
            return;
        }

        data.forEach(item => {
            let el = document.createElement('a');
            el.className = 'list-group-item list-group-item-action';
            el.innerText = item.name;

            el.onclick = () => {
                input.value = item.name;
                box.innerHTML = '';
                box.classList.remove('active');
            };

            box.appendChild(el);
        });

        box.classList.add('active'); // 👈 show only when data exists
    });
});

/* =========================
   OPEN MODAL (CLICK ROW)
========================= */
document.querySelectorAll('.company-row').forEach(row => {
    row.addEventListener('click', function(){

        let id = this.dataset.id;

        fetch("<?= base_url('admin/company') ?>/" + id)
        .then(res => res.json())
        .then(data => {

            document.getElementById('companyTitle').innerText = data.name;

            let html = `
                <div class="mb-3">
                    <h4 class="fw-bold mb-1">${data.name}</h4>
                    <p class="text-muted mb-0">${data.short_description ?? ''}</p>
                </div>

                <div class="row g-3 mb-3">

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="small text-muted">Industry</div>
                            <div class="fw-semibold">${data.industry ?? '-'}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="small text-muted">Company Size</div>
                            <div class="fw-semibold">${data.size ?? '-'}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="small text-muted">Headquarters</div>
                            <div class="fw-semibold">${data.hq ?? '-'}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3">
                            <div class="small text-muted">Website</div>
                            <div class="fw-semibold">
                                <a href="${data.website}" target="_blank">
                                    ${data.website ?? '-'}
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mb-3">
                    <h6 class="fw-bold">What We Do</h6>
                    <p class="text-muted">${data.what_we_do ?? '-'}</p>
                </div>

                <div>
                    <h6 class="fw-bold">Culture</h6>
                    <p class="text-muted">${data.culture_summary ?? '-'}</p>
                </div>
            `;

            document.getElementById('companyDetails').innerHTML = html;

            new bootstrap.Modal(document.getElementById('companyModal')).show();
        });
    });
});

/* =========================
   DELETE COMPANY
========================= */
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e){

        e.stopPropagation();

        let id = this.dataset.id;

        if(!confirm("Are you sure you want to delete this company?")) return;

        fetch("<?= base_url('admin/company/delete') ?>/" + id, {
            method: "POST"
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success'){
                this.closest('tr').remove();
            }
        });
    });
});

/* =========================
   HIDE SUGGESTIONS OUTSIDE CLICK
========================= */
document.addEventListener('click', function(e){
    if(!input.contains(e.target)){
        box.innerHTML = '';
        box.classList.remove('active');
    }
});
</script>
<?= view('Layouts/admin_footer') ?>