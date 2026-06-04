<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class AdminJobController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // LIST PAGE
    public function index()
    {
        $search = $this->request->getGet('search');

        $builder = $this->db->table('jobs');

        if ($search) {
            $builder->like('title', $search);
        }

        $jobs = $builder
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin/jobs', [
            'jobs' => $jobs,
            'search' => $search
        ]);
    }

    // SEARCH SUGGESTIONS
    public function suggestions()
    {
        $term = $this->request->getGet('term');

        $builder = $this->db->table('jobs');

        $data = $builder
            ->select('title')
            ->like('title', $term)
            ->limit(5)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($data);
    }

    // GET SINGLE JOB (FOR MODAL)
    public function getJob($id)
    {
        $builder = $this->db->table('jobs');

        $job = $builder
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $this->response->setJSON($job);
    }
}