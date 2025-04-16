<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Client extends Model 
{
    protected string $table = 'clients';
    
    /**
     * Find client with company information
     * 
     * @param int $id Client ID
     * @return array|null Client with company data or null if not found
     */
    public function findWithCompany(int $id): ?array
    {
        $sql = "SELECT c.*, co.name as company_name, co.id as company_id 
                FROM {$this->table} c 
                LEFT JOIN companies co ON c.company_id = co.id 
                WHERE c.id = ?";
        $stmt = Database::query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get clients by company ID
     * 
     * @param int $companyId Company ID
     * @return array Clients belonging to the company
     */
    public function getByCompany(int $companyId): array
    {
        $sql = "SELECT c.*, COUNT(q.id) as quote_count 
                FROM {$this->table} c 
                LEFT JOIN quotes q ON c.id = q.client_id 
                WHERE c.company_id = ? 
                GROUP BY c.id
                ORDER BY c.name ASC";
        $stmt = Database::query($sql, [$companyId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get clients with pagination and company information
     * 
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Clients and pagination data
     */
    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT c.*, co.name as company_name, COUNT(q.id) as quote_count 
                FROM {$this->table} c 
                LEFT JOIN companies co ON c.company_id = co.id 
                LEFT JOIN quotes q ON c.id = q.client_id 
                GROUP BY c.id
                ORDER BY c.id DESC 
                LIMIT ? OFFSET ?";
        $stmt = Database::query($sql, [$perPage, $offset]);
        $clients = $stmt->fetchAll();
        
        // Get total count
        $sqlCount = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmtCount = Database::query($sqlCount);
        $totalCount = $stmtCount->fetch()['total'];
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'clients' => $clients,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Search clients
     * 
     * @param string $term Search term
     * @return array Matching clients
     */
    public function search(string $term): array
    {
        $term = "%$term%";
        $sql = "SELECT c.*, co.name as company_name 
                FROM {$this->table} c
                LEFT JOIN companies co ON c.company_id = co.id
                WHERE c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR co.name LIKE ?
                ORDER BY c.name ASC";
        $stmt = Database::query($sql, [$term, $term, $term, $term]);
        return $stmt->fetchAll();
    }
    
    /**
     * Find a client by email
     * 
     * @param string $email Client email
     * @return array|null Client data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = Database::query($sql, [$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get client stats (quotes, orders)
     * 
     * @param int $id Client ID
     * @return array Stats data
     */
    public function getStats(int $id): array
    {
        // Get quote count
        $sqlQuotes = "SELECT COUNT(*) as quote_count FROM quotes WHERE client_id = ?";
        $stmtQuotes = Database::query($sqlQuotes, [$id]);
        $quoteCount = $stmtQuotes->fetch()['quote_count'];
        
        // Get order count
        $sqlOrders = "SELECT COUNT(*) as order_count FROM orders WHERE client_id = ?";
        $stmtOrders = Database::query($sqlOrders, [$id]);
        $orderCount = $stmtOrders->fetch()['order_count'];
        
        // Get total spent
        $sqlSpent = "SELECT SUM(total_amount) as total_spent FROM orders WHERE client_id = ? AND status = 'completed'";
        $stmtSpent = Database::query($sqlSpent, [$id]);
        $totalSpent = $stmtSpent->fetch()['total_spent'] ?: 0;
        
        return [
            'quote_count' => $quoteCount,
            'order_count' => $orderCount,
            'total_spent' => $totalSpent
        ];
    }
}