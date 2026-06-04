<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use App\Models\RecruiterCandidateMessageModel;
use App\Models\UserModel;

class CandidateMessages extends BaseController
{
    public function thread($recruiterId)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $candidateId = (int) session()->get('user_id');
        $recruiterId = (int) $recruiterId;
        $applicationId = (int) ($this->request->getGet('application_id') ?? 0);

        $recruiter = (new UserModel())->find($recruiterId);
        if (!$recruiter || ($recruiter['role'] ?? '') !== 'recruiter') {
            return redirect()->back()->with('error', 'Recruiter not found.');
        }

        $messages = (new RecruiterCandidateMessageModel())->getThread(
            $candidateId,
            $recruiterId,
            $applicationId > 0 ? $applicationId : null
        );

        return view('candidate/messages_thread', [
            'title' => 'Messages',
            'messages' => $messages,
            'recruiter' => $recruiter,
            'recruiterId' => $recruiterId,
            'applicationId' => $applicationId,
        ]);
    }

    public function reply($recruiterId)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $candidateId = (int) session()->get('user_id');
        $recruiterId = (int) $recruiterId;
        $applicationId = (int) ($this->request->getPost('application_id') ?? 0);
        $message = trim((string) $this->request->getPost('message'));

        if ($message === '') {
            return redirect()->back()->with('error', 'Reply cannot be empty.');
        }
        if (mb_strlen($message) > 1000) {
            return redirect()->back()->with('error', 'Reply is too long. Max 1000 characters.');
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

        $candidateName = (string) (session()->get('user_name') ?? 'Candidate');
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

        $redirectUrl = base_url('candidate/messages/' . $recruiterId);
        if ($applicationId > 0) {
            $redirectUrl .= '?application_id=' . $applicationId;
        }

        return redirect()->to($redirectUrl)->with('success', 'Reply sent.');
    }
}
