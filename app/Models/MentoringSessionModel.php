<?php

namespace App\Models;

use CodeIgniter\Model;

class MentoringSessionModel extends Model
{
    protected $table = 'mentoring_sessions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'mentor_id', 'goal_id', 'session_type',
        'scheduled_at', 'duration_minutes', 'amount', 'status',
        'payment_status', 'notes', 'feedback_rating', 'feedback_comment'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';

    public function getUserSessions($userId)
    {
        return $this->select('mentoring_sessions.*, mentors.name as mentor_name, mentors.expertise')
                   ->join('mentors', 'mentors.id = mentoring_sessions.mentor_id')
                   ->where('mentoring_sessions.user_id', $userId)
                   ->orderBy('scheduled_at', 'DESC')
                   ->findAll();
    }

    public function getMentorSessions($mentorId)
    {
        return $this->select('mentoring_sessions.*, users.name as user_name, users.email as user_email')
                   ->join('users', 'users.id = mentoring_sessions.user_id')
                   ->where('mentoring_sessions.mentor_id', $mentorId)
                   ->orderBy('scheduled_at', 'DESC')
                   ->findAll();
    }
}