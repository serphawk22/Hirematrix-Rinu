<?php

namespace App\Controllers;

use App\Libraries\RecruiterChatbotService;

class RecruiterChatbotController extends BaseController
{
    /**
     * Handle a chatbot question from the recruiter.
     * POST /recruiter/chatbot/ask
     */
    public function ask()
    {
        $recruiterId = (int) (session()->get('user_id') ?? 0);
        $role = session()->get('role');

        if (!$recruiterId || $role !== 'recruiter') {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'answer'  => 'Please log in as a recruiter to use the assistant.',
                ]);
        }

        $question = trim((string) ($this->request->getPost('question') ?? $this->request->getJSON(true)['question'] ?? ''));
        if ($question === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'answer'  => 'Please enter a question.',
                ]);
        }

        $service = new RecruiterChatbotService();
        $result  = $service->answer($recruiterId, $question);

        return $this->response->setJSON([
            'success' => true,
            'answer'  => $result['answer'],
        ]);
    }

    /**
     * Suggest quick-start questions.
     * GET /recruiter/chatbot/suggestions
     */
    public function suggestions()
    {
        $recruiterId = (int) (session()->get('user_id') ?? 0);
        $role = session()->get('role');

        if (!$recruiterId || $role !== 'recruiter') {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $suggestions = [
            'Post job for Front End Developer',
            'Draft job description for PHP Developer',
            'Create screening questions for job 1',
            'Shortlist candidates for job 1 with ATS above 70',
            'Suggest interview slots for job 1',
            'Draft shortlist email for job 1',
            'Draft rejection email for job 1',
            'Export candidate data',
            'Give me a summary of my hiring',
        ];

        return $this->response->setJSON([
            'success'     => true,
            'suggestions' => $suggestions,
        ]);
    }
}
