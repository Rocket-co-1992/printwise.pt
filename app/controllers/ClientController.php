<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;
use PrintWise\Models\Client;
use PrintWise\Models\Company;

class ClientController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // Verificar autenticação em todos os métodos deste controlador
        $this->requireAuth();
    }
    
    public function index(): string
    {
        $clientModel = new Client();
        $clients = $clientModel->findAllWithCompany();
        
        return $this->render('admin/clients/index', [
            'clients' => $clients
        ]);
    }
    
    public function create(): string
    {
        $companyModel = new Company();
        $companies = $companyModel->findAll();
        
        return $this->render('admin/clients/create', [
            'companies' => $companies
        ]);
    }
    
    public function store(): string
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $companyId = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
        
        // Validar campos obrigatórios
        if (empty($name) || empty($email)) {
            $companyModel = new Company();
            $companies = $companyModel->findAll();
            
            return $this->render('admin/clients/create', [
                'error' => 'Nome e email são campos obrigatórios',
                'companies' => $companies,
                'client' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'company_id' => $companyId
                ]
            ]);
        }
        
        $clientModel = new Client();
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'company_id' => $companyId
        ];
        
        $clientId = $clientModel->create($data);
        
        $this->redirect('admin/clients');
        return '';
    }
    
    public function edit(int $id): string
    {
        $clientModel = new Client();
        $client = $clientModel->find($id);
        
        if (!$client) {
            $this->redirect('admin/clients');
            return '';
        }
        
        $companyModel = new Company();
        $companies = $companyModel->findAll();
        
        return $this->render('admin/clients/edit', [
            'client' => $client,
            'companies' => $companies
        ]);
    }
    
    public function update(int $id): string
    {
        $clientModel = new Client();
        $client = $clientModel->find($id);
        
        if (!$client) {
            $this->redirect('admin/clients');
            return '';
        }
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $companyId = !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null;
        
        // Validar campos obrigatórios
        if (empty($name) || empty($email)) {
            $companyModel = new Company();
            $companies = $companyModel->findAll();
            
            return $this->render('admin/clients/edit', [
                'error' => 'Nome e email são campos obrigatórios',
                'client' => [
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'company_id' => $companyId
                ],
                'companies' => $companies
            ]);
        }
        
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'company_id' => $companyId
        ];
        
        $clientModel->update($id, $data);
        
        $this->redirect('admin/clients');
        return '';
    }
    
    public function delete(int $id): string
    {
        $clientModel = new Client();
        $clientModel->delete($id);
        
        $this->redirect('admin/clients');
        return '';
    }
}