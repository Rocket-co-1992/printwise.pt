<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;
use PrintWise\Models\Quote;
use PrintWise\Models\Client;
use PrintWise\Models\Product;
use PrintWise\Models\Finishing;

class QuoteController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Verificar autenticação em todos os métodos deste controlador, exceto os públicos
        if (!in_array($_SERVER['REQUEST_URI'], ['/quotes/view', '/quotes/approve', '/quotes/reject'])) {
            $this->requireAuth();
        }
    }
    
    public function index(): string
    {
        $quoteModel = new Quote();
        $quotes = $quoteModel->findAllWithDetails();
        
        return $this->render('admin/quotes/index', [
            'quotes' => $quotes
        ]);
    }
    
    public function create(): string
    {
        $clientModel = new Client();
        $productModel = new Product();
        
        $clients = $clientModel->findAllWithCompany();
        $smallProducts = $productModel->findByType('small');
        $largeProducts = $productModel->findByType('large');
        
        return $this->render('admin/quotes/create', [
            'clients' => $clients,
            'smallProducts' => $smallProducts,
            'largeProducts' => $largeProducts
        ]);
    }
    
    public function store(): string
    {
        // Obter dados do formulário
        $clientId = (int)($_POST['client_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $productType = $_POST['product_type'] ?? '';
        $finishingIds = $_POST['finishing_ids'] ?? [];
        
        // Validar campos obrigatórios
        if ($clientId <= 0 || $productId <= 0 || empty($title) || $quantity <= 0) {
            // Recarregar dados para o formulário
            $clientModel = new Client();
            $productModel = new Product();
            
            $clients = $clientModel->findAllWithCompany();
            $smallProducts = $productModel->findByType('small');
            $largeProducts = $productModel->findByType('large');
            
            return $this->render('admin/quotes/create', [
                'error' => 'Preencha todos os campos obrigatórios',
                'clients' => $clients,
                'smallProducts' => $smallProducts,
                'largeProducts' => $largeProducts,
                'quote' => [
                    'client_id' => $clientId,
                    'product_id' => $productId,
                    'title' => $title,
                    'description' => $description,
                    'quantity' => $quantity,
                    'product_type' => $productType
                ]
            ]);
        }
        
        // Calcular preço com base no tipo de produto
        $quoteModel = new Quote();
        $unitPrice = 0;
        
        try {
            if ($productType == 'small') {
                $colors = (int)($_POST['colors'] ?? 4);
                $unitPrice = $quoteModel->calculateSmallFormatPrice($productId, $quantity, $colors, $finishingIds);
                
                $data = [
                    'client_id' => $clientId,
                    'product_id' => $productId,
                    'title' => $title,
                    'description' => $description,
                    'colors' => $colors,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'hash' => $quoteModel->generateHash(),
                    'status' => 'pending'
                ];
            } else { // large format
                $width = (float)($_POST['width'] ?? 0);
                $height = (float)($_POST['height'] ?? 0);
                
                if ($width <= 0 || $height <= 0) {
                    throw new \Exception("Dimensões inválidas");
                }
                
                $unitPrice = $quoteModel->calculateLargeFormatPrice($productId, $width, $height, $quantity, $finishingIds);
                
                $data = [
                    'client_id' => $clientId,
                    'product_id' => $productId,
                    'title' => $title,
                    'description' => $description,
                    'width' => $width,
                    'height' => $height,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'hash' => $quoteModel->generateHash(),
                    'status' => 'pending'
                ];
            }
            
            // Criar o orçamento
            $quoteId = $quoteModel->create($data);
            
            // Adicionar acabamentos
            if (!empty($finishingIds)) {
                $quoteModel->addFinishings($quoteId, $finishingIds);
            }
            
            $this->redirect('admin/quotes');
            return '';
        } catch (\Exception $e) {
            // Em caso de erro, recarregar formulário com mensagem de erro
            $clientModel = new Client();
            $productModel = new Product();
            
            $clients = $clientModel->findAllWithCompany();
            $smallProducts = $productModel->findByType('small');
            $largeProducts = $productModel->findByType('large');
            
            return $this->render('admin/quotes/create', [
                'error' => 'Erro ao calcular orçamento: ' . $e->getMessage(),
                'clients' => $clients,
                'smallProducts' => $smallProducts,
                'largeProducts' => $largeProducts,
                'quote' => [
                    'client_id' => $clientId,
                    'product_id' => $productId,
                    'title' => $title,
                    'description' => $description,
                    'quantity' => $quantity,
                    'product_type' => $productType
                ]
            ]);
        }
    }
    
    public function edit(int $id): string
    {
        $quoteModel = new Quote();
        $quote = $quoteModel->findWithDetails($id);
        
        if (!$quote) {
            $this->redirect('admin/quotes');
            return '';
        }
        
        $clientModel = new Client();
        $productModel = new Product();
        $finishingModel = new Finishing();
        
        $clients = $clientModel->findAllWithCompany();
        $smallProducts = $productModel->findByType('small');
        $largeProducts = $productModel->findByType('large');
        
        // Obter acabamentos do orçamento
        $quoteFinishings = $finishingModel->findByQuote($id);
        $finishingIds = array_column($quoteFinishings, 'id');
        
        // Determinar tipo de produto (small/large)
        $productType = $quote['product_type'] ?? '';
        
        // Obter acabamentos disponíveis para este tipo de produto
        $availableFinishings = $finishingModel->findByProductType($productType);
        
        return $this->render('admin/quotes/edit', [
            'quote' => $quote,
            'clients' => $clients,
            'smallProducts' => $smallProducts,
            'largeProducts' => $largeProducts,
            'finishings' => $availableFinishings,
            'finishingIds' => $finishingIds
        ]);
    }
    
    public function update(int $id): string
    {
        $quoteModel = new Quote();
        $quote = $quoteModel->find($id);
        
        if (!$quote) {
            $this->redirect('admin/quotes');
            return '';
        }
        
        // Obter dados do formulário
        $clientId = (int)($_POST['client_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $productType = $_POST['product_type'] ?? '';
        $finishingIds = $_POST['finishing_ids'] ?? [];
        
        // Validações básicas
        if ($clientId <= 0 || $productId <= 0 || empty($title) || $quantity <= 0) {
            return $this->edit($id); // Retornar ao formulário com dados existentes
        }
        
        try {
            // Calcular preço com base no tipo de produto
            if ($productType == 'small') {
                $colors = (int)($_POST['colors'] ?? 4);
                $unitPrice = $quoteModel->calculateSmallFormatPrice($productId, $quantity, $colors, $finishingIds);
                
                $data = [
                    'client_id' => $clientId,
                    'product_id' => $productId,
                    'title' => $title,
                    'description' => $description,
                    'colors' => $colors,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'status' => 'pending'
                ];
            } else { // large format
                $width = (float)($_POST['width'] ?? 0);
                $height = (float)($_POST['height'] ?? 0);
                
                if ($width <= 0 || $height <= 0) {
                    throw new \Exception("Dimensões inválidas");
                }
                
                $unitPrice = $quoteModel->calculateLargeFormatPrice($productId, $width, $height, $quantity, $finishingIds);
                
                $data = [
                    'client_id' => $clientId,
                    'product_id' => $productId,
                    'title' => $title,
                    'description' => $description,
                    'width' => $width,
                    'height' => $height,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'status' => 'pending'
                ];
            }
            
            // Atualizar o orçamento
            $quoteModel->update($id, $data);
            
            // Atualizar acabamentos
            $quoteModel->updateFinishings($id, $finishingIds);
            
            $this->redirect('admin/quotes');
            return '';
        } catch (\Exception $e) {
            // Recarregar o formulário com mensagem de erro
            $clientModel = new Client();
            $productModel = new Product();
            $finishingModel = new Finishing();
            
            $clients = $clientModel->findAllWithCompany();
            $smallProducts = $productModel->findByType('small');
            $largeProducts = $productModel->findByType('large');
            $availableFinishings = $finishingModel->findByProductType($productType);
            
            $quoteData = [
                'id' => $id,
                'client_id' => $clientId,
                'product_id' => $productId,
                'title' => $title,
                'description' => $description,
                'quantity' => $quantity,
                'product_type' => $productType
            ];
            
            if ($productType == 'small') {
                $quoteData['colors'] = $_POST['colors'] ?? 4;
            } else {
                $quoteData['width'] = $_POST['width'] ?? 0;
                $quoteData['height'] = $_POST['height'] ?? 0;
            }
            
            return $this->render('admin/quotes/edit', [
                'error' => 'Erro ao atualizar orçamento: ' . $e->getMessage(),
                'quote' => $quoteData,
                'clients' => $clients,
                'smallProducts' => $smallProducts,
                'largeProducts' => $largeProducts,
                'finishings' => $availableFinishings,
                'finishingIds' => $finishingIds
            ]);
        }
    }
    
    public function show(int $id): string
    {
        $quoteModel = new Quote();
        $quote = $quoteModel->findWithDetails($id);
        
        if (!$quote) {
            $this->redirect('admin/quotes');
            return '';
        }
        
        $finishingModel = new Finishing();
        $finishings = $finishingModel->findByQuote($id);
        
        // URL de compartilhamento para aprovação
        $config = require_once __DIR__ . '/../../config/config.php';
        $shareUrl = $config['app_url'] . '/quotes/view/' . $quote['hash'];
        
        return $this->render('admin/quotes/show', [
            'quote' => $quote,
            'finishings' => $finishings,
            'shareUrl' => $shareUrl
        ]);
    }
    
    public function delete(int $id): string
    {
        $quoteModel = new Quote();
        $quoteModel->delete($id);
        
        $this->redirect('admin/quotes');
        return '';
    }
    
    public function publicView(string $hash): string
    {
        $quoteModel = new Quote();
        $quote = $quoteModel->findByHash($hash);
        
        if (!$quote) {
            return $this->render('error/not_found', [
                'message' => 'Orçamento não encontrado'
            ]);
        }
        
        $finishingModel = new Finishing();
        $finishings = $finishingModel->findByQuote($quote['id']);
        
        return $this->render('quotes/public_view', [
            'quote' => $quote,
            'finishings' => $finishings
        ]);
    }
    
    public function approve(string $hash): string
    {
        $quoteModel = new Quote();
        $quote = $quoteModel->findByHash($hash);
        
        if (!$quote) {
            return $this->render('error/not_found', [
                'message' => 'Orçamento não encontrado'
            ]);
        }
        
        // Atualizar status para aprovado
        $quoteModel->update($quote['id'], ['status' => 'approved']);
        
        return $this->render('quotes/approved', [
            'quote' => $quote
        ]);
    }
    
    public function reject(string $hash): string
    {
        $quoteModel = new Quote();
        $quote = $quoteModel->findByHash($hash);
        
        if (!$quote) {
            return $this->render('error/not_found', [
                'message' => 'Orçamento não encontrado'
            ]);
        }
        
        // Obter motivo da rejeição
        $rejectReason = $_POST['reject_reason'] ?? '';
        
        // Atualizar status para rejeitado e salvar motivo
        $quoteModel->update($quote['id'], [
            'status' => 'rejected',
            'reject_reason' => $rejectReason
        ]);
        
        return $this->render('quotes/rejected', [
            'quote' => $quote
        ]);
    }
    
    // Método para uso via AJAX para cálculo imediato
    public function calculate(): string
    {
        // Verificar se é uma requisição AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
            return json_encode(['error' => 'Requisição inválida']);
        }
        
        $productId = (int)($_POST['product_id'] ?? 0);
        $productType = $_POST['product_type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $finishingIds = isset($_POST['finishing_ids']) ? array_map('intval', $_POST['finishing_ids']) : [];
        
        if ($productId <= 0 || empty($productType) || $quantity <= 0) {
            return json_encode(['error' => 'Parâmetros inválidos']);
        }
        
        try {
            $quoteModel = new Quote();
            
            if ($productType == 'small') {
                $colors = (int)($_POST['colors'] ?? 4);
                $unitPrice = $quoteModel->calculateSmallFormatPrice($productId, $quantity, $colors, $finishingIds);
            } else {
                $width = (float)($_POST['width'] ?? 0);
                $height = (float)($_POST['height'] ?? 0);
                
                if ($width <= 0 || $height <= 0) {
                    return json_encode(['error' => 'Dimensões inválidas']);
                }
                
                $unitPrice = $quoteModel->calculateLargeFormatPrice($productId, $width, $height, $quantity, $finishingIds);
            }
            
            $totalPrice = $unitPrice * $quantity;
            
            return json_encode([
                'success' => true,
                'unitPrice' => $unitPrice,
                'totalPrice' => $totalPrice,
                'formattedUnitPrice' => number_format($unitPrice, 2, ',', '.') . ' €',
                'formattedTotalPrice' => number_format($totalPrice, 2, ',', '.') . ' €'
            ]);
        } catch (\Exception $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}