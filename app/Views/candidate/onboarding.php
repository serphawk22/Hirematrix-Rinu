<?= view('Layouts/candidate_header', ['title' => 'Complete Your Profile']) ?>

<?php
$stepLabels = [
    'personal' => 'Personal Details',
    'skills' => 'Skills',
    'education' => 'Education',
    'experience' => 'Experience',
    'review' => 'Review',
];
$stepDescriptions = [
    'personal' => 'Add the key identity and contact details recruiters need first.',
    'skills' => 'List your strongest skills in a recruiter-friendly format.',
    'education' => 'Add at least one education record to complete your academic background.',
    'experience' => 'Add work experience, or mark yourself as a fresher.',
    'review' => 'Confirm everything before entering the portal.',
];
$currentStepTitle = $stepLabels[$activeStep] ?? 'Onboarding';

// Define professional options for datalists to mirror Naukri-style UX
$degreeOptions = [
    'B.Tech', 'B.E.', 'M.Tech', 'M.E.', 'MCA', 'BCA', 'MBA', 'PGDM', 
    'B.Sc', 'M.Sc', 'B.Com', 'M.Com', 'B.A.', 'M.A.', 'PhD', 'Diploma',
    'SSLC / 10th', 'Plus Two / 12th', 'Higher Secondary', 'Intermediate', 'Others'
];
$fieldOfStudyOptions = [
    'Computer Science', 'Information Technology', 'Software Engineering', 
    'Electronics and Communication', 'Electrical Engineering', 'Mechanical Engineering', 
    'Civil Engineering', 'Data Science', 'Artificial Intelligence', 'Cyber Security',
    'Business Administration', 'Marketing', 'Finance', 'Human Resources', 'Physics', 'Mathematics',
    'Science', 'Commerce', 'Arts', 'Biology', 'Computer Application', 'Others'
];
$jobTitleOptions = [
    'Software Engineer', 'Frontend Developer', 'Backend Developer', 'Full Stack Developer',
    'Mobile App Developer', 'DevOps Engineer', 'Data Scientist', 'Data Analyst',
    'Machine Learning Engineer', 'UI/UX Designer', 'Product Manager', 'Project Manager',
    'QA Engineer', 'Business Analyst', 'Systems Administrator', 'Network Engineer',
    'Cloud Architect', 'Security Analyst', 'Digital Marketing Executive', 'HR Generalist'
];
$locationOptions = [
    'Bangalore', 'Mumbai', 'Pune', 'Hyderabad', 'Delhi NCR', 'Chennai', 'Kolkata', 
    'Ahmedabad', 'Remote', 'London', 'New York', 'Singapore', 'Dubai'
];

$renderSelectOptions = static function (array $options, string $selected, string $placeholder): string {
    $html = '<option value="">' . esc($placeholder) . '</option>';
    $hasSelected = $selected === '';

    foreach ($options as $option) {
        $isSelected = $selected === $option;
        $hasSelected = $hasSelected || $isSelected;
        $html .= '<option value="' . esc($option) . '"' . ($isSelected ? ' selected' : '') . '>' . esc($option) . '</option>';
    }

    if (!$hasSelected) {
        $html .= '<option value="' . esc($selected) . '" selected>' . esc($selected) . '</option>';
    }

    return $html;
};
?>

<!-- Datalists for professional options (Hidden from UI, used by inputs) -->
<datalist id="degreeList">
    <?php foreach ($degreeOptions as $opt): ?>
        <option value="<?= esc($opt) ?>">
    <?php endforeach; ?>
</datalist>

<datalist id="fieldOfStudyList">
    <?php foreach ($fieldOfStudyOptions as $opt): ?>
        <option value="<?= esc($opt) ?>">
    <?php endforeach; ?>
</datalist>

<datalist id="jobTitleList">
    <?php foreach ($jobTitleOptions as $opt): ?>
        <option value="<?= esc($opt) ?>">
    <?php endforeach; ?>
</datalist>

<datalist id="locationList">
    <?php foreach ($locationOptions as $opt): ?>
        <option value="<?= esc($opt) ?>">
    <?php endforeach; ?>
</datalist>

<div class="onboarding-jobboard">
<section class="content-wrap onboarding-content-canvas">
    <div class="container-fluid">
        <div class="page-board-header page-board-header-tight">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-user-plus"></i> Onboarding</span>
                <h1 class="page-board-title">Complete Your Candidate Profile</h1>
                <p class="page-board-subtitle">Follow the step-by-step setup flow to finish your profile and enter the portal fully prepared.</p>
                <div class="company-profile-meta">
                    <span class="meta-chip"><strong><?= (int) $progressPercent ?>%</strong> Complete</span>
                    <span class="meta-chip"><strong><?= esc($currentStepTitle) ?></strong> Current step</span>
                </div>
            </div>
            <div class="page-board-actions">
                <?php if (!empty($user['resume_path'])): ?>
                    <a href="<?= base_url('candidate/download-resume') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-download mr-1"></i> Download Resume
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
        <?php if (session('validation')): ?>
            <div class="alert alert-danger">
                <?php foreach (session('validation')->getErrors() as $error): ?>
                    <div><?= esc($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="onboarding-wrap">
            <aside class="onboarding-side">
                <div class="text-uppercase text-muted small font-weight-bold">Profile Setup</div>
                <div class="onboarding-progress-bar">
                    <div class="onboarding-progress-fill candidate-progress-fill" style="--candidate-progress: <?= (int) $progressPercent ?>%;"></div>
                </div>
                <div class="font-weight-bold mb-3"><?= (int) $progressPercent ?>% complete</div>

                <div class="onboarding-step-list">
                    <?php $displayIndex = 1; ?>
                    <?php foreach ($steps as $index => $step): ?>
                        <?php // The 'resume' and 'preferences' steps are no longer in CandidateOnboardingService::STEPS
                        if (in_array($step, ['resume', 'preferences'])) continue;
                        $classes = ['onboarding-step-item'];
                        if ($step === $activeStep) {
                            $classes[] = 'is-active';
                        } elseif (!empty($completionMap[$step])) {
                            $classes[] = 'is-done';
                        }
                        ?>
                        <div class="<?= esc(implode(' ', $classes)) ?>">
                            <div class="onboarding-step-index">Step <?= $displayIndex++ ?></div>
                            <div class="font-weight-bold"><?= esc($stepLabels[$step] ?? ucfirst($step)) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>

            <div class="onboarding-content">
                <div class="onboarding-header">
                    <h2><?= esc($currentStepTitle) ?></h2>
                    <p><?= esc($stepDescriptions[$activeStep] ?? '') ?></p>
                </div>

                <?php if ($activeStep === 'personal'): ?>
                    <div class="onboarding-card mb-4 bg-light border-primary candidate-dashed-card">
                        <div class="card-body">
                            <h5 class="text-primary d-flex align-items-center"><i class="fas fa-magic mr-2"></i> Fast-Track Your Profile</h5>
                            <p class="small text-muted">Upload your resume and we'll automatically fill in your details, skills, education, and experience.</p>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="resumePrefillInput" accept=".pdf,.doc,.docx">
                                <label class="custom-file-label" id="resumePrefillLabel" for="resumePrefillInput">Choose resume file...</label>
                            </div>
                            <div id="prefillStatus" class="mt-2 small font-weight-bold candidate-hidden"></div>
                        </div>
                    </div>

                    <form method="post" action="<?= base_url('candidate/onboarding/personal') ?>" data-onboarding-form>
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= esc(old('name', $user['name'] ?? '')) ?>" minlength="3" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?= esc(old('email', $user['email'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= esc(old('phone', $user['phone'] ?? '')) ?>" minlength="10" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Current Location</label>
                                <input type="text" name="location" class="form-control" value="<?= esc(old('location', $user['location'] ?? '')) ?>" list="locationList" minlength="2" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Gender</label>
                                <?php $gender = (string) old('gender', $user['gender'] ?? ''); ?>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select gender</option>
                                    <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                                    <option value="Prefer not to say" <?= $gender === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" value="<?= esc(old('date_of_birth', $user['date_of_birth'] ?? '')) ?>" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label>Professional Summary</label>
                                <textarea name="bio" class="form-control" rows="5" minlength="20" required><?= esc(old('bio', $user['bio'] ?? '')) ?></textarea>
                            </div>
                        </div>
                        <div class="onboarding-actions">
                            <button type="submit" class="btn btn-primary" data-onboarding-submit disabled>Save and Continue</button>
                        </div>
                    </form>
                <?php elseif ($activeStep === 'skills'): ?>
                    <form method="post" action="<?= base_url('candidate/onboarding/skills') ?>" data-onboarding-form>
                        <?= csrf_field() ?>
                        <div class="onboarding-card">
                            <label>Skills</label>
                            <textarea name="skills" class="form-control" rows="4" placeholder="PHP, MySQL, JavaScript, Laravel" minlength="2" required><?= esc(old('skills', $skillsValue)) ?></textarea>
                            <small class="text-muted">Use comma-separated skills so matching works properly.</small>
                        </div>
                        <div class="onboarding-actions">
                            <button type="submit" class="btn btn-primary" data-onboarding-submit disabled>Save and Continue</button>
                        </div>
                    </form>
                <?php elseif ($activeStep === 'education'): ?>
                    <form method="post" action="<?= base_url('candidate/onboarding/education') ?>" data-onboarding-form>
                        <?= csrf_field() ?>
                        <?php $educationItems = !empty($educationRows) ? $educationRows : [[]]; ?>
                        <div class="repeatable-list" id="educationList">
                            <?php foreach ($educationItems as $index => $educationRow): ?>
                                <div class="repeatable-item education-item">
                                    <div class="repeatable-item-title">Education <?= $index + 1 ?></div>
                                    <?php if ($index > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger repeatable-remove" data-remove-item>Remove</button>
                                    <?php endif; ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label>Degree</label>
                                            <select name="degree[]" class="form-control" data-option-source="degree" required>
                                                <?= $renderSelectOptions($degreeOptions, (string) ($educationRow['degree'] ?? ''), 'Select degree') ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Field of Study</label>
                                            <select name="field_of_study[]" class="form-control" data-option-source="field_of_study" required>
                                                <?= $renderSelectOptions($fieldOfStudyOptions, (string) ($educationRow['field_of_study'] ?? ''), 'Select field of study') ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>University/Institute</label>
                                            <input type="text" name="institution[]" class="form-control" value="<?= esc($educationRow['institution'] ?? '') ?>" minlength="2" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label>Start Year</label>
                                            <input type="number" name="start_year[]" class="form-control" value="<?= esc($educationRow['start_year'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label>End Year</label>
                                            <input type="number" name="end_year[]" class="form-control" value="<?= esc($educationRow['end_year'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Grade / CGPA</label>
                                            <input type="text" name="grade[]" class="form-control" value="<?= esc($educationRow['grade'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="onboarding-actions">
                            <button type="button" class="btn btn-outline-secondary" id="addEducationItem" data-inline-repeatable="1">+ Add Education</button>
                            <button type="submit" class="btn btn-primary" data-onboarding-submit disabled>Save and Continue</button>
                        </div>
                    </form>
                <?php elseif ($activeStep === 'experience'): ?>
                    <form method="post" action="<?= base_url('candidate/onboarding/experience') ?>" data-onboarding-form>
                        <?= csrf_field() ?>
                        <div class="onboarding-card mb-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_fresher_candidate" name="is_fresher_candidate" value="1" <?= (int) old('is_fresher_candidate', $user['is_fresher_candidate'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="is_fresher_candidate">I am a fresher / I do not have work experience yet</label>
                            </div>
                        </div>
                        <?php $experienceItems = !empty($experienceRows) ? $experienceRows : [[]]; ?>
                        <div class="repeatable-list" id="experienceFields">
                            <?php foreach ($experienceItems as $index => $experienceRow): ?>
                                <div class="repeatable-item experience-item">
                                    <div class="repeatable-item-title">Experience <?= $index + 1 ?></div>
                                    <?php if ($index > 0): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger repeatable-remove" data-remove-item>Remove</button>
                                    <?php endif; ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label>Job Title</label>
                                            <select name="job_title[]" class="form-control" data-option-source="job_title">
                                                <?= $renderSelectOptions($jobTitleOptions, (string) ($experienceRow['job_title'] ?? ''), 'Select job title') ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Company Name</label>
                                            <input type="text" name="company_name[]" class="form-control" value="<?= esc($experienceRow['company_name'] ?? '') ?>" minlength="2">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Employment Type</label>
                                            <?php $employmentType = (string) ($experienceRow['employment_type'] ?? 'Full-time'); ?>
                                            <select name="employment_type[]" class="form-control">
                                                <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'] as $type): ?>
                                                    <option value="<?= esc($type) ?>" <?= $employmentType === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Location</label>
                                            <input type="text" name="location[]" class="form-control" value="<?= esc($experienceRow['location'] ?? '') ?>" list="locationList">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>Start Date</label>
                                            <input type="date" name="start_date[]" class="form-control" value="<?= esc($experienceRow['start_date'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label>End Date</label>
                                            <input type="date" name="end_date[]" class="form-control" value="<?= esc($experienceRow['end_date'] ?? '') ?>">
                                        </div>
                                        <div class="col-12 mb-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="is_current_<?= $index ?>" name="is_current[<?= $index ?>]" value="1" <?= !empty($experienceRow['is_current']) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="is_current_<?= $index ?>">I currently work here</label>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label>Work Summary</label>
                                            <textarea name="description[]" class="form-control" rows="4"><?= esc($experienceRow['description'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="onboarding-actions">
                            <button type="button" class="btn btn-outline-secondary" id="addExperienceItem" data-inline-repeatable="1">+ Add Experience</button>
                            <button type="submit" class="btn btn-primary" data-onboarding-submit disabled>Save and Continue</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="onboarding-summary-grid">
                        <?php foreach ($completionMap as $step => $done): ?>
                        <?php if (in_array($step, ['resume', 'preferences'])) continue; ?>
                            <div class="onboarding-summary-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?= esc($stepLabels[$step] ?? ucfirst($step)) ?></strong>
                                    <span class="badge badge-<?= !empty($done) ? 'success' : 'secondary' ?>"><?= !empty($done) ? 'Done' : 'Pending' ?></span>
                                </div>
                                <div class="text-muted small"><?= esc($stepDescriptions[$step] ?? '') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" action="<?= base_url('candidate/onboarding/review') ?>" data-onboarding-form>
                        <?= csrf_field() ?>
                        <div class="onboarding-actions">
                            <button type="submit" class="btn btn-primary" data-onboarding-submit>Finish and Go to Dashboard</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- 1. Resume Prefill Input Logic ---
    const prefillInput = document.getElementById('resumePrefillInput');
    const statusEl = document.getElementById('prefillStatus');
    const labelEl = document.getElementById('resumePrefillLabel');

    if (prefillInput) {
        prefillInput.addEventListener('change', function() {
            if (!this.files.length) return;

            const file = this.files[0];
            labelEl.textContent = file.name;
            statusEl.textContent = "Parsing resume... please wait.";
            statusEl.style.display = "block";
            statusEl.className = "mt-2 small font-weight-bold text-primary";

            const formData = new FormData();
            formData.append('resume', file);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            fetch('<?= base_url('auth/parse-resume') ?>', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    statusEl.textContent = "✓ Resume parsed successfully. Profile pre-filled.";
                    statusEl.className = "mt-2 small font-weight-bold text-success";
                    fillPersonalStep(response.data);
                    localStorage.setItem('onboarding_prefill', JSON.stringify(response.data));
                } else {
                    statusEl.textContent = "× Error: " + (response.error || "Failed to parse");
                    statusEl.className = "mt-2 small font-weight-bold text-danger";
                }
            })
            
            .catch(err => { statusEl.textContent = "× Network error occurred."; statusEl.className = "mt-2 small font-weight-bold text-danger"; });
        });
    }

    // --- 2. Logic for Dynamic Add/Remove with Datalist Support ---
    const addEduBtn = document.getElementById('addEducationItem');
    const addExpBtn = document.getElementById('addExperienceItem');
    const eduList = document.getElementById('educationList');
    const expList = document.getElementById('experienceFields');
    const degreeOptions = <?= json_encode(array_values($degreeOptions)) ?>;
    const fieldOfStudyOptions = <?= json_encode(array_values($fieldOfStudyOptions)) ?>;
    const jobTitleOptions = <?= json_encode(array_values($jobTitleOptions)) ?>;
    const optionSources = {
        degree: { options: degreeOptions, placeholder: 'Select degree' },
        field_of_study: { options: fieldOfStudyOptions, placeholder: 'Select field of study' },
        job_title: { options: jobTitleOptions, placeholder: 'Select job title' }
    };

    function buildSelectOptions(options, placeholder) {
        return `<option value="">${placeholder}</option>` + options.map(option => `<option value="${escapeHtml(option)}">${escapeHtml(option)}</option>`).join('');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

const educationTemplate = (count) => `
        <div class="repeatable-item-title">Education ${count}</div>
        <button type="button" class="btn btn-sm btn-outline-danger repeatable-remove" data-remove-item>Remove</button>
        <div class="row">
            <div class="col-md-6 mb-3"><label>Degree</label><select name="degree[]" class="form-control" data-option-source="degree" required>${buildSelectOptions(degreeOptions, 'Select degree')}</select></div>
            <div class="col-md-6 mb-3"><label>Field of Study</label><select name="field_of_study[]" class="form-control" data-option-source="field_of_study" required>${buildSelectOptions(fieldOfStudyOptions, 'Select field of study')}</select></div>
            <div class="col-md-6 mb-3"><label>University/Institute</label><input type="text" name="institution[]" class="form-control" minlength="2" required></div>
            <div class="col-md-3 mb-3"><label>Start Year</label><input type="number" name="start_year[]" class="form-control" required></div>
            <div class="col-md-3 mb-3"><label>End Year</label><input type="number" name="end_year[]" class="form-control" required></div>
            <div class="col-md-6 mb-3"><label>Grade / CGPA</label><input type="text" name="grade[]" class="form-control"></div>
        </div>`;

    const experienceTemplate = (count) => `
        <div class="repeatable-item-title">Experience ${count}</div>
        <button type="button" class="btn btn-sm btn-outline-danger repeatable-remove" data-remove-item>Remove</button>
        <div class="row">
            <div class="col-md-6 mb-3"><label>Job Title</label><select name="job_title[]" class="form-control" data-option-source="job_title">${buildSelectOptions(jobTitleOptions, 'Select job title')}</select></div>
            <div class="col-md-6 mb-3"><label>Company Name</label><input type="text" name="company_name[]" class="form-control" minlength="2"></div>
            <div class="col-md-6 mb-3"><label>Employment Type</label><select name="employment_type[]" class="form-control"><option>Full-time</option><option>Part-time</option><option>Contract</option><option>Internship</option><option>Freelance</option></select></div>
            <div class="col-md-6 mb-3"><label>Location</label><input type="text" name="location[]" class="form-control" list="locationList"></div>
            <div class="col-md-6 mb-3"><label>Start Date</label><input type="date" name="start_date[]" class="form-control"></div>
            <div class="col-md-6 mb-3"><label>End Date</label><input type="date" name="end_date[]" class="form-control"></div>
            <div class="col-12 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="is_current_new_${count}" name="is_current[${count-1}]" value="1"><label class="custom-control-label" for="is_current_new_${count}">I currently work here</label></div></div>
            <div class="col-12 mb-3"><label>Work Summary</label><textarea name="description[]" class="form-control" rows="4"></textarea></div>
        </div>`;

    if (addEduBtn) addEduBtn.onclick = () => addItem(eduList, '.education-item', educationTemplate);
    if (addExpBtn) addExpBtn.onclick = () => addItem(expList, '.experience-item', experienceTemplate);

    function addItem(list, itemSelector, template) {
        const count = list.querySelectorAll(itemSelector).length + 1;
        const wrapper = document.createElement('div');
        wrapper.className = `repeatable-item ${itemSelector.substring(1)}`;
        wrapper.innerHTML = template(count);
        list.appendChild(wrapper);
        hydrateSelectOptions(wrapper);
        attachRemoveEvents();
        attachCustomFieldOfStudyEvents(wrapper);
    }

    function attachRemoveEvents() {
        document.querySelectorAll('[data-remove-item]').forEach(btn => {
            btn.onclick = function() { this.closest('.repeatable-item').remove(); };
        });
    }
    attachRemoveEvents();
    attachCustomFieldOfStudyEvents();

    function attachCustomFieldOfStudyEvents(scope = document) {
        const selects = scope.querySelectorAll ? scope.querySelectorAll('select[data-option-source="field_of_study"]') : [];
        selects.forEach(select => {
            if (select.dataset.customFieldBound === '1') return;
            select.dataset.customFieldBound = '1';
            select.addEventListener('change', function() {
                if (this.value !== 'Others') return;

                const customValue = window.prompt('Enter field of study');
                if (!customValue || !customValue.trim()) {
                    this.value = '';
                    triggerFieldEvents(this);
                    return;
                }

                const value = customValue.trim();
                if (!Array.from(this.options).some(option => option.value === value)) {
                    this.appendChild(new Option(value, value, true, true));
                }
                this.value = value;
                triggerFieldEvents(this);
            });
        });
    }

    // --- 3. Auto-fill Logic for Steps ---
    function fillPersonalStep(data) {
        if (data.name) document.querySelector('input[name="name"]').value = data.name;
        if (data.email) document.querySelector('input[name="email"]').value = data.email;
        if (data.phone) document.querySelector('input[name="phone"]').value = data.phone;
        if (data.location) document.querySelector('input[name="location"]').value = data.location;
        if (data.bio) document.querySelector('textarea[name="bio"]').value = data.bio;
        const manualInput = document.querySelector('input[name="resume"]');
        if (manualInput) manualInput.removeAttribute('required');
        document.querySelectorAll('[data-onboarding-form] input, [data-onboarding-form] textarea').forEach(triggerFieldEvents);
    }

    const prefillDataStr = localStorage.getItem('onboarding_prefill');
    if (prefillDataStr) {
        const data = JSON.parse(prefillDataStr);
        const activeStep = '<?= $activeStep ?>';
        window.setTimeout(() => {
            if (activeStep === 'skills' && data.skills) {
                const sf = document.querySelector('textarea[name="skills"]');
                if (sf && !sf.value) sf.value = data.skills;
            }
            if (activeStep === 'education' && Array.isArray(data.education)) fillRepeatableRows('#educationList', '.education-item', addEduBtn, data.education, fillEducationRow);
            if (activeStep === 'experience' && Array.isArray(data.experience)) fillRepeatableRows('#experienceFields', '.experience-item', addExpBtn, data.experience, fillExperienceRow);
            repairEducationSelects();
            hydrateSelectOptions(document);
            document.querySelectorAll('[data-onboarding-form] input, [data-onboarding-form] select, [data-onboarding-form] textarea').forEach(triggerFieldEvents);
        }, 0);
    }

    function fillRepeatableRows(listSelector, itemSelector, addButton, rows, fillFn) {
        const list = document.querySelector(listSelector);
        if (!list || !addButton || !rows.length) return;
        while (list.querySelectorAll(itemSelector).length < rows.length) addButton.click();
        repairEducationSelects();
        list.querySelectorAll(itemSelector).forEach((item, idx) => { if (rows[idx]) fillFn(item, rows[idx]); });
    }

    function fillEducationRow(item, row) {
        const normalized = normalizeEducationRow(row);
        setScopedValue(item, 'select[name="degree[]"]', normalized.degree);
        setScopedValue(item, 'select[name="field_of_study[]"]', normalized.field_of_study);
        setScopedValue(item, 'input[name="institution[]"]', row.institution);
        setScopedValue(item, 'input[name="start_year[]"]', row.start_year);
        setScopedValue(item, 'input[name="end_year[]"]', row.end_year);
        setScopedValue(item, 'input[name="grade[]"]', row.grade);
    }

    function fillExperienceRow(item, row) {
        setScopedValue(item, 'select[name="job_title[]"]', row.job_title);
        setScopedValue(item, 'input[name="company_name[]"]', row.company_name);
        setScopedValue(item, 'input[name="location[]"]', row.location);
        setScopedValue(item, 'input[name="start_date[]"]', row.start_date);
        setScopedValue(item, 'input[name="end_date[]"]', row.end_date);
        setScopedValue(item, 'textarea[name="description[]"]', row.description);
        const cb = item.querySelector('input[type="checkbox"][name^="is_current"]');
        if (cb) { cb.checked = !!row.is_current; triggerFieldEvents(cb); }
    }

    function setScopedValue(scope, selector, val) {
        const f = scope.querySelector(selector);
        if (!f || val === undefined || val === null || String(val).trim() === '') return;
        if (f.tagName === 'SELECT') {
            hydrateSelectOptions(f);
        }
        if (f.tagName === 'SELECT' && !Array.from(f.options).some(option => option.value === String(val))) {
            f.appendChild(new Option(String(val), String(val), true, true));
        }
        f.value = val;
        triggerFieldEvents(f);
    }

    function hydrateSelectOptions(scope) {
        const selects = scope.matches && scope.matches('select[data-option-source]')
            ? [scope]
            : Array.from(scope.querySelectorAll ? scope.querySelectorAll('select[data-option-source]') : []);

        selects.forEach(select => {
            const source = optionSources[select.dataset.optionSource];
            if (!source || select.options.length > 1) return;

            const selectedValue = select.value;
            select.innerHTML = buildSelectOptions(source.options, source.placeholder);
            if (selectedValue) {
                if (!Array.from(select.options).some(option => option.value === selectedValue)) {
                    select.appendChild(new Option(selectedValue, selectedValue));
                }
                select.value = selectedValue;
            }
        });
    }

    function repairEducationSelects() {
        document.querySelectorAll('.education-item').forEach(item => {
            replaceInputWithSelect(item, 'input[name="degree[]"]', 'degree[]', 'degree', degreeOptions, 'Select degree');
            replaceInputWithSelect(item, 'input[name="field_of_study[]"]', 'field_of_study[]', 'field_of_study', fieldOfStudyOptions, 'Select field of study');
            hydrateSelectOptions(item);
            attachCustomFieldOfStudyEvents(item);
        });
    }

    function replaceInputWithSelect(scope, selector, name, sourceKey, options, placeholder) {
        const input = scope.querySelector(selector);
        if (!input) return;

        const select = document.createElement('select');
        select.name = name;
        select.className = input.className || 'form-control';
        select.required = input.required;
        select.dataset.optionSource = sourceKey;
        select.innerHTML = buildSelectOptions(options, placeholder);

        if (input.value) {
            select.value = input.value;
            if (select.value !== input.value) {
                select.appendChild(new Option(input.value, input.value, true, true));
            }
        }

        input.replaceWith(select);
    }

    function normalizeEducationRow(row) {
        const source = `${row.degree || ''} ${row.field_of_study || ''}`.toLowerCase();
        const normalized = {
            degree: row.degree || '',
            field_of_study: row.field_of_study || ''
        };

        if (source.includes('plus two') || source.includes('12th')) {
            normalized.degree = 'Plus Two / 12th';
        } else if (source.includes('sslc') || source.includes('10th')) {
            normalized.degree = 'SSLC / 10th';
        } else if (source.includes('higher secondary')) {
            normalized.degree = 'Higher Secondary';
        }

        if (!normalized.field_of_study) {
            if (source.includes('computer science')) normalized.field_of_study = 'Computer Science';
            else if (source.includes('science')) normalized.field_of_study = 'Science';
            else if (source.includes('commerce')) normalized.field_of_study = 'Commerce';
            else if (source.includes('arts')) normalized.field_of_study = 'Arts';
        }

        return normalized;
    }

    function triggerFieldEvents(f) {
        f.dispatchEvent(new Event('input', { bubbles: true }));
        f.dispatchEvent(new Event('change', { bubbles: true }));
    }
});
</script>

<?= view('Layouts/candidate_footer') ?>

