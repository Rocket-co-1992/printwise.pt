<?php

namespace PrintWise\Core;

class Config
{
    private static $configs = [];
    
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $filename = array_shift($parts);
        
        if (!isset(self::$configs[$filename])) {
            // Try to load environment-specific config first
            $envFile = __DIR__ . '/../../config/' . $filename . '.' . self::getEnvironment() . '.php';
            
            if (file_exists($envFile)) {
                self::$configs[$filename] = require $envFile;
            } else {
                // Fall back to default config
                $defaultFile = __DIR__ . '/../../config/' . $filename . '.php';
                if (file_exists($defaultFile)) {
                    self::$configs[$filename] = require $defaultFile;
                } else {
                    return $default;
                }
            }
        }
        
        $config = self::$configs[$filename];
        
        // Navigate to nested config values
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            
            $config = $config[$part];
        }
        
        return $config;
    }
    
    public static function getEnvironment(): string
    {
        return getenv('APP_ENV') ?: 'production';
    }
}
