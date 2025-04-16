<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Quote extends Model 
{
    protected string $table = 'quotes';
    
    // Quote statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    
    /**
     * Find a quote with related data
     * 
     * @param int $id Quote ID
     * @return array|null Quote with related data or null if not found
     */
    public function findWithRelations(int $id): ?array
    {
        $sql = "SELECT q.*, 
                c.name as client_name, c.email as client_email,
                p.name as product_name,
                comp.name as company_name
                FROM {$this->table} q
                LEFT JOIN clients c ON q.client_id = c.id
                LEFT JOIN products p ON q.product_id = p.id
                LEFT JOIN companies comp ON c.company_id = comp.id
                WHERE q.id = ?";
        $stmt = Database::query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Get all quotes with related data
     * 
     * @return array Quotes with related data
     */
    public function getAllWithRelations(): array
    {
        $sql = "SELECT q.*, 
                c.name as client_name,
                p.name as product_name,
                comp.name as company_name
                FROM {$this->table} q
                LEFT JOIN clients c ON q.client_id = c.id
                LEFT JOIN products p ON q.product_id = p.id
                LEFT JOIN companies comp ON c.company_id = comp.id
                ORDER BY q.created_at DESC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get quotes by client ID
     * 
     * @param int $clientId Client ID
     * @return array Client's quotes
     */
    public function findByClient(int $clientId): array
    {
        $sql = "SELECT q.*, p.name as product_name
                FROM {$this->table} q
                LEFT JOIN products p ON q.product_id = p.id
                WHERE q.client_id = ?
                ORDER BY q.created_at DESC";
        $stmt = Database::query($sql, [$clientId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get quotes by company ID
     * 
     * @param int $companyId Company ID
     * @return array Company's quotes
     */
    public function findByCompany(int $companyId): array
    {
        $sql = "SELECT q.*, 
                c.name as client_name, c.email as client_email,
                p.name as product_name, p.type as product_type
                FROM {$this->table} q
                LEFT JOIN clients c ON q.client_id = c.id
                LEFT JOIN products p ON q.product_id = p.id
                WHERE c.company_id = ?
                ORDER BY q.created_at DESC";
        $stmt = Database::query($sql, [$companyId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add finishing to quote
     * 
     * @param int $quoteId Quote ID
     * @param int $finishingId Finishing ID
     * @return bool Success or failure
     */
    public function addFinishing(int $quoteId, int $finishingId): bool
    {
        $sql = "INSERT INTO quote_finishings (quote_id, finishing_id) VALUES (?, ?)";
        $stmt = Database::query($sql, [$quoteId, $finishingId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Remove finishing from quote
     * 
     * @param int $quoteId Quote ID
     * @param int $finishingId Finishing ID
     * @return bool Success or failure
     */
    public function removeFinishing(int $quoteId, int $finishingId): bool
    {
        $sql = "DELETE FROM quote_finishings WHERE quote_id = ? AND finishing_id = ?";
        $stmt = Database::query($sql, [$quoteId, $finishingId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get quotes with pagination
     * 
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string|null $status Filter by status (optional)
     * @return array Quotes and pagination data
     */
    public function getPaginated(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $whereClause = '';
        
        if ($status) {
            $whereClause = "WHERE q.status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT q.*, 
                c.name as client_name,
                p.name as product_name,
                comp.name as company_name
                FROM {$this->table} q
                LEFT JOIN clients c ON q.client_id = c.id
                LEFT JOIN products p ON q.product_id = p.id
                LEFT JOIN companies comp ON c.company_id = comp.id
                $whereClause
                ORDER BY q.created_at DESC
                LIMIT ? OFFSET ?";
                
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = Database::query($sql, $params);
        $quotes = $stmt->fetchAll();
        
        // Get total count
        $countParams = [];
        $countWhereClause = '';
        
        if ($status) {
            $countWhereClause = "WHERE status = ?";
            $countParams[] = $status;
        }
        
        $sqlCount = "SELECT COUNT(*) as total FROM {$this->table} $countWhereClause";
        $stmtCount = Database::query($sqlCount, $countParams);
        $totalCount = $stmtCount->fetch()['total'];
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'quotes' => $quotes,
            'pagination' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ]
        ];
    }
    
    /**
     * Search quotes
     * 
     * @param string $term Search term
     * @return array Matching quotes
     */
    public function search(string $term): array
    {
        $term = "%$term%";
        $sql = "SELECT q.*, 
                c.name as client_name, c.email as client_email,
                p.name as product_name, p.type as product_type,
                comp.name as company_name
                FROM {$this->table} q
                LEFT JOIN clients c ON q.client_id = c.id
                LEFT JOIN products p ON q.product_id = p.id
                LEFT JOIN companies comp ON c.company_id = comp.id
                WHERE c.name LIKE ? OR p.name LIKE ? OR comp.name LIKE ? OR q.title LIKE ? OR q.description LIKE ?
                ORDER BY q.created_at DESC";
        $stmt = Database::query($sql, [$term, $term, $term, $term, $term]);
        return $stmt->fetchAll();
    }
    
    public function findAllWithDetails(): array
    {
        $sql = "SELECT q.*, c.name as client_name, p.name as product_name, p.type as product_type
                FROM {$this->table} q
                JOIN clients c ON q.client_id = c.id
                JOIN products p ON q.product_id = p.id
                ORDER BY q.id DESC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT q.*, c.name as client_name, c.email as client_email,
                p.name as product_name, p.type as product_type
                FROM {$this->table} q
                JOIN clients c ON q.client_id = c.id
                JOIN products p ON q.product_id = p.id
                WHERE q.id = ?";
        $stmt = Database::query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function findByHash(string $hash): ?array
    {
        $sql = "SELECT q.*, c.name as client_name, c.email as client_email,
                p.name as product_name, p.type as product_type
                FROM {$this->table} q
                JOIN clients c ON q.client_id = c.id
                JOIN products p ON q.product_id = p.id
                WHERE q.hash = ?";
        $stmt = Database::query($sql, [$hash]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function findByStatus(string $status): array
    {
        return $this->where(['status' => $status]);
    }
    
    public function addFinishings(int $quoteId, array $finishingIds): void
    {
        foreach ($finishingIds as $finishingId) {
            $sql = "INSERT INTO quote_finishings (quote_id, finishing_id) VALUES (?, ?)";
            Database::query($sql, [$quoteId, $finishingId]);
        }
    }
    
    public function removeFinishings(int $quoteId): void
    {
        $sql = "DELETE FROM quote_finishings WHERE quote_id = ?";
        Database::query($sql, [$quoteId]);
    }
    
    public function countByStatus(): array
    {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    public function generateHash(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    public function calculateSmallFormatPrice(int $productId, int $quantity, int $colors, array $finishingIds): float
    {
        // Buscar preço base do produto
        $productModel = new Product();
        $product = $productModel->find($productId);
        
        if (!$product) {
            throw new \Exception("Produto não encontrado");
        }
        
        // Preço base por unidade
        $unitPrice = $product['base_price'];
        
        // Fator de multiplicação por quantidade
        $quantityFactor = 1.0;
        if ($quantity <= 100) {
            $quantityFactor = 1.0;
        } elseif ($quantity <= 500) {
            $quantityFactor = 0.9;  // 10% de desconto
        } elseif ($quantity <= 1000) {
            $quantityFactor = 0.8;  // 20% de desconto
        } else {
            $quantityFactor = 0.7;  // 30% de desconto
        }
        
        // Fator de multiplicação por cores
        $colorFactor = 1.0;
        if ($colors == 1) {
            $colorFactor = 0.8;  // Preto e branco é mais barato
        }
        
        // Aplicar fatores ao preço base
        $unitPrice *= $quantityFactor * $colorFactor;
        
        // Aplicar acabamentos
        if (!empty($finishingIds)) {
            $finishingModel = new Finishing();
            foreach ($finishingIds as $finishingId) {
                $finishing = $finishingModel->find($finishingId);
                
                if ($finishing['is_multiplier']) {
                    // Se for multiplicador (ex: laminação)
                    $unitPrice *= $finishing['price_factor'];
                } else {
                    // Se for aditivo (ex: cantos redondos)
                    $unitPrice += $finishing['price_factor'];
                }
            }
        }
        
        // Preço final por unidade (arredondado para 2 casas decimais)
        return round($unitPrice, 2);
    }
    
    public function calculateLargeFormatPrice(int $productId, float $width, float $height, 
                                            int $quantity, array $finishingIds): float
    {
        // Buscar preço base do produto (que é por m²)
        $productModel = new Product();
        $product = $productModel->find($productId);
        
        if (!$product) {
            throw new \Exception("Produto não encontrado");
        }
        
        // Calcular área em m²
        $area = ($width * $height) / 10000; // Converter cm² para m²
        
        // Preço base por unidade (preço/m² * área)
        $unitPrice = $product['base_price'] * $area;
        
        // Fator de multiplicação por quantidade
        $quantityFactor = 1.0;
        if ($quantity <= 5) {
            $quantityFactor = 1.0;
        } elseif ($quantity <= 10) {
            $quantityFactor = 0.95;  // 5% de desconto
        } elseif ($quantity <= 20) {
            $quantityFactor = 0.9;   // 10% de desconto
        } else {
            $quantityFactor = 0.85;  // 15% de desconto
        }
        
        // Aplicar fator de quantidade
        $unitPrice *= $quantityFactor;
        
        // Aplicar acabamentos
        if (!empty($finishingIds)) {
            $finishingModel = new Finishing();
            foreach ($finishingIds as $finishingId) {
                $finishing = $finishingModel->find($finishingId);
                
                if ($finishing['is_multiplier']) {
                    // Se for multiplicador (ex: laminação)
                    $unitPrice *= $finishing['price_factor'];
                } else {
                    // Se for aditivo (ex: ilhós) - aplicado por unidade, não por m²
                    $unitPrice += $finishing['price_factor'];
                }
            }
        }
        
        // Preço mínimo por unidade (para peças muito pequenas)
        $minPrice = 10.0;
        $unitPrice = max($unitPrice, $minPrice);
        
        // Preço final por unidade (arredondado para 2 casas decimais)
        return round($unitPrice, 2);
    }
}