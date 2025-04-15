<?php

namespace PrintWise\Core;

use PDO;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    
    public function findAll(): array
    {
        $stmt = Database::query("SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC");
        return $stmt->fetchAll();
    }
    
    public function find(int $id): ?array
    {
        $stmt = Database::query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function create(array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $fieldsStr = implode(', ', $fields);
        $placeholdersStr = implode(', ', $placeholders);
        
        $sql = "INSERT INTO {$this->table} ($fieldsStr) VALUES ($placeholdersStr)";
        Database::query($sql, array_values($data));
        
        return Database::getInstance()->lastInsertId();
    }
    
    public function update(int $id, array $data): bool
    {
        $fields = array_keys($data);
        $sets = array_map(fn($field) => "$field = ?", $fields);
        $setsStr = implode(', ', $sets);
        
        $values = array_values($data);
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET $setsStr WHERE {$this->primaryKey} = ?";
        $stmt = Database::query($sql, $values);
        
        return $stmt->rowCount() > 0;
    }
    
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = Database::query($sql, [$id]);
        
        return $stmt->rowCount() > 0;
    }
    
    public function where(array $conditions, string $orderBy = null, string $direction = 'ASC'): array
    {
        $where = [];
        $values = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $values[] = $value;
        }
        
        $whereStr = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table} WHERE $whereStr";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy $direction";
        }
        
        $stmt = Database::query($sql, $values);
        return $stmt->fetchAll();
    }
}