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
            'How many open jobs do I have?',
            'How many applications have I received?',
            'Show me my recent applications',
            'What candidates do I have?',
            'How many interview bookings do I have?',
            'Give me a summary of my hiring',
            'Which jobs have the most applications?',
            'Do I have any upcoming interviews?',
        ];

        return $this->response->setJSON([
            'success'     => true,
            'suggestions' => $suggestions,
        ]);
    }
}
