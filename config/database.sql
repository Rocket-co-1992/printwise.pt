-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS printwise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE printwise;

-- Tabela de usuários (administradores)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    tax_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de clientes
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    company_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL
);

-- Tabela de produtos (para orçamentos)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('small', 'large') NOT NULL COMMENT 'small: pequenos formatos, large: grandes formatos',
    description TEXT,
    base_price DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de acabamentos
CREATE TABLE IF NOT EXISTS finishings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_factor DECIMAL(10, 2) DEFAULT 1.00 COMMENT 'Fator multiplicador ou valor adicional',
    is_multiplier BOOLEAN DEFAULT TRUE COMMENT 'TRUE se for multiplicador, FALSE se for valor adicional',
    product_type ENUM('small', 'large', 'both') DEFAULT 'both',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de orçamentos
CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    product_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    hash VARCHAR(64) NOT NULL UNIQUE COMMENT 'Hash único para acesso externo',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    width DECIMAL(10, 2) NULL COMMENT 'Largura para grandes formatos (em cm)',
    height DECIMAL(10, 2) NULL COMMENT 'Altura para grandes formatos (em cm)',
    colors INT DEFAULT 4 COMMENT 'Número de cores para pequenos formatos',
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    reject_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients (id),
    FOREIGN KEY (product_id) REFERENCES products (id)
);

-- Tabela de relação entre orçamentos e acabamentos (muitos para muitos)
CREATE TABLE IF NOT EXISTS quote_finishings (
    quote_id INT NOT NULL,
    finishing_id INT NOT NULL,
    PRIMARY KEY (quote_id, finishing_id),
    FOREIGN KEY (quote_id) REFERENCES quotes (id) ON DELETE CASCADE,
    FOREIGN KEY (finishing_id) REFERENCES finishings (id) ON DELETE CASCADE
);

-- Tabela de controle de desperdício
CREATE TABLE IF NOT EXISTS waste_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    expected_quantity INT NOT NULL,
    actual_quantity INT NOT NULL,
    waste_percentage DECIMAL(5, 2) GENERATED ALWAYS AS (
        (expected_quantity - actual_quantity) * 100.0 / expected_quantity
    ) STORED,
    justification TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes (id)
);

-- Inserir usuário administrador padrão
-- Senha: admin123 (com bcrypt)
INSERT INTO users (name, email, password) VALUES 
('Administrador', 'admin@printwise.pt', '$2y$10$G6Hl.Sa3GSpSIFDS/aCOt.6IsrXwyKXQ0G3NsR/hcTPaeX3ymBPOO');

-- Inserir alguns produtos básicos
INSERT INTO products (name, type, description, base_price) VALUES 
('Cartão de Visita', 'small', 'Cartão de visita standard', 25.00),
('Flyer A5', 'small', 'Flyer tamanho A5', 35.00),
('Lona', 'large', 'Impressão em lona', 15.00),
('Vinil', 'large', 'Impressão em vinil', 18.00);

-- Inserir acabamentos básicos
INSERT INTO finishings (name, description, price_factor, is_multiplier, product_type) VALUES 
('Laminação Mate', 'Acabamento laminado mate', 1.15, TRUE, 'both'),
('Laminação Brilho', 'Acabamento laminado brilhante', 1.15, TRUE, 'both'),
('Corte Reto', 'Corte reto simples', 0, TRUE, 'both'),
('Cantos Redondos', 'Acabamento com cantos redondos', 5.00, FALSE, 'small'),
('Ilhós', 'Colocação de ilhós', 2.50, FALSE, 'large');