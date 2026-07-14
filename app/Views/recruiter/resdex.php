<?= view('Layouts/recruiter_header', ['title' => 'Search Resumes']) ?>

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
.page-sub { color: var(--muted-foreground); font-size: 14px; margin: 0 0 24px; }

.resdex-topnav { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
.resdex-topnav .spacer { flex: 1; }

.resdex-layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 24px;
  align-items: start;
}
@media (max-width: 991px) {
  .resdex-layout { grid-template-columns: 1fr; }
}

.resdex-wrap .card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
}
.resdex-wrap .card-title {
  font-size: 15px; font-weight: 700; margin: 0 0 16px;
  display: flex; align-items: center; gap: 8px;
}

.resdex-wrap .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.resdex-wrap .field:last-child { margin-bottom: 0; }
.resdex-wrap .field label { font-size: 12px; font-weight: 600; color: var(--muted-foreground); }
.resdex-wrap .field input,
.resdex-wrap .field select,
.resdex-wrap .field textarea {
  background: var(--background); border: 1px solid var(--border); border-radius: 8px;
  padding: 9px 12px; font-size: 13px; color: var(--foreground); width: 100%;
}
.resdex-wrap .field input:focus,
.resdex-wrap .field select:focus {
  outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(31,183,181,0.15);
}
.range-row { display: flex; gap: 8px; }
.range-row input { min-width: 0; }

.kw-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.kw-toggle { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--muted-foreground); }
.kw-toggle input { accent-color: var(--primary); }

.check-row { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted-foreground); margin: 6px 0 16px; }
.check-row input { accent-color: var(--primary); }

.collapsible-toggle {
  font-size: 12.5px; font-weight: 600; color: var(--primary) !important; background: none; border: none;
  cursor: pointer; padding: 0; margin-bottom: 12px; display: inline-flex; align-items: center; gap: 6px;
}
.collapsible-body { display: none; }
.collapsible-body.open { display: block; margin-bottom: 4px; }

.resdex-wrap .btn {
  border: none; border-radius: 9px; padding: 10px 18px; font-size: 13px; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
}
.resdex-wrap .btn-primary { background: var(--gradient-primary) !important; color: #fff !important; }
.resdex-wrap .btn-primary:hover { filter: brightness(1.05); color: #fff; }
.resdex-wrap .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }
.resdex-wrap .btn-outline:hover { background:var(--primary); color:#FFF;}
.resdex-wrap .btn-icon { padding: 9px 12px; }
.resdex-wrap .btn-block { width: 100%; justify-content: center; }
.resdex-wrap .btn-sm { padding: 6px 12px; font-size: 12px; }
.resdex-wrap .btn:disabled { opacity: 0.6; cursor: not-allowed; }

.link-action {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 13px; font-weight: 600; color: var(--primary) !important;
  text-decoration: none;
}
.link-action:hover { text-decoration: underline; }

.results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.results-count { font-size: 14px; font-weight: 600; }
.results-count span { color: var(--primary) !important; }

.select-all-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--muted-foreground);
  cursor: pointer;
  user-select: none;
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
.bulk-action-bar select {
  border: 1px solid var(--border); border-radius: 8px; font-size: 12.5px; padding: 8px 12px;
  background: var(--background); color: var(--foreground); min-width: 180px;
}
.bulk-clear-btn {
  background: none; border: none; color: var(--muted-foreground); font-size: 12.5px;
  font-weight: 600; cursor: pointer; text-decoration: underline; white-space: nowrap;
}

.candidate-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

/* stack to 1 column on small screens */
@media (max-width: 768px) {
  .candidate-grid {
    grid-template-columns: 1fr;
  }
}

.candidate-card {
  display: flex;
  gap: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 16px;
  background: #fff;
}
.candidate-select-wrap {
  display: flex;
  align-items: flex-start;
  padding-top: 2px;
  flex-shrink: 0;
}
.candidate-select-wrap input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--primary);
  cursor: pointer;
}
.candidate-avatar {
  width: 48px; height: 48px; border-radius: 50%; background: var(--gradient-primary) !important;
  display: flex; align-items: center; justify-content: center; color: #fff;
  font-weight: 700; font-size: 18px; flex-shrink: 0;
}
.candidate-main { flex: 1; min-width: 0; }
.candidate-name { font-size: 15.5px; font-weight: 700; margin: 0; }
.candidate-headline { font-size: 13px; color: var(--muted-foreground); margin: 2px 0 8px; }
.candidate-meta { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12.5px; color: var(--muted-foreground); margin-bottom: 10px; }
.candidate-meta span { display: flex; align-items: center; gap: 5px; }
.candidate-skills { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.skill-chip {
  background: rgba(31,183,181,0.1); color: var(--primary) !important; border: 1px solid rgba(31,183,181,0.18);
  border-radius: 999px; padding: 3px 10px; font-size: 11.5px; font-weight: 600;
}
.candidate-actions { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
.action-divider { width: 1px; height: 18px; background: var(--border); }

.folder-save-group { display: flex; align-items: center; gap: 8px; }
.folder-save-group select {
  border: 1px solid var(--border); border-radius: 8px; font-size: 12.5px; padding: 7px 10px;
  background: var(--background); color: var(--foreground);
}

.empty-state { text-align: center; padding: 60px 20px; color: var(--muted-foreground); }
.empty-state i { font-size: 40px; color: var(--border); margin-bottom: 14px; display: block; }

.pagination-row { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
.pagination-row a {
  min-width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
  border: 1.5px solid rgba(31,183,181,0.38); border-radius: 6px; color: var(--primary) !important;
  font-size: 13px; font-weight: 700; text-decoration: none;
}
.pagination-row a.active { background: var(--primary) !important; border-color: var(--primary); color: #fff !important; }

.recent-item { padding: 10px 0; border-bottom: 1px solid var(--border); }
.recent-item:last-child { border-bottom: none; }
.recent-item-title { font-size: 13px; font-weight: 600; margin: 0 0 4px; }
.recent-item-links { display: flex; gap: 12px; }
.recent-item-links a { font-size: 12px; font-weight: 600; color: var(--primary) !important; text-decoration: none; }
.recent-item-links a:hover { text-decoration: underline; }

.folder-select-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.folder-select-wrap select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 7px 32px 7px 12px;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--foreground);
  cursor: pointer;
  min-width: 150px;
  transition: border-color 0.15s ease;
}

.folder-select-wrap select:hover {
  border-color: var(--primary);
}

.folder-select-wrap select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(31,183,181,0.15);
}

.folder-select-wrap::after {
  content: '';
  position: absolute;
  right: 12px;
  top: 50%;
  width: 8px;
  height: 8px;
  border-right: 2px solid var(--muted-foreground);
  border-bottom: 2px solid var(--muted-foreground);
  transform: translateY(-65%) rotate(45deg);
  pointer-events: none;
}
body.dark .candidate-card{
  background-color:#000;
  border: 1px solid #23343A;
}
body.dark h2.card-title{
  color:var(--primary);
}
body.dark h3.candidate-name{
  color:var(--primary);
}
body.dark .bulk-action-bar {
  background: #000;
}
/* Fix flash/alert bar spacing - targets common CI4/Bootstrap alert markup */
.hm-inline-notice-region,.alert,.hm-inline-notice.is-success,.hm-inline-notice.is-error
.alert-success,
.alert-danger {
  margin: 16px 40px 20px !important;
  border-radius: 10px !important;
}

@media (max-width: 1600px) {
  .hm-inline-notice-region,.alert,.hm-inline-notice.is-success,.hm-inline-notice.is-error
  .alert-success,
  .alert-danger {
    margin: 16px 32px 20px !important;
  }
}
.alert-danger {
    background: #FEE2E2 !important;
    border: 1px solid #FCA5A5 !important;
    color: #991B1B !important;
    border-radius: 8px;
    font-size: 1rem;
}
body.dark .alert-danger {
    background: #7F1D1D30 !important;
    border-color: #7F1D1D60 !important;
    color: #FCA5A5 !important;
} 
</style>

<div class="resdex-wrap resdex-jobboard">
  <div class="resdex-shell">
  <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Search Resumes</h1>
            <p class="page-board-subtitle">Search the candidate database by keyword, skills, experience, location, and more.</p>
        </div>
    

    <div class="resdex-topnav">
      <a href="<?= site_url('recruiter/resdex/saved-searches') ?>" class="btn btn-outline">
        <i class="fas fa-bookmark"></i> Manage Searches
      </a>
      <a href="<?= site_url('recruiter/resdex/folders') ?>" class="btn btn-outline ">
        <i class="fas fa-folder"></i> My Folders
      </a>
    </div></div>

    <div class="resdex-layout">

      <!-- ============ FILTER PANEL ============ -->
      <div>
        <form method="get" action="<?= site_url('recruiter/resdex') ?>">
          <input type="hidden" name="search" value="1">

          <div class="card">
            <div class="kw-toolbar">
              <h2 class="card-title" style="margin-bottom:0;"><i class="fas fa-key" style="color:var(--primary)"></i> Keywords</h2>
              <label class="kw-toggle">
                <input type="checkbox" name="boolean_on" value="1" <?= !empty($filters['boolean_on']) ? 'checked' : '' ?>>
                Boolean search
              </label>
            </div>

            <div class="field">
              <input type="text" name="keywords" placeholder="e.g. React, Laravel, AWS — or Java AND Spring NOT PHP"
                     value="<?= esc($filters['keywords'] ?? '') ?>">
            </div>

            <label class="check-row">
              <input type="checkbox" name="mandatory" value="1" <?= !empty($filters['mandatory']) ? 'checked' : '' ?>>
              Mark all keywords as mandatory
            </label>

            <button type="button" class="collapsible-toggle" onclick="document.getElementById('excludeBox').classList.toggle('open')">
              <i class="fas fa-plus"></i> Add Exclude Keywords
            </button>
            <div class="collapsible-body <?= !empty($filters['keyword_exclude']) ? 'open' : '' ?>" id="excludeBox">
              <div class="field">
                <input type="text" name="keyword_exclude" placeholder="Comma separated"
                       value="<?= esc($filters['keyword_exclude'] ?? '') ?>">
              </div>
            </div>

            <button type="button" class="collapsible-toggle" onclick="document.getElementById('itSkillsBox').classList.toggle('open')">
              <i class="fas fa-plus"></i> Add IT Skills
            </button>
            <div class="collapsible-body <?= !empty($filters['it_skills']) ? 'open' : '' ?>" id="itSkillsBox">
              <div class="field">
                <input type="text" name="it_skills" placeholder="Comma separated, e.g. MySQL, Docker"
                       value="<?= esc($filters['it_skills'] ?? '') ?>">
              </div>
            </div>
          </div>

          <div class="card">
            <h2 class="card-title"><i class="fas fa-sliders-h" style="color:var(--primary)"></i> Filters</h2>

            <div class="field">
              <label>Location</label>
              <input type="text" name="location" placeholder="City or region"
                     value="<?= esc($filters['location'] ?? '') ?>">
            </div>

            <div class="field">
              <label>Experience (years)</label>
              <div class="range-row">
                <input type="number" name="exp_min" min="0" placeholder="Min"
                       value="<?= esc($filters['exp_min'] ?? '') ?>">
                <input type="number" name="exp_max" min="0" placeholder="Max"
                       value="<?= esc($filters['exp_max'] ?? '') ?>">
              </div>
            </div>

            <div class="field">
              <label>Expected Salary</label>
              <div class="range-row">
                <input type="number" name="salary_min" min="0" placeholder="Min"
                       value="<?= esc($filters['salary_min'] ?? '') ?>">
                <input type="number" name="salary_max" min="0" placeholder="Max"
                       value="<?= esc($filters['salary_max'] ?? '') ?>">
              </div>
            </div>

            <div class="field">
              <label>Notice Period</label>
              <select name="notice_period">
                <option value="">Any</option>
                <?php foreach (['Immediate','15 Days','30 Days','60 Days','90 Days'] as $np): ?>
                  <option value="<?= esc($np) ?>" <?= ($filters['notice_period'] ?? '') === $np ? 'selected' : '' ?>><?= esc($np) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Employment Type</label>
              <select name="employment_type">
                <option value="">Any</option>
                <?php foreach (['Full-time','Part-time','Contract','Internship'] as $et): ?>
                  <option value="<?= esc($et) ?>" <?= ($filters['employment_type'] ?? '') === $et ? 'selected' : '' ?>><?= esc($et) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Education</label>
              <input type="text" name="education" placeholder="e.g. B.Tech, MBA"
                     value="<?= esc($filters['education'] ?? '') ?>">
            </div>

            <div class="field">
              <label>Gender</label>
              <select name="gender">
                <option value="">Any</option>
                <?php foreach (['Male','Female','Other'] as $g): ?>
                  <option value="<?= esc($g) ?>" <?= ($filters['gender'] ?? '') === $g ? 'selected' : '' ?>><?= esc($g) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label>Must-have Skills (comma separated)</label>
              <input type="text" name="must_have_skills" placeholder="e.g. Python, SQL"
                     value="<?= esc(implode(',', $filters['must_have_skills'] ?? [])) ?>">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-search"></i> Search Resumes
          </button>
        </form>

        <br/>

      </div>

      <!-- ============ RESULTS ============ -->
      <div>
        <?php if (empty($results) || (int) $results['total'] === 0): ?>
          <div class="card empty-state">
            <i class="fas fa-user-slash"></i>
            <p>No candidates found. Try adjusting your filters.</p>
          </div>
        <?php else: ?> 
       
          <div class="results-header">
            <div class="results-count"><span><?= (int) $results['total'] ?></span> candidates found</div>
            <label class="select-all-row" for="selectAllCandidates">
              <input type="checkbox" id="selectAllCandidates">
              Select all on this page
            </label>
          </div>
 <?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success" role="alert">
    <?= esc(session()->getFlashdata('success')) ?>
  </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger" role="alert">
    <?= esc(session()->getFlashdata('error')) ?>
  </div>
<?php endif; ?>
          <!-- Bulk action bar: hidden until at least one candidate is checked -->
          <div class="bulk-action-bar" id="bulkActionBar">
            <div class="bulk-count"><span id="bulkSelectedCount">0</span> selected</div>
            <span class="folder-select-wrap">
              <select id="bulkFolderSelect">
                <option value="">Select Folder</option>
                <?php foreach ($folders as $folder): ?>
                  <option value="<?= (int) $folder['id'] ?>"><?= esc($folder['folder_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </span>
            <button type="button" class="btn btn-primary btn-sm" id="bulkSaveBtn">
              <i class="fas fa-folder-plus"></i> Save to Folder
            </button>
            <div class="spacer"></div>
            <button type="button" class="bulk-clear-btn" id="bulkClearBtn">Clear selection</button>
          </div>

          <!-- Hidden form used by the bulk "Save to Folder" action -->
          <form id="bulkFolderForm" method="post" action="<?= site_url('recruiter/resdex/folder/bulk-add') ?>">
              <?= csrf_field() ?>
          </form>

          <!-- Per-candidate hidden forms:
               1) folderForm_<id>   -> "Save" to a folder (unchanged, already per-candidate)
               2) saveSearchForm_<id> -> toggles the bookmark for THIS candidate card only.
                  It carries the current search filters PLUS candidate_id/candidate_name,
                  so each card's save is independent and doesn't affect the others. -->
          <?php foreach ($results['results'] as $candidate): ?>
              <form id="folderForm_<?= (int) $candidate['user_id'] ?>" method="post"
                    action="<?= site_url('recruiter/resdex/folder/add') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="candidate_id" value="<?= (int) $candidate['user_id'] ?>">
              </form>

              <form id="saveSearchForm_<?= (int) $candidate['user_id'] ?>" method="post"
                    action="<?= site_url('recruiter/resdex/save-search') ?>">
                  <?= csrf_field() ?>
                  <?php foreach ($filters as $key => $val): ?>
                      <?php if (is_array($val)): ?>
                          <input type="hidden" name="<?= esc($key) ?>" value="<?= esc(implode(',', $val)) ?>">
                      <?php elseif (is_bool($val)): ?>
                          <input type="hidden" name="<?= esc($key) ?>" value="<?= $val ? '1' : '0' ?>">
                      <?php else: ?>
                          <input type="hidden" name="<?= esc($key) ?>" value="<?= esc((string) $val) ?>">
                      <?php endif; ?>
                  <?php endforeach; ?>
                  <input type="hidden" name="candidate_id" value="<?= (int) $candidate['user_id'] ?>">
                  <input type="hidden" name="candidate_name" value="<?= esc($candidate['name'] ?? '') ?>">
              </form>
          <?php endforeach; ?>

          <div class="candidate-grid">
          <?php foreach ($results['results'] as $candidate): ?>
            <?php $isCardSaved = !empty($candidate['is_search_saved']); ?>
            <div class="candidate-card">
              <div class="candidate-select-wrap">
                <input type="checkbox" class="candidate-select" value="<?= (int) $candidate['user_id'] ?>">
              </div>
              <div class="candidate-avatar"><?= strtoupper(substr($candidate['name'] ?? 'C', 0, 1)) ?></div>
              <div class="candidate-main">
                <h3 class="candidate-name"><?= esc($candidate['name'] ?? 'Candidate') ?></h3>
                <p class="candidate-headline"><?= esc($candidate['headline'] ?? 'No headline provided') ?>   </p>
                <div class="candidate-meta">
                  <?php if (!empty($candidate['location'])): ?>
                    <span><i class="fas fa-map-marker-alt"></i> <?= esc($candidate['location']) ?></span>
                  <?php endif; ?>
                  <span><i class="fas fa-briefcase"></i> <?= round(($candidate['total_experience_months'] ?? 0) / 12, 1) ?> yrs exp</span>
                  <?php if (!empty($candidate['notice_period'])): ?>
                    <span><i class="fas fa-clock"></i> <?= esc($candidate['notice_period']) ?> notice</span>
                  <?php endif; ?>
                  <?php if (!empty($candidate['expected_salary'])): ?>
                    <span><i class="fas fa-wallet"></i> Exp. <?= number_format((float) $candidate['expected_salary']) ?></span>
                  <?php endif; ?>
                </div>

                <?php if (!empty($candidate['key_skills'])): ?>
                  <div class="candidate-skills">
                    <?php foreach (array_slice(array_map('trim', explode(',', $candidate['key_skills'])), 0, 6) as $skill): ?>
                      <?php if ($skill !== ''): ?><span class="skill-chip"><?= esc($skill) ?></span><?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <div class="candidate-actions">
                  <a href="<?= site_url('recruiter/candidate/' . $candidate['user_id'] . '/view-contact') ?>" class="link-action"  style="text-decoration:none;">
                    <i class="fas fa-eye"></i> 
                  </a>

                  
                    <span class="action-divider"></span>
                    
                    <a href="<?= site_url('recruiter/candidate/' . $candidate['user_id'] . '/download-resume') ?>" class="link-action">
                     <i class="fas fa-download"></i>    
                    </a> 
    
                  <span class="action-divider"></span>

               <span class="folder-select-wrap">
  <select name="folder_id" form="folderForm_<?= (int) $candidate['user_id'] ?>">
      <option value="">Select Folder</option>
      <?php foreach ($folders as $folder): ?>
          <option value="<?= (int) $folder['id'] ?>"><?= esc($folder['folder_name']) ?></option>
      <?php endforeach; ?>
  </select>
</span>
                  <button type="submit" form="folderForm_<?= (int) $candidate['user_id'] ?>" class="btn btn-primary btn-sm">
                      Save 
                  </button>

                  <span class="action-divider"></span>

                  <!-- Per-candidate Save Search button. Tied to its own hidden form
                       (saveSearchForm_<id>) via data-form, so clicking it only
                       toggles THIS card — not every card on the page. -->
                  <button type="button"
                          class="btn btn-sm save-search-btn <?= $isCardSaved ? 'btn-primary' : 'btn-outline' ?>"
                          data-saved="<?= $isCardSaved ? 'true' : 'false' ?>"
                          data-form="saveSearchForm_<?= (int) $candidate['user_id'] ?>"
                          title="<?= $isCardSaved ? 'Remove this search from Saved Searches' : 'Save this search' ?>">
                      <i class="<?= $isCardSaved ? 'fas' : 'far' ?> fa-bookmark"></i>
                      
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?></div>

          <?php if ($results['total_pages'] > 1): ?>
            <div class="pagination-row">
              <?php for ($p = 1; $p <= $results['total_pages']; $p++): ?>
                <?php
                  $query = $filters;
                  $query['search'] = 1;
                  $query['page'] = $p;
                  $query['must_have_skills'] = implode(',', $query['must_have_skills'] ?? []);
                ?>
                <a href="<?= site_url('recruiter/resdex') . '?' . http_build_query($query) ?>"
                   class="<?= $p === $results['page'] ? 'active' : '' ?>"><?= $p ?></a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script>
/* ============ PER-CANDIDATE SAVE SEARCH TOGGLE ============
   Each .save-search-btn owns exactly one hidden form (via data-form).
   Clicking a button only reads/submits ITS OWN form and only updates
   ITS OWN icon/state — other cards on the page are untouched. */
document.addEventListener('DOMContentLoaded', function () {
  const buttons = document.querySelectorAll('.save-search-btn');
  if (!buttons.length) return;

  function updateButton(btn, isSaved) {
    const icon = btn.querySelector('i');

    btn.dataset.saved = isSaved ? '1' : '0';
    btn.classList.toggle('btn-primary', isSaved);
    btn.classList.toggle('btn-outline', !isSaved);
    btn.title = isSaved ? 'Remove this search from Saved Searches' : 'Save this search';

    if (icon) {
      icon.classList.toggle('fas', isSaved);
      icon.classList.toggle('far', !isSaved);
    }
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', async function () {
      const formId = btn.dataset.form;
      const form = formId ? document.getElementById(formId) : null;

      if (!form) {
        alert('Could not find this candidate\'s save form.');
        return;
      }

      btn.disabled = true;

      try {
        const formData = new FormData(form);

        const response = await fetch(form.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Something went wrong.');
        }

        updateButton(btn, data.is_saved);

      } catch (err) {
        alert(err.message || 'Failed to update saved search.');
      } finally {
        btn.disabled = false;
      }
    });
  });
});

/* ============ BULK SELECT + BULK SAVE TO FOLDER ============ */
document.addEventListener('DOMContentLoaded', function () {
  const checkboxes  = document.querySelectorAll('.candidate-select');
  const selectAll   = document.getElementById('selectAllCandidates');
  const bar         = document.getElementById('bulkActionBar');
  const countEl     = document.getElementById('bulkSelectedCount');
  const folderSelect = document.getElementById('bulkFolderSelect');
  const saveBtn     = document.getElementById('bulkSaveBtn');
  const clearBtn    = document.getElementById('bulkClearBtn');
  const bulkForm    = document.getElementById('bulkFolderForm');

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

  if (saveBtn) {
    saveBtn.addEventListener('click', async function () {
      const selected = getSelected().map(function (cb) { return cb.value; });
      const folderId = folderSelect.value;

      if (!selected.length) {
        alert('Select at least one candidate.');
        return;
      }
      if (!folderId) {
        alert('Choose a folder first.');
        return;
      }

      saveBtn.disabled = true;
      const originalHtml = saveBtn.innerHTML;
      saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

      try {
        const formData = new FormData(bulkForm);
        formData.append('folder_id', folderId);
        selected.forEach(function (id) { formData.append('candidate_ids[]', id); });

        const response = await fetch(bulkForm.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Something went wrong.');
        }

        alert(data.message || (selected.length + ' candidate(s) saved to folder.'));
        checkboxes.forEach(function (cb) { cb.checked = false; });
        folderSelect.value = '';
        refreshBar();

      } catch (err) {
        alert(err.message || 'Failed to save candidates to folder.');
      } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalHtml;
      }
    });
  }
});
</script>

<?= view('Layouts/recruiter_footer') ?>