<?php

namespace App\Controllers;

use App\Libraries\CandidateChatbotService;

class CandidateChatbotController extends BaseController
{
    public function ask()
    {
        $candidateId = (int) (session()->get('user_id') ?? 0);
        $role = session()->get('role');

        if (!$candidateId || $role !== 'candidate') {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'answer' => 'Please log in as a candidate to use the assistant.',
                ]);
        }

        $question = trim((string) ($this->request->getPost('question') ?? $this->request->getJSON(true)['question'] ?? ''));
        if ($question === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'answer' => 'Please enter a question.',
                ]);
        }

        $service = new CandidateChatbotService();
        $result = $service->answer($candidateId, $question);

        return $this->response->setJSON([
            'success' => true,
            'answer' => $result['answer'],
        ]);
    }

    public function suggestions()
    {
        $candidateId = (int) (session()->get('user_id') ?? 0);
        $role = session()->get('role');

        if (!$candidateId || $role !== 'candidate') {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $suggestions = [
            'Find matching jobs from my profile',
            'Show remote PHP jobs',
            'Find hybrid jobs in Bangalore',
            'Save job #1',
            'Apply to job #1',
            'Compare job #1 and job #2',
            'Explain why job #1 matches me',
        ];

        return $this->response->setJSON([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }
}
