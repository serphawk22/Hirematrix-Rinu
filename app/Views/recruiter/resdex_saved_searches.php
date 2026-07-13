<?= view('Layouts/recruiter_header', ['title' => 'Manage Searches']) ?>

<style>
.resdex-wrap {
  --primary: #1FB7B5;
  --primary-dark: #17908F;
  --gradient-primary: #1FB7B5;

  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  color: var(--foreground);
  width: 100%;
}
.resdex-wrap * { box-sizing: border-box; }

/* Full-width shell, matching the main Search Resumes page */
.resdex-shell {
  width: 100% !important;
  max-width: 1600px !important;
  margin: 0 auto !important;
  padding: 22px 40px 38px !important;
}
@media (max-width: 1600px) {
  .resdex-shell { max-width: 100% !important; padding: 32px 32px 48px !important; }
}

.page-heading { font-size: 24px; font-weight: 700; margin: 0 0 4px; }
.page-sub { color: var(--muted-foreground); font-size: 14px; margin: 0 0 20px; }

.ms-topbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 4px; }

.ms-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
.ms-tabs a {
  padding: 10px 18px; font-size: 13.5px; font-weight: 600; color: var(--muted-foreground);
  text-decoration: none; border-bottom: 2px solid transparent;
}
.ms-tabs a.active { color: var(--primary-dark); border-bottom-color: var(--primary); }

.select-all-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--muted-foreground);
  cursor: pointer;
  user-select: none;
  margin-bottom: 14px;
}
.select-all-row input { accent-color: var(--primary); cursor: pointer; width: 16px; height: 16px; }

.bulk-action-bar {
  position: sticky;
  top: 10px;
  z-index: 20;
  display: none;
  align-items: center;
  gap: 14px;
  background: var(--card);
  border: 1px solid var(--primary);
  border-radius: 12px;
  padding: 12px 18px;
  margin-bottom: 16px;
  box-shadow: 0 4px 14px rgba(0,0,0,0.08);
  flex-wrap: wrap;
}
.bulk-action-bar.active { display: flex; }
.bulk-action-bar .bulk-count { font-size: 13.5px; font-weight: 700; color: var(--foreground); white-space: nowrap; }
.bulk-action-bar .bulk-count span { color: var(--primary); }
.bulk-action-bar .spacer { flex: 1; }
.bulk-clear-btn {
  background: none; border: none; color: var(--muted-foreground); font-size: 12.5px;
  font-weight: 600; cursor: pointer; text-decoration: underline; white-space: nowrap;
}

/* 2-up grid of search cards, matching the candidate-grid pattern on Search Resumes */
.ms-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}
@media (max-width: 768px) {
  .ms-grid { grid-template-columns: 1fr; }
}

.ms-card {
  background: var(--card); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 20px;
  display: flex; flex-direction: column; gap: 14px;
}
.ms-card-top { display: flex; align-items: flex-start; gap: 12px; }
.ms-select-wrap { display: flex; align-items: flex-start; padding-top: 2px; flex-shrink: 0; }
.ms-select-wrap input[type="checkbox"] { width: 17px; height: 17px; accent-color: var(--primary); cursor: pointer; }
.ms-body { flex: 1; min-width: 0; }
.ms-title { font-size: 14.5px; font-weight: 700; margin: 0 0 4px; }
.ms-meta { font-size: 12px; color: var(--muted-foreground); }
.ms-badge {
  display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 999px;
  background: rgba(31,183,181,0.1); color: var(--primary-dark); margin-left: 8px; text-transform: capitalize;
}
.ms-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.resdex-wrap .btn {
  border: none; border-radius: 9px; padding: 8px 14px; font-size: 12.5px; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
}
.resdex-wrap .btn-primary { background: var(--gradient-primary); color: #fff; }
.resdex-wrap .btn-primary:hover { filter: brightness(1.05); color: #fff; }
.resdex-wrap .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--foreground); }
.resdex-wrap .btn-danger { background: transparent; border: 1px solid #E57373; color: #E57373; }
.resdex-wrap .btn-danger:hover { background: rgba(229,115,115,0.08); }
.resdex-wrap .btn:disabled { opacity: 0.6; cursor: not-allowed; }

.empty-state { text-align: center; padding: 60px 20px; color: var(--muted-foreground); background: var(--card); border: 1px solid var(--border); border-radius: 16px; }
.empty-state i { font-size: 40px; color: var(--border); margin-bottom: 14px; display: block; }

body.dark .ms-card { background-color: #000; border: 1px solid #23343A; }
body.dark .bulk-action-bar { background: #000; }
body.dark .ms-tabs{
  border: none !important;
}
body.dark .btn.btn-outline{
  color:#FFF;
  border:1px solid #23343A;
}
.body.dark .bulk-action-bar .bulk-count{
  color: #1FB7B5;
}
.bulk-action-bar .bulk-count{
  color: #1FB7B5;
}
body.dark .empty-state{
  background-color:#000;
    border:1px solid #23343A;
}
</style>

<div class="resdex-wrap resdex-jobboard">
  <div class="resdex-shell">

    
      <div class="page-board-header page-board-header-tight recruiter-page-board-header">
    <div class="page-board-copy">
      <h1 class="page-board-title">Manage Searches</h1>
      <p class="page-board-subtitle">Your saved searches and recent search history in one place.</p>
    </div>
    <div class="page-board-actions">
      <a href="<?= site_url('recruiter/resdex') ?>" class="btn btn-outline-primary">
        Back to Resdex
      </a>
    </div>
  
     
    </div>

    <div class="ms-tabs">
      <a href="<?= site_url('recruiter/resdex/saved-searches') ?>?tab=saved" class="<?= $activeTab === 'saved' ? 'active' : '' ?>">
        My Saved Searches
      </a>
      <a href="<?= site_url('recruiter/resdex/saved-searches') ?>?tab=recent" class="<?= $activeTab === 'recent' ? 'active' : '' ?>">
        My Recent Searches
      </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
    <?php endif; ?>

    <?php if (empty($searches)): ?>
      <div class="empty-state">
        <i class="fas fa-bookmark"></i>
        <p><?= $activeTab === 'saved' ? 'You haven\'t saved any searches yet.' : 'No recent search activity yet.' ?></p>
      </div>
    <?php else: ?>

      <label class="select-all-row" for="selectAllSearches">
        <input type="checkbox" id="selectAllSearches">
        Select all
      </label>

      <!-- Bulk action bar: hidden until at least one search is checked -->
      <div class="bulk-action-bar" id="bulkActionBar">
        <div class="bulk-count"><span id="bulkSelectedCount">0</span> selected</div>
        <div class="spacer"></div>
        <button type="button" class="btn btn-danger" id="bulkDeleteBtn">
          <i class="fas fa-trash"></i> Delete Selected
        </button>
        <button type="button" class="bulk-clear-btn" id="bulkClearBtn">Clear selection</button>
      </div>

      <!-- Hidden form used by the bulk delete action -->
      <form id="bulkDeleteForm" method="post" action="<?= site_url('recruiter/resdex/saved-searches/delete') ?>">
          <?= csrf_field() ?>
      </form>

      <div class="ms-grid" id="searchCardList">
      <?php foreach ($searches as $s): $sf = json_decode($s['filters_json'], true) ?: []; ?>
        <div class="ms-card" data-search-id="<?= (int) $s['id'] ?>">
          <div class="ms-card-top">
            <div class="ms-select-wrap">
              <input type="checkbox" class="search-select" value="<?= (int) $s['id'] ?>">
            </div>
            <div class="ms-body">
              <p class="ms-title">
                <?= esc($s['search_name']) ?>
                <?php if ($activeTab === 'saved' && $s['alert_frequency'] !== 'none'): ?>
                  <span class="ms-badge"><i class="fas fa-bell"></i> <?= esc($s['alert_frequency']) ?></span>
                <?php endif; ?>
              </p>
              <p class="ms-meta">Created on <?= date('d M Y', strtotime($s['created_at'])) ?></p>
            </div>
          </div>
          <div class="ms-actions">
            
            <a href="<?= site_url('recruiter/resdex') . '?' . http_build_query(array_merge($sf, ['search' => 1])) ?>" class="btn btn-primary">
              <i class="fas fa-search"></i> Search
            </a>
            <form method="post" action="<?= site_url('recruiter/resdex/saved-searches/delete') ?>" onsubmit="return confirm('Delete this search?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button type="submit" class="btn btn-danger">Remove</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
/* ============ BULK SELECT + BULK DELETE ============ */
document.addEventListener('DOMContentLoaded', function () {
  const checkboxes = document.querySelectorAll('.search-select');
  const selectAll  = document.getElementById('selectAllSearches');
  const bar        = document.getElementById('bulkActionBar');
  const countEl    = document.getElementById('bulkSelectedCount');
  const deleteBtn  = document.getElementById('bulkDeleteBtn');
  const clearBtn   = document.getElementById('bulkClearBtn');
  const bulkForm   = document.getElementById('bulkDeleteForm');

  if (!checkboxes.length || !bar) return;

  function getSelected() {
    return Array.from(checkboxes).filter(function (cb) { return cb.checked; });
  }

  function refreshBar() {
    const selected = getSelected();
    countEl.textContent = selected.length;
    bar.classList.toggle('active', selected.length > 0);

    if (selectAll) {
      selectAll.checked = selected.length === checkboxes.length;
      selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
    }
  }

  checkboxes.forEach(function (cb) {
    cb.addEventListener('change', refreshBar);
  });

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
      refreshBar();
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      checkboxes.forEach(function (cb) { cb.checked = false; });
      refreshBar();
    });
  }

  if (deleteBtn) {
    deleteBtn.addEventListener('click', async function () {
      const selected = getSelected().map(function (cb) { return cb.value; });

      if (!selected.length) {
        alert('Select at least one search.');
        return;
      }

      if (!confirm('Delete ' + selected.length + ' selected search(es)? This cannot be undone.')) {
        return;
      }

      deleteBtn.disabled = true;
      const originalHtml = deleteBtn.innerHTML;
      deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

      try {
        const formData = new FormData(bulkForm);
        selected.forEach(function (id) { formData.append('ids[]', id); });

        const response = await fetch(bulkForm.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Something went wrong.');
        }

        // Remove deleted cards from the DOM without a full page reload.
        (data.deleted_ids || []).forEach(function (id) {
          const card = document.querySelector('.ms-card[data-search-id="' + id + '"]');
          if (card) card.remove();
        });

        refreshBar();

        // If nothing's left, do a full reload so the empty-state shows.
        if (!document.querySelectorAll('.ms-card').length) {
          window.location.reload();
        }

      } catch (err) {
        alert(err.message || 'Failed to delete searches.');
      } finally {
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalHtml;
      }
    });
  }
});
</script>

<?= view('Layouts/recruiter_footer') ?>