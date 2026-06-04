<?php

namespace App\Models;

use CodeIgniter\Model;

class InterviewBookingReviewModel extends Model
{
    protected $table = 'interview_booking_reviews';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'booking_id',
        'application_id',
        'candidate_id',
        'job_id',
        'recruiter_id',
        'attendance_status',
        'decision',
        'strengths',
        'concerns',
        'notes',
        'reviewed_at',
    ];

    public function getByBookingId(int $bookingId): ?array
    {
        return $this->where('booking_id', $bookingId)->first();
    }
}
