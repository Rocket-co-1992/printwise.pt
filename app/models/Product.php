<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class Product extends Model
{
    protected $table = 'products';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Get all products with pagination
     * 
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param array $filters Optional filters
     * @return array Array with products and pagination data
     */
    public function getAllPaginated($page = 1, $perPage = 12, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        // Base query
        $sqlBase = "FROM {$this->table} p LEFT JOIN categories c ON p.category_id = c.id";
        $whereClause = "WHERE 1=1";
        
        // Apply filters
        if (!empty($filters)) {
            // Category filter
            if (isset($filters['category_id']) && $filters['category_id']) {
                $whereClause .= " AND p.category_id = :category_id";
                $params[':category_id'] = $filters['category_id'];
            }
            
            // Price range filter
            if (isset($filters['min_price'])) {
                $whereClause .= " AND p.price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (isset($filters['max_price'])) {
                $whereClause .= " AND p.price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            // Search term filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereClause .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.short_description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            // Featured products filter
            if (isset($filters['featured']) && $filters['featured']) {
                $whereClause .= " AND p.is_featured = 1";
            }
        }
        
        // Sort options
        $sortClause = "ORDER BY ";
        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_low':
                    $sortClause .= "p.price ASC";
                    break;
                case 'price_high':
                    $sortClause .= "p.price DESC";
                    break;
                case 'newest':
                    $sortClause .= "p.created_at DESC";
                    break;
                case 'name':
                    $sortClause .= "p.name ASC";
                    break;
                default:
                    $sortClause .= "p.id DESC";
            }
        } else {
            $sortClause .= "p.id DESC";
        }
        
        // Get products
        $sql = "SELECT p.*, c.name as category_name " . $sqlBase . " " . $whereClause . " " . $sortClause . " LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(Database::FETCH_ASSOC);
        
        // Get total count
        $sqlCount = "SELECT COUNT(*) as total " . $sqlBase . " " . $whereClause;
        $stmtCount = $this->db->prepare($sqlCount);
        
        // Bind parameters for count query
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch(Database::FETCH_ASSOC)['total'];
        
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
     * Get product with related information
     * 
     * @param int $id Product ID
     * @return array|null Product data with related info or null if not found
     */
    public function getWithRelated($id)
    {
        // Get product with category
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = :id 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $product = $stmt->fetch(Database::FETCH_ASSOC);
        
        if (!$product) {
            return null;
        }
        
        // Get product images
        $sqlImages = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order";
        $stmtImages = $this->db->prepare($sqlImages);
        $stmtImages->bindParam(':product_id', $id);
        $stmtImages->execute();
        $product['images'] = $stmtImages->fetchAll(Database::FETCH_ASSOC);
        
        // Get product specifications
        $sqlSpecs = "SELECT ps.*, s.name as spec_name, s.unit 
                    FROM product_specifications ps
                    JOIN specifications s ON ps.specification_id = s.id
                    WHERE ps.product_id = :product_id";
        $stmtSpecs = $this->db->prepare($sqlSpecs);
        $stmtSpecs->bindParam(':product_id', $id);
        $stmtSpecs->execute();
        $product['specifications'] = $stmtSpecs->fetchAll(Database::FETCH_ASSOC);
        
        // Get product reviews
        $sqlReviews = "SELECT r.*, u.name as user_name 
                      FROM reviews r 
                      LEFT JOIN users u ON r.user_id = u.id
                      WHERE r.product_id = :product_id AND r.status = 'approved'
                      ORDER BY r.created_at DESC";
        $stmtReviews = $this->db->prepare($sqlReviews);
        $stmtReviews->bindParam(':product_id', $id);
        $stmtReviews->execute();
        $product['reviews'] = $stmtReviews->fetchAll(Database::FETCH_ASSOC);
        
        // Calculate average rating
        $sqlRating = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                      FROM reviews 
                      WHERE product_id = :product_id AND status = 'approved'";
        $stmtRating = $this->db->prepare($sqlRating);
        $stmtRating->bindParam(':product_id', $id);
        $stmtRating->execute();
        $ratingData = $stmtRating->fetch(Database::FETCH_ASSOC);
        $product['avg_rating'] = $ratingData['avg_rating'];
        $product['review_count'] = $ratingData['review_count'];
        
        return $product;
    }
    
    /**
     * Get related products based on category
     * 
     * @param int $productId Current product ID
     * @param int $categoryId Current product category ID
     * @param int $limit Max number of related products
     * @return array Related products
     */
    public function getRelatedProducts($productId, $categoryId, $limit = 4)
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id != :product_id AND p.category_id = :category_id
                ORDER BY RAND()
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(Database::FETCH_ASSOC);
    }
    
    /**
     * Get featured products
     * 
     * @param int $limit Max number of featured products
     * @return array Featured products
     */
    public function getFeaturedProducts($limit = 6)
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_featured = 1
                ORDER BY p.created_at DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(Database::FETCH_ASSOC);
    }
    
    /**
     * Search products
     * 
     * @param string $term Search term
     * @param array $options Search options
     * @return array Search results
     */
    public function search($term, $options = [])
    {
        // Default options
        $defaults = [
            'page' => 1,
            'per_page' => 12,
            'sort' => 'relevance',
            'category_ids' => [],
            'max_price' => null
        ];
        
        $options = array_merge($defaults, $options);
        $params = [':search' => "%{$term}%"];
        $offset = ($options['page'] - 1) * $options['per_page'];
        
        // Build where clause
        $whereClause = "WHERE (p.name LIKE :search OR p.description LIKE :search OR p.short_description LIKE :search)";
        
        // Filter by categories
        if (!empty($options['category_ids'])) {
            $categoryPlaceholders = [];
            foreach ($options['category_ids'] as $i => $id) {
                $paramName = ":category_id{$i}";
                $categoryPlaceholders[] = $paramName;
                $params[$paramName] = $id;
            }
            
            $whereClause .= " AND p.category_id IN (" . implode(', ', $categoryPlaceholders) . ")";
        }
        
        // Filter by max price
        if ($options['max_price']) {
            $whereClause .= " AND p.price <= :max_price";
            $params[':max_price'] = $options['max_price'];
        }
        
        // Build order by clause
        $orderClause = "ORDER BY ";
        switch ($options['sort']) {
            case 'price_low':
                $orderClause .= "p.price ASC";
                break;
            case 'price_high':
                $orderClause .= "p.price DESC";
                break;
            case 'newest':
                $orderClause .= "p.created_at DESC";
                break;
            case 'name':
                $orderClause .= "p.name ASC";
                break;
            case 'relevance':
            default:
                // For relevance, order by how closely the name matches, then by other fields
                $orderClause .= "CASE WHEN p.name LIKE :exact_name THEN 1
                                     WHEN p.name LIKE :start_name THEN 2
                                     ELSE 3 END,
                                p.is_featured DESC,
                                p.name ASC";
                $params[':exact_name'] = $term;
                $params[':start_name'] = "{$term}%";
        }
        
        // Get products
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                {$whereClause} 
                {$orderClause}
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind all parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $options['per_page'], \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        
        $stmt->execute();
        $products = $stmt->fetchAll(Database::FETCH_ASSOC);
        
        // Get total count
        $sqlCount = "SELECT COUNT(*) as total 
                    FROM {$this->table} p
                    LEFT JOIN categories c ON p.category_id = c.id 
                    {$whereClause}";
                    
        $stmtCount = $this->db->prepare($sqlCount);
        
        // Bind parameters for count query (excluding limit and offset)
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        
        $stmtCount->execute();
        $totalCount = $stmtCount->fetch(Database::FETCH_ASSOC)['total'];
        
        // Get category counts for search results
        $sqlCategories = "SELECT p.category_id, c.name, COUNT(*) as count
                         FROM {$this->table} p
                         JOIN categories c ON p.category_id = c.id
                         {$whereClause}
                         GROUP BY p.category_id, c.name
                         ORDER BY count DESC";
                         
        $stmtCategories = $this->db->prepare($sqlCategories);
        
        // Bind parameters for categories query
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $stmtCategories->bindValue($key, $value);
            }
        }
        
        $stmtCategories->execute();
        $categories = $stmtCategories->fetchAll(Database::FETCH_ASSOC);
        
        return [
            'products' => $products,
            'categories' => $categories,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $options['per_page'],
                'current_page' => $options['page'],
                'total_pages' => ceil($totalCount / $options['per_page'])
            ]
        ];
    }
    
    /**
     * Get products by IDs
     * 
     * @param array $ids Product IDs
     * @return array Products
     */
    public function getByIds(array $ids)
    {
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = [];
        $params = [];
        
        foreach ($ids as $i => $id) {
            $paramName = ":id{$i}";
            $placeholders[] = $paramName;
            $params[$paramName] = $id;
        }
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id IN (" . implode(', ', $placeholders) . ")";
                
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(Database::FETCH_ASSOC);
    }
    
    /**
     * Get products with specifications for comparison
     * 
     * @param array $productIds Product IDs to compare
     * @return array Products with their specifications
     */
    public function getProductsForComparison(array $productIds)
    {
        if (empty($productIds)) {
            return [
                'products' => [],
                'specifications' => []
            ];
        }
        
        // Get products by IDs
        $products = $this->getByIds($productIds);
        
        if (empty($products)) {
            return [
                'products' => [],
                'specifications' => []
            ];
        }
        
        // Get all specifications for these products
        $placeholders = [];
        $params = [];
        
        foreach ($productIds as $i => $id) {
            $paramName = ":id{$i}";
            $placeholders[] = $paramName;
            $params[$paramName] = $id;
        }
        
        $sql = "SELECT ps.*, p.id as product_id, s.name as spec_name, s.unit
                FROM product_specifications ps
                JOIN specifications s ON ps.specification_id = s.id
                JOIN products p ON ps.product_id = p.id
                WHERE ps.product_id IN (" . implode(', ', $placeholders) . ")
                ORDER BY s.sort_order, s.name";
                
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $specsData = $stmt->fetchAll(Database::FETCH_ASSOC);
        
        // Format specifications as a nested array for easy comparison display
        $specifications = [];
        
        foreach ($specsData as $spec) {
            $specName = $spec['spec_name'];
            $unit = $spec['unit'] ? ' ' . $spec['unit'] : '';
            $productId = $spec['product_id'];
            $value = $spec['value'] . $unit;
            
            if (!isset($specifications[$specName])) {
                $specifications[$specName] = [];
            }
            
            $specifications[$specName][$productId] = $value;
        }
        
        return [
            'products' => $products,
            'specifications' => $specifications,
            'product_ids' => $productIds
        ];
    }
}