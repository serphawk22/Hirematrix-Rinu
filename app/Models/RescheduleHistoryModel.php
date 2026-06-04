<?php

namespace App\Models;

use CodeIgniter\Model;

class RescheduleHistoryModel extends Model
{
    protected $table = 'reschedule_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'booking_id',
        'old_slot_id',
        'new_slot_id',
        'old_slot_datetime',
        'new_slot_datetime',
        'reason',
        'rescheduled_by',
        'rescheduled_at'
    ];
    
    protected $useTimestamps = false;
    
    /**
     * Get reschedule history for a booking
     */
    public function getBookingHistory(int $bookingId): array
    {
        return $this->where('booking_id', $bookingId)
                    ->orderBy('rescheduled_at', 'DESC')
                    ->findAll();
    }
}
?>