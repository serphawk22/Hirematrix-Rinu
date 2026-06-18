        <?= view('Layouts/recruiter_header', ['title' => 'Edit Job']) ?>
            <style>
   /* ══════════════════════════════════════════
   PAGE WRAPPER & BASE
══════════════════════════════════════════ */
.recruiter-edit-jobboard {
    background: linear-gradient(135deg, #F4FBFA 0%, #EEF9F2 100%) !important;
    min-height: 100vh;
}
body.dark .recruiter-edit-jobboard,body.dark .card-body {
   background: #000000 !important;
    border: 1px solid #23343A !important;
}

/* ══════════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════════ */
.recruiter-edit-jobboard .page-board-header {
    background: transparent !important;
    border: none !important;
    margin-bottom: 1.5rem;
}

.recruiter-edit-jobboard .page-board-title {
    font-size: 26px !important;
    font-weight: 700 !important;
    color: #16212B !important;
    margin: 0;
}
body.dark .recruiter-edit-jobboard .page-board-title {
    color: #FFFFFF !important;
}

.recruiter-edit-jobboard .page-board-subtitle {
    color: #64748B !important;
    font-size: 1rem;
    margin-top: 0.4rem;
    margin-bottom: 0;
}
body.dark .recruiter-edit-jobboard .page-board-subtitle {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════════
   LAYOUT
══════════════════════════════════════════ */
.recruiter-edit-jobboard .recruiter-form-layout {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}
 
.recruiter-edit-jobboard .recruiter-form-main {
    flex: 1 1 0;
    min-width: 0;
}
.recruiter-edit-jobboard .recruiter-form-side {
    width: 380px !important;
    flex-shrink: 0;
}
@media (max-width: 768px) {
    .recruiter-edit-jobboard .recruiter-form-layout {
        flex-direction: column;
    }
    .recruiter-edit-jobboard .recruiter-form-side {
        width: 100%;
    }
}

/* ══════════════════════════════════════════
   CARDS
══════════════════════════════════════════ */
.recruiter-edit-jobboard .card,
.recruiter-edit-jobboard .recruiter-form-card {
    background: black !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 12px !important;
    box-shadow: none !important;
}
body.dark .recruiter-edit-jobboard .card,
body.dark .recruiter-edit-jobboard .recruiter-form-card {
background: #000000 !important;
    border: 1px solid #23343A !important; 
    box-shadow: none !important;
}

/* ══════════════════════════════════════════
   FORM LABELS
══════════════════════════════════════════ */
.recruiter-edit-jobboard label,
.recruiter-edit-jobboard .recruiter-job-form label {
    font-size: 1rem;
    font-weight: 500 !important;
    color: #16212B !important;
    margin-bottom: 6px;
    display: block;
    line-height: 1.5;
}
body.dark .recruiter-edit-jobboard label,
body.dark .recruiter-edit-jobboard .recruiter-job-form label {
    color: #FFFFFF !important;
}

/* Small label inside questionnaire rows */
.recruiter-edit-jobboard label.small,
.recruiter-edit-jobboard .small.text-muted {
    font-size: 0.85rem !important;
    font-weight: 500 !important;
    color: #64748B !important;
}
body.dark .recruiter-edit-jobboard label.small,
body.dark .recruiter-edit-jobboard .small.text-muted {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════════
   FORM CONTROLS (inputs, selects, textareas)
══════════════════════════════════════════ */
.recruiter-edit-jobboard .form-control {
    font-size: 1rem;
    background: #ffffff !important;
    color: #16212B !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 6px !important;
    transition: border-color 0.2s, box-shadow 0.2s;
}
body.dark .recruiter-edit-jobboard .form-control {
 background: #000000 !important; 
    color: #FFFFFF !important;
    border-color: #2E4A52 !important;
}

/* Placeholder */
.recruiter-edit-jobboard .form-control::placeholder {
    color: #94A3B8 !important;
}
body.dark .recruiter-edit-jobboard .form-control::placeholder {
    color: #FFFFFF !important;
}

/* Focus state */
.recruiter-edit-jobboard .form-control:focus,
.recruiter-edit-jobboard select.form-control:focus,
.recruiter-edit-jobboard textarea.form-control:focus {
    outline: 0 !important;
    box-shadow: none !important;
    border-color: #0D8A90 !important;
}
body.dark .recruiter-edit-jobboard .form-control:focus {
    border-color: #1FB7B5 !important;
}

/* Select arrow in dark mode */
body.dark .recruiter-edit-jobboard select.form-control {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%237A8B96' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 10px;
}

/* Small helper text under inputs */
.recruiter-edit-jobboard .form-group small.text-muted,
.recruiter-edit-jobboard small.text-muted {
    font-size: 0.82rem;
    color: #64748B !important;
    display: block;
    margin-top: 4px;
}
body.dark .recruiter-edit-jobboard .form-group small.text-muted,
body.dark .recruiter-edit-jobboard small.text-muted {
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════════
   FORM GROUP SPACING
══════════════════════════════════════════ */
.recruiter-edit-jobboard .form-group {
    margin-bottom: 1.25rem;
}

/* ══════════════════════════════════════════
   QUESTIONNAIRE BUILDER ROWS
══════════════════════════════════════════ */
.recruiter-edit-jobboard .questionnaire-row {
    background: #F8FDFB !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 8px !important;
    padding: 1rem !important;
    margin-bottom: 0.75rem !important;
}
body.dark .recruiter-edit-jobboard .questionnaire-row {
 background: #000000 !important;
    border: 1px solid #23343A !important;  
}

/* Checkbox labels inside questionnaire */
.recruiter-edit-jobboard .custom-control-label {
    font-size: 0.9rem !important;
    font-weight: 400 !important;
    color: #16212B !important;
    cursor: pointer;
}
body.dark .recruiter-edit-jobboard .custom-control-label {
    color: #FFFFFF !important;
}

/* Custom checkbox accent */
.recruiter-edit-jobboard .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #1FB7B5 !important;
    border-color: #1FB7B5 !important;
}
.recruiter-edit-jobboard .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 3px rgba(31, 183, 181, 0.15) !important;
}

/* ══════════════════════════════════════════
   BUTTONS
══════════════════════════════════════════ */

/* Primary */
.recruiter-edit-jobboard .btn-primary {
    background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}
.recruiter-edit-jobboard .btn-primary:hover,
.recruiter-edit-jobboard .btn-primary:focus {
     background:  #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
}

/* Outline secondary (Back to Jobs) */
.recruiter-edit-jobboard .btn-outline-secondary {
    background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}
.recruiter-edit-jobboard .btn-outline-secondary:hover {
    background: #F0FAF7 !important;
    color: #16212B !important;
    border-color: #1FB7B5 !important;
} 

/* Outline primary (Add Question) */
.recruiter-edit-jobboard .btn-outline-primary {
    background: transparent !important;
    border: 1.5px solid #1FB7B5 !important;
    color: #1FB7B5 !important;
    padding: 8px 20px;
    border-radius: 6px !important;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}
.recruiter-edit-jobboard .btn-outline-primary:hover {
       background:  #1FB7B5 !important;
    color: #ffffff !important;
    transform: translateY(-1px);
} 

/* Outline danger (Remove question) */
.recruiter-edit-jobboard .btn-outline-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 6px !important;
    font-size: 0.85rem;
    background: transparent !important;
    color: #DC2626 !important;
    border: 1.5px solid #FCA5A5 !important;
    transition: all 0.2s;
}
.recruiter-edit-jobboard .btn-outline-danger:hover {
    background: #FEE2E2 !important;
    border-color: #DC2626 !important;
    color: #991B1B !important;
}
body.dark .recruiter-edit-jobboard .btn-outline-danger {
    color: #FCA5A5 !important;
    border-color: #7F1D1D60 !important;
    background: transparent !important;
}
body.dark .recruiter-edit-jobboard .btn-outline-danger:hover {
    background: #7F1D1D30 !important;
    border-color: #FCA5A5 !important;
    color: #FCA5A5 !important;
}

/* ══════════════════════════════════════════
   TIPS / SIDEBAR
══════════════════════════════════════════ */
.recruiter-edit-jobboard .recruiter-form-side h6 {
    font-size: 1rem;
    font-weight: 600;
    color: #16212B !important;
    margin-bottom: 0.75rem;
}
body.dark .recruiter-edit-jobboard .recruiter-form-side h6 {
    color: #FFFFFF !important;
}

.recruiter-edit-jobboard .recruiter-tip-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.recruiter-edit-jobboard .recruiter-tip-item {
    background: #F0FAF7 !important;
    border: 1px solid #D9ECE5 !important;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 0.9rem;
    font-weight: 400 !important;
    color: #64748B !important;
    line-height: 1.5;
}
body.dark .recruiter-edit-jobboard .recruiter-tip-item {
    background: #000000 !important;
    border: 1px solid #23343A !important;  
    color: #FFFFFF !important;
}

/* ══════════════════════════════════════════
   ALERT
══════════════════════════════════════════ */
.recruiter-edit-jobboard .alert-danger {
    background: #FEE2E2 !important;
    border: 1px solid #FCA5A5 !important;
    color: #991B1B !important;
    border-radius: 8px;
    font-size: 1rem;
}
body.dark .recruiter-edit-jobboard .alert-danger {
    background: #7F1D1D30 !important;
    border-color: #7F1D1D60 !important;
    color: #FCA5A5 !important;
}
#editJobForm,.card-body{
   background: white !important; 
}
body.dark #editJobForm,body.dark .card-body{
   background: #000000 !important;
}
.card-body{
    border: 1px solid #D9ECE5;
    border-radius:8px;
}
 .container-fluid {
    max-width: 100% !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
}
</style>
<?php
$existingQuestionnaire = [];
$questionnaireRows = old('questionnaire');
if (!is_array($questionnaireRows)) {
    $decodedQuestionnaire = json_decode((string) ($job['application_questionnaire'] ?? ''), true);
    if (is_array($decodedQuestionnaire)) {
        $existingQuestionnaire = array_values(array_filter($decodedQuestionnaire, static fn ($row) => is_array($row)));
    }
    $questionnaireRows = $existingQuestionnaire;
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
$selectedCategory = (string) old('category', $job['category'] ?? '');
$hasCustomCategory = $selectedCategory !== '' && !in_array($selectedCategory, $jobCategoryOptions, true);
$postedFor = (string) old('posted_for', $job['posted_for'] ?? 'own_company');
$clientDisclosure = (string) old('client_disclosure', $job['client_disclosure'] ?? 'visible');
$payrollType = (string) old('payroll_type', $job['payroll_type'] ?? '');
?>

<div class="recruiter-edit-jobboard">
<div class="container-fluid py-5">
    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy"> 
            <h1 class="page-board-title">Edit Job</h1>
            <p class="page-board-subtitle">Update the role description, screening policy, and hiring details without changing the workflow.</p>
        </div>
        <div class="page-board-actions">
            <a href="<?= base_url('recruiter/jobs') ?>" class="btn btn-outline-primary">
                 Back to Jobs
            </a>
        </div>
    </div>

    <div class="recruiter-form-layout recruiter-edit-layout">
        <div class="recruiter-form-main">
            <div class="card shadow-sm recruiter-form-card" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-body">
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= esc(session()->getFlashdata('error')) ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('recruiter/jobs/update/' . $job['id']) ?>" method="post" id="editJobForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="candidate_fee_allowed" value="0">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Posting For *</label>
                                    <select name="posted_for" class="form-control" required>
                                        <option value="own_company" <?= $postedFor === 'own_company' ? 'selected' : '' ?>>Own company</option>
                                        <option value="client" <?= $postedFor === 'client' ? 'selected' : '' ?>>Client company</option>
                                    </select>
                                    <small class="text-muted">Consultancies should choose client company when hiring for a client.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payroll Type</label>
                                    <select name="payroll_type" class="form-control">
                                        <option value="">Select payroll type</option>
                                        <option value="company_payroll" <?= $payrollType === 'company_payroll' ? 'selected' : '' ?>>Company payroll</option>
                                        <option value="client_payroll" <?= $payrollType === 'client_payroll' ? 'selected' : '' ?>>Client payroll</option>
                                        <option value="consultancy_payroll" <?= $payrollType === 'consultancy_payroll' ? 'selected' : '' ?>>Consultancy payroll</option>
                                        <option value="third_party_contract" <?= $payrollType === 'third_party_contract' ? 'selected' : '' ?>>Third-party contract</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Client Company Name</label>
                                    <input type="text" name="client_company_name" class="form-control" value="<?= esc(old('client_company_name', $job['client_company_name'] ?? '')) ?>" placeholder="Client company name">
                                    <small class="text-muted">Required when posting for a client.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Client Disclosure</label>
                                    <select name="client_disclosure" class="form-control">
                                        <option value="visible" <?= $clientDisclosure === 'visible' ? 'selected' : '' ?>>Visible to candidates</option>
                                        <option value="confidential" <?= $clientDisclosure === 'confidential' ? 'selected' : '' ?>>Confidential</option>
                                    </select>
                                    <small class="text-muted">Candidate fees are never allowed on this portal.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Job Title *</label>
                            <input type="text" name="title" class="form-control" value="<?= esc(old('title', $job['title'])) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" class="form-control" required>
                                <option value="">Select Job Category</option>
                                <?php if ($hasCustomCategory): ?>
                                    <option value="<?= esc($selectedCategory) ?>" selected>
                                        <?= esc($selectedCategory) ?> (Current)
                                    </option>
                                <?php endif; ?>
                                <?php foreach ($jobCategoryOptions as $categoryOption): ?>
                                    <option value="<?= esc($categoryOption) ?>" <?= $selectedCategory === $categoryOption ? 'selected' : '' ?>>
                                        <?= esc($categoryOption) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="description" class="form-control" rows="5" required><?= esc(old('description', $job['description'])) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Location *</label>
                                    <input type="text" name="location" class="form-control" value="<?= esc(old('location', $job['location'])) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Experience Level</label>
                                    <input type="text" name="experience_level" class="form-control" value="<?= esc(old('experience_level', $job['experience_level'] ?? '')) ?>" placeholder="e.g., 2-3 years">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Employment Type</label>
                                    <?php $employmentType = old('employment_type', $job['employment_type'] ?? 'Full-time'); ?>
                                    <select name="employment_type" class="form-control">
                                        <option value="Full-time" <?= $employmentType === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                                        <option value="Part-time" <?= $employmentType === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                                        <option value="Contract" <?= $employmentType === 'Contract' ? 'selected' : '' ?>>Contract</option>
                                        <option value="Internship" <?= $employmentType === 'Internship' ? 'selected' : '' ?>>Internship</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Salary Range</label>
                                    <input type="text" name="salary_range" class="form-control" value="<?= esc(old('salary_range', $job['salary_range'] ?? '')) ?>" placeholder="e.g., 5-8 LPA">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Application Deadline</label>
                            <input type="date" name="application_deadline" class="form-control" value="<?= esc(old('application_deadline', $job['application_deadline'] ?? '')) ?>">
                        </div>

                        <div class="form-group">
                            <label>Required Skills *</label>
                            <input type="text" name="required_skills" class="form-control" value="<?= esc(old('required_skills', $job['required_skills'])) ?>" required>
                            <small class="text-muted">Comma separated (e.g., PHP, MySQL, JavaScript)</small>
                        </div>

                        <div class="form-group">
                            <label>Number of Openings *</label>
                            <input type="number" name="openings" class="form-control" value="<?= esc(old('openings', $job['openings'])) ?>" required min="1">
                        </div>

                        <div class="form-group">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap: 10px;">
                                <div>
                                    <label class="mb-0">Application Questionnaire</label>
                                    <small class="text-muted d-block">Recruiters can collect a cover letter, motivation note, availability, or other written screening answers.</small>
                                </div>
                                <div class="d-flex flex-wrap" style="gap: 8px;">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addCoverLetterQuestion">
                                        Add Cover Letter Prompt
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addQuestionnaireRow">
                                        Add Question
                                    </button>
                                </div>
                            </div>
                            <div id="questionnaireBuilder"
                                 data-next-index="<?= count($questionnaireRows) ?>"
                                 data-initial-items="<?= esc(json_encode(array_values($questionnaireRows)), 'attr') ?>"></div>
                        </div>

                        <div class="form-group">
                            <?php $policy = strtoupper(old('ai_interview_policy', $job['ai_interview_policy'] ?? 'REQUIRED_HARD')); ?>
                            <label>AI Interview Policy *</label>
                            <select name="ai_interview_policy" id="ai_interview_policy" class="form-control">
                                <option value="REQUIRED_HARD" <?= $policy === 'REQUIRED_HARD' ? 'selected' : '' ?>>Required Hard (strict)</option>
                                <option value="REQUIRED_SOFT" <?= $policy === 'REQUIRED_SOFT' ? 'selected' : '' ?>>Required Soft (recruiter override)</option>
                                <option value="OPTIONAL" <?= $policy === 'OPTIONAL' ? 'selected' : '' ?>>Optional</option>
                                <option value="OFF" <?= $policy === 'OFF' ? 'selected' : '' ?>>Off</option>
                            </select>
                        </div>

                        <div class="form-group" id="minAiCutoffWrap">
                            <label>Minimum AI Cutoff Score</label>
                            <input type="number" name="min_ai_cutoff_score" id="min_ai_cutoff_score" class="form-control" min="0" max="100" value="<?= esc(old('min_ai_cutoff_score', $job['min_ai_cutoff_score'] ?? '')) ?>" placeholder="0 to 100">
                            <small class="text-muted">Required if AI interview policy is not OFF.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                             Update Job
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="recruiter-form-side">
            <div class="card shadow-sm recruiter-form-card" style="border-radius: 20px !important;overflow: hidden;">
                <div class="card-body">
                    <h6 class="mb-3"> Quick notes</h6>
                    <div class="recruiter-tip-list">
                        <div class="recruiter-tip-item">Keep the title and category aligned for search results.</div>
                        <div class="recruiter-tip-item">Use the policy selector to control AI screening behavior.</div>
                        <div class="recruiter-tip-item">Update the deadline and openings before reopening a role.</div>
                        <div class="recruiter-tip-item">Refine required skills to improve matching quality.</div>
                    </div>
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
    
