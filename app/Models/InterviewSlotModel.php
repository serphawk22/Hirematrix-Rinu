<?php

namespace App\Models;

use CodeIgniter\Model;

class InterviewSlotModel extends Model
{
    protected $table = 'interview_slots';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'job_id',
        'slot_date',
        'slot_time',
        'slot_datetime',
        'capacity',
        'booked_count',
        'is_available',
        'created_by',
        'created_at'
    ];
    
    protected $useTimestamps = false;
    
    /**
     * Get available slots for a job
     */
    public function getAvailableSlots(int $jobId): array
    {
        return $this->where('job_id', $jobId)
                    ->where('is_available', 1)
                    ->where('slot_datetime >', date('Y-m-d H:i:s'))
                    ->where('booked_count < capacity')
                    ->orderBy('slot_datetime', 'ASC')
                    ->findAll();
    }
    
    /**
     * Get available slots grouped by date
     */
    public function getAvailableSlotsGrouped(int $jobId): array
    {
        $slots = $this->getAvailableSlots($jobId);
        
        $grouped = [];
        foreach ($slots as $slot) {
            $date = $slot['slot_date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $slot;
        }
        
        return $grouped;
    }
    
    /**
     * Check if slot is available
     */
    public function isSlotAvailable(int $slotId): bool
    {
        $slot = $this->find($slotId);
        
        if (!$slot) {
            return false;
        }
        
        return $slot['is_available'] == 1 
            && $slot['booked_count'] < $slot['capacity']
            && strtotime($slot['slot_datetime']) > time();
    }
    
    /**
     * Increment booked count
     */
    public function incrementBookedCount(int $slotId): bool
    {
        $slot = $this->find($slotId);
        
        if (!$slot) {
            return false;
        }
        
        $newCount = $slot['booked_count'] + 1;
        
        $updateData = ['booked_count' => $newCount];
        
        // If fully booked, mark as unavailable
        if ($newCount >= $slot['capacity']) {
            $updateData['is_available'] = 0;
        }
        
        return $this->update($slotId, $updateData);
    }
    
    /**
     * Decrement booked count (when rescheduling)
     */
    public function decrementBookedCount(int $slotId): bool
    {
        $slot = $this->find($slotId);
        
        if (!$slot || $slot['booked_count'] <= 0) {
            return false;
        }
        
        $newCount = $slot['booked_count'] - 1;
        
        return $this->update($slotId, [
            'booked_count' => $newCount,
            'is_available' => 1 // Make available again
        ]);
    }
    
    /**
     * Create bulk slots
     */
    public function createBulkSlots(int $jobId, string $date, array $times, int $createdBy, int $capacity = 1): int
    {
        $count = 0;
        
        foreach ($times as $time) {
            $datetime = $date . ' ' . $time;
            
            $inserted = $this->insert([
                'job_id' => $jobId,
                'slot_date' => $date,
                'slot_time' => $time,
                'slot_datetime' => $datetime,
                'capacity' => $capacity,
                'booked_count' => 0,
                'is_available' => 1,
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($inserted) {
                $count++;
            }
        }
        
        return $count;
    }
}
