<?= view('Layouts/recruiter_header', ['title' => 'Company Profile']) ?>

<div class="recruiter-company-edit-jobboard">
<div class="container-fluid py-5">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php
    $companyName = (string) ($company['company_name'] ?? 'Company Profile');
    $companyIndustry = trim((string) ($company['industry'] ?? ''));
    $companyHq = trim((string) ($company['hq'] ?? ''));
    $companySize = trim((string) ($company['size'] ?? ''));
    $companyVisible = (int) ($company['contact_public'] ?? 0) === 1;
    $companyLogo = (string) ($company['logo'] ?? '');
    ?>

    <div class="page-board-header page-board-header-tight recruiter-page-board-header">
        <div class="page-board-copy">
            <span class="page-board-kicker"><i class="fas fa-briefcase"></i> Recruiter profile</span>
            <h1 class="page-board-title">Edit Company Profile</h1>
            <p class="page-board-subtitle">
                Keep your employer brand, visibility, and candidate-facing details current.
            </p>
            <div class="company-profile-meta">
                <span class="meta-chip"><strong><?= esc($companyName) ?></strong></span>
                <?php if ($companyIndustry !== ''): ?>
                    <span class="meta-chip"><?= esc($companyIndustry) ?></span>
                <?php endif; ?>
                <?php if ($companySize !== ''): ?>
                    <span class="meta-chip"><?= esc($companySize) ?></span>
                <?php endif; ?>
                <span class="meta-chip"><?= $companyVisible ? 'Public contact visible' : 'Contact hidden' ?></span>
            </div>
        </div>
    </div>

    <div class="company-edit-layout">
        <div class="company-edit-main">
            <div class="card shadow-sm company-edit-card">
                <div class="card-body">
                    <form method="post" action="<?= base_url('recruiter/company-profile') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="company_name" class="form-control" required value="<?= esc(old('company_name', $company['company_name'] ?? '')) ?>">
                        </div>

                        <div class="form-group">
                            <label>Company Logo</label>
                            <input type="file" name="company_logo" class="form-control" accept="image/*">
                            <?php if (!empty($company['logo'])): ?>
                                <small class="d-block mt-2">Current: <a href="<?= base_url($company['logo']) ?>" target="_blank">View logo</a></small>
                                <button type="submit" name="delete_logo" value="1" class="btn btn-sm btn-outline-danger mt-2" onclick="return confirm('Remove current company logo?')">
                                    Delete Logo
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Main Website</label>
                                <input type="url" name="company_website" class="form-control" placeholder="https://example.com" value="<?= esc(old('company_website', $company['website'] ?? '')) ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Careers Page <small class="text-muted">(where candidates apply)</small></label>
                                <input type="url" name="company_career_page" class="form-control" placeholder="https://example.com/careers" value="<?= esc(old('company_career_page', $company['career_page'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Industry</label>
                            <input type="text" name="company_industry" class="form-control" value="<?= esc(old('company_industry', $company['industry'] ?? '')) ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Company Size</label>
                                <select name="company_size" class="form-control">
                                    <?php $size = old('company_size', $company['size'] ?? ''); ?>
                                    <option value="">Select</option>
                                    <option value="1-10" <?= $size === '1-10' ? 'selected' : '' ?>>1-10</option>
                                    <option value="10-50" <?= $size === '10-50' ? 'selected' : '' ?>>10-50</option>
                                    <option value="50-200" <?= $size === '50-200' ? 'selected' : '' ?>>50-200</option>
                                    <option value="200-500" <?= $size === '200-500' ? 'selected' : '' ?>>200-500</option>
                                    <option value="500-1000" <?= $size === '500-1000' ? 'selected' : '' ?>>500-1000</option>
                                    <option value="1000+" <?= $size === '1000+' ? 'selected' : '' ?>>1000+</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>HQ Location</label>
                                <input type="text" name="company_hq" class="form-control" value="<?= esc(old('company_hq', $company['hq'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Branch Locations</label>
                            <textarea name="company_branches" class="form-control" rows="2" placeholder="City A, City B"><?= esc(old('company_branches', $company['branches'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Short Description</label>
                            <input type="text" name="company_short_description" class="form-control" value="<?= esc(old('company_short_description', $company['short_description'] ?? '')) ?>">
                        </div>

                        <div class="form-group">
                            <label>About Company</label>
                            <textarea name="company_what_we_do" class="form-control" rows="4"><?= esc(old('company_what_we_do', $company['what_we_do'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Social Profiles</label>
                            <p class="form-text text-muted mb-3">Add the company’s public social links. These will show on the company profile page.</p>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>LinkedIn</label>
                                    <input type="url" name="company_linkedin" class="form-control" placeholder="https://www.linkedin.com/company/..." value="<?= esc(old('company_linkedin', $company['linkedin'] ?? '')) ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Twitter / X</label>
                                    <input type="url" name="company_twitter" class="form-control" placeholder="https://x.com/..." value="<?= esc(old('company_twitter', $company['twitter'] ?? '')) ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label>Facebook</label>
                                    <input type="url" name="company_facebook" class="form-control" placeholder="https://facebook.com/..." value="<?= esc(old('company_facebook', $company['facebook'] ?? '')) ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Instagram</label>
                                    <input type="url" name="company_instagram" class="form-control" placeholder="https://instagram.com/..." value="<?= esc(old('company_instagram', $company['instagram'] ?? '')) ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>YouTube</label>
                                    <input type="url" name="company_youtube" class="form-control" placeholder="https://youtube.com/..." value="<?= esc(old('company_youtube', $company['youtube'] ?? '')) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Mission / Values (Optional)</label>
                            <textarea name="company_mission_values" class="form-control" rows="3"><?= esc(old('company_mission_values', $company['mission_values'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Culture / Work Environment</label>
                            <textarea name="company_culture_summary" class="form-control" rows="4" placeholder="Describe team culture, work style, collaboration, learning, and growth opportunities."><?= esc(old('company_culture_summary', $company['culture_summary'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Employee Benefits</label>
                            <textarea name="company_employee_benefits" class="form-control" rows="3" placeholder="Health insurance, flexible hours, bonus, remote work, learning budget"><?= esc(old('company_employee_benefits', $company['employee_benefits'] ?? '')) ?></textarea>
                            <small class="form-text text-muted">Separate benefits with commas or new lines.</small>
                        </div>

                        <div class="form-group">
                            <label>Workplace Photos</label>
                            <input type="file" name="company_brand_photos[]" class="form-control" accept="image/*" multiple>
                            <small class="form-text text-muted">Upload up to 6 office or team photos for employer branding.</small>
                            <?php
                            $existingPhotos = [];
                            $photosRaw = $company['workplace_photos'] ?? '';
                            if (is_string($photosRaw) && trim($photosRaw) !== '') {
                                $decodedPhotos = json_decode($photosRaw, true);
                                if (is_array($decodedPhotos)) {
                                    $existingPhotos = $decodedPhotos;
                                }
                            }
                            ?>
                            <?php if (!empty($existingPhotos)): ?>
                                <div class="row mt-3">
                                    <?php foreach ($existingPhotos as $photo): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="border rounded p-2 h-100">
                                                <img src="<?= base_url($photo) ?>" alt="Brand photo" style="width:100%;height:120px;object-fit:cover;border-radius:8px;">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="remove_brand_photos[]" value="<?= esc($photo) ?>" id="remove_<?= md5($photo) ?>">
                                                    <label class="form-check-label" for="remove_<?= md5($photo) ?>">Remove this photo</label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Office Tour Title</label>
                            <input type="text" name="company_office_tour_title" class="form-control" placeholder="e.g. Explore Our Bangalore Workspace" value="<?= esc(old('company_office_tour_title', $company['office_tour_title'] ?? '')) ?>">
                        </div>

                        <div class="form-group">
                            <label>Office Tour URL</label>
                            <input type="url" name="company_office_tour_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." value="<?= esc(old('company_office_tour_url', $company['office_tour_url'] ?? '')) ?>">
                            <small class="form-text text-muted">Use a YouTube, Vimeo, or hosted 360 tour link.</small>
                        </div>

                        <div class="form-group">
                            <label>Office Tour Summary</label>
                            <textarea name="company_office_tour_summary" class="form-control" rows="3" placeholder="Tell candidates what they will see in the workplace tour."><?= esc(old('company_office_tour_summary', $company['office_tour_summary'] ?? '')) ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>HR / Support Email</label>
                                <input type="email" name="company_contact_email" class="form-control" value="<?= esc(old('company_contact_email', $company['contact_email'] ?? '')) ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Phone (Optional)</label>
                                <input type="text" name="company_contact_phone" class="form-control" value="<?= esc(old('company_contact_phone', $company['contact_phone'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="form-group form-check">
                            <?php $visible = (int) old('company_contact_public', (string) ($company['contact_public'] ?? 0)); ?>
                            <input type="checkbox" class="form-check-input" id="contactPublic" name="company_contact_public" value="1" <?= $visible === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="contactPublic">Show contact info publicly to candidates</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Company Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="company-edit-side">
            <div class="card shadow-sm company-edit-card">
                <div class="card-body text-center">
                    <div class="company-edit-logo mb-3">
                        <?php if ($companyLogo !== ''): ?>
                            <img src="<?= base_url($companyLogo) ?>" alt="<?= esc($companyName) ?>">
                        <?php else: ?>
                            <span><?= strtoupper(substr($companyName, 0, 1) ?: 'C') ?></span>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1"><?= esc($companyName) ?></h5>
                    <p class="text-muted mb-2"><?= $companyIndustry !== '' ? esc($companyIndustry) : 'Industry not specified' ?></p>
                    <div class="company-profile-meta justify-content-center">
                        <span class="meta-chip"><?= $companyVisible ? 'Public contact on' : 'Public contact off' ?></span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm company-edit-card">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-check-circle"></i> Profile checklist</h6>
                    <div class="recruiter-checklist">
                        <div class="checklist-item"><span>Logo and brand assets uploaded</span></div>
                        <div class="checklist-item"><span>Industry and HQ set correctly</span></div>
                        <div class="checklist-item"><span>Tour, benefits, and culture updated</span></div>
                        <div class="checklist-item"><span>Contact visibility matches your policy</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?= view('Layouts/recruiter_footer') ?>
