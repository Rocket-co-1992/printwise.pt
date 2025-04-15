<?php

namespace PrintWise\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    protected Environment $twig;
    
    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../app/views');
        $this->twig = new Environment($loader, [
            'cache' => __DIR__ . '/../../cache/twig',
            'auto_reload' => true,
        ]);
        
        // Adicionar variáveis globais ao Twig
        $config = require_once __DIR__ . '/../../config/config.php';
        $this->twig->addGlobal('app_name', $config['app_name']);
        $this->twig->addGlobal('app_url', $config['app_url']);
        
        // Verificar se o usuário está logado
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->twig->addGlobal('is_logged', isset($_SESSION['user']));
        $this->twig->addGlobal('current_user', $_SESSION['user'] ?? null);
    }
    
    protected function render(string $view, array $data = []): string
    {
        return $this->twig->render("$view.twig", $data);
    }
    
    protected function redirect(string $path): void
    {
        $config = require_once __DIR__ . '/../../config/config.php';
        header("Location: {$config['app_url']}/$path");
        exit;
    }
    
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user']);
    }
    
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('auth/login');
        }
    }
    
    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}