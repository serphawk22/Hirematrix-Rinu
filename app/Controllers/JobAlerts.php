<?php

namespace App\Controllers;

use App\Models\JobAlertModel;
use App\Models\UserModel;

class JobAlerts extends BaseController
{
    public function index()
    {
        return redirect()->to(base_url('candidate/settings?tab=notifications'));
    }

    public function updateSettings()
    {
        $redirect = $this->ensureCandidate();
        if ($redirect !== null) {
            return $redirect;
        }

        $candidateId = (int) session()->get('user_id');
        (new UserModel())->upsertCandidateProfile($candidateId, [
            'job_alerts_enabled' => $this->request->getPost('job_alerts_enabled') === '1' ? 1 : 0,
        ]);

        return redirect()->to(base_url('candidate/job-alerts'))->with('success', 'Job alert settings updated.');
    }

    public function create()
    {
        $redirect = $this->ensureCandidate();
        if ($redirect !== null) {
            return $redirect;
        }

        $candidateId = (int) session()->get('user_id');
        if (!$this->isJobAlertsEnabled($candidateId)) {
            return redirect()->to(base_url('candidate/job-alerts'))
                ->with('error', 'Job alerts are currently turned off in Settings.');
        }

        $alertModel = new JobAlertModel();

        $roleKeywords = trim((string) $this->request->getPost('role_keywords'));
        $locationKeywords = trim((string) $this->request->getPost('location_keywords'));
        $employmentType = trim((string) $this->request->getPost('employment_type'));
        $skillsKeywords = trim((string) $this->request->getPost('skills_keywords'));
        $salaryMinRaw = trim((string) $this->request->getPost('salary_min'));
        $salaryMaxRaw = trim((string) $this->request->getPost('salary_max'));
        $salaryMin = $salaryMinRaw === '' ? null : (int) $salaryMinRaw;
        $salaryMax = $salaryMaxRaw === '' ? null : (int) $salaryMaxRaw;

        if ($roleKeywords === '' && $locationKeywords === '' && $employmentType === '' && $skillsKeywords === '' && $salaryMin === null && $salaryMax === null) {
            return redirect()->back()->with('error', 'Add at least one filter to create a job alert.');
        }

        if ($salaryMin !== null && $salaryMax !== null && $salaryMin > $salaryMax) {
            return redirect()->back()->with('error', 'Minimum salary cannot be greater than maximum salary.');
        }

        $alertModel->insert([
            'candidate_id' => $candidateId,
            'role_keywords' => $roleKeywords !== '' ? $roleKeywords : null,
            'location_keywords' => $locationKeywords !== '' ? $locationKeywords : null,
            'employment_type' => $employmentType !== '' ? $employmentType : null,
            'skills_keywords' => $skillsKeywords !== '' ? $skillsKeywords : null,
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'notify_email' => $this->request->getPost('notify_email') ? 1 : 0,
            'notify_in_app' => $this->request->getPost('notify_in_app') ? 1 : 0,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('candidate/job-alerts'))->with('success', 'Job alert created.');
    }

    public function toggle($id)
    {
        $redirect = $this->ensureCandidate();
        if ($redirect !== null) {
            return $redirect;
        }

        $candidateId = (int) session()->get('user_id');
        if (!$this->isJobAlertsEnabled($candidateId)) {
            return redirect()->to(base_url('candidate/job-alerts'))
                ->with('error', 'Job alerts are currently turned off in Settings.');
        }

        $alertModel = new JobAlertModel();

        $alert = $alertModel
            ->where('id', (int) $id)
            ->where('candidate_id', $candidateId)
            ->first();

        if (!$alert) {
            return redirect()->back()->with('error', 'Job alert not found.');
        }

        $alertModel->update((int) $id, [
            'is_active' => (int) $alert['is_active'] === 1 ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Job alert updated.');
    }

    public function delete($id)
    {
        $redirect = $this->ensureCandidate();
        if ($redirect !== null) {
            return $redirect;
        }

        $candidateId = (int) session()->get('user_id');
        $alertModel = new JobAlertModel();

        $alert = $alertModel
            ->where('id', (int) $id)
            ->where('candidate_id', $candidateId)
            ->first();

        if (!$alert) {
            return redirect()->back()->with('error', 'Job alert not found.');
        }

        $alertModel->delete((int) $id);

        return redirect()->back()->with('success', 'Job alert deleted.');
    }

    private function ensureCandidate()
    {
        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))
                ->with('error', 'Only candidates can manage job alerts.');
        }

        return null;
    }

    private function isJobAlertsEnabled(int $candidateId): bool
    {
        $settings = $this->getCandidateSettings($candidateId);

        return (int) ($settings['job_alerts_enabled'] ?? 1) === 1;
    }

    private function getCandidateSettings(int $candidateId): array
    {
        return (new UserModel())->findCandidateWithProfile($candidateId)
            ?? (new UserModel())->find($candidateId)
            ?? [];
    }
}
