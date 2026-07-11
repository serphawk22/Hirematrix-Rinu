<?= view('Layouts/recruiter_header', ['title' => esc($folder['folder_name'])]) ?>

<style>
.resdex-folder-detail-jobboard {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  color: var(--hm-text);
  width: 100%;
}
.resdex-folder-detail-jobboard * { box-sizing: border-box; }

.resdex-folder-detail-shell {
  width: 100% !important;
  max-width: 1600px !important;
  margin: 0 auto !important;
  padding: 8px 40px 48px !important;
}
@media (max-width: 1600px) {
  .resdex-folder-detail-shell { max-width: 100% !important; padding: 8px 32px 48px !important; }
}
@media (max-width: 767.98px) {
  .resdex-folder-detail-shell { padding: 8px 16px 32px !important; }
}
body.dark h3.candidate-name{
  color:#FFF;
}
.resdex-folder-detail-jobboard .breadcrumb-link { font-size: 12.5px; color: var(--hm-muted); text-decoration: none; }
.resdex-folder-detail-jobboard .breadcrumb-link:hover { color: var(--hm-primary-dark); }

.resdex-folder-detail-jobboard .page-heading { font-size: 24px; font-weight: 700; margin: 10px 0 4px; }
.resdex-folder-detail-jobboard .page-sub { color: var(--hm-muted); font-size: 14px; margin: 0 0 20px; }

/* ── Gmail-style bulk action bar ──
   Default state shows a "select all" checkbox. Once >=1 row is checked,
   the count + Remove Selected button appear, same as Gmail's inbox toolbar. */
.resdex-folder-detail-jobboard .bulk-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  background: var(--hm-card);
  border: 1px solid var(--hm-border);
  border-radius: 12px;
  padding: 10px 16px;
  margin-bottom: 14px;
  min-height: 52px;
}
.resdex-folder-detail-jobboard .bulk-select-all {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 600;
  color: var(--hm-muted);
  cursor: pointer;
  user-select: none;
}
.resdex-folder-detail-jobboard .bulk-select-all input[type="checkbox"] {
  width: 17px; height: 17px; accent-color: var(--hm-primary); cursor: pointer;
}
.resdex-folder-detail-jobboard .bulk-count {
  font-size: 13px;
  font-weight: 600;
  color: var(--hm-text);
  display: none;
}
.resdex-folder-detail-jobboard .bulk-actions {
  margin-left: auto;
  display: none;
  align-items: center;
  gap: 8px;
}
.resdex-folder-detail-jobboard .bulk-bar.has-selection .bulk-count,
.resdex-folder-detail-jobboard .bulk-bar.has-selection .bulk-actions {
  display: flex;
}

.resdex-folder-detail-jobboard .candidate-card {
  background: var(--hm-card); border: 1px solid var(--hm-border); border-radius: var(--hm-card-radius);
  padding: 18px 20px; margin-bottom: 14px; display: flex; gap: 16px; align-items: flex-start;
  transition: border-color .15s, background .15s;
}
.resdex-folder-detail-jobboard .candidate-card.is-selected {
  border-color: var(--hm-primary) !important;
  background: var(--hm-active-bg);
}
.resdex-folder-detail-jobboard .candidate-select {
  padding-top: 4px;
  flex-shrink: 0;
}
.resdex-folder-detail-jobboard .candidate-select input[type="checkbox"] {
  width: 17px; height: 17px; accent-color: var(--hm-primary); cursor: pointer;
}
.resdex-folder-detail-jobboard .candidate-avatar {
  width: 48px; height: 48px; border-radius: 50%; background: var(--hm-brand-grad);
  display: flex; align-items: center; justify-content: center; color: #fff;
  font-weight: 700; font-size: 18px; flex-shrink: 0;
}
.resdex-folder-detail-jobboard .candidate-main { flex: 1; min-width: 0; }
.resdex-folder-detail-jobboard .candidate-name { font-size: 15.5px; font-weight: 700; margin: 0; }
.resdex-folder-detail-jobboard .candidate-headline { font-size: 13px; color: var(--hm-muted); margin: 2px 0 8px; }
.resdex-folder-detail-jobboard .candidate-meta { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12.5px; color: var(--hm-muted); margin-bottom: 10px; }
.resdex-folder-detail-jobboard .candidate-meta span { display: flex; align-items: center; gap: 5px; }
.resdex-folder-detail-jobboard .candidate-skills { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.resdex-folder-detail-jobboard .skill-chip {
  background: rgba(31,183,181,0.1); color: var(--hm-primary-dark); border: 1px solid rgba(31,183,181,0.18);
  border-radius: 999px; padding: 3px 10px; font-size: 11.5px; font-weight: 600;
}
.resdex-folder-detail-jobboard .candidate-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.resdex-folder-detail-jobboard .btn {
  border: none; border-radius: 9px; padding: 8px 14px; font-size: 12.5px; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
}
.resdex-folder-detail-jobboard .btn-outline { background: transparent; border: 1px solid var(--hm-border); color: var(--hm-text); }
.resdex-folder-detail-jobboard .btn-outline:hover { background: var(--hm-hover-bg); }
.resdex-folder-detail-jobboard .btn-danger { background: transparent; border: 1px solid #E57373; color: #E57373; }
.resdex-folder-detail-jobboard .btn-danger:hover { background: rgba(229,115,115,0.08); }
.resdex-folder-detail-jobboard .btn-danger:disabled { opacity: .5; cursor: not-allowed; }

.resdex-folder-detail-jobboard .empty-state {
  text-align: center; padding: 60px 20px; color: var(--hm-muted);
  background: var(--hm-card); border: 1px solid var(--hm-border); border-radius: var(--hm-card-radius);
}
.resdex-folder-detail-jobboard .empty-state i { font-size: 40px; color: var(--hm-border); margin-bottom: 14px; display: block; }

.resdex-folder-detail-jobboard .candidate-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
}
@media (max-width: 900px) {
  .resdex-folder-detail-jobboard .candidate-grid {
    grid-template-columns: 1fr;
  }
}

.resdex-folder-detail-jobboard .candidate-card {
  background: var(--hm-card); border: 1px solid var(--hm-border); border-radius: var(--hm-card-radius);
  padding: 18px 20px; display: flex; gap: 16px; align-items: flex-start;
  transition: border-color .15s, background .15s;
  /* margin-bottom removed — grid gap handles spacing now */
}
</style>

<div class="resdex-folder-detail-jobboard">
<div class="resdex-folder-detail-shell">

  <div class="page-board-header page-board-header-tight recruiter-page-board-header">
    <div class="page-board-copy">
      <h1 class="page-board-title"><?= esc($folder['folder_name']) ?></h1>
      <p class="page-board-subtitle"><?= count($candidates) ?> candidate<?= count($candidates) == 1 ? '' : 's' ?> in this folder</p>
    </div>
    <div class="page-board-actions">
      <a href="<?= site_url('recruiter/resdex/folders') ?>" class="btn btn-outline-primary">
        Back to Folders
      </a>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <?php if (empty($candidates)): ?>
    <div class="empty-state">
      <i class="fas fa-user-slash"></i>
      <p>No candidates in this folder yet. Add candidates from Search Resumes results.</p>
    </div>
  <?php else: ?>

    <!-- Hidden form used for the bulk remove submission -->
    <form id="bulkRemoveForm" method="post" action="<?= site_url('recruiter/resdex/folder/remove') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="folder_id" value="<?= (int) $folder['id'] ?>">
    </form>

    <div class="bulk-bar" id="bulkBar">
      <label class="bulk-select-all">
        <input type="checkbox" id="selectAllCheckbox">
        Select all
      </label>
      <span class="bulk-count" id="bulkCount">0 selected</span>
      <div class="bulk-actions">
        <button type="button" class="btn btn-danger" id="bulkRemoveBtn">
          <i class="fas fa-times"></i> Remove Selected
        </button>
      </div>
    </div>

  <div class="candidate-grid">
  <?php foreach ($candidates as $candidate): ?>
    <div class="candidate-card" data-candidate-row="<?= (int) $candidate['user_id'] ?>">
      <label class="candidate-select">
        <input type="checkbox" class="candidate-checkbox" value="<?= (int) $candidate['user_id'] ?>">
      </label>
      <div class="candidate-avatar"><?= strtoupper(substr($candidate['name'] ?? 'C', 0, 1)) ?></div>
      <div class="candidate-main">
        <h3 class="candidate-name"><?= esc($candidate['name'] ?? 'Candidate') ?></h3>
        <p class="candidate-headline"><?= esc($candidate['headline'] ?? 'No headline provided') ?></p>

        <div class="candidate-meta">
          <?php if (!empty($candidate['location'])): ?>
            <span><i class="fas fa-map-marker-alt"></i> <?= esc($candidate['location']) ?></span>
          <?php endif; ?>
          <span><i class="fas fa-briefcase"></i> <?= round(($candidate['total_experience_months'] ?? 0) / 12, 1) ?> yrs exp</span>
          <?php if (!empty($candidate['notice_period'])): ?>
            <span><i class="fas fa-clock"></i> <?= esc($candidate['notice_period']) ?> notice</span>
          <?php endif; ?>
          <span><i class="fas fa-calendar-plus"></i> Added <?= date('d M Y', strtotime($candidate['added_at'])) ?></span>
        </div>

        <?php if (!empty($candidate['key_skills'])): ?>
          <div class="candidate-skills">
            <?php foreach (array_slice(array_map('trim', explode(',', $candidate['key_skills'])), 0, 6) as $skill): ?>
              <?php if ($skill !== ''): ?><span class="skill-chip"><?= esc($skill) ?></span><?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="candidate-actions">
          <a href="<?= site_url('recruiter/candidate/' . $candidate['user_id'] . '/view-contact') ?>" class="btn btn-outline">
            <i class="fas fa-eye"></i> View Profile
          </a>
          <a href="<?= site_url('recruiter/candidate/' . $candidate['user_id'] . '/download-resume') ?>" class="btn btn-outline">
            <i class="fas fa-file-download"></i> Resume
          </a>
           <form method="post" action="<?= site_url('recruiter/resdex/folder/remove') ?>" onsubmit="return confirm('Remove from this folder?');">
                <input type="hidden" name="folder_id" value="<?= (int) $folder['id'] ?>">
                <input type="hidden" name="candidate_id" value="<?= (int) $candidate['user_id'] ?>">
                <button type="submit" class="btn btn-danger"> Remove</button>
              </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

  <?php endif; ?>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const bulkBar        = document.getElementById('bulkBar');
  const selectAllCb     = document.getElementById('selectAllCheckbox');
  const bulkCountLabel  = document.getElementById('bulkCount');
  const bulkRemoveBtn   = document.getElementById('bulkRemoveBtn');
  const bulkRemoveForm  = document.getElementById('bulkRemoveForm');
  if (!bulkBar) return; // no candidates on this page

  function getCheckboxes() {
    return Array.from(document.querySelectorAll('.candidate-checkbox'));
  }

  function updateBar() {
    const checkboxes = getCheckboxes();
    const checked    = checkboxes.filter(cb => cb.checked);

    bulkBar.classList.toggle('has-selection', checked.length > 0);
    bulkCountLabel.textContent = checked.length + ' selected';

    selectAllCb.checked = checked.length === checkboxes.length && checkboxes.length > 0;
    selectAllCb.indeterminate = checked.length > 0 && checked.length < checkboxes.length;

    checkboxes.forEach(cb => {
      const card = cb.closest('.candidate-card');
      if (card) card.classList.toggle('is-selected', cb.checked);
    });
  }

  selectAllCb.addEventListener('change', function () {
    getCheckboxes().forEach(cb => { cb.checked = selectAllCb.checked; });
    updateBar();
  });

  document.addEventListener('change', function (e) {
    if (e.target.classList && e.target.classList.contains('candidate-checkbox')) {
      updateBar();
    }
  });

  bulkRemoveBtn.addEventListener('click', async function () {
    const selectedIds = getCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
    if (selectedIds.length === 0) return;

    const confirmMsg = 'Remove ' + selectedIds.length + ' candidate' + (selectedIds.length === 1 ? '' : 's') + ' from this folder?';
    if (!confirm(confirmMsg)) return;

    bulkRemoveBtn.disabled = true;

    try {
      const formData = new FormData(bulkRemoveForm);
      selectedIds.forEach(id => formData.append('candidate_ids[]', id));

      const response = await fetch(bulkRemoveForm.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Something went wrong.');
      }

      // Remove the rows from the DOM without a full page reload
      (data.removed_ids || selectedIds).forEach(function (id) {
        const row = document.querySelector('.candidate-card[data-candidate-row="' + id + '"]');
        if (row) row.remove();
      });

      updateBar();

      // If the folder is now empty, reload to show the proper empty state
      if (getCheckboxes().length === 0) {
        window.location.reload();
      }

    } catch (err) {
      alert(err.message || 'Failed to remove candidates.');
    } finally {
      bulkRemoveBtn.disabled = false;
    }
  });
});
</script>

<?= view('Layouts/recruiter_footer') ?>