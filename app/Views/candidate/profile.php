        <?= view('Layouts/candidate_header', ['title' => 'My Profile']) ?>
<?php
$profilePhotoPath = trim((string) ($user['profile_photo'] ?? ''));
$profilePhotoUrl = $profilePhotoPath !== ''
    ? (preg_match('/^https?:\/\//i', $profilePhotoPath) ? $profilePhotoPath : base_url($profilePhotoPath))
    : '';
$introVideoPath = trim((string) ($user['intro_video_path'] ?? ''));
$introVideoUrl = $introVideoPath !== ''
    ? (preg_match('/^https?:\/\//i', $introVideoPath) ? $introVideoPath : base_url($introVideoPath))
    : '';

$profileReadiness = $profileReadiness ?? ['is_ready' => true, 'missing_details' => []];
$totalExperienceMonths = $totalExperienceMonths ?? 0;
$isFresherCandidate = $isFresherCandidate ?? false;

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

<div class="profile-jobboard">
    <div class="container">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-user-circle"></i> Candidate profile</span>
                <h1 class="page-board-title">My Profile</h1>
                <p class="page-board-subtitle">Manage your personal details, career information, resume, and preferences from one place.</p>
            </div>
            <div class="company-profile-actions">
                <a href="<?= base_url('candidate/resume-studio') ?>" class="btn btn-primary">
                    <i class="fas fa-magic mr-1"></i> AI Resume Studio
                </a>
                <a href="<?= base_url('jobs') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-search mr-1"></i> Find Jobs
                </a>
            </div>
        </div>
    </div>

<section class="site-section pt-0 content-wrap">
    <div class="container">
        <?php
        $globalSuccess = session()->getFlashdata('success');
        $globalError = session()->getFlashdata('error');
        ?>
        <div class="dashboard-panel mb-4">
            <div class="panel-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <span class="page-board-kicker mb-1"><i class="fas fa-chart-line"></i> Profile health</span>
                        <h3 class="section-title mb-1">Keep your profile ready for matching jobs</h3>
                        <p class="section-subtitle mb-0">Complete your profile to improve matching accuracy and recruiter visibility.</p>
                    </div>
                    <div class="profile-health-score">
                        <span><?= (int) $completion['percentage'] ?>%</span>
                        <small>complete</small>
                    </div>
                </div>
            </div>
            <div class="panel-body" style="padding-top:0">
                <div class="progress" style="height:8px;border-radius:999px">
                    <div class="progress-bar profile-progress-bar candidate-progress-fill" role="progressbar"
                         style="--candidate-progress:<?= (int) $completion['percentage'] ?>%;border-radius:999px"
                         aria-valuenow="<?= (int) $completion['percentage'] ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>

        <div class="row profile-two-pane">
            <!-- Profile Card -->
            <div class="col-lg-4 mb-4 profile-left-pane">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="profile-avatar mb-3">
                            <?php if ($profilePhotoUrl !== ''): ?>
                                <img src="<?= esc($profilePhotoUrl) ?>" alt="Profile" class="rounded-circle profile-photo" width="120" height="120">
                            <?php else: ?>
                                <div class="rounded-circle mx-auto profile-photo-placeholder"></div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <form method="post" action="<?= base_url('candidate/upload-photo') ?>" enctype="multipart/form-data" class="candidate-inline-form">
                                    <?= csrf_field() ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary profile-action-btn" onclick="document.getElementById('profilePhoto').click()">
                                        <i class="fas fa-camera"></i> Change Photo
                                    </button>
                                    <input type="file" id="profilePhoto" name="profile_photo" accept="image/*" class="candidate-hidden" onchange="this.form.submit()">
                                </form>
                                <?php if (!empty($user['profile_photo'])): ?>
                                    <form method="post" action="<?= base_url('candidate/remove-photo') ?>" class="candidate-inline-form">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger ml-2" onclick="return confirm('Remove profile photo?')">
                                            <i class="fas fa-trash"></i> Remove Photo
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h4><?= esc(session()->get('user_name')) ?></h4>
                        <p class="text-muted">Job Seeker</p>
                        <div class="profile-stats row text-center">
                            <div class="col-4">
                                <strong><?= $stats['applications'] ?></strong><br>
                                <small>Applications</small>
                            </div>
                            <div class="col-4">
                                <strong><?= $stats['interviews'] ?></strong><br>
                                <small>Interviews</small>
                            </div>
                            <div class="col-4">
                                <strong><?= $stats['offers'] ?></strong><br>
                                <small>Offers</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Quick Actions</h6>
                        <div class="profile-quick-actions">
                            <?php if (!empty($user['resume_path'])): ?>
                                <a href="<?= base_url('candidate/download-resume') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i> Download Resume</a>
                                <button class="btn btn-outline-primary btn-sm profile-action-btn" onclick="previewResume()"><i class="fas fa-eye"></i> Preview Profile</button>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm" disabled><i class="fas fa-download"></i> No Resume</button>
                                <button class="btn btn-outline-secondary btn-sm" disabled><i class="fas fa-eye"></i> Upload Resume First</button>
                            <?php endif; ?>
                            <button class="btn btn-outline-info btn-sm" onclick="shareProfile()"><i class="fas fa-share"></i> Share Profile</button>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Quick Links</h6>
                        <div class="list-group list-group-flush">
                            <a href="#personal" class="list-group-item list-group-item-action px-0 py-2">Personal Information</a>
                            <a href="#career-details" class="list-group-item list-group-item-action px-0 py-2">Career Details</a>
                            <a href="#intro-video" class="list-group-item list-group-item-action px-0 py-2">Video Introduction</a>
                            <a href="#preferences" class="list-group-item list-group-item-action px-0 py-2">Preferences</a>
                            <a href="#resume" class="list-group-item list-group-item-action px-0 py-2">Resume</a>
                            <a href="#github" class="list-group-item list-group-item-action px-0 py-2">GitHub</a>
                            <a href="#skills" class="list-group-item list-group-item-action px-0 py-2">Skills</a>
                            <a href="#interests" class="list-group-item list-group-item-action px-0 py-2">Interests</a>
                            <a href="#experience" class="list-group-item list-group-item-action px-0 py-2">Experience</a>
                            <a href="#projects" class="list-group-item list-group-item-action px-0 py-2">Projects</a>
                            <a href="#education" class="list-group-item list-group-item-action px-0 py-2">Education</a>
                            <a href="#certifications" class="list-group-item list-group-item-action px-0 py-2">Certifications</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-8 profile-right-pane">
                <div class="mb-4">
                    <span class="page-board-kicker"><i class="fas fa-id-card"></i> All sections</span>
                    <p class="section-subtitle mb-0">All profile sections are shown below. Click the edit icon on any card to update it.</p>
                </div>
                <?php if (!empty($globalSuccess)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?= esc($globalSuccess) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($globalError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?= esc($globalError) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="profile-section mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Profile Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-tag"></i> Candidate Type
                                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Derived from your work experience or 'I am a Fresher' checkbox in Career Details."></i>
                                    </label>
                                    <div class="profile-readonly-field"><?= $isFresherCandidate ? 'Fresher' : 'Experienced' ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Total Experience</label>
                                    <div class="profile-readonly-field"><?= esc($formatExperienceDisplay($totalExperienceMonths)) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="profileSections">
                    <div class="profile-section mb-4" id="personal">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Personal Information</h5>
                                <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Personal Information">
                                    <span class="profile-edit-toggle-text">&#9998;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (session()->getFlashdata('personal_success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('personal_success') ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="profile-read-view">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <div class="profile-readonly-field<?= empty(session()->get('user_name')) ? ' is-empty' : '' ?>"><?= !empty(session()->get('user_name')) ? esc(session()->get('user_name')) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <div class="profile-readonly-field<?= empty($user['email']) ? ' is-empty' : '' ?>"><?= !empty($user['email']) ? esc($user['email']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone</label>
                                            <div class="profile-readonly-field<?= empty($user['phone']) ? ' is-empty' : '' ?>"><?= !empty($user['phone']) ? esc($user['phone']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Location</label>
                                            <div class="profile-readonly-field<?= empty($user['location']) ? ' is-empty' : '' ?>"><?= !empty($user['location']) ? esc($user['location']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Gender</label>
                                            <div class="profile-readonly-field<?= empty($user['gender']) ? ' is-empty' : '' ?>"><?= !empty($user['gender']) ? esc($user['gender']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Date of Birth</label>
                                            <div class="profile-readonly-field<?= empty($user['date_of_birth']) ? ' is-empty' : '' ?>"><?= !empty($user['date_of_birth']) ? esc($user['date_of_birth']) : 'Not provided' ?></div>
                                        </div>

                                    </div>
                                </div>

                                <form method="post" action="<?= base_url('candidate/update_personal') ?>" class="profile-edit-form">
                                    <?= csrf_field() ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                                            <input type="text" name="name" class="form-control" value="<?= esc(session()->get('user_name')) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                            <input type="email" name="email" class="form-control" value="<?= esc($user['email'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                                            <input type="tel" name="phone" class="form-control" value="<?= esc($user['phone'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Location</label>
                                            <input type="text" name="location" class="form-control" value="<?= esc($user['location'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                                            <select name="gender" class="form-control">
                                                <option value="">Select gender</option>
                                                <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                                <option value="Other" <?= ($user['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                                <option value="Prefer not to say" <?= ($user['gender'] ?? '') === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                                            <input type="date" name="date_of_birth" class="form-control" value="<?= esc($user['date_of_birth'] ?? '') ?>">
                                        </div>

                                    </div>
                                    <div class="profile-edit-actions">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                        <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="career-details">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Career Details</h5>
                                <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Career Details">
                                    <span class="profile-edit-toggle-text">&#9998;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (session()->getFlashdata('career_success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('career_success') ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="profile-read-view">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Resume Headline</label>
                                            <div class="profile-readonly-field<?= empty($user['resume_headline']) ? ' is-empty' : '' ?>"><?= !empty($user['resume_headline']) ? esc($user['resume_headline']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Candidate Type</label>
                                            <div class="profile-readonly-field"><?= (int)($user['is_fresher_candidate'] ?? 0) === 1 ? 'Fresher' : 'Experienced' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Notice Period</label>
                                            <div class="profile-readonly-field<?= empty($user['notice_period']) ? ' is-empty' : '' ?>"><?= !empty($user['notice_period']) ? esc($user['notice_period']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Current Salary (LPA)</label>
                                            <div class="profile-readonly-field<?= empty($user['current_salary']) ? ' is-empty' : '' ?>"><?= !empty($user['current_salary']) ? esc($user['current_salary']) : 'Not provided' ?></div>
                                        </div>
                                    </div>
                                </div>

                                <form method="post" action="<?= base_url('candidate/update-career-details') ?>" class="profile-edit-form">
                                    <?= csrf_field() ?>
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label class="form-label"><i class="fas fa-heading"></i> Resume Headline</label>
                                            <input type="text" name="resume_headline" class="form-control" value="<?= esc($user['resume_headline'] ?? '') ?>" placeholder="e.g. Senior Full Stack Developer with 5+ years experience" maxlength="255">
                                            <small class="text-muted">A one-line professional summary that appears at the top of your profile</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-user-tag"></i> Candidate Type</label>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_fresher_candidate" value="1" id="isFresherCheck" <?= (int)($user['is_fresher_candidate'] ?? 0) === 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="isFresherCheck">
                                                    I am a Fresher
                                                    <small class="text-muted">(Check if you have no prior work experience)</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-clock"></i> Notice Period</label>
                                            <select name="notice_period" class="form-control">
                                                <option value="">Select notice period</option>
                                                <option value="Immediate" <?= ($user['notice_period'] ?? '') === 'Immediate' ? 'selected' : '' ?>>Immediate / 15 days or less</option>
                                                <option value="1 Month" <?= ($user['notice_period'] ?? '') === '1 Month' ? 'selected' : '' ?>>1 Month</option>
                                                <option value="2 Months" <?= ($user['notice_period'] ?? '') === '2 Months' ? 'selected' : '' ?>>2 Months</option>
                                                <option value="3 Months" <?= ($user['notice_period'] ?? '') === '3 Months' ? 'selected' : '' ?>>3 Months</option>
                                                <option value="More than 3 Months" <?= ($user['notice_period'] ?? '') === 'More than 3 Months' ? 'selected' : '' ?>>More than 3 Months</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-rupee-sign"></i> Current Salary (LPA)</label>
                                            <input type="number" name="current_salary" class="form-control" value="<?= esc($user['current_salary'] ?? '') ?>" placeholder="e.g. 8.5" step="0.01" min="0">
                                            <small class="text-muted">Your current annual salary in Lakhs</small>
                                        </div>
                                    </div>
                                    <div class="profile-edit-actions">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Career Details</button>
                                        <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="intro-video">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Video Introduction</h5>
                                <span class="badge badge-light border">Recruiter-facing pitch</span>
                            </div>
                            <div class="card-body">
                                <?php if (session()->getFlashdata('video_success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> <?= esc(session()->getFlashdata('video_success')) ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <p class="text-muted mb-3">Record a short introduction recruiters can watch before shortlisting. Use it to explain your fit for a role, communication style, and confidence beyond resume text.</p>

                                <?php if ($introVideoUrl !== ''): ?>
                                    <video controls preload="metadata" class="profile-intro-video">
                                        <source src="<?= esc($introVideoUrl) ?>">
                                        Your browser does not support the video tag.
                                    </video>
                                    <div class="small text-muted mt-2">Visible to recruiters viewing your profile.</div>
                                <?php else: ?>
                                    <div class="text-muted small mb-3 profile-video-empty">
                                        No intro video uploaded yet.
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <form method="post" action="<?= base_url('candidate/upload-intro-video') ?>" enctype="multipart/form-data" class="mb-2">
                                        <?= csrf_field() ?>
                                        <div class="form-group mb-2">
                                            <label class="small text-muted">Upload MP4, WEBM, or MOV up to 30MB</label>
                                            <input type="file" name="intro_video" class="form-control intro-video-upload-input" accept="video/mp4,video/webm,video/quicktime" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload mr-1"></i> Upload Intro Video</button>
                                    </form>
                                    <?php if ($introVideoUrl !== ''): ?>
                                        <form method="post" action="<?= base_url('candidate/remove-intro-video') ?>" onsubmit="return confirm('Remove your introduction video?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i> Remove Video</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="preferences">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Preferences</h5>
                                <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Preferences">
                                    <span class="profile-edit-toggle-text">&#9998;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (session()->getFlashdata('preferences_success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('preferences_success') ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="profile-read-view">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Preferred Job Titles</label>
                                            <div class="profile-readonly-field<?= empty($user['preferred_job_titles']) ? ' is-empty' : '' ?>"><?= !empty($user['preferred_job_titles']) ? esc($user['preferred_job_titles']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Preferred Locations</label>
                                            <div class="profile-readonly-field<?= empty($user['preferred_locations']) ? ' is-empty' : '' ?>"><?= !empty($user['preferred_locations']) ? esc($user['preferred_locations']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Preferred Employment Type</label>
                                            <div class="profile-readonly-field<?= empty($user['preferred_employment_type']) ? ' is-empty' : '' ?>"><?= !empty($user['preferred_employment_type']) ? esc($user['preferred_employment_type']) : 'Not provided' ?></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Expected Salary (LPA)</label>
                                            <div class="profile-readonly-field<?= empty($user['expected_salary']) ? ' is-empty' : '' ?>"><?= !empty($user['expected_salary']) ? esc($user['expected_salary']) : 'Not provided' ?></div>
                                        </div>
                                    </div>
                                </div>

                                <form method="post" action="<?= base_url('candidate/update-preferences') ?>" class="profile-edit-form">
                                    <?= csrf_field() ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-bullseye"></i> Preferred Job Titles</label>
                                            <input type="text" name="preferred_job_titles" class="form-control" value="<?= esc($user['preferred_job_titles'] ?? '') ?>" placeholder="Backend Developer, PHP Developer, API Engineer">
                                            <small class="text-muted">Used as the main role preference for alerts and recommendations.</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Preferred Locations</label>
                                            <input type="text" name="preferred_locations" class="form-control" value="<?= esc($user['preferred_locations'] ?? '') ?>" placeholder="Bangalore, Mumbai, Remote">
                                            <small class="text-muted">Used for job alerts and preference-based recommendations.</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-briefcase"></i> Preferred Employment Type</label>
                                            <?php $preferredEmploymentType = (string) ($user['preferred_employment_type'] ?? ''); ?>
                                            <select name="preferred_employment_type" class="form-control">
                                                <option value="">Select employment type</option>
                                                <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'] as $employmentTypeOption): ?>
                                                    <option value="<?= esc($employmentTypeOption) ?>" <?= $preferredEmploymentType === $employmentTypeOption ? 'selected' : '' ?>><?= esc($employmentTypeOption) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">This preference is also used by job alerts and recommendation ranking.</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><i class="fas fa-rupee-sign"></i> Expected Salary (LPA)</label>
                                            <input type="number" name="expected_salary" class="form-control" value="<?= esc($user['expected_salary'] ?? '') ?>" placeholder="e.g. 12" step="0.01" min="0">
                                            <small class="text-muted">Used as part of your preference profile and job alert criteria.</small>
                                        </div>
                                    </div>
                                    <div class="profile-edit-actions">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Preferences</button>
                                        <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="resume">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Resume Management</h5>
                                <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Resume">
                                    <span class="profile-edit-toggle-text">&#9998;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (session()->getFlashdata('upload_success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('upload_success') ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="profile-read-view">
                                    <?php if (!empty($user['resume_path'])): ?>
                                        <div class="current-resume mb-4">
                                            <div class="d-flex align-items-center p-3 border rounded ">
                                                <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">Current Resume</h6>
                                                    <small class="text-muted"><?= esc($user['resume_path']) ?></small>
                                                </div>
                                                <div>
                                                    <button class="btn btn-outline-primary btn-sm profile-action-btn me-2" onclick="previewResume()"><i class="fas fa-eye"></i> Preview</button>
                                                    <a href="<?= base_url('candidate/download-resume') ?>" class="btn btn-outline-primary btn-sm profile-action-btn"><i class="fas fa-download"></i> Download</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="profile-readonly-field is-empty mb-4">No resume uploaded</div>
                                    <?php endif; ?>

                                    <div class="border rounded p-4 mt-4">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap candidate-flex-gap-12">
                                            <div>
                                                <h6 class="mb-1">AI Resume Studio</h6>
                                                <p class="text-muted mb-0">Create premium AI resume versions, choose templates, manage job-specific resumes, and export polished PDFs from a dedicated page.</p>
                                            </div>
                                            <a href="<?= base_url('candidate/resume-studio') ?>" class="btn btn-dark btn-sm">
                                                <i class="fas fa-arrow-right"></i> Open Studio
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <form method="post" action="<?= base_url('candidate/resume_upload') ?>" enctype="multipart/form-data" class="profile-edit-form" data-loading-form>
                                    <?= csrf_field() ?>
                                    <div class="upload-area border-2 border-dashed rounded p-4 text-center mb-3 profile-upload-box">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h6><?= !empty($user['resume_path']) ? 'Replace your resume' : 'Upload your resume' ?></h6>
                                        <p class="text-muted mb-3">Choose a new file only when you want to update it</p>
                                        <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx" required>
                                        <small class="text-muted">Supported formats: PDF, DOC, DOCX (Max 5MB)</small>
                                    </div>
                                    <?php if (!empty($user['resume_path'])): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Current file: <?= esc($user['resume_path']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    <div class="profile-edit-actions">
                                        <button type="submit" class="btn btn-primary" data-loading-button>
                                            <span class="btn-submit-text"><i class="fas fa-upload"></i> Update Resume</span>
                                            <span class="btn-loading-state"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="github">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">GitHub Integration</h5>
                                <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit GitHub Integration">
                                    <span class="profile-edit-toggle-text">&#9998;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (session()->getFlashdata('profile_success')): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('profile_success') ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <div class="profile-read-view">
                                    <div class="mb-3">
                                        <label class="form-label">GitHub Username</label>
                                        <div class="profile-readonly-field<?= empty($github['github_username']) ? ' is-empty' : '' ?>">
                                            <?= !empty($github['github_username']) ? 'github.com/' . esc($github['github_username']) : 'Not connected' ?>
                                        </div>
                                    </div>
                                </div>

                                <form method="post" action="<?= base_url('candidate/analyze_github') ?>" data-loading-form class="profile-edit-form">
                                    <?= csrf_field() ?>
                                    <div class="mb-3">
                                        <label class="form-label">GitHub Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text">github.com/</span>
                                            <input type="text" name="github_username" class="form-control" value="<?= esc($github['github_username'] ?? '') ?>" placeholder="your-username">
                                        </div>
                                        <small class="text-muted">We'll analyze your repositories to extract skills automatically</small>
                                    </div>
                                    <div class="profile-edit-actions">
                                        <button type="submit" class="btn btn-primary" data-loading-button>
                                            <span class="btn-submit-text">
                                                <i class="fas fa-sync"></i> Analyze GitHub
                                            </span>
                                            <span class="btn-loading-state" aria-hidden="true">
                                                <i class="fas fa-spinner fa-spin"></i> Analyzing...
                                            </span>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Cancel</button>
                                    </div>
                                </form>

                                <?php if (!empty($github['github_username'])): ?>
                                    <div class="github-stats mt-4">
                                        <h6>GitHub Stats</h6>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="stat-card p-3 border rounded">
                                                    <strong><?= esc($github['repo_count'] ?? 0) ?></strong><br>
                                                    <small>Repositories</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stat-card p-3 border rounded">
                                                    <strong><?= esc($github['commit_count'] ?? 0) ?></strong><br>
                                                    <small>Commits</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stat-card p-3 border rounded">
                                                    <strong><?= count(explode(',', $github['languages_used'] ?? '')) ?></strong><br>
                                                    <small>Languages</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <h6>Languages Used</h6>
                                            <div class="languages-list">
                                                <?php 
                                                $languages = explode(',', $github['languages_used'] ?? '');
                                                foreach($languages as $lang): 
                                                    if(trim($lang)): 
                                                ?>
                                                    <span class="badge bg-info me-1 mb-1"><?= esc(trim($lang)) ?></span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="skills">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Skills & Technologies</h5>
                                <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Skills">
                                    <span class="profile-edit-toggle-text">&#9998;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($skills['skill_name'])): ?>
                                    <div class="skills-section mb-4">
                                        <h6><i class="fas fa-laptop-code"></i> Extracted Skills</h6>
                                        <div class="skills-tags">
                                            <?php 
                                            $skillList = explode(',', $skills['skill_name']);
                                            foreach($skillList as $skill): 
                                                $trimmedSkill = trim($skill);
                                                if($trimmedSkill): 
                                            ?>
                                                <span class="profile-chip profile-chip-blue me-2 mb-2"><?= esc($trimmedSkill) ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                        <small class="text-muted">Skills extracted from your resume</small>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($github['languages_used'])): ?>
                                    <div class="skills-section mb-4">
                                        <h6>GitHub Languages</h6>
                                        <div class="skills-tags">
                                            <?php 
                                            $languages = explode(',', $github['languages_used']);
                                            foreach($languages as $lang): 
                                                $trimmedLang = trim($lang);
                                                if($trimmedLang): 
                                            ?>
                                                <span class="profile-chip profile-chip-blue me-2 mb-2"><?= esc($trimmedLang) ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                        <small class="text-muted">Languages from your GitHub repositories</small>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($skills['skill_name']) && empty($github['languages_used'])): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-code fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No Skills Found</h6>
                                        <p class="text-muted">Upload your resume or connect GitHub to extract skills automatically</p>
                                        <div class="mt-3">
                                            <button class="btn btn-primary me-2" type="button" onclick="document.getElementById('resume').scrollIntoView({ behavior: 'smooth', block: 'start' })">Upload Resume</button>
                                            <button class="btn btn-primary" type="button" onclick="document.getElementById('github').scrollIntoView({ behavior: 'smooth', block: 'start' })">Connect GitHub</button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="skills-section mt-4 profile-edit-controls">
                                    <h6><i class="fas fa-plus-circle"></i> Add Custom Skills</h6>
                                    <form method="post" action="<?= base_url('candidate/add-skill') ?>" class="d-flex">
                                        <?= csrf_field() ?>
                                        <input type="text" name="skill_name" class="form-control me-2" placeholder="Enter skill name" required>
                                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Add</button>
                                    </form>
                                </div>
                                <div class="profile-edit-actions">
                                    <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="interests">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Job Interests</h5>
                                <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Interests">
                                    <span class="profile-edit-toggle-text">&#9998;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    <i class="fas fa-info-circle"></i>
                                    Add job categories, roles, or technologies you're interested in.
                                    These are used to personalise your <strong>For You</strong> job recommendations.
                                </p>

                                <!-- Current interests -->
                                <?php if (!empty($interests)): ?>
                                    <div class="mb-4">
                                        <h6><i class="fas fa-tags"></i> Your Interests</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($interests as $interest): ?>
                                                <span class="profile-chip profile-chip-sky d-inline-flex align-items-center gap-1 px-3 py-2 profile-chip-compact">
                                                    <?= esc($interest) ?>
                                                    <span class="profile-edit-controls">
                                                        <a href="<?= base_url('candidate/delete-interest/' . urlencode($interest)) ?>"
                                                           onclick="return confirm('Remove this interest?')"
                                                           class="text-white ms-1 profile-chip-remove"
                                                           title="Remove">
                                                            &times;
                                                        </a>
                                                    </span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-3 mb-4">
                                        <i class="fas fa-heart fa-3x text-muted mb-2"></i>
                                        <p class="text-muted">No interests added yet. Add some below to get personalised job matches!</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Add interest form -->
                                <div class="mt-2 profile-edit-controls">
                                    <h6><i class="fas fa-plus-circle"></i> Add an Interest</h6>
                                    <form method="post" action="<?= base_url('candidate/add-interest') ?>" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="text" name="interest" class="form-control"
                                               placeholder="e.g. Web Development, Data Science, React, Remote…" required>
                                        <button type="submit" class="btn btn-primary text-nowrap">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </form>
                                </div>

                                <!-- Suggestions -->
                                <div class="mt-4 profile-edit-controls">
                                    <h6 class="text-muted"><i class="fas fa-lightbulb"></i> Popular interests — click to add</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php
                                        $suggestions = [
                                            'Web Development','Mobile Development','Data Science','Machine Learning',
                                            'DevOps','Cloud Computing','Cybersecurity','UI/UX Design',
                                            'Backend Development','Frontend Development','Full Stack','Remote',
                                            'Python','JavaScript','PHP','Java','React','Node.js',
                                        ];
                                        // $interests is a flat string array e.g. ['PHP', 'React', 'DevOps']
                                        $existingInterests = array_map('strtolower', $interests ?? []);
                                        foreach ($suggestions as $sug):
                                            if (!in_array(strtolower($sug), $existingInterests)):
                                        ?>
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm"
                                                    onclick="quickAddInterest('<?= esc($sug) ?>')">
                                                + <?= esc($sug) ?>
                                            </button>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </div>
                                </div>

                                <!-- Hidden quick-add form -->
                                <form method="post" action="<?= base_url('candidate/add-interest') ?>" id="quickInterestForm" class="candidate-hidden">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="interest" id="quickInterestValue">
                                </form>
                                <div class="profile-edit-actions">
                                    <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="experience">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Work Experience</h5>
                                <div class="d-flex align-items-center candidate-flex-gap-10">
                                    <button type="button" class="btn btn-sm btn-primary profile-header-add" data-toggle="modal" data-target="#addExperienceModal"><i class="fas fa-plus"></i> Add</button>
                                    <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Work Experience">
                                        <span class="profile-edit-toggle-text">&#9998;</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">
                                    Your <strong>Total Experience</strong> shown in the summary is automatically calculated from the entries below.
                                </p>
                                <?php if (!empty($workExperiences)): ?>
                                    <?php foreach($workExperiences as $exp): ?>
                                    <div class="experience-item border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="mb-1"><?= esc($exp['job_title']) ?></h6>
                                                <p class="mb-1 text-muted"><i class="fas fa-building"></i> <?= esc($exp['company_name']) ?> • <?= esc($exp['employment_type']) ?></p>
                                                <p class="mb-1 text-muted"><i class="fas fa-calendar"></i> <?= date('M Y', strtotime($exp['start_date'])) ?> - <?= $exp['is_current'] ? 'Present' : date('M Y', strtotime($exp['end_date'])) ?></p>
                                                <?php if($exp['location']): ?><p class="mb-1 text-muted"><i class="fas fa-map-marker-alt"></i> <?= esc($exp['location']) ?></p><?php endif; ?>
                                                <?php if($exp['description']): ?><p class="mt-2"><?= nl2br(esc($exp['description'])) ?></p><?php endif; ?>
                                            </div>
                                            <div class="profile-edit-controls">
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editExperience(<?= json_encode($exp) ?>)'><i class="fas fa-edit"></i></button>
                                                <a href="<?= base_url('candidate/delete-work-experience/'.$exp['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this experience?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No work experience added yet</p>
                                <?php endif; ?>
                                <div class="profile-edit-actions">
                                    <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="education">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Education</h5>
                                <div class="d-flex align-items-center candidate-flex-gap-10">
                                    <button type="button" class="btn btn-sm btn-primary profile-header-add" data-toggle="modal" data-target="#addEducationModal"><i class="fas fa-plus"></i> Add</button>
                                    <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Education">
                                        <span class="profile-edit-toggle-text">&#9998;</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($education)): ?>
                                    <?php foreach($education as $edu): ?>
                                    <div class="education-item border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="mb-1"><?= esc($edu['degree']) ?></h6>
                                                <p class="mb-1 text-muted"><i class="fas fa-university"></i> <?= esc($edu['institution']) ?></p>
                                                <p class="mb-1 text-muted"><i class="fas fa-book"></i> <?= esc($edu['field_of_study']) ?></p>
                                                <p class="mb-1 text-muted"><i class="fas fa-calendar"></i> <?= esc($edu['start_year']) ?> - <?= esc($edu['end_year']) ?></p>
                                                <?php if($edu['grade']): ?><p class="mb-1 text-muted"><i class="fas fa-award"></i> Grade: <?= esc($edu['grade']) ?></p><?php endif; ?>
                                            </div>
                                            <div class="profile-edit-controls">
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editEducation(<?= json_encode($edu) ?>)'><i class="fas fa-edit"></i></button>
                                                <a href="<?= base_url('candidate/delete-education/'.$edu['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this education?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No education added yet</p>
                                <?php endif; ?>
                                <div class="profile-edit-actions">
                                    <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="projects">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Projects</h5>
                                <div class="d-flex align-items-center candidate-flex-gap-10">
                                    <button type="button" class="btn btn-sm btn-primary profile-header-add" data-toggle="modal" data-target="#addProjectModal"><i class="fas fa-plus"></i> Add</button>
                                    <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Projects">
                                        <span class="profile-edit-toggle-text">&#9998;</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
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
                                            <div class="profile-edit-controls">
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editProject(<?= json_encode($project) ?>)'><i class="fas fa-edit"></i></button>
                                                <a href="<?= base_url('candidate/delete-project/' . $project['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this project?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No projects added yet</p>
                                <?php endif; ?>
                                <div class="profile-edit-actions">
                                    <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Done</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section mb-4" id="certifications">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Certifications</h5>
                                <div class="d-flex align-items-center candidate-flex-gap-10">
                                    <button type="button" class="btn btn-sm btn-primary profile-header-add" data-toggle="modal" data-target="#addCertificationModal"><i class="fas fa-plus"></i> Add</button>
                                    <button type="button" class="profile-edit-toggle" data-edit-toggle title="Edit Certifications">
                                        <span class="profile-edit-toggle-text">&#9998;</span>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($certifications)): ?>
                                    <?php foreach($certifications as $cert): ?>
                                    <div class="certification-item border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="mb-1"><?= esc($cert['certification_name']) ?></h6>
                                                <p class="mb-1 text-muted"><i class="fas fa-building"></i> <?= esc($cert['issuing_organization']) ?></p>
                                                <p class="mb-1 text-muted"><i class="fas fa-calendar"></i> Issued: <?= date('M Y', strtotime($cert['issue_date'])) ?><?= $cert['expiry_date'] ? ' • Expires: '.date('M Y', strtotime($cert['expiry_date'])) : '' ?></p>
                                                <?php if($cert['credential_id']): ?><p class="mb-1 text-muted"><i class="fas fa-id-card"></i> ID: <?= esc($cert['credential_id']) ?></p><?php endif; ?>
                                                <?php if($cert['credential_url']): ?><p class="mb-1"><a href="<?= esc($cert['credential_url']) ?>" target="_blank"><i class="fas fa-external-link-alt"></i> View Credential</a></p><?php endif; ?>
                                            </div>
                                            <div class="profile-edit-controls">
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick='editCertification(<?= json_encode($cert) ?>)'><i class="fas fa-edit"></i></button>
                                                <a href="<?= base_url('candidate/delete-certification/'.$cert['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this certification?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No certifications added yet</p>
                                <?php endif; ?>
                                <div class="profile-edit-actions">
                                    <button type="button" class="btn btn-outline-secondary" data-edit-cancel>Done</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<!-- Add Work Experience Modal -->
<div class="modal fade" id="addExperienceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Work Experience</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post" action="<?= base_url('candidate/add-work-experience') ?>" id="workExpForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="exp_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Job Title *</label>
                            <input type="text" name="job_title" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company Name *</label>
                            <input type="text" name="company_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employment Type</label>
                            <select name="employment_type" class="form-control">
                                <option>Full-time</option>
                                <option>Part-time</option>
                                <option>Contract</option>
                                <option>Freelance</option>
                                <option>Internship</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" id="endDate">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="is_current" value="1" class="form-check-input" id="isCurrent" onchange="document.getElementById('endDate').disabled=this.checked">
                                <label class="form-check-label" for="isCurrent">Currently working here</label>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Describe your responsibilities and achievements..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <small class="text-muted mr-auto">Updates Total Experience automatically</small>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Education Modal -->
<div class="modal fade" id="addEducationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Education</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post" action="<?= base_url('candidate/add-education') ?>" id="educationForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="edu_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Degree *</label>
                        <input type="text" name="degree" class="form-control" placeholder="e.g., Bachelor of Technology" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Field of Study *</label>
                        <input type="text" name="field_of_study" class="form-control" placeholder="e.g., Computer Science" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Institution *</label>
                        <input type="text" name="institution" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Start Year *</label>
                            <input type="number" name="start_year" class="form-control" min="1950" max="2030" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">End Year *</label>
                            <input type="number" name="end_year" class="form-control" min="1950" max="2030" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grade/CGPA</label>
                        <input type="text" name="grade" class="form-control" placeholder="e.g., 8.5 CGPA">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Certification Modal -->
<div class="modal fade" id="addCertificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Certification</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post" action="<?= base_url('candidate/add-certification') ?>" id="certificationForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="cert_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Certification Name *</label>
                        <input type="text" name="certification_name" class="form-control" placeholder="e.g., AWS Certified Developer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Issuing Organization *</label>
                        <input type="text" name="issuing_organization" class="form-control" placeholder="e.g., Amazon Web Services" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Issue Date *</label>
                            <input type="date" name="issue_date" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credential ID</label>
                        <input type="text" name="credential_id" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credential URL</label>
                        <input type="url" name="credential_url" class="form-control" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Project</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post" action="<?= base_url('candidate/add-project') ?>" id="projectForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="project_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Project Name *</label>
                            <input type="text" name="project_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Your Role</label>
                            <input type="text" name="role_name" class="form-control" placeholder="e.g. Lead Developer">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tech Stack</label>
                            <input type="text" name="tech_stack" class="form-control" placeholder="React, Laravel, MySQL">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Project URL</label>
                            <input type="url" name="project_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Project Summary</label>
                            <textarea name="project_summary" class="form-control" rows="4" placeholder="Describe the product, scope, and your contribution..."></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Impact / Metrics</label>
                            <textarea name="impact_metrics" class="form-control" rows="3" placeholder="e.g. reduced load time by 35%, served 10k monthly users"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ═══ CONTEXTUAL RESUME STUDIO NUDGE (fires on download resume click) ═══ -->
<style>
/* ── Resume Studio Nudge – HireMatrix theme ─────────────────────── */
#rsNudge-backdrop{
  position:fixed;inset:0;z-index:9998;
  background:rgba(22,33,43,0.55);
  display:none;align-items:center;justify-content:center;padding:20px;
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
}
#rsNudge-backdrop.visible{display:flex;animation:rsNudgeFadeIn .22s ease;}
@keyframes rsNudgeFadeIn{from{opacity:0}to{opacity:1}}

#rsNudge-modal{
  background:var(--card,#fff);
  border:1px solid var(--border,#D9ECE5);
  border-radius:10px;
  width:100%;max-width:480px;
  overflow:hidden;
  animation:rsNudgePop .3s cubic-bezier(.34,1.56,.64,1);
  box-shadow:0 24px 60px rgba(0,0,0,.12);
}
@keyframes rsNudgePop{from{opacity:0;transform:scale(.93) translateY(18px)}to{opacity:1;transform:none}}

/* ── Header (mirrors hm-header style) ── */
.rsn-header{
  padding:18px 20px 0;
  display:flex;justify-content:space-between;align-items:flex-start;gap:12px;
}
.rsn-header-left{display:flex;flex-direction:column;gap:4px;}
.rsn-eyebrow{
  font-size:18px !important;font-weight:700;
  letter-spacing:.12em;text-transform:uppercase;
  color:var(--primary,#1FB7B5);
}
.rsn-counter{
  font-size:10px;font-weight:600;
  color:var(--text-light,#94A3B8);letter-spacing:.04em;
}
.rsn-close-x{
  width:26px;height:26px;border-radius:5px;flex-shrink:0;
  border:1px solid var(--border,#D9ECE5);background:transparent;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  color:var(--text-light,#94A3B8);font-size:13px;margin-top:1px;
  transition:background .15s,color .15s,border-color .15s;
}
.rsn-close-x:hover{
  background:var(--muted,#EDF8F5);
  color:var(--foreground,#16212B);
  border-color:var(--primary,#1FB7B5);
}

/* ── Progress bar (single active pip) ── */
.rsn-progress{padding:14px 20px 0;display:flex;gap:4px;}
.rsn-pip{height:2px;flex:1;border-radius:2px;background:var(--border,#D9ECE5);}
.rsn-pip.active{background:var(--primary,#1FB7B5);}

/* ── Body ── */
.rsn-body{padding:16px 20px 0;}
.rsn-slide-title{
  font-size:19px;font-weight:700;
  color:var(--foreground,#16212B);
  line-height:1.3;margin-bottom:8px;
}
.rsn-slide-title em{
  font-style:normal;
  background:var(--gradient-primary,linear-gradient(135deg,#1FB7B5 0%,#53B86C 55%,#B5D84E 100%));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.rsn-slide-desc{
  font-size:13px;color:var(--muted-foreground,#64748B);
  line-height:1.65;margin-bottom:14px;
}

/* ── rill tags (mirrors hm-rills2 / hm-rill2) ── */
.rsn-rills{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:18px;}
.rsn-rill{
  display:flex;align-items:center;gap:5px;
  padding:5px 10px;border-radius:999px;
  background:var(--muted,#EDF8F5);
  border:1px solid var(--border,#D9ECE5);
  font-size:12px;font-weight:600;
  color:var(--primary-dark,#0D8A90);
  white-space:nowrap;
}

/* ── Compare grid ── */
.rsn-compare{
  display:grid;grid-template-columns:1fr 1fr;
  gap:10px;margin-bottom:18px;
}
.rsn-col{border-radius:8px;padding:12px;}
.rsn-col-before{
  background:#FEF2F2;border:1px solid #FECACA;
}
.rsn-col-after{
  background:var(--muted,#EDF8F5);border:1px solid var(--border,#D9ECE5);
}
.rsn-col-label{
  font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  margin-bottom:8px;
}
.rsn-col-before .rsn-col-label{color:#EF4444;}
.rsn-col-after  .rsn-col-label{color:var(--primary,#1FB7B5);}
.rsn-col-row{
  display:flex;align-items:flex-start;gap:6px;
  font-size:12px;color:var(--foreground,#16212B);
  margin-bottom:5px;line-height:1.4;
}
.rsn-col-row:last-child{margin-bottom:0;}
.rsn-col-row i{margin-top:2px;font-size:11px;flex-shrink:0;}
.rsn-col-before .rsn-col-row i{color:#EF4444;}
.rsn-col-after  .rsn-col-row i{color:var(--secondary,#53B86C);}

/* ── Divider ── */
.rsn-divider{height:1px;background:var(--border,#D9ECE5);}

/* ── Footer (mirrors hm-footer) ── */
.rsn-footer{
  padding:14px 20px;
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
}
.rsn-btn-primary{
  padding:8px 18px;border-radius:6px;
  background:var(--gradient-primary,linear-gradient(135deg,#1FB7B5,#53B86C,#B5D84E));
  color:#fff !important;font-size:13px;font-weight:600;
  border:none;cursor:pointer;
  text-decoration:none !important;display:inline-block;white-space:nowrap;
  transition:opacity .15s,transform .15s;
}
.rsn-btn-primary:hover{opacity:.88;transform:translateY(-1px);}
.rsn-btn-maybe{
  padding:8px 16px;border-radius:6px;background:transparent;
  border:1px solid var(--border,#D9ECE5);
  color:var(--muted-foreground,#64748B);
  font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;
  transition:border-color .15s,color .15s,background .15s;
}
.rsn-btn-maybe:hover{
  border-color:var(--primary,#1FB7B5);
  color:var(--primary,#1FB7B5);
  background:var(--muted,#EDF8F5);
}

/* ── Dark theme ── */
body.dark #rsNudge-modal{
  background:var(--card,#162327) !important;
  border-color:var(--border,#23343A);
}
body.dark .rsn-close-x{
  border-color:var(--border,#23343A);
  color:var(--text-light,#7A8B96);
}
body.dark .rsn-close-x:hover{
  background:var(--muted,#1B2A2F);
  color:var(--foreground,#F8FAFC);
  border-color:var(--primary,#1FB7B5);
}
body.dark .rsn-pip{background:var(--border,#23343A);}
body.dark .rsn-pip.active{background:var(--primary,#1FB7B5);}
body.dark .rsn-slide-title{color:var(--foreground,#F8FAFC);}
body.dark .rsn-slide-desc{color:var(--muted-foreground,#94A3B8);}
body.dark .rsn-rill{
  background:var(--card,#162327)  !important;
  border-color:var(--border,#23343A);
  color:var(--primary,#1FB7B5);
}
body.dark .rsn-rills{
  background:var(--card,#162327)  !important;
  border-color:var(--border,#23343A); 
}
body.dark .rsn-divider{background:var(--border,#23343A);}
body.dark .rsn-col-before{background:var(--muted,#1B2A2F); border-color:var(--border,#23343A);}
body.dark .rsn-col-after{
  background:var(--muted,#1B2A2F);
  border-color:var(--border,#23343A);
}
body.dark .rsn-col-row{color:var(--foreground,#F8FAFC);}
body.dark .rsn-btn-maybe{
  border-color:var(--border,#23343A);
  color:var(--muted-foreground,#94A3B8);
}
body.dark .rsn-btn-maybe:hover{
  border-color:var(--primary,#1FB7B5);
  color:var(--primary,#1FB7B5);
  background:var(--muted,#1B2A2F);
}
.rsn-close-x{
  width:26px;height:26px;border-radius:5px;flex-shrink:0;
  border:1px solid var(--border,#D9ECE5);background:transparent;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  color:var(--text-light,#94A3B8);font-size:13px;margin-top:1px;
  transition:background .15s,color .15s,border-color .15s;
  outline:none;                              /* ← add this */
  -webkit-tap-highlight-color:transparent;   /* ← add this (kills mobile tap flash too) */
}
.rsn-close-x:hover{
  background:var(--muted,#EDF8F5);
  color:var(--foreground,#16212B);
  border-color:var(--primary,#1FB7B5);
}
.rsn-close-x:focus{
  outline:none;                              /* ← add this */
}
.rsn-close-x:focus-visible{
  outline:2px solid var(--primary,#1FB7B5);  /* keeps keyboard-nav accessibility */
  outline-offset:2px;
}
</style>

<div id="rsNudge-backdrop" role="dialog" aria-modal="true" aria-label="Resume Studio suggestion">
  <div id="rsNudge-modal">

    <div class="rsn-header">
      <div class="rsn-header-left">
        <span class="rsn-eyebrow">HireMatrix AI Features</span>
        <span class="rsn-counter">Resume Studio — downloaded just now</span>
      </div>
      <button class="rsn-close-x" id="rsNudgeCloseX" aria-label="Close">✕</button>
    </div>

    <div class="rsn-progress">
      <div class="rsn-pip active"></div> 
    </div>

    <div class="rsn-body">
      <h2 class="rsn-slide-title">Make this resume work <em>harder for you</em></h2>
      <p class="rsn-slide-desc">
        You just downloaded a generic CV. Resume Studio tailors it per role,
        highlights what recruiters want, and gets it past ATS filters automatically.
      </p>

      <div class="rsn-compare">
        <div class="rsn-col rsn-col-before">
          <div class="rsn-col-label">Generic resume</div>
          <div class="rsn-col-row"><i class="fas fa-times-circle"></i> Same CV for every job</div>
          <div class="rsn-col-row"><i class="fas fa-times-circle"></i> Often rejected by ATS</div>
          <div class="rsn-col-row"><i class="fas fa-times-circle"></i> Misses role keywords</div>
        </div>
        <div class="rsn-col rsn-col-after">
          <div class="rsn-col-label">With Resume Studio</div>
          <div class="rsn-col-row"><i class="fas fa-check-circle"></i> Role-targeted version</div>
          <div class="rsn-col-row"><i class="fas fa-check-circle"></i> ATS-optimised layout</div>
          <div class="rsn-col-row"><i class="fas fa-check-circle"></i> AI rewrite suggestions</div>
        </div>
      </div>

      <div class="rsn-rills">
        <span class="rsn-rill">Job-specific CVs</span>
        <span class="rsn-rill">ATS optimisation</span>
        <span class="rsn-rill">AI improvement tips</span>
        <span class="rsn-rill">Job fit scoring</span>
      </div>
    </div>

    <div class="rsn-divider"></div>

    <div class="rsn-footer">
      <a href="<?= base_url('candidate/resume-studio') ?>" class="btn btn-primary">
        <i class="fas fa-magic"></i> Open Resume Studio
      </a>
      <button class="rsn-btn-maybe" id="rsNudgeClose">Maybe later</button>
    </div>

  </div>
</div>

<iframe id="rsDownloadFrame" style="display:none;" aria-hidden="true"></iframe>

<script>
(function () {
  var backdrop = document.getElementById('rsNudge-backdrop');
  var frame    = document.getElementById('rsDownloadFrame');

  function openNudge() { backdrop.classList.add('visible'); }

  function closeNudge() {
    backdrop.style.transition = 'opacity .18s';
    backdrop.style.opacity = '0';
    setTimeout(function () {
      backdrop.classList.remove('visible');
      backdrop.style.opacity = '';
      backdrop.style.transition = '';
    }, 200);
  }

  var rsStorageKey = 'rsNudgeShown';
  var rsAlreadyShown = false;
  try {
    rsAlreadyShown = localStorage.getItem(rsStorageKey) === '1';
  } catch (e) {
    rsAlreadyShown = false; // storage unavailable — fail open, show it
  }

  document.querySelectorAll('a[href*="download-resume"]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      if (rsAlreadyShown) {
        return; // already shown before — let the link download normally, no popup
      }
      e.preventDefault();
      rsAlreadyShown = true; // lock immediately so a second click/link can't slip through
      try { localStorage.setItem(rsStorageKey, '1'); } catch (err) {}
      frame.src = link.href;
      setTimeout(openNudge, 400);
    });
  });

  document.getElementById('rsNudgeClose').addEventListener('click', closeNudge);
  document.getElementById('rsNudgeCloseX').addEventListener('click', closeNudge);
  backdrop.addEventListener('click', function (e) { if (e.target === backdrop) closeNudge(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeNudge(); });
})();
</script>
<?= view('Layouts/candidate_footer') ?>
    


