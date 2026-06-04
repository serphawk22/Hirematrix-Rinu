<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\CompanyReviewModel;
use App\Models\JobModel;
use App\Models\UserModel;
use App\Libraries\TargetCompanyJobService;

class CompanyProfile extends BaseController
{
    private const MAX_BRANDING_PHOTOS = 6;

    public function index()
    {
        $companyModel = new CompanyModel();
        $jobModel = new JobModel();

        $query = trim((string) $this->request->getGet('q'));
        $industry = trim((string) $this->request->getGet('industry'));
        $location = trim((string) $this->request->getGet('location'));
        $limit = max(1, min(100, (int) ($this->request->getGet('limit') ?: 10)));

        $builder = $companyModel
            ->select('companies.*, COUNT(DISTINCT jobs.id) as open_jobs_count')
            ->join('jobs', "jobs.company_id = companies.id AND jobs.status = 'open'", 'left')
            ->groupBy('companies.id')
            ->orderBy('open_jobs_count', 'DESC')
            ->orderBy('companies.name', 'ASC');

        if ($query !== '') {
            $builder->groupStart()
                ->like('companies.name', $query)
                ->orLike('companies.short_description', $query)
                ->orLike('companies.what_we_do', $query)
                ->groupEnd();
        }

        if ($industry !== '') {
            $builder->like('companies.industry', $industry);
        }

        if ($location !== '') {
            $builder->groupStart()
                ->like('companies.hq', $location)
                ->orLike('companies.branches', $location)
                ->groupEnd();
        }

        $companies = $builder->paginate(12);
        $pager = $companyModel->pager;
        $companies = $this->dedupeCompanies($companies);

        $industries = $companyModel
            ->select('industry')
            ->where('industry IS NOT NULL')
            ->where('industry !=', '')
            ->groupBy('industry')
            ->orderBy('industry', 'ASC')
            ->findAll();

        return view('company/index', [
            'companies' => $companies,
            'pager' => $pager,
            'filters' => [
                'q' => $query,
                'industry' => $industry,
                'location' => $location,
                'limit' => $limit,
            ],
            'industries' => array_values(array_filter(array_map(static fn (array $row): string => trim((string) ($row['industry'] ?? '')), $industries))),
            'totalCompanies' => $companyModel->countAllResults(),
            'totalOpenJobs' => $jobModel->where('status', 'open')->countAllResults(),
        ]);
    }

    public function searchJobs()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'candidate') {
            return $this->response->setJSON(['error' => 'Candidate access only']);
        }

        $companyName = trim((string) $this->request->getPost('company_name'));
        $limit = (int) ($this->request->getPost('limit') ?: 10);
        $limit = max(1, min(100, $limit));
        $infoOnly = in_array(strtolower(trim((string) $this->request->getPost('info_only'))), ['1', 'true'], true);

        if ($companyName === '') {
            return $this->response->setJSON(['error' => 'Company name is required.', 'jobs' => [], 'company_info' => []]);
        }

        try {
            $service = new TargetCompanyJobService();
            $companyInfo = $service->fetchCompanyDetails($companyName);

            // Auto-save new company to DB
            $companyModel = model(CompanyModel::class);
            $savedCompanyId = $companyModel->upsertByName($companyName, [
                'name'              => $companyName,
                'logo'              => $companyInfo['logo_url'] ?? '',
                'website'           => $companyInfo['website'] ?? '',
                'short_description' => $companyInfo['short_description'] ?? $companyInfo['description'] ?? '',
                'what_we_do'        => $companyInfo['what_we_do'] ?? '',
                'hq'                => $companyInfo['hq'] ?? $companyInfo['location'] ?? '',
                'industry'          => $companyInfo['industry'] ?? '',
                'size'              => $companyInfo['size'] ?? '',
                'career_page'       => $companyInfo['career_page'] ?? '',
                'linkedin'          => $companyInfo['linkedin'] ?? '',
                'twitter'           => $companyInfo['twitter'] ?? '',
                'facebook'          => $companyInfo['facebook'] ?? '',
                'instagram'         => $companyInfo['instagram'] ?? '',
                'youtube'           => $companyInfo['youtube'] ?? '',
                'source'            => !empty($companyInfo['career_page']) ? 'official_career_page' : 'auto_discovered',
                'last_enriched_at'  => date('Y-m-d H:i:s'),
            ]);

            if ($infoOnly) {
                return $this->response->setJSON([
                    'company' => $companyInfo['name'] ?: $companyName,
                    'company_info' => $companyInfo,
                    'saved_company_id' => $savedCompanyId,
                    'count' => 0,
                    'jobs' => [],
                    'status' => 'info_only'
                ]);
            }

            $jobs = $service->fetchJobs($companyName, '', '', $limit);
            $jobModel = model(JobModel::class);
            foreach ($jobs as $job) {
                $jobModel->upsertExternalJob(
                    (int) $savedCompanyId,
                    (string) ($companyInfo['name'] ?: $companyName),
                    $job,
                    (string) ($companyInfo['career_page'] ?: ($companyInfo['website'] ?? ''))
                );
            }
            $status = 'official';

            return $this->response->setJSON([
                'company' => $companyInfo['name'] ?: $companyName,
                'company_info' => $companyInfo,
                'saved_company_id' => $savedCompanyId,
                'count' => count($jobs),
                'jobs' => $jobs,
            'status' => $status
        ]);
        } catch (\Throwable $e) {
            log_message('error', '[CompanySearch] ' . $e->getMessage());
            return $this->response->setJSON([
                'company' => $companyName,
                'error' => 'Fetched company info, but could not connect to live job board. ' . $e->getMessage(),
                'jobs' => [],
                'status' => 'error'
            ]);
        }
    }

    /**
     * Collapse near-duplicate company names into a single card for search results.
     */
    private function dedupeCompanies(array $companies): array
    {
        $companyModel = new CompanyModel();
        $seen = [];
        $deduped = [];

        usort($companies, static function (array $a, array $b): int {
            $sourceScore = static function (array $row): int {
                $source = strtolower(trim((string) ($row['source'] ?? '')));
                return match ($source) {
                    'official_career_page' => 3,
                    'auto_discovered' => 1,
                    default => 2,
                };
            };

            $aScore = $sourceScore($a);
            $bScore = $sourceScore($b);
            if ($aScore !== $bScore) {
                return $bScore <=> $aScore;
            }

            $aJobs = (int) ($a['open_jobs_count'] ?? 0);
            $bJobs = (int) ($b['open_jobs_count'] ?? 0);
            if ($aJobs !== $bJobs) {
                return $bJobs <=> $aJobs;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        foreach ($companies as $company) {
            $brandKey = $companyModel->normalizeBrandKey((string) ($company['name'] ?? ''));
            if ($brandKey === '') {
                $brandKey = strtolower(trim((string) ($company['name'] ?? '')));
            }

            if (isset($seen[$brandKey])) {
                continue;
            }

            $seen[$brandKey] = true;
            $deduped[] = $company;
        }

        return $deduped;
    }

    public function show(int $id)
    {
        $userModel = new UserModel();
        $companyModel = new CompanyModel();
        $companyReviewModel = new CompanyReviewModel();
        $jobModel = new JobModel();

        // First treat route param as company_id (new model).
        $company = $companyModel->find($id);
        $companyId = (int) ($company['id'] ?? 0);

        // Backward compatibility: if not found, treat it as recruiter_id.
        if (!$company) {
            $recruiter = $userModel->findRecruiterWithProfile((int) $id);
            if (!$recruiter) {
                return redirect()->back()->with('error', 'Company profile not found.');
            }
            $companyId = (int) ($recruiter['company_id'] ?? 0);
            if ($companyId <= 0) {
                return redirect()->back()->with('error', 'Company profile not found.');
            }
            $company = $companyModel->find($companyId);
        }

        $company = $companyModel->find($companyId);
        if (!$company) {
            return redirect()->back()->with('error', 'Company profile not found.');
        }

        $openJobs = $jobModel->where('company_id', $companyId)->where('status', 'open')->orderBy('created_at', 'DESC')->findAll(5);
        $openJobsCount = $jobModel->where('company_id', $companyId)->where('status', 'open')->countAllResults();

        if (empty($openJobs) && (
            trim((string) ($company['website'] ?? '')) !== '' ||
            trim((string) ($company['career_page'] ?? '')) !== ''
        )) {
            try {
                $service = new TargetCompanyJobService();
                $importedJobs = $service->fetchJobs((string) $company['name'], '', '', 10);
                foreach ($importedJobs as $job) {
                    $jobModel->upsertExternalJob(
                        $companyId,
                        (string) ($company['name'] ?? 'Company'),
                        $job,
                        (string) ($company['career_page'] ?? $company['website'] ?? '')
                    );
                }
                $openJobs = $jobModel->where('company_id', $companyId)->where('status', 'open')->orderBy('created_at', 'DESC')->findAll(5);
                $openJobsCount = $jobModel->where('company_id', $companyId)->where('status', 'open')->countAllResults();
            } catch (\Throwable $e) {
                log_message('warning', 'Company profile fallback job enrichment failed: ' . $e->getMessage());
            }
        }
        $reviews = $companyReviewModel
            ->select('company_reviews.*, users.name as candidate_name')
            ->join('users', 'users.id = company_reviews.candidate_id', 'left')
            ->where('company_reviews.company_id', $companyId)
            ->where('company_reviews.status', 'published')
            ->orderBy('company_reviews.updated_at', 'DESC')
            ->findAll(8);

        $reviewSummary = $companyReviewModel
            ->select('COUNT(*) as total_reviews, AVG(rating) as average_rating')
            ->where('company_id', $companyId)
            ->where('status', 'published')
            ->first();

        $currentUserReview = null;
        $reviewEligibility = [
            'canInterviewReview' => false,
            'canEmployeeReview' => false,
        ];
        if ((string) session()->get('role') === 'candidate' && (int) session()->get('user_id') > 0) {
            $candidateId = (int) session()->get('user_id');
            $currentUserReview = $companyReviewModel
                ->where('company_id', $companyId)
                ->where('candidate_id', $candidateId)
                ->first();
            $reviewEligibility = [
                'canInterviewReview' => $this->hasInterviewEligibility($candidateId, $companyId),
                'canEmployeeReview' => $this->hasEmployeeEligibility($candidateId, $companyId),
            ];
        }

        return view('company/profile', [
            'company' => $company,
            'openJobs' => $openJobs,
            'openJobsCount' => $openJobsCount,
            'reviews' => $reviews,
            'reviewSummary' => $reviewSummary,
            'currentUserReview' => $currentUserReview,
            'reviewEligibility' => $reviewEligibility,
        ]);
    }

    public function submitReview(int $companyId)
    {
        $session = session();
        if (!$session->get('logged_in') || $session->get('role') !== 'candidate') {
            return redirect()->to(base_url('login'))->with('error', 'Candidate login required.');
        }

        $companyModel = new CompanyModel();
        $companyReviewModel = new CompanyReviewModel();

        $company = $companyModel->find($companyId);
        if (!$company) {
            return redirect()->back()->with('error', 'Company not found.');
        }

        $rating = (int) $this->request->getPost('rating');
        $reviewType = trim((string) $this->request->getPost('review_type'));
        $headline = trim((string) $this->request->getPost('headline'));
        $reviewText = trim((string) $this->request->getPost('review_text'));
        $pros = trim((string) $this->request->getPost('pros'));
        $cons = trim((string) $this->request->getPost('cons'));
        $candidateId = (int) $session->get('user_id');

        if ($rating < 1 || $rating > 5) {
            return redirect()->back()->withInput()->with('error', 'Please select a rating between 1 and 5.');
        }

        if (!in_array($reviewType, ['interview', 'employee'], true)) {
            return redirect()->back()->withInput()->with('error', 'Please choose a review type.');
        }

        $canInterviewReview = $this->hasInterviewEligibility($candidateId, $companyId);
        $canEmployeeReview = $this->hasEmployeeEligibility($candidateId, $companyId);

        if (!$canInterviewReview) {
            return redirect()->back()->withInput()->with('error', 'You can review this company only after applying or interviewing with them.');
        }

        if ($reviewType === 'employee' && !$canEmployeeReview) {
            return redirect()->back()->withInput()->with('error', 'Employee reviews are available only for candidates with a selected or hired outcome at this company.');
        }

        if ($headline === '' || mb_strlen($headline) < 4) {
            return redirect()->back()->withInput()->with('error', 'Review headline must be at least 4 characters.');
        }

        if ($reviewText === '' || mb_strlen($reviewText) < 20) {
            return redirect()->back()->withInput()->with('error', 'Review text must be at least 20 characters.');
        }

        $payload = [
            'company_id' => $companyId,
            'candidate_id' => $candidateId,
            'review_type' => $reviewType,
            'rating' => $rating,
            'headline' => $headline,
            'review_text' => $reviewText,
            'pros' => $pros,
            'cons' => $cons,
            'status' => 'published',
        ];

        $existingReview = $companyReviewModel
            ->where('company_id', $companyId)
            ->where('candidate_id', $candidateId)
            ->first();

        if ($existingReview) {
            $updated = $companyReviewModel->update((int) $existingReview['id'], $payload);
            if (!$updated) {
                $errorText = implode(' ', $companyReviewModel->errors());
                if ($errorText === '') {
                    $dbError = $companyReviewModel->db->error();
                    $errorText = trim((string) ($dbError['message'] ?? 'Unable to update your review right now.'));
                }

                return redirect()->to(base_url('company/' . $companyId) . '#write-review')
                    ->withInput()
                    ->with('error', $errorText);
            }

            return redirect()->to(base_url('company/' . $companyId) . '#company-reviews')
                ->with('success', 'Your review has been updated.');
        }

        $inserted = $companyReviewModel->insert($payload);
        if ($inserted === false) {
            $errorText = implode(' ', $companyReviewModel->errors());
            if ($errorText === '') {
                $dbError = $companyReviewModel->db->error();
                $errorText = trim((string) ($dbError['message'] ?? 'Unable to publish your review right now.'));
            }

            return redirect()->to(base_url('company/' . $companyId) . '#write-review')
                ->withInput()
                ->with('error', $errorText);
        }

        return redirect()->to(base_url('company/' . $companyId) . '#company-reviews')
            ->with('success', 'Your review has been published.');
    }

    private function hasInterviewEligibility(int $candidateId, int $companyId): bool
    {
        $db = \Config\Database::connect();

        $count = $db->table('applications')
            ->join('jobs', 'jobs.id = applications.job_id', 'inner')
            ->where('applications.candidate_id', $candidateId)
            ->where('jobs.company_id', $companyId)
            ->where('applications.status !=', 'withdrawn')
            ->countAllResults();

        return $count > 0;
    }

    private function hasEmployeeEligibility(int $candidateId, int $companyId): bool
    {
        $db = \Config\Database::connect();

        $count = $db->table('applications')
            ->join('jobs', 'jobs.id = applications.job_id', 'inner')
            ->where('applications.candidate_id', $candidateId)
            ->where('jobs.company_id', $companyId)
            ->whereIn('applications.status', ['selected', 'hired'])
            ->countAllResults();

        return $count > 0;
    }

    public function edit()
    {
        $session = session();
        if (!$session->get('logged_in') || $session->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Recruiter login required.');
        }

        $userModel = new UserModel();
        $companyModel = new CompanyModel();
        $recruiterId = (int) $session->get('user_id');

        $recruiter = $userModel->findRecruiterWithProfile($recruiterId) ?? $userModel->find($recruiterId);
        if (!$recruiter) {
            return redirect()->to(base_url('login'))->with('error', 'User not found.');
        }
        $companyId = (int) ($recruiter['company_id'] ?? 0);
        $company = $companyId > 0 ? ($companyModel->find($companyId) ?? []) : [];
        $company['company_name'] = $company['name'] ?? $recruiter['company_name'] ?? '';

        return view('recruiter/company_profile', ['company' => $company]);
    }

    public function update()
    {
        $session = session();
        if (!$session->get('logged_in') || $session->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Recruiter login required.');
        }

        $userId = (int) $session->get('user_id');
        $userModel = new UserModel();
        $companyModel = new CompanyModel();
        $recruiter = $userModel->findRecruiterWithProfile($userId) ?? $userModel->find($userId);
        if (!$recruiter) {
            return redirect()->to(base_url('login'))->with('error', 'User not found.');
        }

        $companyId = (int) ($recruiter['company_id'] ?? 0);
        if ($this->request->getPost('delete_logo')) {
            if ($companyId <= 0) {
                return redirect()->to(base_url('recruiter/company-profile'))->with('error', 'Company profile not found.');
            }

            $company = $companyModel->find($companyId);
            if (!$company) {
                return redirect()->to(base_url('recruiter/company-profile'))->with('error', 'Company profile not found.');
            }

            $logoPath = trim((string) ($company['logo'] ?? ''));
            if ($logoPath !== '' && str_starts_with($logoPath, 'uploads/company_logos/')) {
                $fullPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoPath);
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $companyModel->update($companyId, ['logo' => null]);
            return redirect()->to(base_url('recruiter/company-profile'))->with('success', 'Company logo removed.');
        }

        $data = [
            'name' => trim((string) $this->request->getPost('company_name')),
            'website'     => trim((string) $this->request->getPost('company_website')),
            'career_page' => trim((string) $this->request->getPost('company_career_page')),
            'industry'    => trim((string) $this->request->getPost('company_industry')),
            'size' => trim((string) $this->request->getPost('company_size')),
            'hq' => trim((string) $this->request->getPost('company_hq')),
            'branches' => trim((string) $this->request->getPost('company_branches')),
            'short_description' => trim((string) $this->request->getPost('company_short_description')),
            'what_we_do' => trim((string) $this->request->getPost('company_what_we_do')),
            'linkedin' => trim((string) $this->request->getPost('company_linkedin')),
            'twitter' => trim((string) $this->request->getPost('company_twitter')),
            'facebook' => trim((string) $this->request->getPost('company_facebook')),
            'instagram' => trim((string) $this->request->getPost('company_instagram')),
            'youtube' => trim((string) $this->request->getPost('company_youtube')),
            'mission_values' => trim((string) $this->request->getPost('company_mission_values')),
            'culture_summary' => trim((string) $this->request->getPost('company_culture_summary')),
            'employee_benefits' => trim((string) $this->request->getPost('company_employee_benefits')),
            'office_tour_title' => trim((string) $this->request->getPost('company_office_tour_title')),
            'office_tour_url' => trim((string) $this->request->getPost('company_office_tour_url')),
            'office_tour_summary' => trim((string) $this->request->getPost('company_office_tour_summary')),
            'contact_email' => trim((string) $this->request->getPost('company_contact_email')),
            'contact_phone' => trim((string) $this->request->getPost('company_contact_phone')),
            'contact_public' => $this->request->getPost('company_contact_public') ? 1 : 0,
        ];

        if ($data['name'] === '' || mb_strlen($data['name']) < 2) {
            return redirect()->back()->withInput()->with('error', 'Company name is required (min 2 characters).');
        }

        if ($data['website'] !== '' && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            return redirect()->back()->withInput()->with('error', 'Company website must be a valid URL.');
        }

        if ($data['career_page'] !== '' && !filter_var($data['career_page'], FILTER_VALIDATE_URL)) {
            return redirect()->back()->withInput()->with('error', 'Careers page must be a valid URL.');
        }

        foreach (['linkedin', 'twitter', 'facebook', 'instagram', 'youtube'] as $socialField) {
            if ($data[$socialField] !== '' && !filter_var($data[$socialField], FILTER_VALIDATE_URL)) {
                return redirect()->back()->withInput()->with('error', ucfirst($socialField) . ' must be a valid URL.');
            }
        }

        if ($data['office_tour_url'] !== '' && !filter_var($data['office_tour_url'], FILTER_VALIDATE_URL)) {
            return redirect()->back()->withInput()->with('error', 'Office tour URL must be a valid URL.');
        }

        if ($data['contact_email'] !== '' && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Contact email must be valid.');
        }

        $existingCompany = $companyId > 0 ? ($companyModel->find($companyId) ?? []) : [];
        $currentBrandPhotos = $this->parseWorkplacePhotos($existingCompany['workplace_photos'] ?? null);
        $removeBrandPhotos = $this->normalizePhotoPaths($this->request->getPost('remove_brand_photos'));
        $retainedBrandPhotos = array_values(array_diff($currentBrandPhotos, $removeBrandPhotos));

        $logo = $this->request->getFile('company_logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
            if (!in_array($logo->getMimeType(), $allowed, true)) {
                return redirect()->back()->withInput()->with('error', 'Logo must be PNG/JPG/WEBP/GIF.');
            }

            $uploadDir = FCPATH . 'uploads/company_logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newName = $logo->getRandomName();
            $logo->move($uploadDir, $newName);
            $data['logo'] = 'uploads/company_logos/' . $newName;
        }

        $newBrandPhotos = [];
        $brandPhotoFiles = $this->request->getFileMultiple('company_brand_photos');
        if (!empty($brandPhotoFiles)) {
            $brandingDir = FCPATH . 'uploads/company_branding/';
            if (!is_dir($brandingDir)) {
                mkdir($brandingDir, 0755, true);
            }

            foreach ($brandPhotoFiles as $photo) {
                if (!$photo || !$photo->isValid() || $photo->hasMoved()) {
                    continue;
                }

                $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
                if (!in_array($photo->getMimeType(), $allowed, true)) {
                    return redirect()->back()->withInput()->with('error', 'Branding photos must be PNG/JPG/WEBP/GIF.');
                }

                $newName = $photo->getRandomName();
                $photo->move($brandingDir, $newName);
                $newBrandPhotos[] = 'uploads/company_branding/' . $newName;
            }
        }

        $mergedBrandPhotos = array_slice(
            array_values(array_unique(array_merge($retainedBrandPhotos, $newBrandPhotos))),
            0,
            self::MAX_BRANDING_PHOTOS
        );
        $data['workplace_photos'] = $mergedBrandPhotos === [] ? null : json_encode($mergedBrandPhotos);

        if ($companyId > 0) {
            $companyModel->update($companyId, $data);
        } else {
            $existingByName = $companyModel->where('LOWER(name)', strtolower($data['name']))->first();
            if ($existingByName) {
                $companyId = (int) $existingByName['id'];
                $companyModel->update($companyId, $data);
            } else {
                $companyModel->insert($data);
                $companyId = (int) $companyModel->getInsertID();
            }
        }

        foreach ($removeBrandPhotos as $removedPhoto) {
            if (!in_array($removedPhoto, $currentBrandPhotos, true)) {
                continue;
            }

            if (str_starts_with($removedPhoto, 'uploads/company_branding/')) {
                $fullPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $removedPhoto);
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        // Keep recruiter foreign key and recruiter profile snapshot in sync.
        $userModel->update($userId, [
            'company_id' => $companyId > 0 ? $companyId : null,
        ]);
        $userModel->upsertRecruiterProfile($userId, [
            'company_name' => $data['name'],
        ]);

        $db = \Config\Database::connect();
        if ($db->tableExists('recruiter_company_map') && $companyId > 0) {
            $exists = $db->table('recruiter_company_map')
                ->where('recruiter_user_id', $userId)
                ->where('company_id', $companyId)
                ->get()
                ->getRowArray();

            if (!$exists) {
                $db->table('recruiter_company_map')->insert([
                    'recruiter_user_id' => $userId,
                    'company_id' => $companyId,
                    'is_admin' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Keep existing jobs snapshot + company_id aligned for this recruiter's old posts.
        model('JobModel')
            ->where('recruiter_id', $userId)
            ->set(['company' => $data['name'], 'company_id' => $companyId > 0 ? $companyId : null])
            ->update();

        return redirect()->to(base_url('recruiter/company-profile'))->with('success', 'Company profile updated.');
    }

    private function parseWorkplacePhotos($raw): array
    {
        if (is_array($raw)) {
            return $this->normalizePhotoPaths($raw);
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $this->normalizePhotoPaths($decoded);
        }

        return $this->normalizePhotoPaths(explode(',', $raw));
    }

    private function normalizePhotoPaths($paths): array
    {
        if (!is_array($paths)) {
            return [];
        }

        $normalized = [];
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $normalized[] = $path;
        }

        return array_values(array_unique($normalized));
    }
}