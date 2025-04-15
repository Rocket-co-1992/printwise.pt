<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Finishing extends Model 
{
    protected string $table = 'finishings';
    
    public function findByProductType(string $type): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_type = ? OR product_type = 'both' 
                ORDER BY name ASC";
        $stmt = Database::query($sql, [$type]);
        return $stmt->fetchAll();
    }
    
    public function findByQuote(int $quoteId): array
    {
        $sql = "SELECT f.* 
                FROM {$this->table} f
                JOIN quote_finishings qf ON f.id = qf.finishing_id
                WHERE qf.quote_id = ?
                ORDER BY f.name ASC";
        $stmt = Database::query($sql, [$quoteId]);
        return $stmt->fetchAll();
    }
}