<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;
use PrintWise\Models\WasteControl;
use PrintWise\Models\Quote;

class WasteController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // Verificar autenticação em todos os métodos deste controlador
        $this->requireAuth();
    }
    
    public function index(): string
    {
        $wasteModel = new WasteControl();
        $wasteRecords = $wasteModel->findAllWithQuoteDetails();
        
        // Carregar configurações para obter limiar de desperdício
        $config = require_once __DIR__ . '/../../config/config.php';
        $threshold = $config['waste_threshold'] ?? 5;
        
        return $this->render('admin/waste/index', [
            'wasteRecords' => $wasteRecords,
            'threshold' => $threshold
        ]);
    }
    
    public function create(int $quoteId): string
    {
        $quoteModel = new Quote();
        $quote = $quoteModel->findWithDetails($quoteId);
        
        if (!$quote) {
            $this->redirect('admin/quotes');
            return '';
        }
        
        // Verificar se já existe um registro para este orçamento
        $wasteModel = new WasteControl();
        $existingRecord = $wasteModel->findByQuote($quoteId);
        
        if ($existingRecord) {
            $this->redirect('admin/waste');
            return '';
        }
        
        // Carregar configurações para obter limiar de desperdício
        $config = require_once __DIR__ . '/../../config/config.php';
        $threshold = $config['waste_threshold'] ?? 5;
        
        return $this->render('admin/waste/create', [
            'quote' => $quote,
            'threshold' => $threshold
        ]);
    }
    
    public function store(): string
    {
        $quoteId = (int)($_POST['quote_id'] ?? 0);
        $expectedQuantity = (int)($_POST['expected_quantity'] ?? 0);
        $actualQuantity = (int)($_POST['actual_quantity'] ?? 0);
        $justification = $_POST['justification'] ?? '';
        
        if ($quoteId <= 0 || $expectedQuantity <= 0 || $actualQuantity < 0) {
            $this->redirect('admin/waste');
            return '';
        }
        
        $wasteModel = new WasteControl();
        
        // Calcular porcentagem de desperdício
        $wastePercentage = $wasteModel->calculateWastePercentage($expectedQuantity, $actualQuantity);
        
        // Verificar se justificativa é necessária
        $isJustificationRequired = $wasteModel->isJustificationRequired($wastePercentage);
        
        if ($isJustificationRequired && empty($justification)) {
            // Recarregar formulário com erro
            $quoteModel = new Quote();
            $quote = $quoteModel->findWithDetails($quoteId);
            
            $config = require_once __DIR__ . '/../../config/config.php';
            $threshold = $config['waste_threshold'] ?? 5;
            
            return $this->render('admin/waste/create', [
                'quote' => $quote,
                'error' => 'O desperdício está acima do limiar. É necessário fornecer uma justificação.',
                'input' => [
                    'expected_quantity' => $expectedQuantity,
                    'actual_quantity' => $actualQuantity
                ],
                'threshold' => $threshold,
                'waste_percentage' => $wastePercentage
            ]);
        }
        
        // Salvar registro de desperdício
        $data = [
            'quote_id' => $quoteId,
            'expected_quantity' => $expectedQuantity,
            'actual_quantity' => $actualQuantity,
            'justification' => $justification
        ];
        
        $wasteModel->create($data);
        
        // Atualizar status do orçamento para completo
        $quoteModel = new Quote();
        $quoteModel->update($quoteId, ['status' => 'completed']);
        
        $this->redirect('admin/waste');
        return '';
    }
}