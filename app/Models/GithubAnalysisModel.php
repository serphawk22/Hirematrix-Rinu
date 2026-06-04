<?php

namespace App\Models;

use CodeIgniter\Model;

class GithubAnalysisModel extends Model
{
    protected $table = 'candidate_github_stats';

    protected $allowedFields = [
        'candidate_id',
        'github_username',
        'repo_count',
        'commit_count',
        'languages_used',
        'github_score',
        'created_at'
    ];
}
