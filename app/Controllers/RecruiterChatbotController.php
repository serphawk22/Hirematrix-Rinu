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
            ['text' => 'Post job for Front End Developer', 'mode' => 'send'],
            ['text' => 'Draft job description for PHP Developer', 'mode' => 'send'],
            ['text' => 'Create screening questions for job #ID', 'mode' => 'edit'],
            ['text' => 'Shortlist candidates for job #ID with ATS above 70', 'mode' => 'edit'],
            ['text' => 'Suggest interview slots for job #ID', 'mode' => 'edit'],
            ['text' => 'Draft shortlist email for job #ID', 'mode' => 'edit'],
            ['text' => 'Draft rejection email for job #ID', 'mode' => 'edit'],
            ['text' => 'Export candidate data', 'mode' => 'send'],
            ['text' => 'Give me a summary of my hiring', 'mode' => 'send'],
        ];

        return $this->response->setJSON([
            'success'     => true,
            'suggestions' => $suggestions,
        ]);
    }
}
