<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMultiLanguageSupport extends Migration
{
    public function up()
    {
        // Add preferred_language column to users table
        $this->forge->addColumn('users', [
            'preferred_language' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'default' => 'en',
                'null' => false,
                'after' => 'email'
            ]
        ]);

        // Create translations cache table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'source_text' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'source_lang' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'default' => 'en',
            ],
            'target_lang' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => false,
            ],
            'translated_text' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'hash' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('hash');
        $this->forge->addKey(['source_lang', 'target_lang']);
        $this->forge->createTable('translation_cache');

        // Create voice_interactions table (optional - for analytics)
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
                'unsigned' => true,
                'null' => false,
            ],
            'language' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => false,
            ],
            'interaction_type' => [
                'type' => 'ENUM',
                'constraint' => ['voice_input', 'text_to_speech', 'interview'],
                'default' => 'voice_input',
            ],
            'content' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'success' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('language');
        $this->forge->createTable('voice_interactions');

        // Add regional_preference to users (optional)
        $this->forge->addColumn('users', [
            'regional_preference' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'preferred_language',
                'comment' => 'Preferred city/region for job search'
            ]
        ]);
    }

    public function down()
    {
        // Remove columns
        $this->forge->dropColumn('users', ['preferred_language', 'regional_preference']);
        
        // Drop tables
        $this->forge->dropTable('translation_cache', true);
        $this->forge->dropTable('voice_interactions', true);
    }
}