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
            ['text' => 'Find matching jobs from my profile', 'mode' => 'send'],
            ['text' => 'Show remote PHP jobs', 'mode' => 'send'],
            ['text' => 'Find hybrid jobs in Bangalore', 'mode' => 'send'],
            ['text' => 'Save job #Name', 'mode' => 'edit'],
            ['text' => 'Apply to job #Name', 'mode' => 'edit'],
            ['text' => 'Compare job #Name and job #Name', 'mode' => 'edit'],
            ['text' => 'Explain why job #Name matches me', 'mode' => 'edit'],
        ];

        return $this->response->setJSON([
            'success' => true,
            'suggestions' => $suggestions,
        ]);
    }
}
