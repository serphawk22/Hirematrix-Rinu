<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\RecruiterCandidateMessageModel;
use App\Models\UserModel;
use App\Models\NotificationModel;

class ApiMessagesController extends ResourceController
{
    protected $format = 'json';

    /**
     * GET api/messages/thread
     * Fetch message thread between candidate and recruiter
     */
    public function getThread()
    {
        $candidateId = (int) $this->request->getGet('candidate_id');
        $recruiterId = (int) $this->request->getGet('recruiter_id');
        $applicationId = (int) $this->request->getGet('application_id');

        if ($candidateId <= 0 || $recruiterId <= 0) {
            return $this->fail('Invalid candidate or recruiter ID');
        }

        $recruiter = (new UserModel())->find($recruiterId);
        if (!$recruiter || ($recruiter['role'] ?? '') !== 'recruiter') {
            return $this->failNotFound('Recruiter not found');
        }

        $messages = (new RecruiterCandidateMessageModel())->getThread(
            $candidateId,
            $recruiterId,
            $applicationId > 0 ? $applicationId : null
        );

        return $this->respond([
            'status' => 'success',
            'data' => [
                'recruiter' => [
                    'id' => (int) $recruiter['id'],
                    'name' => $recruiter['name'],
                    'email' => $recruiter['email']
                ],
                'messages' => $messages
            ]
        ]);
    }

    /**
     * POST api/messages/reply
     * Send a new message reply from candidate to recruiter
     */
    public function sendReply()
    {
        $json = $this->request->getJSON();
        if (!$json) {
            return $this->fail('Invalid JSON payload');
        }

        $candidateId = (int) ($json->candidate_id ?? 0);
        $recruiterId = (int) ($json->recruiter_id ?? 0);
        $applicationId = (int) ($json->application_id ?? 0);
        $message = trim((string) ($json->message ?? ''));

        if ($candidateId <= 0 || $recruiterId <= 0) {
            return $this->fail('Invalid candidate or recruiter ID');
        }

        if ($message === '') {
            return $this->fail('Message cannot be empty');
        }

        if (mb_strlen($message) > 1000) {
            return $this->fail('Message exceeds 1000 characters limit');
        }

        $candidate = (new UserModel())->find($candidateId);
        if (!$candidate) {
            return $this->failNotFound('Candidate not found');
        }

        $recruiter = (new UserModel())->find($recruiterId);
        if (!$recruiter || ($recruiter['role'] ?? '') !== 'recruiter') {
            return $this->failNotFound('Recruiter not found');
        }

        (new RecruiterCandidateMessageModel())->insert([
            'candidate_id' => $candidateId,
            'recruiter_id' => $recruiterId,
            'application_id' => $applicationId > 0 ? $applicationId : null,
            'job_id' => null,
            'sender_id' => $candidateId,
            'sender_role' => 'candidate',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Send notification to Recruiter
        $candidateName = (string) ($candidate['name'] ?? 'Candidate');
        $recruiterThreadLink = base_url('recruiter/candidate/' . $candidateId)
            . '?application_id=' . $applicationId
            . '&show_contact=1';

        (new NotificationModel())->insert([
            'user_id' => $recruiterId,
            'application_id' => $applicationId > 0 ? $applicationId : null,
            'type' => 'candidate_message_reply',
            'title' => 'Candidate Replied',
            'message' => "{$candidateName} replied to your message.",
            'action_link' => $recruiterThreadLink,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status' => 'success',
            'message' => 'Message sent successfully'
        ]);
    }
}
