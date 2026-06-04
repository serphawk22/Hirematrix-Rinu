<?= view('Layouts/candidate_header', ['title' => 'Job Details']) ?>
<?php
$companyRefId = (int) ($job['company_id'] ?? 0);
if ($companyRefId <= 0) {
    $companyRefId = (int) ($job['recruiter_id'] ?? 0);
}
$isExternalJob = (int) ($job['is_external'] ?? 0) === 1;
$externalSource = trim((string) ($job['external_source'] ?? ''));
$externalApplyUrl = trim((string) ($job['external_apply_url'] ?? ''));
$hasExternalApplyUrl = $externalApplyUrl !== '' && filter_var($externalApplyUrl, FILTER_VALIDATE_URL) !== false;
$companyProfileUrl = (!$isExternalJob && $companyRefId > 0) ? base_url('company/' . $companyRefId) : '#';
$isSaved = (bool) ($isSaved ?? false);
$company = is_array($company ?? null) ? $company : [];
$brandingPhotos = [];
$brandingPhotosRaw = $company['workplace_photos'] ?? '';
if (is_string($brandingPhotosRaw) && trim($brandingPhotosRaw) !== '') {
    $decodedBrandingPhotos = json_decode($brandingPhotosRaw, true);
    if (is_array($decodedBrandingPhotos)) {
        $brandingPhotos = array_values(array_filter(array_map('strval', $decodedBrandingPhotos)));
    }
}
$resolveAssetUrl = static function (string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
        return $path;
    }
    return base_url(ltrim($path, '/'));
};
$benefitsRaw = trim((string) ($company['employee_benefits'] ?? ''));
$benefits = [];
if ($benefitsRaw !== '') {
    $benefits = preg_split('/[\r\n,]+/', $benefitsRaw) ?: [];
    $benefits = array_values(array_filter(array_map('trim', $benefits)));
}
$cultureSummary = trim((string) ($company['culture_summary'] ?? ''));
$resumeCoach = is_array($resumeCoach ?? null) ? $resumeCoach : [];
$invitation = is_array($invitation ?? null) ? $invitation : [];
$applicationQuestionnaire = is_array($applicationQuestionnaire ?? null) ? $applicationQuestionnaire : [];
$successFlash = session()->getFlashdata('success');
$errorFlash = session()->getFlashdata('error');
$careerSuggestion = session()->getFlashdata('career_suggestion');
$skills = array_filter(array_map('trim', explode(',', (string) ($job['required_skills'] ?? ''))));
$jobTypeLabel = ucwords(str_replace('-', ' ', (string) ($job['employment_type'] ?? 'Full Time')));
$jobLocation = (string) ($job['location'] ?? 'Not specified');
$jobCompany = (string) ($job['company'] ?? 'Company');
$jobCategory = trim((string) ($job['category'] ?? ''));
$jobExperience = (string) ($job['experience_level'] ?? 'Not specified');
$jobSalary = trim((string) ($job['salary_range'] ?? ''));
$jobDeadline = trim((string) ($job['application_deadline'] ?? ''));
$jobOpenings = trim((string) ($job['openings'] ?? ''));
$jobPublished = !empty($job['created_at']) ? date('d M Y', strtotime((string) $job['created_at'])) : null;
$companyInitial = strtoupper(substr($jobCompany, 0, 1) ?: 'C');

$summaryRows = [
    ['Published on', $jobPublished ?: 'Not specified'],
    ['Company', $jobCompany],
    ['Employment', $jobTypeLabel],
    ['Experience', $jobExperience],
    ['Location', $jobLocation],
];
if ($jobCategory !== '') {
    $summaryRows[] = ['Category', $jobCategory];
}
if ($jobSalary !== '') {
    $summaryRows[] = ['Salary Range', $jobSalary];
}
if ($jobDeadline !== '') {
    $summaryRows[] = ['Deadline', date('d M Y', strtotime($jobDeadline))];
}
if ($jobOpenings !== '') {
    $summaryRows[] = ['Openings', $jobOpenings];
}
if ($isExternalJob) {
    $summaryRows[] = ['Listing Type', 'Remote Listing'];
}

$policyRaw = strtoupper($job['ai_interview_policy'] ?? 'REQUIRED_HARD');
$policyMap = [
    'OFF' => [
        'title' => 'AI Interview: Not Required',
        'desc' => 'You can apply directly. No AI round is needed for this job.',
        'class' => 'ai-policy-off',
        'icon' => 'fas fa-check-circle',
    ],
    'OPTIONAL' => [
        'title' => 'AI Interview: Optional',
        'desc' => 'Optional AI round is available and may improve your visibility.',
        'class' => 'ai-policy-optional',
        'icon' => 'fas fa-lightbulb',
    ],
    'REQUIRED_SOFT' => [
        'title' => 'AI Interview: Required + Recruiter Review',
        'desc' => 'AI round is required, and recruiter can still make the final decision.',
        'class' => 'ai-policy-soft',
        'icon' => 'fas fa-user-check',
    ],
    'REQUIRED_HARD' => [
        'title' => 'AI Interview: Mandatory Screening',
        'desc' => 'AI interview is mandatory and works as the primary screening gate.',
        'class' => 'ai-policy-hard',
        'icon' => 'fas fa-shield-alt',
    ],
];
$policy = $policyMap[$policyRaw] ?? $policyMap['REQUIRED_HARD'];
?>

<div class="job-details-jobboard">
    <div class="container">
        <div class="job-details-page-header">
            <div class="page-board-copy">
                <span class="page-board-kicker"><i class="fas fa-briefcase"></i> Job details</span>
                <?php if ($isExpired): ?>
    <div class="badge-deadline-passed">
        <i class="fas fa-history"></i> Application Deadline Passed
    </div>
<?php endif; ?>

                <h1 class="page-board-title"><?= esc($job['title']) ?></h1>
                <p class="page-board-subtitle">
                    <?= $isExternalJob
                        ? 'This role was imported from an external source. Review details and apply on the original site.'
                        : 'Review the role, AI screening policy, and company summary before applying.' ?>
                </p>
                <div class="job-details-header-meta">
                    <span class="meta-chip"><i class="fas fa-building"></i> <?= esc($jobCompany) ?></span>
                    <span class="meta-chip"><i class="fas fa-map-pin"></i> <?= esc($jobLocation) ?></span>
                    <span class="meta-chip"><i class="fas fa-clock"></i> <?= esc($jobTypeLabel) ?></span>
                    <?php if ($isExternalJob): ?>
                        <span class="meta-chip"><i class="fas fa-globe"></i> Remote · Verified Listing</span>
                    <?php endif; ?>
                    <?php if ($jobSalary !== ''): ?>
                        <span class="meta-chip"><i class="fas fa-rupee-sign"></i> <?= esc($jobSalary) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="job-details-header-actions">
                <?php if (!$isExternalJob): ?>
                    <a href="<?= esc($companyProfileUrl) ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-store mr-1"></i> Company Profile
                    </a>
                <?php endif; ?>
                <a href="<?= base_url($isSaved ? 'job/unsave/' . $job['id'] : 'job/save/' . $job['id']) ?>"
                   class="btn btn-outline-secondary js-save-job-toggle"
                   data-save-url="<?= base_url($isSaved ? 'job/unsave/' . $job['id'] : 'job/save/' . $job['id']) ?>"
                   data-job-id="<?= (int) $job['id'] ?>"
                   data-saved="<?= $isSaved ? '1' : '0' ?>"
                   data-save-label-save="Save Job"
                   data-save-label-saved="Saved">
                    <span class="js-save-icon <?= $isSaved ? 'fas' : 'far' ?> fa-bookmark mr-1"></span><span class="js-save-label"><?= $isSaved ? 'Saved' : 'Save Job' ?></span>
                </a>
                <div id="jobDetailsTopAction">
                    <?php if ($isExternalJob && $hasExternalApplyUrl): ?>
                        <a href="<?= esc($externalApplyUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                            <i class="fas fa-up-right-from-square mr-1"></i> Apply On Source
                        </a>
                    <?php elseif ($alreadyApplied): ?>
                        <button class="btn job-details-applied-btn js-job-details-top-applied" disabled>
                            <i class="fas fa-check mr-1"></i> Already Applied
                        </button>
                        <button type="button" class="btn btn-info ml-2" onclick="generateCoverLetter(<?= (int) $job['id'] ?>)">
                            <i class="fas fa-magic mr-1"></i> AI Cover Letter
                        
                    <?php else: ?>
                        <a href="#apply-job" class="btn btn-primary js-job-details-apply-link">
                            <i class="fas fa-paper-plane mr-1"></i> Apply Now
                        </a>
                        <button type="button" class="btn btn-info ml-2" onclick="generateCoverLetter(<?= (int) $job['id'] ?>)">
                            <i class="fas fa-magic mr-1"></i> AI Cover Letter
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>

        <?php if (ENVIRONMENT !== 'production'): // Debugging client company visibility ?>
            <div class="alert alert-info mt-3">
                <strong>Debug Info:</strong> Job Company: <?= esc($job['company'] ?? 'N/A') ?>, Posted For: <?= esc($job['posted_for'] ?? 'N/A') ?>, Client Disclosure: <?= esc($job['client_disclosure'] ?? 'N/A') ?>, Client Company Name: <?= esc($job['client_company_name'] ?? 'N/A') ?>
            </div>
        <?php endif; ?>

    </div>

    <section class="site-section pt-0 content-wrap">
        <div class="container">
            <div id="candidateApplicationAjaxAlert"></div>
            <?php if ($successFlash): ?>
                <div class="alert alert-success">
                    <?= esc($successFlash) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorFlash): ?>
                <div class="alert alert-danger">
                    <?= esc($errorFlash) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($invitation)): ?>
                <?php
                $inviteStatus = (string) ($invitation['status'] ?? 'sent');
                $inviteHeading = $inviteStatus === 'applied' ? 'You already responded to this invitation' : 'You were personally invited to apply';
                $inviteMeta = trim((string) ($invitation['recruiter_name'] ?? 'A recruiter'));
                ?>
                <div class="job-invite-banner">
                    <div class="job-invite-banner__eyebrow">Direct recruiter signal</div>
                    <div class="job-invite-banner__content">
                        <div>
                            <h3><?= esc($inviteHeading) ?></h3>
                            <p><?= esc((string) ($invitation['message'] ?? 'A recruiter believes your profile aligns with this role.')) ?></p>
                            <div class="job-invite-banner__meta">
                                <span><i class="fas fa-user-tie"></i> <?= esc($inviteMeta) ?></span>
                                <span><i class="fas fa-building"></i> <?= esc($jobCompany) ?></span>
                                <span><i class="fas fa-briefcase"></i> <?= esc((string) ($job['title'] ?? 'Job')) ?></span>
                            </div>
                        </div>
                        <div class="job-invite-banner__badge is-<?= esc($inviteStatus) ?>">
                            <?= esc(ucfirst($inviteStatus)) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="job-details-layout">
                <div class="job-details-main">
                    <?php if (!$alreadyApplied && !$isExternalJob && !empty($resumeCoach)): ?>
                        <div class="resume-coach-card">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                <div>
                                    <h3 class="h5 mb-1 text-primary"><i class="fas fa-magic mr-2"></i>Job-specific Resume Coach</h3>
                                    <p class="text-muted mb-0">See how well your current resume/profile aligns with this job before you apply.</p>
                                </div>
                                <div class="text-right">
                                    <div class="resume-coach-score"><?= (int) ($resumeCoach['score'] ?? 0) ?></div>
                                    <small class="text-muted">ATS readiness</small>
                                </div>
                            </div>

                            <?php if (!empty($resumeCoach['resume_version']['title'])): ?>
                                <div class="alert alert-light border mb-3">
                                    Using resume version: <strong><?= esc($resumeCoach['resume_version']['title']) ?></strong>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($resumeCoach['matched_skills'])): ?>
                                <div class="mb-3">
                                    <div class="small text-uppercase font-weight-bold text-muted mb-2">Matched Skills</div>
                                    <div class="resume-coach-chip-list">
                                        <?php foreach (array_slice((array) $resumeCoach['matched_skills'], 0, 6) as $skill): ?>
                                            <span class="resume-coach-chip match"><?= esc($skill) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($resumeCoach['missing_skills'])): ?>
                                <div class="mb-3">
                                    <div class="small text-uppercase font-weight-bold text-muted mb-2">Missing or Weak Keywords</div>
                                    <div class="resume-coach-chip-list">
                                        <?php foreach (array_slice((array) $resumeCoach['missing_skills'], 0, 6) as $skill): ?>
                                            <span class="resume-coach-chip missing"><?= esc($skill) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <div class="small text-uppercase font-weight-bold text-muted mb-2">Suggested Summary Direction</div>
                                <p class="mb-0"><?= esc((string) ($resumeCoach['summary_suggestion'] ?? 'Tailor your resume summary to this role.')) ?></p>
                            </div>

                            <?php if (!empty($resumeCoach['suggestions'])): ?>
                                <div class="mb-3">
                                    <div class="small text-uppercase font-weight-bold text-muted mb-2">What to Improve</div>
                                    <ul class="resume-coach-suggestion-list">
                                        <?php foreach ((array) $resumeCoach['suggestions'] as $suggestion): ?>
                                            <li><?= esc($suggestion) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <a href="<?= esc((string) ($resumeCoach['resume_studio_url'] ?? base_url('candidate/resume-studio'))) ?>" class="btn btn-primary">
                                <i class="fas fa-file-alt mr-1"></i> Improve Resume for This Job
                            </a>
                        </div>
                    <?php elseif ($alreadyApplied): ?>
                        <div class="resume-coach-card">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                <div>
                                    <h3 class="h5 mb-1 text-primary"><i class="fas fa-check-circle mr-2"></i>Application Already Submitted</h3>
                                    <p class="text-muted mb-0">Your focus should now move from resume tailoring to interview readiness and application follow-up.</p>
                                </div>
                                <span class="badge badge-light border px-3 py-2">Post-application guidance</span>
                            </div>

                            <div class="mb-3">
                                <div class="small text-uppercase font-weight-bold text-muted mb-2">What to Do Next</div>
                                <ul class="resume-coach-suggestion-list">
                                    <li>Review your submitted application status and recruiter activity from the applications page.</li>
                                    <li>Use the mock interview coach to practice role-specific questions for this job.</li>
                                    <li>Keep your project stories and examples aligned with the resume version you used for this application.</li>
                                </ul>
                            </div>

                            <div class="d-flex flex-wrap candidate-flex-gap-10">
                                <a href="<?= base_url('candidate/applications') ?>" class="btn btn-primary">
                                    <i class="fas fa-briefcase mr-1"></i> View My Application
                                </a>
                                <?php if (!empty($application['id'])): ?>
                                    <a href="<?= base_url('candidate/applications/' . (int) $application['id'] . '/mock-interview') ?>" class="btn btn-outline-primary job-details-mock-btn">
                                        <i class="fas fa-comments mr-1"></i> Open Mock Interview
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="detail-card">
                        <div class="detail-card-title">
                            <span class="detail-card-icon"><i class="fas fa-align-left"></i></span>
                            <span>Job Description</span>
                        </div>
                        <div class="job-details-section-text">
                            <p><?= nl2br(esc($job['description'])) ?></p>
                        </div>
                    </div>

                    <div class="detail-card">
                        <div class="detail-card-title">
                            <span class="detail-card-icon"><i class="fas fa-rocket"></i></span>
                            <span>Required Knowledge, Skills, and Abilities</span>
                        </div>
                        <?php if (!empty($skills)): ?>
                            <div class="job-details-chip-list">
                                <?php foreach ($skills as $skill): ?>
                                    <span class="summary-chip"><?= esc($skill) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="job-details-section-text mb-0">Skills not specified.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($cultureSummary !== '' || !empty($benefits) || !empty($brandingPhotos)): ?>
                        <div class="detail-card">
                            <div class="detail-card-title">
                                <span class="detail-card-icon"><i class="fas fa-building"></i></span>
                                <span>Company Snapshot</span>
                            </div>
                            <?php if ($cultureSummary !== ''): ?>
                                <p class="job-details-section-text"><?= esc($cultureSummary) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($benefits)): ?>
                                <div class="job-details-chip-list">
                                    <?php foreach (array_slice($benefits, 0, 6) as $benefit): ?>
                                        <span class="summary-chip"><?= esc($benefit) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($brandingPhotos)): ?>
                                <div class="job-details-gallery">
                                    <?php foreach (array_slice($brandingPhotos, 0, 3) as $photo): ?>
                                        <img src="<?= esc($resolveAssetUrl($photo)) ?>" alt="<?= esc($jobCompany) ?>" loading="lazy">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-card" id="apply-job">
                        <div class="detail-card-title">
                            <span class="detail-card-icon"><i class="fas fa-paper-plane"></i></span>
                            <span>Apply</span>
                        </div>
                        <?php if ($isExternalJob): ?>
                            <?php if ($hasExternalApplyUrl): ?>
                                <a href="<?= esc($externalApplyUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-block btn-primary btn-md">
                                    <i class="fas fa-up-right-from-square mr-2"></i>Apply on Company Site
                                </a>
                                <small class="text-muted d-block mt-2">This listing is sourced externally. Applications are completed on the source website.</small>
                            <?php else: ?>
                                <button type="button" class="btn btn-block btn-outline-secondary btn-md" disabled>
                                    External apply link unavailable
                                </button>
                            <?php endif; ?>
                        <?php elseif ($alreadyApplied): ?>
                            <button class="btn btn-block btn-outline-primary btn-md job-details-applied-btn" disabled data-application-state="applied">
                                <span class="icon-check mr-2"></span>Already Applied
                            </button>
                            <?php elseif ($isExpired): ?>
        <button class="btn btn-secondary btn-md job-details-applied-btn" disabled title="The deadline for this position has passed">
            <i class="fas fa-ban mr-1"></i> Applications Closed
        </button>

                        <?php else: ?>
                            <form method="post" action="<?= base_url('job/apply/' . $job['id']) ?>" class="js-apply-job-form" data-job-id="<?= (int) $job['id'] ?>">
                                <?= csrf_field() ?>
                                <?php if (!empty($applicationQuestionnaire)): ?>
                                    <div class="mb-3 text-left">
                                        <div class="small text-uppercase font-weight-bold text-muted mb-2">Additional Questions</div>
                                        <?php foreach ($applicationQuestionnaire as $question): ?>
                                            <?php
                                            $questionId = (string) ($question['id'] ?? '');
                                            $questionType = (string) ($question['type'] ?? 'textarea');
                                            $questionLabel = (string) ($question['label'] ?? 'Question');
                                            $questionPlaceholder = (string) ($question['placeholder'] ?? '');
                                            $questionRequired = !empty($question['required']);
                                            ?>
                                            <div class="form-group">
                                                <label for="questionnaire_<?= esc($questionId) ?>" class="font-weight-bold">
                                                    <?= esc($questionLabel) ?><?= $questionRequired ? ' *' : '' ?>
                                                    <?php if (!empty($question['knockout'])): ?>
                                                        <span class="badge badge-warning ml-1">Must-have</span>
                                                    <?php endif; ?>
                                                </label>
                                                <?php if ($questionType === 'text'): ?>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="questionnaire_<?= esc($questionId) ?>"
                                                        name="questionnaire_response[<?= esc($questionId) ?>]"
                                                        value="<?= esc(old('questionnaire_response.' . $questionId)) ?>"
                                                        placeholder="<?= esc($questionPlaceholder) ?>"
                                                        <?= $questionRequired ? 'required' : '' ?>>
                                                <?php else: ?>
                                                    <textarea
                                                        class="form-control"
                                                        id="questionnaire_<?= esc($questionId) ?>"
                                                        name="questionnaire_response[<?= esc($questionId) ?>]"
                                                        rows="<?= stripos($questionLabel, 'cover letter') !== false ? '6' : '4' ?>"
                                                        placeholder="<?= esc($questionPlaceholder) ?>"
                                                        <?= $questionRequired ? 'required' : '' ?>
                                                    ><?= esc(old('questionnaire_response.' . $questionId)) ?></textarea>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-block btn-primary btn-md">Apply Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="job-details-side">
                    <div class="summary-card policy-card">
                        <div class="ai-policy-card <?= esc($policy['class']) ?>">
                            <span class="ai-policy-icon"><i class="<?= esc($policy['icon']) ?>"></i></span>
                            <div>
                                <div class="ai-policy-title"><?= esc($policy['title']) ?></div>
                                <p class="ai-policy-desc"><?= esc($policy['desc']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="detail-card-title mb-3">
                            <span class="detail-card-icon"><i class="fas fa-list"></i></span>
                            <span>Job Summary</span>
                        </div>
                        <ul class="job-details-summary-list">
                            <?php foreach ($summaryRows as [$label, $value]): ?>
                                <li>
                                    <strong><?= esc($label) ?></strong>
                                    <span><?= esc($value) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="summary-card">
                        <div class="detail-card-title mb-3">
                            <span class="detail-card-icon"><i class="fas fa-store"></i></span>
                            <span>Company</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="job-details-logo mr-3"><?= esc($companyInitial) ?></div>
                            <div>
                                <div class="font-weight-bold"><?= esc($jobCompany) ?></div>
                                <?php if (!$isExternalJob): ?>
                                    <a href="<?= esc($companyProfileUrl) ?>" class="small">View company profile</a>
                                <?php elseif ($hasExternalApplyUrl): ?>
                                    <a href="<?= esc($externalApplyUrl) ?>" target="_blank" rel="noopener noreferrer" class="small">View source listing</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($benefits)): ?>
                            <div class="job-details-chip-list">
                                <?php foreach (array_slice($benefits, 0, 4) as $benefit): ?>
                                    <span class="summary-chip"><?= esc($benefit) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($careerSuggestion): ?>
                        <div class="summary-card career-card">
                            <h3 class="text-primary h5 mb-2"><i class="fas fa-rocket mr-2"></i>Career Transition Opportunity</h3>
                            <p class="small mb-3"><?= esc($careerSuggestion['message']) ?></p>
                            <a href="<?= base_url('career-transition') ?>" class="btn btn-sm btn-primary btn-block">
                                <i class="fas fa-graduation-cap mr-1"></i> Get Learning Roadmap
                            </a>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </section>
</div>

<!-- AI Cover Letter Modal -->
<div class="modal fade" id="coverLetterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-magic mr-2"></i>AI Cover Letter Draft</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                <div id="coverLetterLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 font-weight-bold">Our AI is analyzing the job and your profile...</p>
                </div>
                <div id="coverLetterContent">
                    <div class="form-group">
                        <label class="font-weight-bold small text-muted text-uppercase">Targeting:</label>
                        <div id="jobTargetDisplay" class="h6 font-weight-bold"></div>
                        <hr>
                        <textarea id="coverLetterTextArea" class="form-control border-0 shadow-none candidate-cover-letter-input" rows="15"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-dismiss="modal">Discard</button>
                <button type="button" class="btn btn-primary px-4" id="copyLetterBtn" onclick="copyCoverLetter()">
                    <i class="far fa-copy mr-1"></i> Copy to Clipboard
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function generateCoverLetter(jobId) {
    const modal = $('#coverLetterModal');
    const contentArea = $('#coverLetterContent');
    const loadingArea = $('#coverLetterLoading');
    const textArea = $('#coverLetterTextArea');
    const targetDisplay = $('#jobTargetDisplay');

    textArea.val('');
    contentArea.addClass('d-none');
    loadingArea.removeClass('d-none');
    modal.modal('show');

    try {
        const response = await fetch(`<?= base_url('candidate/generate-ai-cover-letter') ?>?job_id=${jobId}`);
        const data = await response.json();

        if (data.success) {
            targetDisplay.text(`${data.job_title} at ${data.company}`);
            textArea.val(data.cover_letter);
            loadingArea.addClass('d-none');
            contentArea.removeClass('d-none');
        } else {
            alert('Error: ' + (data.error || 'Failed to generate cover letter'));
            modal.modal('hide');
        }
    } catch (error) {
        console.error('AI Error:', error);
        modal.modal('hide');
    }
}

function copyCoverLetter() {
    const textArea = document.getElementById('coverLetterTextArea');
    const btn = document.getElementById('copyLetterBtn');
    
    textArea.select();
    document.execCommand('copy');
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check mr-1"></i> Copied!';
    btn.className = 'btn btn-success px-4';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.className = 'btn btn-primary px-4';
    }, 2000);
}
</script>

<?= view('Layouts/candidate_footer') ?>

