<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTempRfidTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'rfid_temp' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('temp_rfid');

        // Insert a single row that will be used to store the temporary RFID
        $this->db->query("INSERT INTO temp_rfid (rfid_temp, created_at, updated_at) VALUES (NULL, NOW(), NOW())");
    }

    public function down()
    {
        $this->forge->dropTable('temp_rfid');
    }
} 