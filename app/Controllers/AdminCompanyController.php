<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class AdminCompanyController extends Controller
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

        $builder = $this->db->table('companies');

        if (!empty($search)) {
            $builder->like('name', $search);
        }

        $companies = $builder
            ->orderBy('id', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return view('admin/companies', [
            'companies' => $companies,
            'search' => $search
        ]);
    }

    // SEARCH SUGGESTIONS
    public function suggestions()
    {
        $term = $this->request->getGet('term');

        $data = $this->db->table('companies')
            ->select('id, name')
            ->like('name', $term)
            ->limit(5)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($data);
    }

    // FETCH SINGLE COMPANY (POPUP)
    public function getCompany($id)
    {
        $company = $this->db->table('companies')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $this->response->setJSON($company);
    }

    public function delete($id)
{
    $this->db->table('companies')->where('id', $id)->delete();

    return $this->response->setJSON([
        'status' => 'success'
    ]);
}
}