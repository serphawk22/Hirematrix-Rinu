<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignApplicationStatusEnumWithAiInterviewCompleted extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('applications')) {
            return;
        }

        $this->db->query("UPDATE applications SET status = 'ai_interview_completed' WHERE status = 'ai_evaluated'");

        $this->db->query(
            "ALTER TABLE applications MODIFY status ENUM(
                'pending',
                'applied',
                'ai_interview_started',
                'ai_interview_completed',
                'shortlisted',
                'hold',
                'filtered_out',
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
        if (!$this->db->tableExists('applications')) {
            return;
        }

        $this->db->query(
            "ALTER TABLE applications MODIFY status ENUM(
                'pending',
                'applied',
                'ai_interview_started',
                'ai_interview_completed',
                'ai_evaluated',
                'shortlisted',
                'hold',
                'filtered_out',
                'rejected',
                'interview_slot_booked',
                'selected',
                'hired',
                'withdrawn'
            ) DEFAULT NULL"
        );
    }
}
