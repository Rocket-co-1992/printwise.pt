<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class User extends Model 
{
    protected string $table = 'users';
    
    // User roles
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_CUSTOMER = 'customer';
    
    /**
     * Find a user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = Database::query($sql, [$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Authenticate a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|null User data or null if authentication failed
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return int New user ID
     */
    public function createUser(array $data): int
    {
        // Hash the password
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        return $this->create($data);
    }
    
    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return bool Success or failure
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
    
    /**
     * Get users with pagination
     * 
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Users and pagination data
     */
    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = Database::query($sql, [$perPage, $offset]);
        $users = $stmt->fetchAll();
        
        // Get total count
        $sqlCount = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmtCount = Database::query($sqlCount);
        $totalCount = $stmtCount->fetch()['total'];
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'users' => $users,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
}