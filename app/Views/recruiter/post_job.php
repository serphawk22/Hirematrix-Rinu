        <?= view('Layouts/recruiter_header', ['title' => 'Post Job']) ?>
<?php
$questionnaireRows = old('questionnaire');
if (!is_array($questionnaireRows)) {
    $questionnaireRows = [];
}
$jobCategoryOptions = [
    'Software Development',
    'Data Science',
    'DevOps',
    'Quality Assurance',
    'UI/UX Design',
    'Product Management',
    'Project Management',
    'Marketing',
    'Sales',
    'Human Resources',
    'Finance',
    'Operations',
    'Customer Support',
    'Business Analysis',
    'Cybersecurity',
];
$selectedCategory = old('category');
$postedFor = old('posted_for', 'own_company');
$clientDisclosure = old('client_disclosure', 'visible');
$payrollType = old('payroll_type', '');
?>

<div class="recruiter-post-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-plus-circle"></i> Recruiter posting</span>
            <h1 class="page-board-title">Post a Job</h1>
            <p class="page-board-subtitle">Create a clear role listing and control how AI interview screening applies to applicants.</p>
        </div>
    </div>

    <?php if(session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show recruiter-alert" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <?php if(session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show recruiter-alert" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="recruiter-form-layout">
        <div class="recruiter-form-main">
            <div class="card shadow-sm recruiter-form-card">
                <div class="card-body">
                    <form class="form-contact contact_form recruiter-job-form" method="post" action="<?= base_url('recruiter/post_job') ?>" id="jobForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="candidate_fee_allowed" value="0">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Posting For *</label>
                                    <select class="form-control" name="posted_for" id="posted_for" required>
                                        <option value="own_company" <?= $postedFor === 'own_company' ? 'selected' : '' ?>>Own company</option>
                                        <option value="client" <?= $postedFor === 'client' ? 'selected' : '' ?>>Client company</option>
                                    </select>
                                    <small class="text-muted">Consultancies should choose client company when hiring for a client.</small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Payroll Type</label>
                                    <select class="form-control" name="payroll_type" id="payroll_type">
                                        <option value="">Select payroll type</option>
                                        <option value="company_payroll" <?= $payrollType === 'company_payroll' ? 'selected' : '' ?>>Company payroll</option>
                                        <option value="client_payroll" <?= $payrollType === 'client_payroll' ? 'selected' : '' ?>>Client payroll</option>
                                        <option value="consultancy_payroll" <?= $payrollType === 'consultancy_payroll' ? 'selected' : '' ?>>Consultancy payroll</option>
                                        <option value="third_party_contract" <?= $payrollType === 'third_party_contract' ? 'selected' : '' ?>>Third-party contract</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Client Company Name</label>
                                    <input class="form-control" name="client_company_name" id="client_company_name" type="text" value="<?= old('client_company_name') ?>" placeholder="Client company name">
                                    <small class="text-muted">Required when posting for a client.</small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Client Disclosure</label>
                                    <select class="form-control" name="client_disclosure" id="client_disclosure">
                                        <option value="visible" <?= $clientDisclosure === 'visible' ? 'selected' : '' ?>>Visible to candidates</option>
                                        <option value="confidential" <?= $clientDisclosure === 'confidential' ? 'selected' : '' ?>>Confidential</option>
                                    </select>
                                    <small class="text-muted">Candidate fees are never allowed on this portal.</small>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Job Title *</label>
                                    <input class="form-control" name="title" id="title" type="text" value="<?= old('title') ?>" placeholder="Job Title" required>
                                    <small class="text-danger" id="title-error"></small>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Category *</label>
                                    <select class="form-control" name="category" id="category" required>
                                        <option value="">Select Job Category</option>
                                        <?php foreach ($jobCategoryOptions as $categoryOption): ?>
                                            <option value="<?= esc($categoryOption) ?>" <?= $selectedCategory === $categoryOption ? 'selected' : '' ?>>
                                                <?= esc($categoryOption) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-danger" id="category-error"></small>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Location *</label>
                                    <input class="form-control" name="location" id="location" type="text" value="<?= old('location') ?>" placeholder="Location" required>
                                    <small class="text-danger" id="location-error"></small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Description *</label>
                                    <textarea class="form-control w-100" name="description" id="description" cols="30" rows="9" placeholder="Job Description" required><?= old('description') ?></textarea>
                                    <small class="text-danger" id="description-error"></small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Experience</label>
                                    <input class="form-control" name="experience_level" id="experience_level" type="text" value="<?= old('experience_level') ?>" placeholder="Experience (e.g., 2-3 years)">
                                    <small class="text-danger" id="experience_level-error"></small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Employment Type</label>
                                    <select class="form-control" name="employment_type" id="employment_type">
                                        <option value="Full-time" <?= old('employment_type') === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                                        <option value="Part-time" <?= old('employment_type') === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                                        <option value="Contract" <?= old('employment_type') === 'Contract' ? 'selected' : '' ?>>Contract</option>
                                        <option value="Internship" <?= old('employment_type') === 'Internship' ? 'selected' : '' ?>>Internship</option>
                                    </select>
                                    <small class="text-danger" id="employment_type-error"></small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Salary Range</label>
                                    <input class="form-control" name="salary_range" id="salary_range" type="text" value="<?= old('salary_range') ?>" placeholder="Salary Range (e.g., 5-8 LPA)">
                                    <small class="text-danger" id="salary_range-error"></small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Application Deadline</label>
                                    <input class="form-control" name="application_deadline" id="application_deadline" type="date" value="<?= old('application_deadline') ?>" title="Application Deadline">
                                    <small class="text-muted">Application deadline (optional)</small>
                                    <small class="text-danger" id="application_deadline-error"></small>
                                </div>
                            </div>
                            <?php $selectedPolicy = old('ai_interview_policy', 'REQUIRED_HARD'); ?>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>AI Interview Policy</label>
                                    <select class="form-control" name="ai_interview_policy" id="ai_interview_policy">
                                        <option value="REQUIRED_HARD" <?= $selectedPolicy === 'REQUIRED_HARD' ? 'selected' : '' ?>>AI Interview: Mandatory (Strict)</option>
                                        <option value="REQUIRED_SOFT" <?= $selectedPolicy === 'REQUIRED_SOFT' ? 'selected' : '' ?>>AI Interview: Mandatory (Recruiter Can Override)</option>
                                        <option value="OPTIONAL" <?= $selectedPolicy === 'OPTIONAL' ? 'selected' : '' ?>>AI Interview: Optional</option>
                                        <option value="OFF" <?= $selectedPolicy === 'OFF' ? 'selected' : '' ?>>AI Interview: Not Required</option>
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        Choose how AI interview affects applications: strict reject, recruiter override, optional, or disabled.
                                    </small>
                                </div>
                            </div>
                            <div class="col-sm-6" id="minAiCutoffWrap">
                                <div class="form-group">
                                    <label>Minimum AI Cutoff Score</label>
                                    <input class="form-control" name="min_ai_cutoff_score" id="min_ai_cutoff_score" type="number" min="0" max="100" value="<?= old('min_ai_cutoff_score') ?>" placeholder="Minimum AI Cutoff Score">
                                    <small class="text-danger" id="min_ai_cutoff_score-error"></small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Number of Openings *</label>
                                    <input class="form-control" name="openings" id="openings" type="number" min="1" value="<?= old('openings', '1') ?>" placeholder="Number of Openings" required>
                                    <small class="text-danger" id="openings-error"></small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Required Skills</label>
                                    <input class="form-control" name="required_skills" id="required_skills" type="text" value="<?= old('required_skills') ?>" placeholder="Required Skills">
                                    <small class="text-danger" id="required_skills-error"></small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap: 10px;">
                                        <div>
                                            <label class="mb-0">Application Questionnaire</label>
                                            <small class="text-muted d-block">Add optional screening prompts. You can use this for a cover letter, notice period, motivation, or any short written response.</small>
                                        </div>
                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addCoverLetterQuestion">
                                                <i class="fas fa-file-alt mr-1"></i> Add Cover Letter Prompt
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addQuestionnaireRow">
                                                <i class="fas fa-plus mr-1"></i> Add Question
                                            </button>
                                        </div>
                                    </div>
                                    <div id="questionnaireBuilder"
                                         data-next-index="<?= count($questionnaireRows) ?>"
                                         data-initial-items="<?= esc(json_encode(array_values($questionnaireRows)), 'attr') ?>"></div>
                                    <small class="text-muted d-block mt-2">Candidates will answer these questions inside the Apply form.</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <button type="submit" class="button button-contactForm boxed-btn">Post Job</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="recruiter-form-side">
            <div class="card shadow-sm recruiter-form-card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-lightbulb"></i> Posting tips</h6>
                    <div class="recruiter-tip-list">
                        <div class="recruiter-tip-item">Use a precise title so matching works better.</div>
                        <div class="recruiter-tip-item">Add the core skills candidates should have on day one.</div>
                        <div class="recruiter-tip-item">Set the AI policy to match your screening process.</div>
                        <div class="recruiter-tip-item">Keep salary and deadline fields updated for trust.</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm recruiter-form-card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-shield-alt"></i> AI interview policy</h6>
                    <p class="text-muted mb-0">Strict and soft modes keep screening automated. Optional lets recruiters review more manually. OFF disables AI screening for the role.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const builder = document.getElementById('questionnaireBuilder');
    const addButton = document.getElementById('addQuestionnaireRow');
    const addCoverLetterButton = document.getElementById('addCoverLetterQuestion');

    if (!builder || !addButton || !addCoverLetterButton) {
        return;
    }

    let nextIndex = parseInt(builder.dataset.nextIndex || '0', 10) || 0;
    let initialItems = [];

    try {
        initialItems = JSON.parse(builder.dataset.initialItems || '[]');
    } catch (error) {
        initialItems = [];
    }

    function createRow(data) {
        const index = nextIndex++;
        const row = document.createElement('div');
        row.className = 'border rounded p-3 mb-3 questionnaire-row';
        row.innerHTML = `
            <div class="row">
                <input type="hidden" name="questionnaire[${index}][id]" value="${escapeHtml(data.id || '')}">
                <div class="col-md-5">
                    <label class="small text-muted">Question Prompt</label>
                    <input type="text" class="form-control" name="questionnaire[${index}][label]" maxlength="150" placeholder="e.g. Why are you a fit for this role?" value="${escapeHtml(data.label || '')}">
                </div>
                <div class="col-md-3">
                    <label class="small text-muted">Field Type</label>
                    <select class="form-control" name="questionnaire[${index}][type]">
                        <option value="textarea"${data.type === 'textarea' ? ' selected' : ''}>Long answer</option>
                        <option value="text"${data.type === 'text' ? ' selected' : ''}>Short answer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small text-muted">Placeholder</label>
                    <input type="text" class="form-control" name="questionnaire[${index}][placeholder]" maxlength="200" placeholder="Optional helper text" value="${escapeHtml(data.placeholder || '')}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-block js-remove-question">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="col-12 mt-2">
                    <div class="custom-control custom-checkbox">
                        <input type="hidden" name="questionnaire[${index}][required]" value="0">
                        <input type="checkbox" class="custom-control-input" id="questionnaire_required_${index}" name="questionnaire[${index}][required]" value="1"${data.required ? ' checked' : ''}>
                        <label class="custom-control-label" for="questionnaire_required_${index}">Required question</label>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <div class="custom-control custom-checkbox">
                        <input type="hidden" name="questionnaire[${index}][knockout]" value="0">
                        <input type="checkbox" class="custom-control-input js-knockout-toggle" id="questionnaire_knockout_${index}" name="questionnaire[${index}][knockout]" value="1"${data.knockout ? ' checked' : ''}>
                        <label class="custom-control-label" for="questionnaire_knockout_${index}">Knock-out must-have</label>
                    </div>
                    <div class="row mt-2 js-knockout-fields"${data.knockout ? '' : ' style="display: none;"'}>
                        <div class="col-md-7">
                            <label class="small text-muted">Expected answer</label>
                            <input type="text" class="form-control" name="questionnaire[${index}][knockout_answer]" maxlength="150" placeholder="e.g. Yes, Valid work visa, 30 days" value="${escapeHtml(data.knockout_answer || '')}">
                            <small class="text-muted">Use comma-separated values for alternatives, like Yes, Y.</small>
                        </div>
                        <div class="col-md-5">
                            <label class="small text-muted">Match type</label>
                            <select class="form-control" name="questionnaire[${index}][knockout_match]">
                                <option value="exact"${(data.knockout_match || 'exact') === 'exact' ? ' selected' : ''}>Exact answer</option>
                                <option value="contains"${data.knockout_match === 'contains' ? ' selected' : ''}>Answer contains</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
        builder.appendChild(row);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    addButton.addEventListener('click', function () {
        createRow({ type: 'textarea', required: false });
    });

    addCoverLetterButton.addEventListener('click', function () {
        createRow({
            label: 'Cover letter / Why are you a fit?',
            type: 'textarea',
            placeholder: 'Share why you are interested in this role and what makes you a strong fit.',
            required: true
        });
    });

    builder.addEventListener('click', function (event) {
        const button = event.target.closest('.js-remove-question');
        if (!button) {
            return;
        }

        const row = button.closest('.questionnaire-row');
        if (row) {
            row.remove();
        }
    });

    builder.addEventListener('change', function (event) {
        if (!event.target.classList.contains('js-knockout-toggle')) {
            return;
        }

        const row = event.target.closest('.questionnaire-row');
        const fields = row ? row.querySelector('.js-knockout-fields') : null;
        const required = row ? row.querySelector('[name$="[required]"][type="checkbox"]') : null;
        if (fields) {
            fields.style.display = event.target.checked ? '' : 'none';
        }
        if (required && event.target.checked) {
            required.checked = true;
        }
    });

    if (initialItems.length > 0) {
        initialItems.forEach(function (item) {
            createRow(item || {});
        });
    }
})();
</script>
<?= view('Layouts/recruiter_footer') ?>
    
