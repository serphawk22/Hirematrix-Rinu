<?= view('Layouts/admin_header', ['title' => 'Feedback Management']) ?>

<h3 class="mb-4 fw-bold">Feedback Management</h3>

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
 <br/>
<!-- TABLE -->
<div class="card shadow-sm"> 
    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
      
        <table class="table mb-0 align-middle">
            <thead class="sticky-header">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Message</th>
                    <th>Rating</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                <?php if(empty($feedbacks)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No feedback found</td></tr>
                <?php else: ?>
                    <?php foreach($feedbacks as $f): ?>
                        <tr>
                            <td><?= $f['id'] ?></td>
                            <td><?= esc($f['name']) ?></td>
                            <td><?= esc($f['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $f['role']=='recruiter' ? 'primary' : 'secondary' ?>">
                                    <?= ucfirst($f['role']) ?>
                                </span>
                            </td>
                            <td style="max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= esc($f['message']) ?>
                            </td>
                            <td><?= $f['rating'] ?? '-' ?></td>
                            <td><?= $f['created_at'] ?></td>
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

    fetch("<?= base_url('admin/feedback/suggestions') ?>?term=" + value)
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

document.addEventListener('click', function(e){
    if(!input.contains(e.target)){
        box.innerHTML = '';
    }
});
</script>
<?= view('Layouts/admin_footer') ?>