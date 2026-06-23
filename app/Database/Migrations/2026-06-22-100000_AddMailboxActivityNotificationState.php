<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMailboxActivityNotificationState extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('recruiter_email_activities') && !$this->db->fieldExists('notified_at', 'recruiter_email_activities')) {
            $this->forge->addColumn('recruiter_email_activities', [
                'notified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'occurred_at'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('recruiter_email_activities') && $this->db->fieldExists('notified_at', 'recruiter_email_activities')) {
            $this->forge->dropColumn('recruiter_email_activities', 'notified_at');
        }
    }
}
