<?php

namespace App\Controllers;

class Users extends BaseController
{
    public function inspector_login()
    {
        // Check if this is an AJAX request
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }

        // Get the RFID from the POST data
        $rfid = $this->request->getPost('rfid');

        if (empty($rfid)) {
            return $this->response->setJSON(['success' => false, 'message' => 'RFID data is required']);
        }

        // Initialize the database connection
        $db = \Config\Database::connect();

        // Prepare and execute the query with parameter binding for security
        $query = $db->query("SELECT ID, inspector_name, inspector_rfid FROM inspector_users WHERE inspector_rfid = ?", [$rfid]);
        $result = $query->getRow();

        if ($result) {
            // Create session data
            $sessionData = [
                'inspector_id'    => $result->ID,
                'inspector_name'  => $result->inspector_name,
                'inspector_rfid'  => $result->inspector_rfid,
                'is_logged_in'   => true,
                'login_time'      => time()
            ];

            // Start the session
            $session = session();
            $session->set($sessionData);

            // Set session expiration to 3 hours
            $session->setTempdata('is_logged_in', true, 10800); // 3 hours in seconds

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Welcome, ' . $result->inspector_name,
                'redirect' => base_url('/dashboard') // Updated redirect URL to pages/dashboard
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Inspector data not found!'
        ]);
    }

    // Add a method to check if inspector is logged in
    public function isLoggedIn()
    {
        $session = session();
        return $session->get('is_logged_in') &&
            (time() - $session->get('login_time')) < 10800; // 3 hours
    }
}
