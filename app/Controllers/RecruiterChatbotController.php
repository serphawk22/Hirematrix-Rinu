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

        $json = [];
        if (str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'application/json')) {
            $json = $this->request->getJSON(true) ?: [];
        }

        $question = trim((string) ($this->request->getPost('question') ?? $json['question'] ?? ''));
        $contextRaw = (string) ($this->request->getPost('context') ?? ($json['context'] ?? ''));
        $chatContext = [];
        if ($contextRaw !== '') {
            $decodedContext = json_decode($contextRaw, true);
            $chatContext = is_array($decodedContext) ? $decodedContext : [];
        }
        if ($question === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'answer'  => 'Please enter a question.',
                ]);
        }

        $service = new RecruiterChatbotService();
        $result  = $service->answer($recruiterId, $question, $chatContext);

        return $this->response->setJSON([
            'success' => true,
            'answer'  => $result['answer'],
            'actions' => $result['actions'] ?? [],
            'meta'    => $result['data_summary'] ?? [],
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

        $suggestions = $this->buildRecruiterSuggestions($recruiterId);

        return $this->response->setJSON([
            'success'     => true,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * GET /recruiter/chatbot/brief
     */
    public function brief()
    {
        $recruiterId = (int) (session()->get('user_id') ?? 0);
        $role = session()->get('role');

        if (!$recruiterId || $role !== 'recruiter') {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $service = new RecruiterChatbotService();
        $result = $service->buildMorningBrief($recruiterId);

        return $this->response->setJSON([
            'success' => true,
            'answer' => $result['answer'],
            'actions' => $result['actions'] ?? [],
            'meta' => $result['data_summary'] ?? [],
        ]);
    }

    private function buildRecruiterSuggestions(int $recruiterId): array
    {
        $jobs = \Config\Database::connect()->table('jobs j')
            ->select('j.id, j.title, j.status, j.created_at, COUNT(a.id) as application_count')
            ->join('applications a', 'a.job_id = j.id', 'left')
            ->where('j.recruiter_id', $recruiterId)
            ->groupBy('j.id, j.title, j.status, j.created_at')
            ->orderBy('CASE WHEN j.status IN ("active", "open", "published") THEN 0 ELSE 1 END', 'ASC', false)
            ->orderBy('application_count', 'DESC')
            ->orderBy('j.created_at', 'DESC')
            ->limit(3)
            ->get()
            ->getResultArray();

        $suggestions = [];

        if (!empty($jobs)) {
            foreach ($jobs as $job) {
                $title = trim((string) ($job['title'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $quoted = '"' . $title . '"';
                $suggestions[] = ['text' => 'Create screening questions for ' . $quoted, 'mode' => 'send'];
                $suggestions[] = ['text' => 'Shortlist candidates for ' . $quoted . ' with ATS above 70', 'mode' => 'send'];
                $suggestions[] = ['text' => 'Suggest interview slots for ' . $quoted, 'mode' => 'send'];
                $suggestions[] = ['text' => 'Draft shortlist email for ' . $quoted, 'mode' => 'send'];
                $suggestions[] = ['text' => 'Draft rejection email for ' . $quoted, 'mode' => 'send'];
            }
        } else {
            $suggestions[] = ['text' => 'Post job for Front End Developer', 'mode' => 'send'];
            $suggestions[] = ['text' => 'Draft job description for QA Engineer', 'mode' => 'send'];
        }

        $suggestions[] = ['text' => 'Export candidate data', 'mode' => 'send'];
        $suggestions[] = ['text' => 'Give me a summary of my hiring', 'mode' => 'send'];

        return array_slice($suggestions, 0, 12);
    }
}
