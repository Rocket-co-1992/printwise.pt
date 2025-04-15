<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;

class HomeController extends Controller
{
    public function index(): string
    {
        $config = require_once __DIR__ . '/../../config/config.php';
        $whatsapp = $config['whatsapp'] ?? null;
        
        return $this->render('home/index', [
            'whatsapp' => $whatsapp
        ]);
    }
    
    public function about(): string
    {
        return $this->render('home/about');
    }
    
    public function contact(): string
    {
        return $this->render('home/contact');
    }
}