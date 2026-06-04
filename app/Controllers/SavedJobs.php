<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\JobModel;
use App\Models\SavedJobModel;

class SavedJobs extends BaseController
{
    public function index()
    {
        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0) {
            return redirect()->to(base_url('login'));
        }

        $savedRows = (new SavedJobModel())
            ->select('saved_jobs.created_at as saved_at, jobs.*')
            ->join('jobs', 'jobs.id = saved_jobs.job_id', 'inner')
            ->where('saved_jobs.candidate_id', $candidateId)
            ->where('jobs.status', 'open')
            ->orderBy('saved_jobs.created_at', 'DESC')
            ->findAll();

        foreach ($savedRows as $index => $job) {
            $savedRows[$index]['is_external'] = false;
            $savedRows[$index]['details_url'] = base_url('job/' . (int) ($job['id'] ?? 0));
            $savedRows[$index]['unsave_url'] = base_url('job/unsave/' . (int) ($job['id'] ?? 0));
        }

        $savedMncRows = (new SavedJobModel())
            ->select(
                'saved_jobs.created_at as saved_at, ' .
                'mnc_external_jobs.id, ' .
                'mnc_external_jobs.company_name as company, ' .
                'mnc_external_jobs.title, ' .
                'mnc_external_jobs.location, ' .
                'mnc_external_jobs.apply_url, ' .
                'mnc_external_jobs.source_platform, ' .
                'mnc_external_jobs.posted_at_raw, ' .
                'mnc_external_jobs.last_sync_at'
            )
            ->join('mnc_external_jobs', 'mnc_external_jobs.id = saved_jobs.mnc_external_job_id', 'inner')
            ->where('saved_jobs.candidate_id', $candidateId)
            ->where('mnc_external_jobs.is_active', 1)
            ->orderBy('saved_jobs.created_at', 'DESC')
            ->findAll();

        foreach ($savedMncRows as $index => $job) {
            $savedMncRows[$index]['is_external'] = true;
            $savedMncRows[$index]['created_at'] = $job['saved_at'] ?? null;
            $savedMncRows[$index]['employment_type'] = trim((string) ($job['source_platform'] ?? '')) ?: 'External';
            $savedMncRows[$index]['details_url'] = trim((string) ($job['apply_url'] ?? ''));
            $savedMncRows[$index]['unsave_url'] = base_url('mnc/job/unsave/' . (int) ($job['id'] ?? 0));
        }

        $savedRows = array_merge($savedRows, $savedMncRows);
        usort($savedRows, static function (array $left, array $right): int {
            return strtotime((string) ($right['saved_at'] ?? '')) <=> strtotime((string) ($left['saved_at'] ?? ''));
        });

        $companyIds = [];
        foreach ($savedRows as $job) {
            $id = (int) ($job['company_id'] ?? 0);
            if ($id > 0) {
                $companyIds[] = $id;
            }
        }

        $companyLogoMap = [];
        $companyIds = array_values(array_unique($companyIds));
        if (!empty($companyIds)) {
            $companies = (new CompanyModel())
                ->select('id, logo')
                ->whereIn('id', $companyIds)
                ->findAll();
            foreach ($companies as $company) {
                $companyLogoMap[(int) $company['id']] = (string) ($company['logo'] ?? '');
            }
        }

        foreach ($savedRows as $index => $job) {
            $id = (int) ($job['company_id'] ?? 0);
            $savedRows[$index]['company_logo'] = $companyLogoMap[$id] ?? '';
        }

        return view('candidate/saved_jobs', [
            'title' => 'Saved Jobs',
            'jobs' => $savedRows,
        ]);
    }

    public function save(int $jobId)
    {
        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0) {
            return redirect()->to(base_url('login'));
        }

        $jobExists = (new JobModel())
            ->select('id')
            ->where('id', $jobId)
            ->where('status', 'open')
            ->first();

        if (!$jobExists) {
            return redirect()->back()->with('error', 'Job not found.');
        }

        $savedJobModel = new SavedJobModel();
        $alreadySaved = $savedJobModel
            ->where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->first();

        if (!$alreadySaved) {
            $savedJobModel->insert([
                'candidate_id' => $candidateId,
                'job_id' => $jobId,
            ]);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'saved' => true,
                'job_id' => $jobId,
                'message' => 'Job saved.',
            ]);
        }

        return redirect()->back();
    }

    public function unsave(int $jobId)
    {
        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0) {
            return redirect()->to(base_url('login'));
        }

        (new SavedJobModel())
            ->where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->delete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'saved' => false,
                'job_id' => $jobId,
                'message' => 'Job removed from saved list.',
            ]);
        }

        return redirect()->back();
    }
}
