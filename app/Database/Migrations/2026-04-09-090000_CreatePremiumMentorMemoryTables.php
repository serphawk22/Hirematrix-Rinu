<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePremiumMentorMemoryTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('premium_mentor_memories')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'memory_summary' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'key_facts' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'last_compacted_at' => [
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
            $this->forge->addKey('user_id');
            $this->forge->addUniqueKey('user_id', 'premium_mentor_memories_user_unique');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'premium_mentor_memories_user_fk');
            $this->forge->createTable('premium_mentor_memories', true);
        }

        if (!$this->db->tableExists('premium_mentor_messages')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'session_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                ],
                'content' => [
                    'type' => 'LONGTEXT',
                    'null' => false,
                ],
                'compacted' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
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
            $this->forge->addKey('user_id');
            $this->forge->addKey('session_id');
            $this->forge->addKey('compacted');
            $this->forge->addKey('created_at');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'premium_mentor_messages_user_fk');
            $this->forge->createTable('premium_mentor_messages', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('premium_mentor_messages')) {
            $this->forge->dropTable('premium_mentor_messages', true);
        }

        if ($this->db->tableExists('premium_mentor_memories')) {
            $this->forge->dropTable('premium_mentor_memories', true);
        }
    }
}
