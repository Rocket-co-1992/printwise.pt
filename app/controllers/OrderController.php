<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;

class OrderController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }
    
    public function index(): string
    {
        $user = $this->getCurrentUser();
        
        // TODO: Fetch orders from database
        $orders = []; // Placeholder for orders
        
        return $this->render('order/index', [
            'orders' => $orders
        ]);
    }
    
    public function create(): string
    {
        // TODO: Fetch products from database
        $products = []; // Placeholder for products
        
        return $this->render('order/create', [
            'products' => $products
        ]);
    }
    
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $this->getCurrentUser()['id'];
            $productId = $_POST['product_id'] ?? null;
            $quantity = $_POST['quantity'] ?? 1;
            $specifications = $_POST['specifications'] ?? '';
            $files = $_FILES['files'] ?? [];
            
            // TODO: Validate and store order in database
            
            // TODO: Handle file uploads
            
            $this->redirect('order/confirmation');
        }
    }
    
    public function show(int $id): string
    {
        // TODO: Fetch order details from database
        $order = []; // Placeholder for order details
        
        return $this->render('order/show', [
            'order' => $order
        ]);
    }
    
    public function confirmation(): string
    {
        // Display confirmation page after successful order
        return $this->render('order/confirmation');
    }
}