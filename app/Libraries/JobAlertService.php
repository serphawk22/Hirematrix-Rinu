<?php

namespace App\Libraries;

use App\Models\JobAlertDeliveryModel;
use App\Models\JobAlertModel;
use App\Models\NotificationModel;
use App\Models\UserModel;

class JobAlertService
{
    public function processNewJob(array $job): void
    {
        $jobId = (int) ($job['id'] ?? 0);
        if ($jobId <= 0) {
            return;
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('job_alerts') || !$db->tableExists('job_alert_deliveries')) {
            return;
        }

        $alertModel = new JobAlertModel();
        $deliveryModel = new JobAlertDeliveryModel();
        $userModel = new UserModel();
        $notificationModel = new NotificationModel();

        $alerts = $alertModel->where('is_active', 1)->findAll();
        if (empty($alerts)) {
            return;
        }

        foreach ($alerts as $alert) {
            if (!$this->matchesAlert($job, $alert)) {
                continue;
            }

            $existing = $deliveryModel
                ->where('job_alert_id', (int) $alert['id'])
                ->where('job_id', $jobId)
                ->first();

            if ($existing) {
                continue;
            }

            $candidateId = (int) ($alert['candidate_id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }

            $delivery = [
                'job_alert_id' => (int) $alert['id'],
                'job_id' => $jobId,
                'candidate_id' => $candidateId,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ((int) ($alert['notify_in_app'] ?? 1) === 1) {
                $notificationModel->createNotification(
                    $candidateId,
                    null,
                    'job_alert_match',
                    'New matching job: ' . ($job['title'] ?? 'Job') . ' - ' . ($job['location'] ?? 'Location not specified'),
                    base_url('job/' . $jobId)
                );
                $delivery['in_app_sent_at'] = date('Y-m-d H:i:s');
            }

            if ((int) ($alert['notify_email'] ?? 1) === 1) {
                $candidate = $userModel->find($candidateId);
                if (!empty($candidate['email'])) {
                    if ($this->sendEmailAlert($candidate, $job)) {
                        $delivery['email_sent_at'] = date('Y-m-d H:i:s');
                    }
                }
            }

            $deliveryModel->insert($delivery);
        }
    }

    private function matchesAlert(array $job, array $alert): bool
    {
        $title = strtolower((string) ($job['title'] ?? ''));
        $location = strtolower((string) ($job['location'] ?? ''));
        $skills = strtolower((string) ($job['required_skills'] ?? ''));
        $description = strtolower((string) ($job['description'] ?? ''));

        $roleKeywords = $this->splitKeywords((string) ($alert['role_keywords'] ?? ''));
        if (!empty($roleKeywords) && !$this->containsAny($title . ' ' . $description, $roleKeywords)) {
            return false;
        }

        $locationKeywords = $this->splitKeywords((string) ($alert['location_keywords'] ?? ''));
        if (!empty($locationKeywords) && !$this->containsAny($location, $locationKeywords)) {
            return false;
        }

        $employmentType = strtolower(trim((string) ($alert['employment_type'] ?? '')));
        $jobEmploymentType = strtolower(trim((string) ($job['employment_type'] ?? '')));
        if ($employmentType !== '' && $jobEmploymentType !== '' && $employmentType !== $jobEmploymentType) {
            return false;
        }

        $skillKeywords = $this->splitKeywords((string) ($alert['skills_keywords'] ?? ''));
        if (!empty($skillKeywords) && !$this->containsAny($skills . ' ' . $description, $skillKeywords)) {
            return false;
        }

        $salaryMin = $alert['salary_min'] !== null ? (int) $alert['salary_min'] : null;
        $salaryMax = $alert['salary_max'] !== null ? (int) $alert['salary_max'] : null;
        if ($salaryMin !== null || $salaryMax !== null) {
            $jobSalary = $this->extractSalary((string) ($job['description'] ?? ''));
            if ($jobSalary === null) {
                return false;
            }
            if ($salaryMin !== null && $jobSalary < $salaryMin) {
                return false;
            }
            if ($salaryMax !== null && $jobSalary > $salaryMax) {
                return false;
            }
        }

        return true;
    }

    private function splitKeywords(string $input): array
    {
        $parts = preg_split('/[,|]/', $input) ?: [];
        $parts = array_map(static fn ($item) => strtolower(trim((string) $item)), $parts);
        return array_values(array_filter($parts, static fn ($item) => $item !== ''));
    }

    private function containsAny(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if ($keyword !== '' && stripos($haystack, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function extractSalary(string $description): ?int
    {
        if (preg_match('/(?:salary|ctc|pay)\s*[:\-]?\s*(?:rs\.?|inr)?\s*([0-9]{4,9})/i', $description, $match)) {
            return (int) ($match[1] ?? 0);
        }

        return null;
    }

    private function sendEmailAlert(array $candidate, array $job): bool
    {
        try {
            $emailConfig = config('Email');
            $email = \Config\Services::email(null, false);
            $email->clear(true);

            if ($emailConfig->fromEmail !== '') {
                $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'HireMatrix');
            }

            $jobUrl = base_url('job/' . (int) ($job['id'] ?? 0));
            $subject = 'New job match: ' . (string) ($job['title'] ?? 'Job');
            $message = "Hi " . (string) ($candidate['name'] ?? 'Candidate') . ",\n\n"
                . "A new job matching your alert was posted.\n\n"
                . "Role: " . (string) ($job['title'] ?? '-') . "\n"
                . "Company: " . (string) ($job['company'] ?? '-') . "\n"
                . "Location: " . (string) ($job['location'] ?? '-') . "\n\n"
                . "View job: " . $jobUrl . "\n\n"
                . "You are receiving this because job alerts are enabled in your profile.";

            $email->setTo((string) $candidate['email']);
            $email->setSubject($subject);
            $email->setMessage($message);

            return (bool) $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Job alert email failed: ' . $e->getMessage());
            return false;
        }
    }
}
