<?php

namespace App\Controllers;

use App\Controllers\BaseController;
class AdminUserController extends BaseController
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

        $builder = $this->db->table('users');
        $builder->select('users.*');

        if ($this->db->tableExists('recruiter_profiles')) {
            $builder->join('recruiter_profiles', 'recruiter_profiles.user_id = users.id', 'left');
            foreach (['recruiter_type', 'verification_status', 'can_post_jobs', 'agency_registration_number', 'gst_number', 'official_email'] as $field) {
                if ($this->db->fieldExists($field, 'recruiter_profiles')) {
                    $builder->select("recruiter_profiles.{$field} AS {$field}");
                }
            }
        }

        if (!empty($search)) {
            $builder->like('name', $search);
        }

        if (!empty($role)) {
            $builder->where('role', $role);
        }

        $users = $builder->orderBy('id', 'DESC')->get()->getResultArray();

        return view('admin/users', [
            'users' => $users,
            'search' => $search,
            'role' => $role
        ]);
    }

    // AJAX SUGGESTIONS
    public function suggestions()
    {
        $term = $this->request->getGet('term');

        $builder = $this->db->table('users');

        if ($term) {
            $builder->like('name', $term);
        }

        $results = $builder
            ->select('name')
            ->limit(5)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($results);
    }

    public function updateRecruiterVerification(int $userId)
    {
        if (!$this->db->tableExists('recruiter_profiles')) {
            return redirect()->back()->with('error', 'Recruiter profiles table is not available.');
        }

        $user = $this->db->table('users')
            ->where('id', $userId)
            ->where('role', 'recruiter')
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->back()->with('error', 'Recruiter not found.');
        }

        $status = (string) $this->request->getPost('verification_status');
        if (!in_array($status, ['pending', 'verified', 'rejected'], true)) {
            return redirect()->back()->with('error', 'Invalid verification status.');
        }

        $payload = [
            'verification_status' => $status,
            'can_post_jobs' => $status === 'verified' ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        foreach (array_keys($payload) as $field) {
            if (!$this->db->fieldExists($field, 'recruiter_profiles')) {
                unset($payload[$field]);
            }
        }

        if (empty($payload)) {
            return redirect()->back()->with('error', 'Recruiter verification fields are not available.');
        }

        $profile = $this->db->table('recruiter_profiles')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if ($profile) {
            $this->db->table('recruiter_profiles')->where('user_id', $userId)->update($payload);
        } else {
            $payload['user_id'] = $userId;
            if ($this->db->fieldExists('created_at', 'recruiter_profiles')) {
                $payload['created_at'] = date('Y-m-d H:i:s');
            }
            $this->db->table('recruiter_profiles')->insert($payload);
        }

        return redirect()->back()->with('success', 'Recruiter verification updated.');
    }
}
