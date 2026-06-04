<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterCandidateNoteModel extends Model
{
    protected $table = 'recruiter_candidate_notes';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'candidate_id',
        'recruiter_id',
        'tags',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getByCandidateAndRecruiter(int $candidateId, int $recruiterId): ?array
    {
        return $this->where('candidate_id', $candidateId)
            ->where('recruiter_id', $recruiterId)
            ->first();
    }
}
