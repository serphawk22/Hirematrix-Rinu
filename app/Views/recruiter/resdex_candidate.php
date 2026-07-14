<?= view('Layouts/recruiter_header', ['title' => 'Candidate Profile']) ?>

<style>
.resdex-wrap { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--foreground); width: 100%; }
.resdex-wrap * { box-sizing: border-box; }

.breadcrumb-link { font-size: 12.5px; color: var(--muted-foreground); text-decoration: none; }
.breadcrumb-link:hover { color: var(--primary-dark); }

.profile-layout { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; margin-top: 14px; }
@media (max-width: 991px) { .profile-layout { grid-template-columns: 1fr; } }

.resdex-wrap .card {
  background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 22px; margin-bottom: 18px;
}
.card-title { font-size: 15px; font-weight: 700; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }

.profile-header { display: flex; gap: 18px; align-items: flex-start; }
.profile-avatar {
  width: 66px; height: 66px; border-radius: 50%; background: var(--gradient-primary);
  display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 26px; flex-shrink: 0;
}
.profile-name { font-size: 20px; font-weight: 700; margin: 0; }
.profile-headline { font-size: 14px; color: var(--muted-foreground); margin: 4px 0 10px; }
.profile-meta { display: flex; flex-wrap: wrap; gap: 16px; font-size: 13px; color: var(--muted-foreground); }
.profile-meta span { display: flex; align-items: center; gap: 6px; }

.skill-chip {
  background: rgba(31,183,181,0.1); color: var(--primary-dark); border: 1px solid rgba(31,183,181,0.18);
  border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 600; display: inline-block; margin: 0 6px 6px 0;
}

.timeline-item { border-left: 2px solid var(--border); padding: 0 0 16px 16px; margin-left: 6px; position: relative; }
.timeline-item::before {
  content: ''; position: absolute; left: -6px; top: 2px; width: 10px; height: 10px; border-radius: 50%; background: var(--primary);
}
.timeline-item:last-child { padding-bottom: 0; }
.timeline-title { font-size: 14px; font-weight: 700; margin: 0; }
.timeline-sub { font-size: 12.5px; color: var(--muted-foreground); margin: 2px 0 6px; }
.timeline-desc { font-size: 13px; color: var(--foreground); margin: 0; }

.resdex-wrap .btn {
  border: none; border-radius: 9px; padding: 10px 16px; font-size: 13px; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
}
.resdex-wrap .btn-primary { background: var(--gradient-primary); color: #fff; }
.resdex-wrap .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--foreground); }
.resdex-wrap .btn-block { width: 100%; justify-content: center; }

.contact-row { display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 10px; color: var(--foreground); }
.contact-row i { color: var(--primary); width: 16px; }

.empty-inline { font-size: 13px; color: var(--muted-foreground); }
</style>

<div class="resdex-wrap">
  <div class="container-fluid py-4">

    <a href="<?= site_url('recruiter/resdex') ?>" class="breadcrumb-link"><i class="fas fa-arrow-left"></i> Back to Search Resumes</a>

    <div class="profile-layout">

      <div>
        <div class="card">
          <div class="profile-header">
            <div class="profile-avatar"><?= strtoupper(substr($profile['name'] ?? 'C', 0, 1)) ?></div>
            <div>
              <h1 class="profile-name"><?= esc($profile['name'] ?? 'Candidate') ?></h1>
              <p class="profile-headline"><?= esc($profile['headline'] ?? 'No headline provided') ?></p>
              <div class="profile-meta">
                <?php if (!empty($profile['location'])): ?>
                  <span><i class="fas fa-map-marker-alt"></i> <?= esc($profile['location']) ?></span>
                <?php endif; ?>
                <span><i class="fas fa-briefcase"></i> <?= round(($profile['total_experience_months'] ?? 0) / 12, 1) ?> yrs exp</span>
                <?php if (!empty($profile['notice_period'])): ?>
                  <span><i class="fas fa-clock"></i> <?= esc($profile['notice_period']) ?> notice</span>
                <?php endif; ?>
                <?php if (!empty($profile['expected_salary'])): ?>
                  <span><i class="fas fa-wallet"></i> Exp. <?= number_format((float) $profile['expected_salary']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <?php if (!empty($profile['bio'])): ?>
        <div class="card">
          <h2 class="card-title"><i class="fas fa-user" style="color:var(--primary)"></i> Summary</h2>
          <p style="font-size:13.5px; line-height:1.6; margin:0;"><?= nl2br(esc($profile['bio'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="card">
          <h2 class="card-title"><i class="fas fa-star" style="color:var(--primary)"></i> Key Skills</h2>
          <?php if (!empty($profile['skills'])): ?>
            <?php foreach ($profile['skills'] as $skill): ?>
              <span class="skill-chip"><?= esc($skill['skill_name']) ?></span>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="empty-inline">No skills listed.</p>
          <?php endif; ?>
        </div>

        <div class="card">
          <h2 class="card-title"><i class="fas fa-briefcase" style="color:var(--primary)"></i> Work Experience</h2>
          <?php if (!empty($profile['experience'])): ?>
            <?php foreach ($profile['experience'] as $exp): ?>
              <div class="timeline-item">
                <p class="timeline-title"><?= esc($exp['designation'] ?? $exp['job_title'] ?? 'Role') ?> · <?= esc($exp['company_name'] ?? '') ?></p>
                <p class="timeline-sub">
                  <?= !empty($exp['start_date']) ? date('M Y', strtotime($exp['start_date'])) : '' ?> —
                  <?= !empty($exp['end_date']) ? date('M Y', strtotime($exp['end_date'])) : 'Present' ?>
                </p>
                <?php if (!empty($exp['description'])): ?>
                  <p class="timeline-desc"><?= nl2br(esc($exp['description'])) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="empty-inline">No work experience listed.</p>
          <?php endif; ?>
        </div>

        <div class="card">
          <h2 class="card-title"><i class="fas fa-graduation-cap" style="color:var(--primary)"></i> Education</h2>
          <?php if (!empty($profile['education'])): ?>
            <?php foreach ($profile['education'] as $edu): ?>
              <div class="timeline-item">
                <p class="timeline-title"><?= esc($edu['degree'] ?? '') ?> <?= !empty($edu['field_of_study']) ? '— ' . esc($edu['field_of_study']) : '' ?></p>
                <p class="timeline-sub"><?= esc($edu['institution'] ?? '') ?> <?= !empty($edu['end_year']) ? '· ' . esc($edu['end_year']) : '' ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="empty-inline">No education details listed.</p>
          <?php endif; ?>
        </div>

        <?php if (!empty($profile['certifications'])): ?>
        <div class="card">
          <h2 class="card-title"><i class="fas fa-certificate" style="color:var(--primary)"></i> Certifications</h2>
          <?php foreach ($profile['certifications'] as $cert): ?>
            <div class="timeline-item">
              <p class="timeline-title"><?= esc($cert['title'] ?? $cert['name'] ?? 'Certification') ?></p>
              <p class="timeline-sub"><?= esc($cert['issuer'] ?? '') ?> <?= !empty($cert['issue_date']) ? '· ' . date('M Y', strtotime($cert['issue_date'])) : '' ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($profile['projects'])): ?>
        <div class="card">
          <h2 class="card-title"><i class="fas fa-diagram-project" style="color:var(--primary)"></i> Projects</h2>
          <?php foreach ($profile['projects'] as $proj): ?>
            <div class="timeline-item">
              <p class="timeline-title"><?= esc($proj['title'] ?? $proj['project_name'] ?? 'Project') ?></p>
              <?php if (!empty($proj['description'])): ?>
                <p class="timeline-desc"><?= nl2br(esc($proj['description'])) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div>
        <div class="card">
          <h2 class="card-title"><i class="fas fa-address-card" style="color:var(--primary)"></i> Contact</h2>
          <?php if (!empty($profile['email'])): ?>
            <div class="contact-row"><i class="fas fa-envelope"></i> <?= esc($profile['email']) ?></div>
          <?php endif; ?>
          <?php if (!empty($profile['phone'])): ?>
            <div class="contact-row"><i class="fas fa-phone"></i> <?= esc($profile['phone']) ?></div>
          <?php endif; ?>
          <?php if (!empty($profile['resume_path'])): ?>
            <a href="<?= base_url($profile['resume_path']) ?>" target="_blank" class="btn btn-outline btn-block" style="margin-top:8px;">
              <i class="fas fa-file-download"></i> Download Resume
            </a>
          <?php endif; ?>
        </div>

        <div class="card">
          <h2 class="card-title"><i class="fas fa-folder-plus" style="color:var(--primary)"></i> Add to Folder</h2>
          <form method="post" action="<?= site_url('recruiter/resdex/folder/add') ?>">
            <input type="hidden" name="candidate_id" value="<?= (int) $profile['user_id'] ?>">
            <div class="field" style="margin-bottom:10px;">
              <select name="folder_id" style="width:100%; border:1px solid var(--border); border-radius:8px; font-size:13px; padding:9px 12px; background:var(--background); color:var(--foreground);">
                <option value="">Choose folder...</option>
                <?php foreach ($folders as $folder): ?>
                  <option value="<?= (int) $folder['id'] ?>"><?= esc($folder['folder_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field" style="margin-bottom:10px;">
              <input type="text" name="new_folder_name" placeholder="Or create new folder"
                     style="width:100%; border:1px solid var(--border); border-radius:8px; font-size:13px; padding:9px 12px; background:var(--background); color:var(--foreground);">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?= view('Layouts/recruiter_footer') ?>