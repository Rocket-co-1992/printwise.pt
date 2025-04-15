<?php

namespace PrintWise\Models;

use PrintWise\Core\Database;
use PrintWise\Core\Model;

class Company extends Model 
{
    protected string $table = 'companies';
    
    public function findWithClientCount(): array
    {
        $sql = "SELECT c.*, COUNT(cl.id) as client_count 
                FROM {$this->table} c 
                LEFT JOIN clients cl ON c.id = cl.company_id 
                GROUP BY c.id 
                ORDER BY c.id DESC";
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
}