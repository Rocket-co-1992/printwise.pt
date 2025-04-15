<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;

class ProductController extends Controller
{
    public function index(): string
    {
        // TODO: Fetch products from database
        $products = []; // Placeholder for products
        
        return $this->render('product/index', [
            'products' => $products
        ]);
    }
    
    public function show(int $id): string
    {
        // TODO: Fetch product details from database
        $product = []; // Placeholder for product details
        
        return $this->render('product/show', [
            'product' => $product
        ]);
    }
    
    public function categories(): string
    {
        // TODO: Fetch product categories from database
        $categories = []; // Placeholder for categories
        
        return $this->render('product/categories', [
            'categories' => $categories
        ]);
    }
    
    public function byCategory(int $categoryId): string
    {
        // TODO: Fetch products by category from database
        $category = []; // Placeholder for category
        $products = []; // Placeholder for products
        
        return $this->render('product/by-category', [
            'category' => $category,
            'products' => $products
        ]);
    }
}