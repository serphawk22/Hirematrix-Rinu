<?php

namespace App\Controllers;

use App\Models\BlogModel;
use CodeIgniter\Validation\Validation;

class AdminBlogController extends BaseController
{
    protected BlogModel $blogModel;
    protected $db;
    protected Validation $validation;

    public function __construct()
    {
        $this->blogModel = new BlogModel();
        $this->db = \Config\Database::connect();
        $this->validation = \Config\Services::validation();
    }

    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));
        $status = trim((string) $this->request->getGet('status'));

        $posts = [];
        if ($this->db->tableExists('blog_posts')) {
            $builder = $this->blogModel->orderBy('id', 'DESC');

            if ($search !== '') {
                $builder = $builder->groupStart()
                    ->like('title', $search)
                    ->orLike('slug', $search)
                    ->groupEnd();
            }

            if ($status !== '') {
                $builder = $builder->where('status', $status);
            }

            $posts = $builder->findAll();
        }

        return view('admin/blogs', [
            'posts' => $posts,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create()
    {   
        $loggedInAdminEmail = session()->get('admin_email') ?? 'admin@local.test';
        $loggedInAdminName = session()->get('admin_name') ?? 'Admin User'; // Assuming admin_name is stored in session

        return view('admin/blog_form', [
            'author_email' => $loggedInAdminEmail,
            'author_name' => $loggedInAdminName,
            'post' => null,
            'errors' => session()->getFlashdata('errors') ?? [], // Pass errors from session
            'validation' => $this->validation, // Pass validation service
        ]);
    }

    public function store()
    {
        $data = $this->collectPayload();
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        // Use model's save method which triggers validation
        if (!$this->blogModel->save($data)) {
            return redirect()->back()->withInput()->with('errors', $this->blogModel->errors());
        }

        return redirect()->to(base_url('admin/blogs'))->with('success', 'Blog post created successfully.');
    }

    public function edit(int $id)
    {
        $post = $this->blogModel->find($id);
        if (!$post) {
            return redirect()->to(base_url('admin/blogs'))->with('error', 'Blog post not found.');
        }

        return view('admin/blog_form', [
            'post' => $post,
            'errors' => session()->getFlashdata('errors') ?? [], // Pass errors from session
            'validation' => $this->validation, // Pass validation service
        ]);
    }

    public function update(int $id)
    {
        $post = $this->blogModel->find($id);
        if (!$post) {
            return redirect()->to(base_url('admin/blogs'))->with('error', 'Blog post not found.');
        }

        $data = $this->collectPayload(); // Collect data without ID initially
        $data['id'] = $id; // Add ID for update operation
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {
            // Use model's save method which triggers validation
            if (!$this->blogModel->save($data)) {
                log_message('error', 'Blog post update failed validation: ' . json_encode($this->blogModel->errors()));
                return redirect()->back()->withInput()->with('errors', $this->blogModel->errors());
            }
        } catch (\Throwable $e) {
            log_message('error', 'Blog post update failed with exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->withInput()->with('error', 'An unexpected error occurred during update. Please check logs.');
        }
        
        return redirect()->to(base_url('admin/blogs'))->with('success', 'Blog post updated successfully.');
    }

    public function delete(int $id)
    {
        $post = $this->blogModel->find($id);
        if ($post) {
            $this->blogModel->delete($id);
        }

        return redirect()->to(base_url('admin/blogs'))->with('success', 'Blog post deleted successfully.');
    }

    private function collectPayload(): array // Removed $id parameter as it's not directly used for collection
    {
        $title = trim((string) $this->request->getPost('title'));
        $slugInput = trim((string) $this->request->getPost('slug'));
        $status = trim((string) $this->request->getPost('status'));
        $publishedAt = trim((string) $this->request->getPost('published_at'));

        if ($slugInput === '') {
            $slugInput = url_title($title, '-', true);
        }

        return [
            'title' => $title,
            'slug' => url_title($slugInput, '-', true),
            'excerpt' => trim((string) $this->request->getPost('excerpt')),
            'content' => trim((string) $this->request->getPost('content')),
            'cover_image' => trim((string) $this->request->getPost('cover_image')),
            'status' => in_array($status, ['draft', 'published'], true) ? $status : 'draft',
            'featured' => $this->request->getPost('featured') ? 1 : 0,
            'published_at' => $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt)) : null,
            // Ensure author_email and author_name are always present, falling back to session if form is empty
            'author_email' => (string) ($this->request->getPost('author_email') ?: (session()->get('admin_email') ?? 'admin@local.test')),
            'author_name' => (string) ($this->request->getPost('author_name') ?: (session()->get('admin_name') ?? 'Admin User')),
        ];
    }

}
            