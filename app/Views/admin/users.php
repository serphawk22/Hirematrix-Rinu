<?= view('Layouts/admin_header', ['title' => 'User Management']) ?>

    <h3 class="mb-4">User Management</h3>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- FILTER -->
    <form method="get" class="row g-2 mb-3 align-items-stretch">

    <div class="col-md-4 position-relative">
        <input type="text" 
               name="search" 
               id="searchInput"
               value="<?= esc($search) ?>"
               class="form-control h-100"
               placeholder="Search by name...">

        <div id="suggestionsBox" 
             class="list-group position-absolute w-100 shadow"
             style="z-index: 9999; background: #fff;">
        </div> 
    </div>

    <div class="col-md-3 d-flex">
        <select name="role" class="form-select h-100">
            <option value="">All Roles</option>
            <option value="candidate" <?= ($role=='candidate')?'selected':'' ?>>Candidate</option>
            <option value="recruiter" <?= ($role=='recruiter')?'selected':'' ?>>Recruiter</option>
        </select>
    </div>

    <div class="col-md-2 d-flex">
        <button class="btn btn-primary w-100 h-100">Filter</button>
    </div>

</form>

    <!-- TABLE -->
   <div class="card shadow-sm">
    <div class="table-responsive" style="max-height: 900px; overflow-y: auto;">
        
        <table class="table table-bordered mb-0">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 2;">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Recruiter Verification</th>
                    <th>Created</th>
                </tr>
            </thead>

            <tbody>
                <?php if(empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No users found</td></tr>
                <?php else: ?>
                    <?php foreach($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= esc($u['name']) ?></td>
                            <td><?= esc($u['email']) ?></td>
                            <td><?= esc($u['phone']) ?></td>
                            <td>
                                <span class="badge bg-<?= $u['role']=='recruiter' ? 'primary' : 'secondary' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($u['role'] ?? '') === 'recruiter'): ?>
                                    <?php
                                        $type = (string) ($u['recruiter_type'] ?? 'direct_employer');
                                        $status = (string) ($u['verification_status'] ?? 'verified');
                                        $canPost = (int) ($u['can_post_jobs'] ?? 1) === 1;
                                        $statusClass = $status === 'verified' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning');
                                    ?>
                                    <div class="mb-2">
                                        <span class="badge bg-info"><?= esc($type === 'consultancy' ? 'Consultancy' : 'Direct employer') ?></span>
                                        <span class="badge bg-<?= esc($statusClass) ?>"><?= esc(ucfirst($status)) ?></span>
                                        <span class="badge bg-<?= $canPost ? 'success' : 'secondary' ?>"><?= $canPost ? 'Can post' : 'Post blocked' ?></span>
                                    </div>
                                    <?php if (!empty($u['official_email']) || !empty($u['agency_registration_number']) || !empty($u['gst_number'])): ?>
                                        <div class="small text-muted">
                                            <?= !empty($u['official_email']) ? 'Official: ' . esc($u['official_email']) . '<br>' : '' ?>
                                            <?= !empty($u['agency_registration_number']) ? 'Reg: ' . esc($u['agency_registration_number']) . '<br>' : '' ?>
                                            <?= !empty($u['gst_number']) ? 'GST: ' . esc($u['gst_number']) : '' ?>
                                        </div>
                                    <?php endif; ?>
                                    <form method="post" action="<?= base_url('admin/users/recruiter-verification/' . $u['id']) ?>" class="d-flex gap-2 mt-2">
                                        <?= csrf_field() ?>
                                        <select name="verification_status" class="form-select form-select-sm">
                                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="verified" <?= $status === 'verified' ? 'selected' : '' ?>>Verified</option>
                                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $u['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>

    </div>
</div>

<!-- JS -->
<script>
const input = document.getElementById('searchInput');
const box = document.getElementById('suggestionsBox');

input.addEventListener('keyup', function() {
    let value = this.value;

    if(value.length < 2){
        box.innerHTML = '';
        return;
    }

    fetch("<?= base_url('admin/users/suggestions') ?>?term=" + value)
    .then(res => res.json())
    .then(data => {

        box.innerHTML = '';

        data.forEach(item => {
            let el = document.createElement('a');
            el.classList.add('list-group-item', 'list-group-item-action');
            el.innerText = item.name;

            el.onclick = function(){
                input.value = item.name;
                box.innerHTML = '';
            };

            box.appendChild(el);
        });
    });
});

// Hide suggestions on click outside
document.addEventListener('click', function(e){
    if(!input.contains(e.target)){
        box.innerHTML = '';
    }
});
</script>
<?= view('Layouts/admin_footer') ?>
