<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateApplicationStatusEnumForHold extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE applications MODIFY status ENUM(
                'applied',
                'ai_interview_started',
                'ai_interview_completed',
                'ai_evaluated',
                'shortlisted',
                'hold',
                'rejected',
                'interview_slot_booked',
                'selected',
                'hired',
                'withdrawn'
            ) DEFAULT NULL"
        );
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE applications MODIFY status ENUM(
                'applied',
                'ai_interview_started',
                'ai_interview_completed',
                'ai_evaluated',
                'shortlisted',
                'rejected',
                'interview_slot_booked',
                'selected',
                'hired',
                'withdrawn'
            ) DEFAULT NULL"
        );
    }
}
