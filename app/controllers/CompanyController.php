<?php

namespace PrintWise\Controllers;

use PrintWise\Core\Controller;
use PrintWise\Models\Company;

class CompanyController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        // Verificar autenticação em todos os métodos deste controlador
        $this->requireAuth();
    }
    
    public function index(): string
    {
        $companyModel = new Company();
        $companies = $companyModel->findWithClientCount();
        
        return $this->render('admin/companies/index', [
            'companies' => $companies
        ]);
    }
    
    public function create(): string
    {
        return $this->render('admin/companies/create');
    }
    
    public function store(): string
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $taxId = $_POST['tax_id'] ?? '';
        
        // Validar campos obrigatórios
        if (empty($name)) {
            return $this->render('admin/companies/create', [
                'error' => 'Nome da empresa é obrigatório',
                'company' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'tax_id' => $taxId
                ]
            ]);
        }
        
        $companyModel = new Company();
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'tax_id' => $taxId
        ];
        
        $companyId = $companyModel->create($data);
        
        $this->redirect('admin/companies');
        return '';
    }
    
    public function edit(int $id): string
    {
        $companyModel = new Company();
        $company = $companyModel->find($id);
        
        if (!$company) {
            $this->redirect('admin/companies');
            return '';
        }
        
        return $this->render('admin/companies/edit', [
            'company' => $company
        ]);
    }
    
    public function update(int $id): string
    {
        $companyModel = new Company();
        $company = $companyModel->find($id);
        
        if (!$company) {
            $this->redirect('admin/companies');
            return '';
        }
        
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $taxId = $_POST['tax_id'] ?? '';
        
        // Validar campos obrigatórios
        if (empty($name)) {
            return $this->render('admin/companies/edit', [
                'error' => 'Nome da empresa é obrigatório',
                'company' => [
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'tax_id' => $taxId
                ]
            ]);
        }
        
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'tax_id' => $taxId
        ];
        
        $companyModel->update($id, $data);
        
        $this->redirect('admin/companies');
        return '';
    }
    
    public function delete(int $id): string
    {
        $companyModel = new Company();
        $companyModel->delete($id);
        
        $this->redirect('admin/companies');
        return '';
    }
}