<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Product extends Model 
{
    protected string $table = 'products';
    
    // Product types
    const TYPE_SMALL_FORMAT = 'small_format';
    const TYPE_LARGE_FORMAT = 'large_format';
    
    /**
     * Get all small format products
     * 
     * @return array Small format products
     */
    public function getSmallFormatProducts(): array
    {
        return $this->where(['type' => self::TYPE_SMALL_FORMAT, 'active' => 1]);
    }
    
    /**
     * Get all large format products
     * 
     * @return array Large format products
     */
    public function getLargeFormatProducts(): array
    {
        return $this->where(['type' => self::TYPE_LARGE_FORMAT, 'active' => 1]);
    }
    
    /**
     * Get all products with quote count
     * 
     * @return array Products with quote counts
     */
    public function getAllWithQuoteCount(): array
    {
        $sql = "SELECT p.*, COUNT(q.id) as quote_count 
                FROM {$this->table} p 
                LEFT JOIN quotes q ON p.id = q.product_id 
                GROUP BY p.id
                ORDER BY p.name ASC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Find product with quote count
     * 
     * @param int $id Product ID
     * @return array|null Product with quote count or null if not found
     */
    public function findWithQuoteCount(int $id): ?array
    {
        $sql = "SELECT p.*, COUNT(q.id) as quote_count 
                FROM {$this->table} p 
                LEFT JOIN quotes q ON p.id = q.product_id 
                WHERE p.id = ? 
                GROUP BY p.id";
        $stmt = Database::query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get products with pagination
     * 
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string|null $type Filter by product type (optional)
     * @return array Products and pagination data
     */
    public function getPaginated(int $page = 1, int $perPage = 10, ?string $type = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $whereClause = '';
        
        if ($type) {
            $whereClause = "WHERE p.type = ?";
            $params[] = $type;
        }
        
        $sql = "SELECT p.*, COUNT(q.id) as quote_count 
                FROM {$this->table} p 
                LEFT JOIN quotes q ON p.id = q.product_id 
                $whereClause
                GROUP BY p.id
                ORDER BY p.id DESC 
                LIMIT ? OFFSET ?";
                
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = Database::query($sql, $params);
        $products = $stmt->fetchAll();
        
        // Get total count
        $countParams = [];
        $countWhereClause = '';
        
        if ($type) {
            $countWhereClause = "WHERE type = ?";
            $countParams[] = $type;
        }
        
        $sqlCount = "SELECT COUNT(*) as total FROM {$this->table} $countWhereClause";
        $stmtCount = Database::query($sqlCount, $countParams);
        $totalCount = $stmtCount->fetch()['total'];
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'products' => $products,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Search products
     * 
     * @param string $term Search term
     * @return array Matching products
     */
    public function search(string $term): array
    {
        $term = "%$term%";
        $sql = "SELECT p.*, COUNT(q.id) as quote_count 
                FROM {$this->table} p 
                LEFT JOIN quotes q ON p.id = q.product_id 
                WHERE p.name LIKE ? OR p.description LIKE ?
                GROUP BY p.id
                ORDER BY p.name ASC";
        $stmt = Database::query($sql, [$term, $term]);
        return $stmt->fetchAll();
    }
}