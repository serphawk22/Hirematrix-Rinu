<?php

namespace App\Models;

use CodeIgniter\Model;

class CareerTransitionModel extends Model
{
    protected $table = 'career_transitions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'candidate_id',
        'current_role',
        'target_role',
        'skill_gaps',
        'learning_roadmap',
        'status',           // Using existing: enum('active', 'completed', 'paused')
        'course_status',    // Using existing: enum('pending', 'processing', 'completed', 'failed')
        'created_at',
        'deactivated_at',
        'reactivated_at',
        'reactivation_count',
        'updated_at',
        'course_status'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get active transition for a candidate
     * Now checks for both active status and any course_status
     */
    public function getActiveTransition($candidateId)
    {
        return $this->where('candidate_id', $candidateId)
                    ->where('status', 'active')
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }

    /**
     * Get transitions that are still being processed
     */
    public function getPendingTransitions()
    {
        return $this->whereIn('course_status', ['pending', 'processing'])
                    ->where('status', 'active')
                    ->findAll();
    }

    /**
     * Check if a transition's course is ready
     */
    public function isCourseReady($transitionId)
    {
        $transition = $this->find($transitionId);
        return $transition && $transition['course_status'] === 'completed';
    }

    /**
     * Get completed transitions for a candidate
     */
    public function getCompletedTransitions($candidateId)
    {
        return $this->where('candidate_id', $candidateId)
                    ->where('status', 'completed')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}