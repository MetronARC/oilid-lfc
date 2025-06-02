<?php

namespace App\Controllers;

class Read extends BaseController
{
    public function item_query()
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method. Please use AJAX calls only.',
                    'error_type' => 'request_error'
                ]);
            }

            $uid = $this->request->getPost('uid');
            
            if (empty($uid)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No UID provided in the request',
                    'error_type' => 'validation_error'
                ]);
            }

            $db = \Config\Database::connect();
            
            try {
                $query = $db->query('SELECT "item_name", "item_type", "item_image" FROM "item_list" WHERE "item_uid" = ?', [$uid]);
                
                if (!$query) {
                    throw new \Exception('Database query failed');
                }

                $result = $query->getRow();

                if ($result) {
                    return $this->response->setJSON([
                        'success' => true,
                        'item' => [
                            'item_uid' => $uid,
                            'item_name' => $result->item_name,
                            'item_type' => $result->item_type,
                            'item_image' => $result->item_image
                        ]
                    ]);
                }

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Item not found in database',
                    'error_type' => 'not_found',
                    'uid' => $uid
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Database error in item_query: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database error occurred: ' . $e->getMessage(),
                    'error_type' => 'database_error'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'General error in item_query: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error_type' => 'server_error'
            ]);
        }
    }

    public function item_inspect()
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method. Please use AJAX calls only.',
                    'error_type' => 'request_error'
                ]);
            }

            $uid = $this->request->getPost('uid');
            
            if (empty($uid)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No UID provided in the request',
                    'error_type' => 'validation_error'
                ]);
            }

            $db = \Config\Database::connect();
            
            try {
                // Log the incoming UID for debugging
                log_message('debug', 'Inspecting item with UID: ' . $uid);

                $query = $db->query(
                    'SELECT i."item_name", i."item_type", i."item_image", 
                            COALESCE(insp."inspection_status", \'No Previous Inspection\') as "last_status",
                            COALESCE(insp."inspection_note", \'\') as "last_notes",
                            COALESCE(insp."inspection_timestamp"::text, \'\') as "last_inspection_date"
                     FROM "item_list" i 
                     LEFT JOIN (
                         SELECT * FROM "inspection_data" 
                         WHERE "item_uid" = ? 
                         ORDER BY "inspection_timestamp" DESC 
                         LIMIT 1
                     ) insp ON i."item_uid" = insp."item_uid" 
                     WHERE i."item_uid" = ?', 
                    [$uid, $uid]
                );
                
                if (!$query) {
                    throw new \Exception('Database query failed');
                }

                $result = $query->getRow();

                if ($result) {
                    // Log successful query
                    log_message('debug', 'Item found: ' . json_encode($result));
                    
                    return $this->response->setJSON([
                        'success' => true,
                        'item' => [
                            'item_uid' => $uid,
                            'item_name' => $result->item_name,
                            'item_type' => $result->item_type,
                            'item_image' => $result->item_image,
                            'last_status' => $result->last_status,
                            'last_notes' => $result->last_notes,
                            'last_inspection_date' => $result->last_inspection_date
                        ]
                    ]);
                }

                // Log when item is not found
                log_message('debug', 'No item found with UID: ' . $uid);
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Item not found in database',
                    'error_type' => 'not_found',
                    'uid' => $uid
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Database error in item_inspect: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database error occurred: ' . $e->getMessage(),
                    'error_type' => 'database_error'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'General error in item_inspect: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error_type' => 'server_error'
            ]);
        }
    }

    public function save_inspection()
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method. Please use AJAX calls only.',
                    'error_type' => 'request_error'
                ]);
            }

            $session = session();
            $inspector_uid = $session->get('inspector_uid');

            if (!$inspector_uid) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Inspector session not found. Please login again.',
                    'error_type' => 'auth_error'
                ]);
            }

            $uid = $this->request->getPost('uid');
            $status = $this->request->getPost('status');
            $notes = $this->request->getPost('notes');
            
            if (empty($uid) || empty($status)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'error_type' => 'validation_error'
                ]);
            }

            $db = \Config\Database::connect();
            
            try {
                // Get inspector name from inspector_users table
                $inspector_query = $db->query('SELECT "inspector_name" FROM "inspector_users" WHERE "inspector_uid" = ?', [$inspector_uid]);
                $inspector_result = $inspector_query->getRow();
                
                // Use inspector_name from database if found, fallback to inspector_uid if not found
                $inspection_user = $inspector_result ? $inspector_result->inspector_name : $inspector_uid;
                
                // Start transaction
                $db->transStart();
                
                // Save to local inspection_data
                $query = $db->query('INSERT INTO "inspection_data" ("item_uid", "inspection_status", "inspection_note", "inspection_timestamp", "inspection_user") 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP AT TIME ZONE \'Asia/Jakarta\', ?)', 
                    [$uid, $status, $notes, $inspection_user]
                );

                // Prepare data for sync queue
                $inspection_data = [
                    'item_uid' => $uid,
                    'inspection_status' => $status,
                    'inspection_note' => $notes,
                    'inspection_timestamp' => date('Y-m-d H:i:s'),
                    'inspection_user' => $inspection_user
                ];

                // Add to sync queue
                $db->query(
                    'INSERT INTO "sync_queue" ("inspection_data", "created_at") 
                    VALUES (?, CURRENT_TIMESTAMP)',
                    [json_encode($inspection_data)]
                );

                // Complete transaction
                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new \Exception('Failed to save inspection data');
                }

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Inspection data saved successfully'
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Database error in save_inspection: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database error occurred: ' . $e->getMessage(),
                    'error_type' => 'database_error'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'General error in save_inspection: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error_type' => 'server_error'
            ]);
        }
    }

    public function get_inspection_history()
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method. Please use AJAX calls only.',
                    'error_type' => 'request_error'
                ]);
            }

            $session = session();
            $inspector_uid = $session->get('inspector_uid');

            if (!$inspector_uid) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Inspector session not found. Please login again.',
                    'error_type' => 'auth_error'
                ]);
            }

            $uid = $this->request->getPost('uid');
            
            if (empty($uid)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No UID provided in the request',
                    'error_type' => 'validation_error'
                ]);
            }

            $db = \Config\Database::connect();
            
            try {
                $query = $db->query(
                    'SELECT "inspection_status", "inspection_note", "inspection_timestamp", "inspection_user" 
                     FROM "inspection_data" 
                     WHERE "item_uid" = ? 
                     ORDER BY "inspection_timestamp" DESC',
                    [$uid]
                );
                
                if (!$query) {
                    throw new \Exception('Database query failed');
                }

                $results = $query->getResult();

                return $this->response->setJSON([
                    'success' => true,
                    'history' => $results
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Database error in get_inspection_history: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database error occurred: ' . $e->getMessage(),
                    'error_type' => 'database_error'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'General error in get_inspection_history: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error_type' => 'server_error'
            ]);
        }
    }
}