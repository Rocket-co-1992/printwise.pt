<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;

class UserController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }
    
    public function profile(): string
    {
        $user = $this->getCurrentUser();
        
        return $this->render('user/profile', [
            'user' => $user
        ]);
    }
    
    public function updateProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            // TODO: Add validation and update user profile logic
            
            // Update session with new data
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            
            $this->redirect('user/profile');
        }
    }
    
    public function changePassword(): string
    {
        return $this->render('user/change-password');
    }
    
    public function updatePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // TODO: Add validation and password update logic
            
            $this->redirect('user/profile');
        }
    }
}