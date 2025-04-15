<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;
use PrintWise\Models\User;

class AuthController extends Controller
{
    public function loginForm(): string
    {
        // Se já estiver logado, redireciona para o dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('admin/dashboard');
        }
        
        return $this->render('auth/login');
    }
    
    public function login(): string
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
        }
        
        return $this->render('auth/login');
    }
    
    public function authenticate(): void
    {
        // Handle login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // TODO: Add validation and authentication logic
            // For now, just a placeholder implementation
            if ($email && $password) {
                // Mock user for demonstration
                $_SESSION['user'] = [
                    'id' => 1,
                    'name' => 'User',
                    'email' => $email,
                    'role' => 'customer'
                ];
                
                $this->redirect('dashboard');
            }
            
            // If authentication fails
            $this->redirect('auth/login');
        }
    }
    
    public function register(): string
    {
        if ($this->isAuthenticated()) {
            $this->redirect('dashboard');
        }
        
        return $this->render('auth/register');
    }
    
    public function store(): void
    {
        // Handle registration form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // TODO: Add validation and user creation logic
            
            // Redirect to login page
            $this->redirect('auth/login');
        }
    }
    
    public function logout(): void
    {
        // Destroy the session
        session_destroy();
        
        // Redirect to home page
        $this->redirect('');
    }
}