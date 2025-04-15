<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;
use PrintWise\Models\Quote;
use PrintWise\Models\Client;
use PrintWise\Models\Company;
use PrintWise\Models\WasteControl;

class AdminController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireAdmin();
    }
    
    protected function requireAdmin(): void
    {
        $user = $this->getCurrentUser();
        if (!$user || $user['role'] !== 'admin') {
            $this->redirect('');
        }
    }
    
    public function index(): string
    {
        // Redirecionar para o dashboard
        $this->redirect('admin/dashboard');
        return '';
    }
    
    public function dashboard(): string
    {
        // Obter estatísticas para o dashboard
        $quoteModel = new Quote();
        $clientModel = new Client();
        $companyModel = new Company();
        $wasteModel = new WasteControl();
        
        // Contagem de orçamentos por status
        $quoteStats = [];
        $quoteStatusCounts = $quoteModel->countByStatus();
        
        $statusLabels = [
            'pending' => 'Pendentes',
            'approved' => 'Aprovados',
            'rejected' => 'Rejeitados',
            'completed' => 'Concluídos'
        ];
        
        // Inicializar todos os status com zero
        foreach ($statusLabels as $key => $label) {
            $quoteStats[$key] = [
                'label' => $label,
                'count' => 0
            ];
        }
        
        // Preencher com os valores reais
        foreach ($quoteStatusCounts as $stat) {
            if (isset($quoteStats[$stat['status']])) {
                $quoteStats[$stat['status']]['count'] = $stat['count'];
            }
        }
        
        // Últimos orçamentos
        $recentQuotes = $quoteModel->findAllWithDetails();
        $recentQuotes = array_slice($recentQuotes, 0, 5);
        
        // Contagem total de clientes e empresas
        $totalClients = count($clientModel->findAll());
        $totalCompanies = count($companyModel->findAll());
        
        return $this->render('admin/dashboard', [
            'quoteStats' => $quoteStats,
            'recentQuotes' => $recentQuotes,
            'totalClients' => $totalClients,
            'totalCompanies' => $totalCompanies
        ]);
    }
    
    public function users(): string
    {
        // TODO: Fetch users from database
        $users = []; // Placeholder for users
        
        return $this->render('admin/users', [
            'users' => $users
        ]);
    }
    
    public function orders(): string
    {
        // TODO: Fetch orders from database
        $orders = []; // Placeholder for orders
        
        return $this->render('admin/orders', [
            'orders' => $orders
        ]);
    }
    
    public function updateOrderStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = $_POST['order_id'] ?? null;
            $status = $_POST['status'] ?? null;
            
            // TODO: Update order status in database
            
            $this->redirect('admin/orders');
        }
    }
    
    public function products(): string
    {
        // TODO: Fetch products from database
        $products = []; // Placeholder for products
        
        return $this->render('admin/products', [
            'products' => $products
        ]);
    }
    
    public function createProduct(): string
    {
        return $this->render('admin/create-product');
    }
    
    public function storeProduct(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $categoryId = $_POST['category_id'] ?? null;
            
            // TODO: Store product in database
            
            $this->redirect('admin/products');
        }
    }
    
    public function editProduct(int $id): string
    {
        // TODO: Fetch product from database
        $product = []; // Placeholder for product
        
        return $this->render('admin/edit-product', [
            'product' => $product
        ]);
    }
    
    public function updateProduct(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = $_POST['price'] ?? 0;
            $categoryId = $_POST['category_id'] ?? null;
            
            // TODO: Update product in database
            
            $this->redirect('admin/products');
        }
    }
    
    public function deleteProduct(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            
            // TODO: Delete product from database
            
            $this->redirect('admin/products');
        }
    }
}