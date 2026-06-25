<?php

namespace App\Libraries;

use App\Models\RecruiterWorkflowSettingModel;

class ApplicationRejectionMailer
{
    public function sendIfEnabled(int $applicationId, int $recruiterId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_workflow_settings')) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'Workflow settings are not migrated.'];
        }

        $settings = (new RecruiterWorkflowSettingModel())->getForRecruiter($recruiterId);
        if ((int) ($settings['send_rejection_email'] ?? 0) !== 1) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'Rejection emails are disabled.'];
        }

        $application = $db->table('applications')
            ->select('applications.id, applications.job_id, applications.candidate_id, users.name candidate_name, users.email candidate_email, jobs.title job_title, jobs.company job_company')
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->where('applications.id', $applicationId)
            ->where('jobs.recruiter_id', $recruiterId)
            ->get()
            ->getRowArray();

        if (!$application || empty($application['candidate_email'])) {
            return ['sent' => false, 'skipped' => true, 'reason' => 'Candidate email not found.'];
        }

        $recruiter = model('UserModel')->findRecruiterWithProfile($recruiterId) ?? model('UserModel')->find($recruiterId) ?? [];
        $tokens = [
            '{candidate_name}' => (string) ($application['candidate_name'] ?? 'Candidate'),
            '{job_title}' => (string) ($application['job_title'] ?? 'the role'),
            '{company_name}' => (string) ($application['job_company'] ?? 'our team'),
            '{recruiter_name}' => (string) ($recruiter['name'] ?? 'Recruiting Team'),
        ];

        $subject = strtr((string) ($settings['rejection_email_subject'] ?? ''), $tokens);
        $plainBody = strtr((string) ($settings['rejection_email_body'] ?? ''), $tokens);
        $htmlBody = $this->wrapEmail($plainBody, (string) $tokens['{company_name}']);

        $sent = false;
        if ((int) ($settings['rejection_email_use_mailbox'] ?? 1) === 1 && $db->tableExists('recruiter_mailbox_connections')) {
            $sent = (new RecruiterMailboxService())->sendForRecruiter($recruiterId, (string) $application['candidate_email'], $subject, $htmlBody, [
                'candidate_id' => (int) $application['candidate_id'],
                'application_id' => (int) $application['id'],
                'job_id' => (int) $application['job_id'],
            ]);
        }

        if (!$sent && (int) ($settings['rejection_email_allow_system_fallback'] ?? 1) === 1) {
            $sent = $this->sendViaSystemEmail((string) $application['candidate_email'], $subject, $htmlBody, $settings, $recruiter);
        }

        return ['sent' => $sent, 'skipped' => false, 'reason' => $sent ? '' : 'Email delivery failed.'];
    }

    private function sendViaSystemEmail(string $to, string $subject, string $htmlBody, array $settings, array $recruiter): bool
    {
        try {
            $emailConfig = config('Email');
            $email = \Config\Services::email(null, false);
            $email->clear(true);
            $email->setMailType('html');

            if ($emailConfig->fromEmail !== '') {
                $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'HireMatrix');
            }

            if ((int) ($settings['rejection_email_cc_self'] ?? 0) === 1 && !empty($recruiter['email'])) {
                $email->setCC((string) $recruiter['email']);
            }

            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMessage($htmlBody);

            return (bool) $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Rejection email failed: ' . $e->getMessage());
            return false;
        }
    }

    private function wrapEmail(string $plainBody, string $companyName): string
    {
        return '
            <div style="margin:0;padding:24px;background:#f8fafc;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
                <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;">
                    <div style="padding:22px 26px;border-bottom:1px solid #e2e8f0;">
                        <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#0D8A90;font-weight:700;">Application update</div>
                        <h1 style="margin:8px 0 0;font-size:21px;line-height:1.35;color:#16212B;">' . esc($companyName) . '</h1>
                    </div>
                    <div style="padding:26px;font-size:15px;line-height:1.75;color:#334155;">' . nl2br(esc($plainBody)) . '</div>
                </div>
            </div>';
    }
}
