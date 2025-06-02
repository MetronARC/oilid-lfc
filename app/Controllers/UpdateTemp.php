<?php

namespace App\Controllers;

class UpdateTemp extends BaseController
{

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect('default');
    }
    public function updateTempRFID()
    {
        try {
            $json = $this->request->getJSON();
            
            if (!$json || !is_array($json)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid JSON data format'
                ]);
            }

            $updateCount = 0;
            $errors = [];

            foreach ($json as $sensorData) {
                if (!isset($sensorData->rfid_temp)) {
                    $errors[] = 'Missing required fields (rfid_temp)';
                    continue;
                }

                $result = $this->db->table('temp_rfid')
                    ->where('id', 1)
                    ->update(['rfid_temp' => $sensorData->rfid_temp]);

                if ($result) {
                    $updateCount++;
                } else {
                    $errors[] = "Failed to update RFID: {$sensorData->rfid_temp}";
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => "$updateCount Temporary RFID updated successfully",
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error updating RFID: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update RFID',
                'error' => $e->getMessage()
            ]);
        }
    }
}
