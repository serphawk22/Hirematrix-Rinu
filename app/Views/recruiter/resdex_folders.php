<?= view('Layouts/recruiter_header', ['title' => 'My Folders']) ?>

<style>
.resdex-folders-jobboard {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  color: var(--hm-text);
  width: 100%;
}
.resdex-folders-jobboard * { box-sizing: border-box; }

.resdex-folders-shell {
  width: 100% !important;
  max-width: 1600px !important;
  margin: 0 auto !important;
  padding: 8px 40px 48px !important;
}
@media (max-width: 1600px) {
  .resdex-folders-shell { max-width: 100% !important; padding: 8px 32px 48px !important; }
}
@media (max-width: 767.98px) {
  .resdex-folders-shell { padding: 8px 16px 32px !important; }
}

.resdex-folders-jobboard .page-heading { font-size: 24px; font-weight: 700; margin: 0 0 4px; }
.resdex-folders-jobboard .page-sub { color: var(--hm-muted); font-size: 14px; margin: 0 0 20px; }

.resdex-folders-jobboard .folders-toolbar { display: flex; gap: 10px; margin-bottom: 20px; }
.resdex-folders-jobboard .folders-toolbar input {
  flex: 1; max-width: 320px; background: var(--hm-card); border: 1px solid var(--hm-border); border-radius: 9px;
  padding: 10px 14px; font-size: 13px; color: var(--hm-text);
}
.resdex-folders-jobboard .folders-toolbar input:focus {
  outline: none; border-color: var(--hm-primary-dark); box-shadow: none;
}
.resdex-folders-jobboard .folders-toolbar input::placeholder { color: var(--hm-light); }

.resdex-folders-jobboard .btn {
  border: none; border-radius: 9px; padding: 10px 16px; font-size: 13px; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
}
.resdex-folders-jobboard .btn-primary { background: var(--hm-brand-grad); color: #fff; }
.resdex-folders-jobboard .btn-primary:hover { filter: brightness(1.05); color: #fff; }
.resdex-folders-jobboard .btn-outline { background: transparent; border: 1px solid var(--hm-border); color: var(--hm-text); }
.resdex-folders-jobboard .btn-outline:hover { background: var(--hm-hover-bg); }
.resdex-folders-jobboard .btn-danger { background: transparent; border: 1px solid #E57373; color: #E57373; }
.resdex-folders-jobboard .btn-danger:hover { background: rgba(229,115,115,0.08); }
.resdex-folders-jobboard .btn-danger:disabled { opacity: .5; cursor: not-allowed; }

/* ── Gmail-style bulk action bar ──
   Default: just a "select all" checkbox. Once >=1 folder is checked,
   the count + Delete Selected button appear. */
.resdex-folders-jobboard .bulk-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  background: var(--hm-card);
  border: 1px solid var(--hm-border);
  border-radius: 12px;
  padding: 10px 16px;
  margin-bottom: 16px;
  min-height: 52px;
}
.resdex-folders-jobboard .bulk-select-all {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: var(--hm-muted);
  cursor: pointer;
  user-select: none;
}
.resdex-folders-jobboard .bulk-select-all input[type="checkbox"] {
  width: 17px; height: 17px; accent-color: var(--hm-primary); cursor: pointer;
}
.resdex-folders-jobboard .bulk-count {
  font-size: 13px;
  font-weight: 600;
  color: var(--hm-text);
  display: none;
}
.resdex-folders-jobboard .bulk-actions {
  margin-left: auto;
  display: none;
  align-items: center;
  gap: 8px;
}
.resdex-folders-jobboard .bulk-bar.has-selection .bulk-count,
.resdex-folders-jobboard .bulk-bar.has-selection .bulk-actions {
  display: flex;
}

.resdex-folders-jobboard .folder-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;
}

/* Folder card is now a div (not an <a>) so it can hold a checkbox without
   breaking navigation. The inner .folder-card-link wraps the clickable area. */
.resdex-folders-jobboard .folder-card {
  position: relative;
  background: var(--hm-card); border: 1px solid var(--hm-border); border-radius: var(--hm-card-radius);
  padding: 20px; transition: border-color .15s, background .15s;
}
.resdex-folders-jobboard .folder-card.is-selected {
  border-color: var(--hm-primary) !important;
  background: var(--hm-active-bg);
}
.resdex-folders-jobboard .folder-select {
  position: absolute;
  top: 14px;
  right: 14px;
  z-index: 2;
}
.resdex-folders-jobboard .folder-select input[type="checkbox"] {
  width: 18px; height: 18px; accent-color: var(--hm-primary); cursor: pointer;
}
.resdex-folders-jobboard .folder-card-link {
  display: flex; flex-direction: column; gap: 10px;
  text-decoration: none; color: var(--hm-text);
  padding-right: 26px; /* keep text clear of the checkbox */
}
.resdex-folders-jobboard .folder-card:hover { border-color: var(--hm-primary) !important; }

.resdex-folders-jobboard .folder-icon {
  width: 42px; height: 42px; border-radius: 10px; background: var(--hm-brand-grad);
  display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px;
}
.resdex-folders-jobboard .folder-name { font-size: 14.5px; font-weight: 700; margin: 0; }
.resdex-folders-jobboard .folder-count { font-size: 12px; color: var(--hm-muted); }

.resdex-folders-jobboard .empty-state {
  text-align: center; padding: 60px 20px; color: var(--hm-muted);
  background: var(--hm-card); border: 1px solid var(--hm-border); border-radius: var(--hm-card-radius);
}
.resdex-folders-jobboard .empty-state i { font-size: 40px; color: var(--hm-border); margin-bottom: 14px; display: block; }
</style>

<div class="resdex-folders-jobboard">
<div class="resdex-folders-shell">

  <div class="page-board-header page-board-header-tight recruiter-page-board-header">
    <div class="page-board-copy">
      <h1 class="page-board-title">My Folders</h1>
      <p class="page-board-subtitle">Candidates you've shortlisted, organized into folders.</p>
    </div>
    <div class="page-board-actions">
      <a href="<?= site_url('recruiter/resdex') ?>" class="btn btn-outline-primary">
        Back to Resdex
      </a>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= site_url('recruiter/resdex/folder/create') ?>" class="folders-toolbar">
    <?= csrf_field() ?>
    <input type="text" name="folder_name" placeholder="New folder name" required>
    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Folder</button>
  </form>

  <?php if (empty($folders)): ?>
    <div class="empty-state">
      <i class="fas fa-folder-open"></i>
      <p>No folders yet. Create one above, or add a candidate to a folder from search results.</p>
    </div>
  <?php else: ?>

    <!-- Hidden form used for the bulk delete submission -->
    <form id="bulkDeleteForm" method="post" action="<?= site_url('recruiter/resdex/folder/delete') ?>">
      <?= csrf_field() ?>
    </form>

    <div class="bulk-bar" id="bulkBar">
      <label class="bulk-select-all">
        <input type="checkbox" id="selectAllCheckbox">
        Select all
      </label>
      <span class="bulk-count" id="bulkCount">0 selected</span>
      <div class="bulk-actions">
        <button type="button" class="btn btn-danger" id="bulkDeleteBtn">
          <i class="fas fa-trash"></i> Delete Selected
        </button>
      </div>
    </div>

    <div class="folder-grid">
      <?php foreach ($folders as $folder): ?>
        <div class="folder-card" data-folder-row="<?= (int) $folder['id'] ?>">
          <label class="folder-select">
            <input type="checkbox" class="folder-checkbox" value="<?= (int) $folder['id'] ?>">
          </label>
          <a href="<?= site_url('recruiter/resdex/folders/' . (int) $folder['id']) ?>" class="folder-card-link">
            <div class="folder-icon"><i class="fas fa-folder"></i></div>
            <p class="folder-name"><?= esc($folder['folder_name']) ?></p>
            <p class="folder-count"><?= (int) $folder['candidate_count'] ?> candidate<?= $folder['candidate_count'] == 1 ? '' : 's' ?></p>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const bulkBar       = document.getElementById('bulkBar');
  const selectAllCb    = document.getElementById('selectAllCheckbox');
  const bulkCountLabel = document.getElementById('bulkCount');
  const bulkDeleteBtn  = document.getElementById('bulkDeleteBtn');
  const bulkDeleteForm = document.getElementById('bulkDeleteForm');
  if (!bulkBar) return; // no folders on this page

  function getCheckboxes() {
    return Array.from(document.querySelectorAll('.folder-checkbox'));
  }

  function updateBar() {
    const checkboxes = getCheckboxes();
    const checked    = checkboxes.filter(cb => cb.checked);

    bulkBar.classList.toggle('has-selection', checked.length > 0);
    bulkCountLabel.textContent = checked.length + ' selected';

    selectAllCb.checked = checked.length === checkboxes.length && checkboxes.length > 0;
    selectAllCb.indeterminate = checked.length > 0 && checked.length < checkboxes.length;

    checkboxes.forEach(cb => {
      const card = cb.closest('.folder-card');
      if (card) card.classList.toggle('is-selected', cb.checked);
    });
  }

  selectAllCb.addEventListener('change', function () {
    getCheckboxes().forEach(cb => { cb.checked = selectAllCb.checked; });
    updateBar();
  });

  document.addEventListener('change', function (e) {
    if (e.target.classList && e.target.classList.contains('folder-checkbox')) {
      updateBar();
    }
  });

  bulkDeleteBtn.addEventListener('click', async function () {
    const selectedIds = getCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
    if (selectedIds.length === 0) return;

    const confirmMsg = 'Delete ' + selectedIds.length + ' folder' + (selectedIds.length === 1 ? '' : 's')
      + '? Candidates saved in ' + (selectedIds.length === 1 ? 'it' : 'them') + ' will be removed from that folder, but not from ResDex.';
    if (!confirm(confirmMsg)) return;

    bulkDeleteBtn.disabled = true;

    try {
      const formData = new FormData(bulkDeleteForm);
      selectedIds.forEach(id => formData.append('folder_ids[]', id));

      const response = await fetch(bulkDeleteForm.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Something went wrong.');
      }

      (data.deleted_ids || selectedIds).forEach(function (id) {
        const row = document.querySelector('.folder-card[data-folder-row="' + id + '"]');
        if (row) row.remove();
      });

      updateBar();

      if (getCheckboxes().length === 0) {
        window.location.reload();
      }

    } catch (err) {
      alert(err.message || 'Failed to delete folders.');
    } finally {
      bulkDeleteBtn.disabled = false;
    }
  });
});
</script>

<?= view('Layouts/recruiter_footer') ?>