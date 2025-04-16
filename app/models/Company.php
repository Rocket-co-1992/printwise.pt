<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Company extends Model 
{
    protected string $table = 'companies';
    
    /**
     * Find company with client count
     * 
     * @param int $id Company ID
     * @return array|null Company with client count or null if not found
     */
    public function findWithClientCount(int $id): ?array
    {
        $sql = "SELECT c.*, COUNT(cl.id) as client_count 
                FROM {$this->table} c 
                LEFT JOIN clients cl ON c.id = cl.company_id 
                WHERE c.id = ? 
                GROUP BY c.id";
        $stmt = Database::query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get all companies with client count
     * 
     * @return array Companies with client counts
     */
    public function getAllWithClientCount(): array
    {
        $sql = "SELECT c.*, COUNT(cl.id) as client_count 
                FROM {$this->table} c 
                LEFT JOIN clients cl ON c.id = cl.company_id 
                GROUP BY c.id
                ORDER BY c.name ASC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Find a company by VAT number
     * 
     * @param string $vatNumber Company VAT number
     * @return array|null Company data or null if not found
     */
    public function findByVatNumber(string $vatNumber): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE vat_number = ?";
        $stmt = Database::query($sql, [$vatNumber]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get companies with pagination
     * 
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Companies and pagination data
     */
    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT c.*, COUNT(cl.id) as client_count 
                FROM {$this->table} c 
                LEFT JOIN clients cl ON c.id = cl.company_id 
                GROUP BY c.id
                ORDER BY c.id DESC 
                LIMIT ? OFFSET ?";
        $stmt = Database::query($sql, [$perPage, $offset]);
        $companies = $stmt->fetchAll();
        
        // Get total count
        $sqlCount = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmtCount = Database::query($sqlCount);
        $totalCount = $stmtCount->fetch()['total'];
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'companies' => $companies,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Search companies
     * 
     * @param string $term Search term
     * @return array Matching companies
     */
    public function search(string $term): array
    {
        $term = "%$term%";
        $sql = "SELECT c.*, COUNT(cl.id) as client_count 
                FROM {$this->table} c
                LEFT JOIN clients cl ON c.id = cl.company_id
                WHERE c.name LIKE ? OR c.vat_number LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.address LIKE ?
                GROUP BY c.id
                ORDER BY c.name ASC";
        $stmt = Database::query($sql, [$term, $term, $term, $term, $term]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get company stats (quotes, clients)
     * 
     * @param int $id Company ID
     * @return array Stats data
     */
    public function getStats(int $id): array
    {
        // Get client count
        $sqlClients = "SELECT COUNT(*) as client_count FROM clients WHERE company_id = ?";
        $stmtClients = Database::query($sqlClients, [$id]);
        $clientCount = $stmtClients->fetch()['client_count'];
        
        // Get quote count
        $sqlQuotes = "SELECT COUNT(*) as quote_count FROM quotes q
                     JOIN clients c ON q.client_id = c.id
                     WHERE c.company_id = ?";
        $stmtQuotes = Database::query($sqlQuotes, [$id]);
        $quoteCount = $stmtQuotes->fetch()['quote_count'];
        
        // Get total value of quotes
        $sqlValue = "SELECT SUM(total_amount) as total_value FROM quotes q
                    JOIN clients c ON q.client_id = c.id
                    WHERE c.company_id = ?";
        $stmtValue = Database::query($sqlValue, [$id]);
        $totalValue = $stmtValue->fetch()['total_value'] ?: 0;
        
        return [
            'client_count' => $clientCount,
            'quote_count' => $quoteCount,
            'total_value' => $totalValue
        ];
    }
}