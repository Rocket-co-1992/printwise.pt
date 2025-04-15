<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class User extends Model
{
    protected $table = 'users';
    
    // User roles
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_CUSTOMER = 'customer';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(Database::FETCH_ASSOC);
    }
    
    /**
     * Authenticate a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|false User data or false if authentication failed
     */
    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password before returning user data
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return int|false The ID of the newly created user, or false on failure
     */
    public function create($data)
    {
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = self::ROLE_CUSTOMER;
        }
        
        // Set creation date
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return parent::create($data);
    }
    
    /**
     * Update a user
     * 
     * @param int $id User ID
     * @param array $data User data to update
     * @return bool Success status
     */
    public function update($id, $data)
    {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Don't update password if empty
            unset($data['password']);
        }
        
        // Set update date
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::update($id, $data);
    }
    
    /**
     * Get all users with pagination
     * 
     * @param int $page Current page
     * @param int $perPage Items per page
     * @return array Array with users and pagination data
     */
    public function getAllPaginated($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        
        // Get users
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(Database::FETCH_ASSOC);
        
        // Get total count
        $sqlCount = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch(Database::FETCH_ASSOC)['total'];
        
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