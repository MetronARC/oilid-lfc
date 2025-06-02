<?php

namespace App\Services;

class SupabaseService
{
    private $supabaseDb;
    
    public function __construct()
    {
        try {
            // Initialize Supabase connection
            $this->supabaseDb = \Config\Database::connect('supabase');
            
            // Test the connection
            $this->supabaseDb->initialize();
        } catch (\Exception $e) {
            log_message('error', 'Supabase connection error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            throw new \RuntimeException('Failed to connect to Supabase: ' . $e->getMessage());
        }
    }

    public function insertInspectionData($data)
    {
        try {
            if (!$this->isValidInspectionData($data)) {
                throw new \InvalidArgumentException('Invalid inspection data format');
            }

            $query = $this->supabaseDb->query(
                'INSERT INTO "inspection_data" ("item_uid", "inspection_status", "inspection_note", "inspection_timestamp", "inspection_user") 
                VALUES (?, ?, ?, ?, ?)',
                [
                    $data['item_uid'],
                    $data['inspection_status'],
                    $data['inspection_note'],
                    $data['inspection_timestamp'],
                    $data['inspection_user']
                ]
            );
            
            return $query ? true : false;
        } catch (\Exception $e) {
            log_message('error', 'Supabase sync error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    private function isValidInspectionData($data)
    {
        $requiredFields = [
            'item_uid',
            'inspection_status',
            'inspection_note',
            'inspection_timestamp',
            'inspection_user'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                log_message('error', 'Missing required field: ' . $field);
                return false;
            }
        }

        return true;
    }
} 