<?php

namespace PrintWise\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = Config::get('database.host');
            $database = Config::get('database.database');
            $username = Config::get('database.username');
            $password = Config::get('database.password');
            $charset = Config::get('database.charset', 'utf8mb4');
            $options = Config::get('database.options', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";
            
            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                throw new \Exception("Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
    
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}