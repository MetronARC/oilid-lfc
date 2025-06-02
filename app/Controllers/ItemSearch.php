<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class ItemSearch extends Controller
{
    public function search()
    {
        try {
            if (!$this->request->isAJAX()) {
                log_message('error', 'Non-AJAX request received in ItemSearch::search');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }

            $uid = $this->request->getPost('uid');
            
            if (empty($uid)) {
                log_message('info', 'Empty UID received in search request');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Please provide a UID'
                ]);
            }

            log_message('info', 'Searching for item with UID: ' . $uid);
            
            $db = \Config\Database::connect();
            $query = $db->query('SELECT "item_name", "item_type", "item_certNo", "item_manufacturer", "item_partNo", "item_supplier", "item_description" FROM "item_list" WHERE "item_uid" = ?', [$uid]);
            
            if (!$query) {
                log_message('error', 'Database query failed for UID: ' . $uid);
                throw new \Exception('Database query failed');
            }

            $result = $query->getRow();

            if ($result) {
                log_message('info', 'Item found for UID: ' . $uid);
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $result
                ]);
            }

            log_message('info', 'No item found for UID: ' . $uid);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No item found with this UID'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in ItemSearch::search: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while searching for the item'
            ]);
        }
    }
} 