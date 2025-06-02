<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class Inspector extends Controller
{
    private $encryptionKey;

    public function __construct()
    {
        $this->encryptionKey = getenv('encryption.key');
        
        // Only require encryption key for operations that need it
        $encryptionRoutes = ['register', 'login', 'nfc_login'];
        $currentRoute = service('router')->methodName();
        
        if (in_array($currentRoute, $encryptionRoutes) && !$this->encryptionKey) {
            log_message('error', 'Encryption key not found in .env file');
            throw new \RuntimeException('Encryption key not configured');
        }
    }

    private function encryptPassword($password)
    {
        $cipher = "AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($password, $cipher, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decryptPassword($encryptedData)
    {
        $cipher = "AES-256-CBC";
        $encryptedData = base64_decode($encryptedData);
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($encryptedData, 0, $ivlen);
        $encrypted = substr($encryptedData, $ivlen);
        return openssl_decrypt($encrypted, $cipher, $this->encryptionKey, 0, $iv);
    }

    private function hashPassword($password)
    {
        // Generate a random salt
        $salt = random_bytes(16);
        // Hash the password with the salt using PBKDF2
        $hash = hash_pbkdf2(
            "sha256",
            $password,
            $salt,
            10000,  // Number of iterations
            32,     // Length of the hash
            true    // Raw binary output
        );
        // Combine salt and hash for storage
        return base64_encode($salt . $hash);
    }

    private function verifyPassword($password, $storedHash)
    {
        // Decode the stored value
        $decoded = base64_decode($storedHash);
        // Extract salt and hash
        $salt = substr($decoded, 0, 16);
        $hash = substr($decoded, 16);
        // Hash the input password with the same salt
        $newHash = hash_pbkdf2(
            "sha256",
            $password,
            $salt,
            10000,
            32,
            true
        );
        // Compare the hashes
        return hash_equals($hash, $newHash);
    }

    private function setUserSession($uid)
    {
        $session = session();
        
        // Get inspector name from database
        $db = \Config\Database::connect();
        $query = $db->query('SELECT "inspector_name" FROM "inspector_users" WHERE "inspector_uid" = ?', [$uid]);
        $result = $query->getRow();
        
        $session->set([
            'isLoggedIn' => true,
            'inspector_uid' => $uid,
            'inspector_name' => $result ? $result->inspector_name : $uid, // Fallback to UID if name not found
            'login_time' => time()
        ]);
        log_message('info', 'Session created for inspector: ' . $uid);
    }

    public function logout()
    {
        $session = session();
        $uid = $session->get('inspector_uid');
        log_message('info', 'Logging out inspector: ' . $uid);
        
        $session->destroy();
        
        // Check if it's an AJAX request
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => base_url()
            ]);
        }
        
        // For non-AJAX requests, redirect directly
        return redirect()->to(base_url());
    }

    public function register()
    {
        try {
            if (!$this->request->isAJAX()) {
                log_message('error', 'Non-AJAX request received in Inspector::register');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }

            $uid = $this->request->getPost('uid');
            $password = $this->request->getPost('password');
            
            if (empty($uid) || empty($password)) {
                log_message('info', 'Empty UID or password received in registration request');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Please provide both UID and password'
                ]);
            }

            log_message('info', 'Attempting to register inspector with UID: ' . $uid);
            
            $db = \Config\Database::connect();
            
            // Start transaction
            $db->transStart();
            
            try {
                // Check if UID already exists in either table
                $existingUser = $db->query('SELECT "inspector_uid" FROM "inspector_login_user" WHERE "inspector_uid" = ?', [$uid])->getRow();
                
                if ($existingUser) {
                    log_message('info', 'UID already exists: ' . $uid);
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'This UID is already registered'
                    ]);
                }

                // Encrypt password for inspector_login_user
                $encryptedPassword = $this->encryptPassword($password);
                
                // Hash password for inspector_users
                $hashedPassword = $this->hashPassword($password);
                
                // Insert into inspector_login_user
                $db->query(
                    'INSERT INTO "inspector_login_user" ("inspector_uid", "password_encrypt") VALUES (?, ?)',
                    [$uid, $encryptedPassword]
                );

                // Insert into inspector_users
                $db->query(
                    'INSERT INTO "inspector_users" ("inspector_uid", "password_hash") VALUES (?, ?)',
                    [$uid, $hashedPassword]
                );

                // Commit transaction
                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new \Exception('Transaction failed');
                }

                log_message('info', 'Successfully registered inspector with UID: ' . $uid);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Registration successful'
                ]);

            } catch (\Exception $e) {
                $db->transRollback();
                throw $e;
            }

        } catch (\Exception $e) {
            log_message('error', 'Error in Inspector::register: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred during registration'
            ]);
        }
    }

    public function login()
    {
        try {
            if (!$this->request->isAJAX()) {
                log_message('error', 'Non-AJAX request received in Inspector::login');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }

            $uid = $this->request->getPost('uid');
            $password = $this->request->getPost('password');
            
            if (empty($uid) || empty($password)) {
                log_message('info', 'Empty UID or password received in login request');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Please provide both UID and password'
                ]);
            }

            log_message('info', 'Attempting login for inspector with UID: ' . $uid);
            
            $db = \Config\Database::connect();
            
            // Get user from inspector_users table (using hashed password)
            $user = $db->query('SELECT "password_hash" FROM "inspector_users" WHERE "inspector_uid" = ?', [$uid])->getRow();
            
            if (!$user) {
                log_message('info', 'No user found with UID: ' . $uid);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
            }

            // Verify password using hash
            if ($this->verifyPassword($password, $user->password_hash)) {
                // Set session data
                $this->setUserSession($uid);
                
                log_message('info', 'Successful login for inspector with UID: ' . $uid);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Login successful'
                ]);
            }

            log_message('info', 'Invalid password for inspector with UID: ' . $uid);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in Inspector::login: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred during login'
            ]);
        }
    }

    public function nfc_login()
    {
        try {
            if (!$this->request->isAJAX()) {
                log_message('error', 'Non-AJAX request received in Inspector::nfc_login');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }

            $uid = $this->request->getPost('uid');
            
            if (empty($uid)) {
                log_message('info', 'Empty UID received in NFC login request');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No UID provided'
                ]);
            }

            log_message('info', 'Starting NFC login process for UID: ' . $uid);
            
            $db = \Config\Database::connect();
            
            // Step 1: Get encrypted password from inspector_login_user
            log_message('info', 'Fetching encrypted password from inspector_login_user');
            $encryptedUser = $db->query(
                'SELECT "inspector_uid", "password_encrypt" FROM "inspector_login_user" WHERE "inspector_uid" = ?',
                [$uid]
            )->getRow();
            
            if (!$encryptedUser) {
                log_message('info', 'No user found with UID: ' . $uid);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
            }

            // Step 2: Decrypt the password
            log_message('info', 'Decrypting password for UID: ' . $uid);
            try {
                $decryptedPassword = $this->decryptPassword($encryptedUser->password_encrypt);
            } catch (\Exception $e) {
                log_message('error', 'Failed to decrypt password: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error processing credentials'
                ]);
            }

            // Step 3: Verify against hashed password in inspector_users
            log_message('info', 'Verifying credentials against inspector_users');
            $hashedUser = $db->query(
                'SELECT "password_hash" FROM "inspector_users" WHERE "inspector_uid" = ?',
                [$uid]
            )->getRow();

            if (!$hashedUser) {
                log_message('error', 'User found in inspector_login_user but not in inspector_users. UID: ' . $uid);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error verifying credentials'
                ]);
            }

            // Step 4: Verify the decrypted password against the hash
            if ($this->verifyPassword($decryptedPassword, $hashedUser->password_hash)) {
                // Set session data
                $this->setUserSession($uid);
                
                log_message('info', 'Successful NFC login for inspector with UID: ' . $uid);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Login successful'
                ]);
            }

            log_message('info', 'Invalid credentials for UID: ' . $uid);
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in Inspector::nfc_login: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred during login'
            ]);
        }
    }

    public function check_rfid()
    {
        try {
            log_message('info', 'Starting check_rfid method');
            
            if (!$this->request->isAJAX()) {
                log_message('error', 'Non-AJAX request received in Inspector::check_rfid');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }

            $db = \Config\Database::connect();
            log_message('info', 'Database connection established');
            
            try {
                // Get the first row from temp_rfid table
                log_message('info', 'Attempting to query temp_rfid table');
                $result = $db->query('SELECT "rfid_temp" FROM "temp_rfid" LIMIT 1')->getRow();
                log_message('info', 'Query executed successfully. Result: ' . json_encode($result));
                
                if (!$result || $result->rfid_temp === null) {
                    log_message('info', 'No RFID value found or value is null');
                    return $this->response->setJSON([
                        'success' => true,
                        'rfid' => null
                    ]);
                }

                // Store the RFID value before clearing it
                $rfidValue = $result->rfid_temp;
                log_message('info', 'RFID value found: ' . $rfidValue);

                // Clear the rfid_temp value after reading it
                log_message('info', 'Attempting to clear RFID value from table');
                $db->query('UPDATE "temp_rfid" SET "rfid_temp" = NULL WHERE "rfid_temp" = ?', [$rfidValue]);
                log_message('info', 'RFID value cleared successfully');
                
                return $this->response->setJSON([
                    'success' => true,
                    'rfid' => $rfidValue
                ]);

            } catch (\Exception $dbError) {
                log_message('error', 'Database error in check_rfid: ' . $dbError->getMessage());
                log_message('error', 'Stack trace: ' . $dbError->getTraceAsString());
                throw $dbError;
            }

        } catch (\Exception $e) {
            log_message('error', 'Error in Inspector::check_rfid: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while checking RFID: ' . $e->getMessage()
            ]);
        }
    }

    public function ping()
    {
        return $this->response
            ->setStatusCode(200)
            ->setJSON(['status' => 'ok']);
    }

    public function sync_pending_data()
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }

            $db = \Config\Database::connect();
            
            try {
                $supabaseService = new \App\Services\SupabaseService();
            } catch (\Exception $e) {
                log_message('error', 'Failed to initialize SupabaseService: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to initialize Supabase connection'
                ]);
            }
            
            // Get pending items from queue
            try {
                $query = $db->query(
                    'SELECT * FROM "sync_queue" 
                    WHERE "sync_status" = \'pending\' 
                    AND ("retry_count" < 3 OR "retry_count" IS NULL)
                    ORDER BY "created_at" ASC 
                    LIMIT 10'
                );
                
                $pendingItems = $query->getResult();
            } catch (\Exception $e) {
                log_message('error', 'Database error fetching pending items: ' . $e->getMessage());
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to fetch pending items'
                ]);
            }
            
            $syncedCount = 0;
            $failedItems = [];
            
            foreach ($pendingItems as $item) {
                try {
                    $inspectionData = json_decode($item->inspection_data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid JSON data for item ' . $item->id);
                    }
                    
                    // Try to sync with Supabase
                    if ($supabaseService->insertInspectionData($inspectionData)) {
                        // Update sync status to completed
                        $db->query(
                            'UPDATE "sync_queue" 
                            SET "sync_status" = \'completed\', 
                                "last_sync_attempt" = CURRENT_TIMESTAMP 
                            WHERE "id" = ?',
                            [$item->id]
                        );
                        $syncedCount++;
                    } else {
                        // Update retry count
                        $db->query(
                            'UPDATE "sync_queue" 
                            SET "retry_count" = COALESCE("retry_count", 0) + 1,
                                "last_sync_attempt" = CURRENT_TIMESTAMP 
                            WHERE "id" = ?',
                            [$item->id]
                        );
                        $failedItems[] = $item->id;
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Error syncing item ' . $item->id . ': ' . $e->getMessage());
                    $failedItems[] = $item->id;
                    continue;
                }
            }

            $message = $syncedCount . ' items synchronized successfully';
            if (!empty($failedItems)) {
                $message .= '. Failed to sync items: ' . implode(', ', $failedItems);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'synced_count' => $syncedCount,
                'failed_items' => $failedItems
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Sync error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error during sync process: ' . $e->getMessage()
            ]);
        }
    }
} 