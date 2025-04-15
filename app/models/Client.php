<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Client extends Model 
{
    protected string $table = 'clients';
    
    public function findAllWithCompany(): array
    {
        $sql = "SELECT c.*, co.name as company_name 
                FROM {$this->table} c 
                LEFT JOIN companies co ON c.company_id = co.id 
                ORDER BY c.id DESC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    public function findWithCompany(int $id): ?array 
    {
        $sql = "SELECT c.*, co.name as company_name 
                FROM {$this->table} c 
                LEFT JOIN companies co ON c.company_id = co.id 
                WHERE c.id = ?";
        $stmt = Database::query($sql, [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}