<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class AdminFeedbackController extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // MAIN PAGE
    public function index()
    {
        $search = $this->request->getGet('search');
        $role   = $this->request->getGet('role');

        $builder = $this->db->table('feedback');

        if (!empty($search)) {
            $builder->like('name', $search);
        }

        if (!empty($role)) {
            $builder->where('role', $role);
        }

        $feedbacks = $builder
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin/feedback', [
            'feedbacks' => $feedbacks,
            'search' => $search,
            'role' => $role
        ]);
    }

    // AJAX SUGGESTIONS
    public function suggestions()
    {
        $term = $this->request->getGet('term');

        $results = $this->db->table('feedback')
            ->select('DISTINCT name')
            ->like('name', $term)
            ->limit(5)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($results);
    }
}