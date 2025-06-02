<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // If user is not logged in and trying to access a protected page
        if (!$session->get('isLoggedIn'))
        {
            // Log the attempt
            log_message('info', 'Unauthorized access attempt to: ' . $request->getUri()->getPath());
            
            // If it's an AJAX request, return JSON response
            if ($request->isAJAX()) {
                return response()->setJSON([
                    'success' => false,
                    'message' => 'Session expired or unauthorized access',
                    'redirect' => base_url()
                ]);
            }
            
            // For regular requests, redirect to login page
            return redirect()->to(base_url());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing after the request
    }
} 