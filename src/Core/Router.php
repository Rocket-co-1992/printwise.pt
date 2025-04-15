<?php

namespace PrintWise\Core;

class Router
{
    private array $routes = [];
    private string $notFoundHandler = '';
    
    public function add(string $method, string $path, string $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }
    
    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }
    
    public function notFound(string $handler): void
    {
        $this->notFoundHandler = $handler;
    }
    
    public function resolve(string $method, string $uri): array
    {
        $uriParts = explode('?', $uri);
        $path = $uriParts[0];
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->getPatternFromPath($route['path']);
            
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove o primeiro item (match completo)
                
                list($controller, $action) = explode('@', $route['handler']);
                return ['controller' => $controller, 'action' => $action, 'params' => $matches];
            }
        }
        
        if ($this->notFoundHandler) {
            list($controller, $action) = explode('@', $this->notFoundHandler);
            return ['controller' => $controller, 'action' => $action, 'params' => []];
        }
        
        throw new \Exception("Rota não encontrada: $method $path");
    }
    
    private function getPatternFromPath(string $path): string
    {
        $pattern = str_replace('/', '\/', $path);
        $pattern = preg_replace('/{(\w+)}/', '([^\/]+)', $pattern);
        return '/^' . $pattern . '$/';
    }
}