<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentOrdersTable extends Migration
{
    public function up(): void
    {
        // payment_orders table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'plan_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'razorpay_order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'razorpay_payment_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'razorpay_signature' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
                'default'    => 'INR',
            ],
            'receipt' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['created', 'paid', 'failed'],
                'null'       => false,
                'default'    => 'created',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('razorpay_order_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('payment_orders', true);

        // Add order_id to user_subscriptions if not already there
        $db = \Config\Database::connect();
        if ($db->tableExists('user_subscriptions') && !$db->fieldExists('order_id', 'user_subscriptions')) {
            $this->forge->addColumn('user_subscriptions', [
                'order_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'payment_id',
                ],
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('payment_orders', true);

        $db = \Config\Database::connect();
        if ($db->tableExists('user_subscriptions') && $db->fieldExists('order_id', 'user_subscriptions')) {
            $this->forge->dropColumn('user_subscriptions', 'order_id');
        }
    }
}
