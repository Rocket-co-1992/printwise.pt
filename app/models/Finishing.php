<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Finishing extends Model 
{
    protected string $table = 'finishings';
    
    /**
     * Get all active finishings
     * 
     * @return array Active finishings
     */
    public function getAllActive(): array
    {
        return $this->where(['active' => 1]);
    }
    
    /**
     * Get finishings compatible with a specific product type
     * 
     * @param string $productType Product type
     * @return array Compatible finishings
     */
    public function getByProductType(string $productType): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE FIND_IN_SET(?, compatible_types) > 0 
                AND active = 1
                ORDER BY name ASC";
        $stmt = Database::query($sql, [$productType]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get finishings with usage count
     * 
     * @return array Finishings with usage count
     */
    public function getAllWithUsageCount(): array
    {
        $sql = "SELECT f.*, COUNT(qf.quote_id) as usage_count 
                FROM {$this->table} f 
                LEFT JOIN quote_finishings qf ON f.id = qf.finishing_id 
                GROUP BY f.id
                ORDER BY f.name ASC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Find finishing with usage count
     * 
     * @param int $id Finishing ID
     * @return array|null Finishing with usage count or null if not found
     */
    public function findWithUsageCount(int $id): ?array
    {
        $sql = "SELECT f.*, COUNT(qf.quote_id) as usage_count 
                FROM {$this->table} f 
                LEFT JOIN quote_finishings qf ON f.id = qf.finishing_id 
                WHERE f.id = ? 
                GROUP BY f.id";
        $stmt = Database::query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get finishings applied to a quote
     * 
     * @param int $quoteId Quote ID
     * @return array Finishings applied to the quote
     */
    public function getByQuote(int $quoteId): array
    {
        $sql = "SELECT f.* 
                FROM {$this->table} f 
                JOIN quote_finishings qf ON f.id = qf.finishing_id 
                WHERE qf.quote_id = ?
                ORDER BY f.name ASC";
        $stmt = Database::query($sql, [$quoteId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get finishings with pagination
     * 
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @return array Finishings and pagination data
     */
    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT f.*, COUNT(qf.quote_id) as usage_count 
                FROM {$this->table} f 
                LEFT JOIN quote_finishings qf ON f.id = qf.finishing_id 
                GROUP BY f.id
                ORDER BY f.id DESC 
                LIMIT ? OFFSET ?";
        $stmt = Database::query($sql, [$perPage, $offset]);
        $finishings = $stmt->fetchAll();
        
        // Get total count
        $sqlCount = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmtCount = Database::query($sqlCount);
        $totalCount = $stmtCount->fetch()['total'];
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'finishings' => $finishings,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
}