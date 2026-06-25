<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidateEmailReplyNotificationType extends Migration
{
    private const BASE_TYPES = "'resume_not_uploaded','ai_not_started','ai_incomplete','slot_not_booked','reschedule_required','interview_scheduled','result_published'";

    public function up()
    {
        if (!$this->db->tableExists('notifications')) {
            return;
        }
        $this->db->query("ALTER TABLE notifications MODIFY type ENUM(" . self::BASE_TYPES . ",'candidate_email_reply') NOT NULL");
        if ($this->db->tableExists('recruiter_email_activities') && $this->db->fieldExists('notified_at', 'recruiter_email_activities')) {
            $this->db->table('recruiter_email_activities')->where('direction', 'inbound')->update(['notified_at' => null]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('notifications')) {
            $this->db->table('notifications')->where('type', 'candidate_email_reply')->delete();
            $this->db->query("ALTER TABLE notifications MODIFY type ENUM(" . self::BASE_TYPES . ") NOT NULL");
        }
    }
}
