-- ============================================
-- MÓDULO FINANCEIRO COMPLETO - SQL PARA phpMyAdmin
-- ============================================
-- Execute este script no phpMyAdmin após garantir que as tabelas
-- companies, stores, users, clients, suppliers, products existem
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. ACCOUNTS (Contas Financeiras)
-- ============================================
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(190) NOT NULL,
  `type` ENUM('cash','bank','credit_gateway') NOT NULL,
  `bank_name` VARCHAR(120) NULL DEFAULT NULL,
  `agency` VARCHAR(20) NULL DEFAULT NULL,
  `number` VARCHAR(30) NULL DEFAULT NULL,
  `pix_key` VARCHAR(120) NULL DEFAULT NULL,
  `opening_balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_accounts_company` (`company_id`),
  INDEX `idx_accounts_store` (`store_id`),
  INDEX `idx_accounts_type` (`type`),
  INDEX `idx_accounts_active` (`is_active`),
  CONSTRAINT `fk_accounts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_accounts_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. FINANCE_CATEGORIES (Plano de Contas Gerencial)
-- ============================================
CREATE TABLE IF NOT EXISTS `finance_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(190) NOT NULL,
  `nature` ENUM('revenue','expense','asset','liability','equity') NOT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `cost_center_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_finance_categories_company` (`company_id`),
  INDEX `idx_finance_categories_parent` (`parent_id`),
  INDEX `idx_finance_categories_nature` (`nature`),
  INDEX `idx_finance_categories_active` (`is_active`),
  CONSTRAINT `fk_finance_categories_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_finance_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `finance_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. COST_CENTERS (Centros de Custo)
-- ============================================
CREATE TABLE IF NOT EXISTS `cost_centers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_cost_centers_company` (`company_id`),
  INDEX `idx_cost_centers_active` (`is_active`),
  CONSTRAINT `fk_cost_centers_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar FK de cost_center_id em finance_categories após criar cost_centers
ALTER TABLE `finance_categories` 
  ADD CONSTRAINT `fk_finance_categories_cost_center` 
  FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL;

-- ============================================
-- 4. CASH_SESSIONS (Sessão de Caixa)
-- ============================================
CREATE TABLE IF NOT EXISTS `cash_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `opened_by` BIGINT UNSIGNED NOT NULL,
  `closed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
  `opened_at` TIMESTAMP NOT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  `opening_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `closing_amount` DECIMAL(14,2) NULL DEFAULT NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_cash_sessions_company` (`company_id`),
  INDEX `idx_cash_sessions_store` (`store_id`),
  INDEX `idx_cash_sessions_account` (`account_id`),
  INDEX `idx_cash_sessions_status` (`status`),
  INDEX `idx_cash_sessions_opened_by` (`opened_by`),
  CONSTRAINT `fk_cash_sessions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cash_sessions_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cash_sessions_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cash_sessions_opened_by` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cash_sessions_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CASH_MOVEMENTS (Movimentos dentro da Sessão)
-- ============================================
CREATE TABLE IF NOT EXISTS `cash_movements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cash_session_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('in','out') NOT NULL,
  `method` ENUM('money','pix','card','boleto','other') NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `origin_type` VARCHAR(50) NULL DEFAULT NULL,
  `origin_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `note` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_cash_movements_session` (`cash_session_id`),
  INDEX `idx_cash_movements_method` (`method`),
  INDEX `idx_cash_movements_origin` (`origin_type`, `origin_id`),
  CONSTRAINT `fk_cash_movements_session` FOREIGN KEY (`cash_session_id`) REFERENCES `cash_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cash_movements_category` FOREIGN KEY (`category_id`) REFERENCES `finance_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. TRANSACTIONS (Lançamentos em Dupla Entrada)
-- ============================================
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `txn_date` DATETIME NOT NULL,
  `description` VARCHAR(190) NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `dr_account_id` BIGINT UNSIGNED NOT NULL,
  `cr_account_id` BIGINT UNSIGNED NOT NULL,
  `link_type` VARCHAR(50) NULL DEFAULT NULL,
  `link_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `cost_center_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `tags` JSON NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_transactions_company` (`company_id`),
  INDEX `idx_transactions_store` (`store_id`),
  INDEX `idx_transactions_date` (`txn_date`),
  INDEX `idx_transactions_dr_account` (`dr_account_id`),
  INDEX `idx_transactions_cr_account` (`cr_account_id`),
  INDEX `idx_transactions_link` (`link_type`, `link_id`),
  INDEX `idx_transactions_category` (`category_id`),
  CONSTRAINT `fk_transactions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_dr_account` FOREIGN KEY (`dr_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_transactions_cr_account` FOREIGN KEY (`cr_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) REFERENCES `finance_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transactions_cost_center` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. RECEIVABLES (Contas a Receber)
-- ============================================
CREATE TABLE IF NOT EXISTS `receivables` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `sale_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `os_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `original_amount` DECIMAL(14,2) NOT NULL,
  `balance_amount` DECIMAL(14,2) NOT NULL,
  `status` ENUM('open','partial','paid','canceled','renegotiated') NOT NULL DEFAULT 'open',
  `method` VARCHAR(50) NOT NULL,
  `gateway_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `our_number` VARCHAR(50) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_receivables_company` (`company_id`),
  INDEX `idx_receivables_store` (`store_id`),
  INDEX `idx_receivables_customer` (`customer_id`),
  INDEX `idx_receivables_due_date` (`due_date`),
  INDEX `idx_receivables_status` (`status`),
  INDEX `idx_receivables_balance` (`balance_amount`),
  INDEX `idx_receivables_sale` (`sale_id`),
  INDEX `idx_receivables_os` (`os_id`),
  CONSTRAINT `fk_receivables_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receivables_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receivables_customer` FOREIGN KEY (`customer_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
  -- FK para sales será adicionada após criar a tabela sales
  -- CONSTRAINT `fk_receivables_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_receivables_os` FOREIGN KEY (`os_id`) REFERENCES `service_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. RECEIVABLE_PAYMENTS (Pagamentos de Contas a Receber)
-- ============================================
CREATE TABLE IF NOT EXISTS `receivable_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receivable_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `gateway_fee_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `method` VARCHAR(50) NOT NULL,
  `note` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_receivable_payments_receivable` (`receivable_id`),
  INDEX `idx_receivable_payments_account` (`account_id`),
  CONSTRAINT `fk_receivable_payments_receivable` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receivable_payments_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. PAYABLES (Contas a Pagar)
-- ============================================
CREATE TABLE IF NOT EXISTS `payables` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `supplier_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `document_no` VARCHAR(50) NULL DEFAULT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `original_amount` DECIMAL(14,2) NOT NULL,
  `balance_amount` DECIMAL(14,2) NOT NULL,
  `status` ENUM('open','partial','paid','canceled') NOT NULL DEFAULT 'open',
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `cost_center_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `note` TEXT NULL DEFAULT NULL,
  `attachment_path` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_payables_company` (`company_id`),
  INDEX `idx_payables_store` (`store_id`),
  INDEX `idx_payables_supplier` (`supplier_id`),
  INDEX `idx_payables_due_date` (`due_date`),
  INDEX `idx_payables_status` (`status`),
  INDEX `idx_payables_balance` (`balance_amount`),
  CONSTRAINT `fk_payables_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payables_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payables_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payables_category` FOREIGN KEY (`category_id`) REFERENCES `finance_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payables_cost_center` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. PAYABLE_PAYMENTS (Pagamentos de Contas a Pagar)
-- ============================================
CREATE TABLE IF NOT EXISTS `payable_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payable_id` BIGINT UNSIGNED NOT NULL,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `method` VARCHAR(50) NOT NULL,
  `note` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_payable_payments_payable` (`payable_id`),
  INDEX `idx_payable_payments_account` (`account_id`),
  CONSTRAINT `fk_payable_payments_payable` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payable_payments_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. BANK_RECONCILIATIONS (Conciliação Bancária)
-- ============================================
CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` BIGINT UNSIGNED NOT NULL,
  `statement_date` DATE NOT NULL,
  `starting_balance` DECIMAL(14,2) NOT NULL,
  `ending_balance` DECIMAL(14,2) NOT NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_bank_reconciliations_account` (`account_id`),
  INDEX `idx_bank_reconciliations_status` (`status`),
  CONSTRAINT `fk_bank_reconciliations_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. BANK_RECONCILIATION_ITEMS (Itens de Conciliação)
-- ============================================
CREATE TABLE IF NOT EXISTS `bank_reconciliation_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reconciliation_id` BIGINT UNSIGNED NOT NULL,
  `transaction_id` BIGINT UNSIGNED NOT NULL,
  `statement_amount` DECIMAL(14,2) NOT NULL,
  `matched` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_bank_reconciliation_items_reconciliation` (`reconciliation_id`),
  INDEX `idx_bank_reconciliation_items_transaction` (`transaction_id`),
  CONSTRAINT `fk_bank_reconciliation_items_reconciliation` FOREIGN KEY (`reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bank_reconciliation_items_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. SALES (Ajustes na tabela de vendas)
-- ============================================
-- Verificar se a tabela sales existe, se não, criar
CREATE TABLE IF NOT EXISTS `sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `sale_number` VARCHAR(50) NULL DEFAULT NULL,
  `sale_date` DATETIME NOT NULL,
  `total_gross` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_discount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_net` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `payment_summary` JSON NULL DEFAULT NULL,
  `status` ENUM('completed','canceled') NOT NULL DEFAULT 'completed',
  `account_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_sales_company` (`company_id`),
  INDEX `idx_sales_store` (`store_id`),
  INDEX `idx_sales_customer` (`customer_id`),
  INDEX `idx_sales_date` (`sale_date`),
  INDEX `idx_sales_status` (`status`),
  CONSTRAINT `fk_sales_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar colunas na tabela sales se ela já existir
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'sales' 
  AND COLUMN_NAME = 'total_gross');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `sales` 
    ADD COLUMN `total_gross` DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER `sale_date`,
    ADD COLUMN `total_discount` DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER `total_gross`,
    ADD COLUMN `total_net` DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER `total_discount`,
    ADD COLUMN `total_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER `total_net`,
    ADD COLUMN `payment_summary` JSON NULL DEFAULT NULL AFTER `total_cost`,
    ADD COLUMN `status` ENUM(\'completed\',\'canceled\') NOT NULL DEFAULT \'completed\' AFTER `payment_summary`,
    ADD COLUMN `account_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `status`,
    ADD INDEX `idx_sales_status` (`status`),
    ADD CONSTRAINT `fk_sales_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;',
  'SELECT "Colunas já existem na tabela sales" AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 14. SALE_ITEMS (Itens da Venda)
-- ============================================
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `qty` DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `unit_price` DECIMAL(14,2) NOT NULL,
  `discount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(14,2) NOT NULL,
  `total_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_sale_items_sale` (`sale_id`),
  INDEX `idx_sale_items_product` (`product_id`),
  CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. SALE_PAYMENTS (Pagamentos da Venda)
-- ============================================
CREATE TABLE IF NOT EXISTS `sale_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` BIGINT UNSIGNED NOT NULL,
  `method` VARCHAR(50) NOT NULL,
  `account_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `paid_at` DATETIME NOT NULL,
  `gateway_fee_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `installments` INT UNSIGNED NULL DEFAULT NULL,
  `card_brand` VARCHAR(50) NULL DEFAULT NULL,
  `auth_code` VARCHAR(50) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_sale_payments_sale` (`sale_id`),
  INDEX `idx_sale_payments_account` (`account_id`),
  CONSTRAINT `fk_sale_payments_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sale_payments_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar FK de receivables para sales se a tabela sales existir
SET @sales_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
  WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'sales');
SET @sql = IF(@sales_exists > 0, 
  'ALTER TABLE `receivables` 
    ADD CONSTRAINT `fk_receivables_sale` 
    FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL;',
  'SELECT "Tabela sales não existe, FK não adicionada" AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- FIM DO SCRIPT
-- ============================================

