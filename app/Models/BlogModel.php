<?php

namespace App\Models;

use CodeIgniter\Model;

class BlogModel extends Model
{
    protected $table            = 'blog_posts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['title', 'slug', 'excerpt', 'content', 'cover_image', 'status', 'featured', 'published_at', 'author_email', 'author_name'];

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    // Validation
    protected $validationRules = [
        'id'           => 'permit_empty', // Added to allow 'id' in validation data for updates
        'title'        => 'required|min_length[5]|max_length[255]',
        'slug'         => 'required|alpha_dash|is_unique[blog_posts.slug,id,{id}]',
        'content'      => 'required',
        'author_email' => 'required|valid_email',
        'author_name'  => 'required|min_length[3]|max_length[100]',
        'status'       => 'required|in_list[draft,published]',
    ];
    protected $skipValidation = false;

    /**
     * Get published blog posts.
     *
     * @param int $limit
     * @return array
     */
    public function getPublishedPosts(int $limit = 10): array
    {
        return $this->where('status', 'published')
                    ->groupStart()
                        ->where('published_at <= CURRENT_TIMESTAMP', null, false)
                        ->orWhere('published_at IS NULL')
                    ->groupEnd()
                    ->orderBy('COALESCE(published_at, created_at)', 'DESC', false)
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get a single published blog post by slug.
     *
     * @param string $slug
     * @return array|null
     */
    public function getPublishedPostBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)
                    ->where('status', 'published')
                    ->groupStart()
                        ->where('published_at <= CURRENT_TIMESTAMP', null, false)
                        ->orWhere('published_at IS NULL')
                    ->groupEnd()
                    ->first();
    }
}            