<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInterviewBookingReviewsTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('interview_booking_reviews')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'booking_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'application_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'candidate_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'job_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'recruiter_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'attendance_status' => [
                    'type' => 'ENUM',
                    'constraint' => ['attended', 'late', 'no_show'],
                    'default' => 'attended',
                ],
                'decision' => [
                    'type' => 'ENUM',
                    'constraint' => ['shortlisted', 'hold', 'selected', 'rejected'],
                    'null' => true,
                ],
                'strengths' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'concerns' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'reviewed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('booking_id', false, true);
            $this->forge->addKey('application_id');
            $this->forge->addKey('candidate_id');
            $this->forge->addKey('job_id');
            $this->forge->addKey('recruiter_id');
            $this->forge->createTable('interview_booking_reviews', true);
        }

        $this->db->query(
            "ALTER TABLE interview_bookings MODIFY booking_status ENUM(
                'booked',
                'confirmed',
                'rescheduled',
                'completed',
                'no_show',
                'cancelled'
            ) DEFAULT 'booked'"
        );
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE interview_bookings MODIFY booking_status ENUM(
                'booked',
                'confirmed',
                'rescheduled',
                'completed',
                'cancelled'
            ) DEFAULT 'booked'"
        );

        if ($this->db->tableExists('interview_booking_reviews')) {
            $this->forge->dropTable('interview_booking_reviews', true);
        }
    }
}
