<?php

namespace App\Controllers;

class CandidateFeedbackController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // SHOW FORM
    public function index()
    {
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $user = $this->db->table('users')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        return view('candidate/feedback', [
            'user' => $user
        ]);
    }

    // SAVE FEEDBACK
    public function save()
    {
        $userId = session()->get('user_id');

        $user = $this->db->table('users')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        $data = [
            'user_id' => $userId,
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'message' => $this->request->getPost('message'),
            'rating' => $this->request->getPost('rating'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('feedback')->insert($data);

        return redirect()->back()->with('success', 'Feedback submitted successfully!');
    }
}