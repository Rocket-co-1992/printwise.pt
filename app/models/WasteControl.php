<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class WasteControl extends Model 
{
    protected string $table = 'waste_control';
    
    public function findAllWithQuoteDetails(): array
    {
        $sql = "SELECT w.*, q.title as quote_title, q.quantity as quote_quantity, 
                p.name as product_name, c.name as client_name
                FROM {$this->table} w
                JOIN quotes q ON w.quote_id = q.id
                JOIN products p ON q.product_id = p.id
                JOIN clients c ON q.client_id = c.id
                ORDER BY w.created_at DESC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    public function findByQuote(int $quoteId): ?array
    {
        $sql = "SELECT w.* FROM {$this->table} w WHERE w.quote_id = ?";
        $stmt = Database::query($sql, [$quoteId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function calculateWastePercentage(int $expected, int $actual): float
    {
        if ($expected <= 0) {
            return 0;
        }
        
        $waste = $expected - $actual;
        if ($waste <= 0) {
            return 0;
        }
        
        return round(($waste * 100) / $expected, 2);
    }
    
    public function isJustificationRequired(float $wastePercentage): bool
    {
        $config = require_once __DIR__ . '/../../config/config.php';
        $threshold = $config['waste_threshold'] ?? 5;
        
        return $wastePercentage > $threshold;
    }
}