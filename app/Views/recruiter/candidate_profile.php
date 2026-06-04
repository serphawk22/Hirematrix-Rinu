<?= view('Layouts/recruiter_header', ['title' => 'Candidate Profile']) ?>

<div class="recruiter-candidate-profile-jobboard">
<div class="container-fluid py-5">
    <?php
    $applicationId = (int) (service('request')->getGet('application_id') ?? 0);
    $jobId = (int) (service('request')->getGet('job_id') ?? 0);
    $showContact = (int) (service('request')->getGet('show_contact') ?? 0) === 1;
    $contactViewUrl = base_url('recruiter/candidate/' . $candidate['id'] . '/view-contact')
        . '?application_id=' . $applicationId
        . '&job_id=' . $jobId;
    $resumeUrl = base_url('recruiter/candidate/' . $candidate['id'] . '/download-resume');
    $resumeUrl .= '?application_id=' . $applicationId . '&job_id=' . $jobId;
    $introVideoPath = trim((string) ($candidate['intro_video_path'] ?? ''));
    $introVideoUrl = $introVideoPath !== ''
        ? (preg_match('/^https?:\/\//i', $introVideoPath) ? $introVideoPath : base_url($introVideoPath))
        : '';
    $messages = $messages ?? [];
    $recruiterNote = $recruiterNote ?? null;
    $interests = $interests ?? [];
    $projects = $projects ?? [];
    $recruiterJobs = $recruiterJobs ?? [];
    $jobInvitations = $jobInvitations ?? [];
    $applicationContext = is_array($applicationContext ?? null) ? $applicationContext : null;

    $formatExperienceDisplay = static function (int $months): string {
        if ($months <= 0) {
            return '0 years';
        }
        $years = floor($months / 12);
        $remainingMonths = $months % 12;
        $parts = [];
        if ($years > 0) { $parts[] = $years . ' year' . ($years === 1 ? '' : 's'); }
        if ($remainingMonths > 0) { $parts[] = $remainingMonths . ' month' . ($remainingMonths === 1 ? '' : 's'); }
        return implode(' ', $parts);
    };
    ?>
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-id-card"></i> Candidate profile</span>
            <h1 class="page-board-title"><?= esc($candidate['name']) ?></h1>
            <p class="page-board-subtitle">
                Review candidate details, notes, skills, and history before moving forward.
            </p>
        </div>
        <div class="page-board-actions candidate-profile-actions">
            <?php if (!$showContact): ?>
                <a href="<?= $contactViewUrl ?>" class="btn btn-outline-primary candidate-contact-btn">
                    <i class="fas fa-address-card"></i> View Contact
                </a>
            <?php endif; ?>
            <?php if ($candidate['resume_path']): ?>
                <a href="<?= $resumeUrl ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download Resume
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <div class="candidate-profile-shell">
        <div class="candidate-profile-side">
            <div class="card shadow-sm candidate-summary-card candidate-profile-rail-card">
                <div class="card-body text-center">
                    <?php
                    $candidatePhotoPath = trim((string) ($candidate['profile_photo'] ?? ''));
                    $candidatePhotoUrl = $candidatePhotoPath !== ''
                        ? (preg_match('/^https?:\/\//i', $candidatePhotoPath) ? $candidatePhotoPath : base_url($candidatePhotoPath))
                        : '';
                    ?>
                    <?php if ($candidatePhotoUrl !== ''): ?>
                        <img src="<?= esc($candidatePhotoUrl) ?>" alt="Profile" class="candidate-summary-avatar">
                    <?php else: ?>
                        <div class="candidate-summary-fallback"></div>
                    <?php endif; ?>
                    <h4 class="candidate-name"><?= esc($candidate['name']) ?></h4>
                    <div class="candidate-meta">
                        <?php if ($showContact): ?>
                            <p><i class="fas fa-envelope"></i> <?= esc($candidate['email']) ?></p>
                            <?php if ($candidate['phone']): ?>
                                <p><i class="fas fa-phone"></i> <?= esc($candidate['phone']) ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($candidate['location']): ?>
                            <p><i class="fas fa-map-marker-alt"></i> <?= esc($candidate['location']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if($candidate['bio']): ?>
            <div class="card shadow-sm mt-3 candidate-profile-rail-card">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle"></i> About</h6>
                    <p><?= nl2br(esc($candidate['bio'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($introVideoUrl !== '' || !empty($candidate['intro_video_pitch']) || !empty($candidate['intro_video_target_role'])): ?>
            <div class="card shadow-sm mt-3 candidate-profile-rail-card">
                <div class="card-body">
                    <h6><i class="fas fa-video"></i> Video Introduction</h6>
                    <?php if (!empty($candidate['intro_video_target_role'])): ?>
                        <p class="small text-muted mb-2">Pitching for: <?= esc($candidate['intro_video_target_role']) ?></p>
                    <?php endif; ?>
                    <?php if ($introVideoUrl !== ''): ?>
                        <video controls preload="metadata" style="width: 100%; border-radius: 10px; background: #111827; margin-bottom: 12px;">
                            <source src="<?= esc($introVideoUrl) ?>">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                    <?php if (!empty($candidate['intro_video_pitch'])): ?>
                        <p class="mb-0 small" style="line-height: 1.55; color: #475569;"><?= nl2br(esc($candidate['intro_video_pitch'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mt-3 candidate-profile-rail-card">
                <div class="card-body">
                    <h6><i class="fas fa-paper-plane"></i> Invite to Apply</h6>
                    <p class="small text-muted mb-3">Send a direct invitation for one of your open roles. The candidate gets an in-app alert and an email if their notification settings allow it.</p>
                    <form method="post" action="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/invite-job') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="return_to" value="<?= current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') ?>">
                        <div class="form-group mb-2">
                            <label class="small text-muted">Choose Job</label>
                            <select name="job_id" class="form-control" required>
                                <option value="">Select an open job</option>
                                <?php foreach ($recruiterJobs as $jobOption): ?>
                                    <?php
                                    $jobOptionId = (int) ($jobOption['id'] ?? 0);
                                    $invitation = $jobInvitations[$jobOptionId] ?? null;
                                    $labelSuffix = $invitation ? ' [' . ucfirst((string) ($invitation['status'] ?? 'sent')) . ']' : '';
                                    ?>
                                    <option value="<?= $jobOptionId ?>" <?= $jobId === $jobOptionId ? 'selected' : '' ?>>
                                        <?= esc((string) ($jobOption['title'] ?? 'Untitled role') . $labelSuffix) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label class="small text-muted">Optional note</label>
                            <textarea name="message" class="form-control" rows="4" maxlength="500" placeholder="Add a short personal note about why this role could fit them."></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-paper-plane mr-1"></i> Send Invitation
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-3 candidate-profile-rail-card">
                <div class="card-body">
                    <h6><i class="fas fa-sticky-note"></i> Recruiter Notes & Tags</h6>
                    <?php if (!empty($recruiterNote['tags'])): ?>
                        <div class="mb-2">
                            <?php foreach (explode(',', (string) $recruiterNote['tags']) as $tag): ?>
                                <?php $trimmedTag = trim($tag); ?>
                                <?php if ($trimmedTag !== ''): ?>
                                    <span class="badge badge-light border mr-1 mb-1"><?= esc($trimmedTag) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/save-notes') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <input type="hidden" name="job_id" value="<?= $jobId ?>">
                        <input type="hidden" name="show_contact" value="<?= $showContact ? 1 : 0 ?>">
                        <div class="form-group mb-2">
                            <label class="small text-muted">Tags (comma separated)</label>
                            <input type="text" name="tags" class="form-control" maxlength="255" value="<?= esc($recruiterNote['tags'] ?? '') ?>" placeholder="e.g. Strong communication, Backend, Immediate joiner">
                        </div>
                        <div class="form-group mb-2">
                            <label class="small text-muted">Private Notes</label>
                            <textarea name="notes" class="form-control" rows="4" maxlength="5000" placeholder="Add private notes for this candidate..."><?= esc($recruiterNote['notes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-save mr-1"></i> Save Notes
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-3 candidate-profile-rail-card">
                <div class="card-body">
                    <h6><i class="icon-mail_outline"></i> Message Candidate</h6>
                    <?php if (!empty($messages)): ?>
                        <div class="candidate-profile-stream mb-2">
                            <?php foreach (array_slice($messages, -8) as $msg): ?>
                                <?php $isRecruiterMsg = ($msg['sender_role'] ?? '') === 'recruiter'; ?>
                                <div class="candidate-profile-entry">
                                    <small class="text-muted d-block mb-1">
                                        <?= $isRecruiterMsg ? 'You' : esc($candidate['name']) ?> • <?= date('M d, h:i A', strtotime($msg['created_at'])) ?>
                                    </small>
                                    <div><?= nl2br(esc($msg['message'] ?? '')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('recruiter/candidate/' . $candidate['id'] . '/send-message') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="application_id" value="<?= $applicationId ?>">
                        <input type="hidden" name="job_id" value="<?= $jobId ?>">
                        <input type="hidden" name="show_contact" value="<?= $showContact ? 1 : 0 ?>">
                        <div class="form-group mb-2">
                            <textarea name="message" class="form-control" rows="4" maxlength="1000" placeholder="Write a message to candidate..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary candidate-message-btn">
                            <i class="icon-send mr-1"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="candidate-profile-main">
            <?php if (!empty($applicationContext['questionnaire_items'])): ?>
            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-clipboard-list"></i> Application Questionnaire</h5>
                    <?php if (!empty($applicationContext['job_title'])): ?>
                        <p class="text-muted mb-3">Responses submitted for <?= esc((string) $applicationContext['job_title']) ?>.</p>
                    <?php endif; ?>
                    <div class="candidate-detail-grid mt-3">
                        <?php foreach ((array) $applicationContext['questionnaire_items'] as $item): ?>
                            <div class="candidate-detail-item">
                                <label><?= esc((string) ($item['label'] ?? 'Question')) ?></label>
                                <div style="white-space: pre-wrap;"><?= esc((string) ($item['answer'] ?? '')) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-info-circle"></i> Professional Summary</h5>
                    <div class="row mt-3">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small text-uppercase font-weight-bold d-block">Candidate Type</label>
                            <div class="h6 mb-0">
                                <?php if ($isFresherCandidate): ?>
                                    <span class="badge badge-info">Fresher</span>
                                <?php else: ?>
                                    <span class="badge badge-primary">Experienced</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small text-uppercase font-weight-bold d-block">Total Experience</label>
                            <div class="h6 mb-0"><?= esc($formatExperienceDisplay($totalExperienceMonths)) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-id-card"></i> Personal Information</h5>
                    <div class="candidate-detail-grid mt-3">
                        <div class="candidate-detail-item">
                            <label>Full Name</label>
                            <div><?= esc($candidate['name'] ?? '-') ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Email</label>
                            <div><?= $showContact ? esc($candidate['email'] ?? '-') : 'Hidden until contact is viewed' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Phone</label>
                            <div><?= $showContact && !empty($candidate['phone']) ? esc($candidate['phone']) : ($showContact ? '-' : 'Hidden until contact is viewed') ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Location</label>
                            <div class="<?= !empty($candidate['location']) ? '' : 'value-empty' ?>"><?= !empty($candidate['location']) ? esc($candidate['location']) : 'Not provided' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Gender</label>
                            <div class="<?= !empty($candidate['gender']) ? '' : 'value-empty' ?>"><?= !empty($candidate['gender']) ? esc($candidate['gender']) : 'Not provided' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Date of Birth</label>
                            <div class="<?= !empty($candidate['date_of_birth']) ? '' : 'value-empty' ?>"><?= !empty($candidate['date_of_birth']) ? esc($candidate['date_of_birth']) : 'Not provided' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-briefcase"></i> Career Details</h5>
                    <div class="candidate-detail-grid mt-3">
                        <div class="candidate-detail-item">
                            <label>Resume Headline</label>
                            <div class="<?= !empty($candidate['resume_headline']) ? '' : 'value-empty' ?>"><?= !empty($candidate['resume_headline']) ? esc($candidate['resume_headline']) : 'Not provided' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Notice Period</label>
                            <div class="<?= !empty($candidate['notice_period']) ? '' : 'value-empty' ?>"><?= !empty($candidate['notice_period']) ? esc($candidate['notice_period']) : 'Not provided' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Current Salary (LPA)</label>
                            <div class="<?= !empty($candidate['current_salary']) ? '' : 'value-empty' ?>"><?= !empty($candidate['current_salary']) ? esc($candidate['current_salary']) : 'Not provided' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-sliders-h"></i> Preferences</h5>
                    <div class="candidate-detail-grid mt-3">
                        <div class="candidate-detail-item">
                            <label>Preferred Job Titles</label>
                            <div class="<?= !empty($candidate['preferred_job_titles']) ? '' : 'value-empty' ?>"><?= !empty($candidate['preferred_job_titles']) ? esc($candidate['preferred_job_titles']) : 'Not provided' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Preferred Locations</label>
                            <div class="<?= !empty($candidate['preferred_locations']) ? '' : 'value-empty' ?>"><?= !empty($candidate['preferred_locations']) ? esc($candidate['preferred_locations']) : 'Not provided' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Preferred Employment Type</label>
                            <div class="<?= !empty($candidate['preferred_employment_type']) ? '' : 'value-empty' ?>"><?= !empty($candidate['preferred_employment_type']) ? esc($candidate['preferred_employment_type']) : 'Not provided' ?></div>
                        </div>
                        <div class="candidate-detail-item">
                            <label>Expected Salary (LPA)</label>
                            <div class="<?= !empty($candidate['expected_salary']) ? '' : 'value-empty' ?>"><?= !empty($candidate['expected_salary']) ? esc($candidate['expected_salary']) : 'Not provided' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills -->
            <?php if (!empty($skills['skill_name']) || !empty($github['languages_used'])): ?>
            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-code"></i> Skills & Technologies</h5>
                    <?php if (!empty($skills['skill_name'])): ?>
                        <h6 class="mt-3">Resume Skills</h6>
                        <div>
                            <?php foreach(explode(',', $skills['skill_name']) as $skill): ?>
                                <span class="candidate-profile-pill candidate-profile-pill-blue"><?= esc(trim($skill)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($github['languages_used'])): ?>
                        <h6 class="mt-3">GitHub Languages</h6>
                        <div>
                            <?php foreach(explode(',', $github['languages_used']) as $lang): ?>
                                <span class="candidate-profile-pill candidate-profile-pill-orange"><?= esc(trim($lang)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($interests)): ?>
            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-heart"></i> Job Interests</h5>
                    <div class="mt-3">
                        <?php foreach ($interests as $interest): ?>
                            <span class="candidate-profile-pill candidate-profile-pill-sky"><?= esc($interest) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Work Experience -->
            <?php if (!empty($workExperiences)): ?>
            <div class="card shadow-sm mb-3 candidate-profile-section">
                <div class="card-body">
                    <h5><i class="fas fa-briefcase"></i> Work Experience</h5>
                    <?php foreach($workExperiences as $exp): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="mb-1"><?= esc($exp['job_title']) ?></h6>
                        <p class="mb-1 text-muted"><i class="fas fa-building"></i> <?= esc($exp['company_name']) ?> • <?= esc($exp['employment_type']) ?></p>
                        <p class="mb-1 text-muted"><i class="fas fa-calendar"></i> <?= date('M Y', strtotime($exp['start_date'])) ?> - <?= $exp['is_current'] ? 'Present' : date('M Y', strtotime($exp['end_date'])) ?></p>
                        <?php if($exp['location']): ?><p class="mb-1 text-muted"><i class="fas fa-map-marker-alt"></i> <?= esc($exp['location']) ?></p><?php endif; ?>
                        <?php if($exp['description']): ?><p class="mt-2"><?= nl2br(esc($exp['description'])) ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($projects)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5><i class="fas fa-diagram-project"></i> Projects</h5>
                    <?php foreach ($projects as $project): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="mb-1"><?= esc($project['project_name']) ?></h6>
                        <?php if (!empty($project['role_name'])): ?>
                            <p class="mb-1 text-muted"><i class="fas fa-user-tie"></i> <?= esc($project['role_name']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($project['tech_stack'])): ?>
                            <p class="mb-1 text-muted"><i class="fas fa-layer-group"></i> <?= esc($project['tech_stack']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($project['project_summary'])): ?>
                            <p class="mt-2 mb-1"><?= nl2br(esc($project['project_summary'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($project['impact_metrics'])): ?>
                            <p class="mb-1 text-muted"><strong>Impact:</strong> <?= esc($project['impact_metrics']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($project['project_url'])): ?>
                            <p class="mb-0"><a href="<?= esc($project['project_url']) ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> View Project</a></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Education -->
            <?php if (!empty($education)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5><i class="fas fa-graduation-cap"></i> Education</h5>
                    <?php foreach($education as $edu): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="mb-1"><?= esc($edu['degree']) ?></h6>
                        <p class="mb-1 text-muted"><i class="fas fa-university"></i> <?= esc($edu['institution']) ?></p>
                        <p class="mb-1 text-muted"><i class="fas fa-book"></i> <?= esc($edu['field_of_study']) ?></p>
                        <p class="mb-1 text-muted"><i class="fas fa-calendar"></i> <?= esc($edu['start_year']) ?> - <?= esc($edu['end_year']) ?></p>
                        <?php if($edu['grade']): ?><p class="mb-1 text-muted"><i class="fas fa-award"></i> Grade: <?= esc($edu['grade']) ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Certifications -->
            <?php if (!empty($certifications)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5><i class="fas fa-certificate"></i> Certifications</h5>
                    <?php foreach($certifications as $cert): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="mb-1"><?= esc($cert['certification_name']) ?></h6>
                        <p class="mb-1 text-muted"><i class="fas fa-building"></i> <?= esc($cert['issuing_organization']) ?></p>
                        <p class="mb-1 text-muted"><i class="fas fa-calendar"></i> Issued: <?= date('M Y', strtotime($cert['issue_date'])) ?><?= $cert['expiry_date'] ? ' • Expires: '.date('M Y', strtotime($cert['expiry_date'])) : '' ?></p>
                        <?php if($cert['credential_id']): ?><p class="mb-1 text-muted"><i class="fas fa-id-card"></i> ID: <?= esc($cert['credential_id']) ?></p><?php endif; ?>
                        <?php if($cert['credential_url']): ?><p class="mb-1"><a href="<?= esc($cert['credential_url']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> View Credential</a></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- GitHub Stats -->
            <?php if (!empty($github['github_username'])): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5><i class="fab fa-github"></i> GitHub Profile</h5>
                    <p><a href="https://github.com/<?= esc($github['github_username']) ?>" target="_blank">@<?= esc($github['github_username']) ?></a></p>
                    <div class="row text-center">
                        <div class="col-4">
                            <strong><?= esc($github['repo_count']) ?></strong><br>
                            <small>Repositories</small>
                        </div>
                        <div class="col-4">
                            <strong><?= esc($github['commit_count']) ?></strong><br>
                            <small>Commits</small>
                        </div>
                        <div class="col-4">
                            <strong><?= esc($github['github_score']) ?>/10</strong><br>
                            <small>Score</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
