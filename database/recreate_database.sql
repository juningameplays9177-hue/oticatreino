-- ============================================
-- SCRIPT SQL PARA RECRIAR O BANCO DE DADOS
-- Hospital dos Óculos - Laravel 11
-- ============================================
-- Execute este script no phpMyAdmin ou MySQL
-- Certifique-se de criar o banco de dados primeiro: CREATE DATABASE nome_do_banco;
-- USE nome_do_banco;
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- 1. TABELAS BÁSICAS (sem dependências)
-- ============================================

-- Users (tabela base do Laravel)
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `role` ENUM('admin','gerente') NULL DEFAULT 'gerente',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL DEFAULT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stores (Lojas)
DROP TABLE IF EXISTS `stores`;
CREATE TABLE `stores` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `code` VARCHAR(30) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stores_code_unique` (`code`),
  KEY `stores_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Companies (Empresas)
DROP TABLE IF EXISTS `company_licenses`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tax_id_type` ENUM('CPF','CNPJ') NULL DEFAULT NULL,
  `cpf_clean` CHAR(11) NULL DEFAULT NULL,
  `cnpj_clean` CHAR(14) NULL DEFAULT NULL,
  `legal_name` VARCHAR(190) NULL DEFAULT NULL,
  `trade_name` VARCHAR(190) NULL DEFAULT NULL,
  `slug` VARCHAR(80) NULL DEFAULT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `mobile` VARCHAR(30) NULL DEFAULT NULL,
  `contact_name` VARCHAR(120) NULL DEFAULT NULL,
  `email` VARCHAR(190) NULL DEFAULT NULL,
  `zip_code` CHAR(8) NULL DEFAULT NULL,
  `address` VARCHAR(190) NULL DEFAULT NULL,
  `number` VARCHAR(30) NULL DEFAULT NULL,
  `complement` VARCHAR(120) NULL DEFAULT NULL,
  `district` VARCHAR(120) NULL DEFAULT NULL,
  `city` VARCHAR(120) NULL DEFAULT NULL,
  `state` CHAR(2) NULL DEFAULT NULL,
  `logo_path` VARCHAR(255) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_slug_unique` (`slug`),
  KEY `idx_companies_slug_active` (`slug`,`is_active`),
  KEY `idx_companies_cnpj` (`cnpj_clean`),
  KEY `idx_companies_cpf` (`cpf_clean`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Brands (Marcas)
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Groups (Grupos de Produtos)
DROP TABLE IF EXISTS `product_subgroups`;
DROP TABLE IF EXISTS `product_groups`;
CREATE TABLE `product_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Subgroups (Subgrupos de Produtos)
CREATE TABLE `product_subgroups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_subgroups_group_id_foreign` (`group_id`),
  CONSTRAINT `product_subgroups_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `product_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Functions (Funções de Trabalho)
DROP TABLE IF EXISTS `job_functions`;
CREATE TABLE `job_functions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TABELAS COM DEPENDÊNCIAS
-- ============================================

-- Atualizar Users com relacionamentos
ALTER TABLE `users` 
  ADD COLUMN `employee_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`,
  ADD COLUMN `store_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `employee_id`,
  ADD COLUMN `company_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `store_id`,
  ADD KEY `users_employee_id_foreign` (`employee_id`),
  ADD KEY `users_store_id_foreign` (`store_id`),
  ADD KEY `users_company_id_foreign` (`company_id`),
  ADD KEY `idx_users_company_role` (`company_id`,`role`);

-- Suppliers (Fornecedores)
DROP TABLE IF EXISTS `supplier_representatives`;
DROP TABLE IF EXISTS `supplier_contacts`;
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `tax_id_type` ENUM('CPF','CNPJ') NOT NULL DEFAULT 'CNPJ',
  `cpf_clean` CHAR(11) NULL DEFAULT NULL,
  `cnpj_clean` CHAR(14) NULL DEFAULT NULL,
  `trade_name` VARCHAR(190) NOT NULL,
  `legal_name` VARCHAR(190) NULL DEFAULT NULL,
  `is_lab` TINYINT(1) NOT NULL DEFAULT 0,
  `taxpayer_icms` TINYINT(1) NOT NULL DEFAULT 0,
  `ie` VARCHAR(40) NULL DEFAULT NULL,
  `im` VARCHAR(40) NULL DEFAULT NULL,
  `suframa` VARCHAR(40) NULL DEFAULT NULL,
  `email` VARCHAR(190) NULL DEFAULT NULL,
  `website` VARCHAR(190) NULL DEFAULT NULL,
  `zip_code` CHAR(8) NULL DEFAULT NULL,
  `address` VARCHAR(190) NULL DEFAULT NULL,
  `number` VARCHAR(30) NULL DEFAULT NULL,
  `complement` VARCHAR(120) NULL DEFAULT NULL,
  `district` VARCHAR(120) NULL DEFAULT NULL,
  `city` VARCHAR(120) NULL DEFAULT NULL,
  `state` CHAR(2) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_company_active` (`company_id`,`is_active`),
  KEY `idx_suppliers_cnpj` (`cnpj_clean`),
  KEY `idx_suppliers_cpf` (`cpf_clean`),
  CONSTRAINT `fk_suppliers_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employees (Funcionários)
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(190) NOT NULL,
  `role_func_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `rg` VARCHAR(40) NULL DEFAULT NULL,
  `cpf_clean` CHAR(11) NULL DEFAULT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `mobile` VARCHAR(30) NULL DEFAULT NULL,
  `zip_code` CHAR(8) NULL DEFAULT NULL,
  `address` VARCHAR(190) NULL DEFAULT NULL,
  `district` VARCHAR(120) NULL DEFAULT NULL,
  `city` VARCHAR(120) NULL DEFAULT NULL,
  `state` CHAR(2) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employees_company_store_active` (`company_id`,`store_id`,`is_active`),
  KEY `idx_employees_cpf` (`cpf_clean`),
  KEY `employees_company_id_foreign` (`company_id`),
  KEY `employees_store_id_foreign` (`store_id`),
  KEY `employees_role_func_id_foreign` (`role_func_id`),
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_role_func_id_foreign` FOREIGN KEY (`role_func_id`) REFERENCES `job_functions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atualizar Foreign Keys em Users
ALTER TABLE `users`
  ADD CONSTRAINT `users_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

-- Subscriptions
CREATE TABLE `subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `plan_code` VARCHAR(60) NOT NULL,
  `contract_type` ENUM('MONTHLY','QUARTERLY','SEMIANNUAL','ANNUAL') NOT NULL,
  `activation_fee_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL DEFAULT NULL,
  `status` ENUM('ACTIVE','SUSPENDED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_company_id_foreign` (`company_id`),
  KEY `idx_subscriptions_company_status` (`company_id`,`status`),
  CONSTRAINT `subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company Licenses
CREATE TABLE `company_licenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `license_status` ENUM('ATIVA','PENDENTE','CANCELADA','EXPIRADA') NOT NULL DEFAULT 'PENDENTE',
  `cert_valid_from` DATETIME NULL DEFAULT NULL,
  `cert_valid_to` DATETIME NULL DEFAULT NULL,
  `cert_status` ENUM('VALIDO','EXPIRADO','NAO_CONFIGURADO') NOT NULL DEFAULT 'NAO_CONFIGURADO',
  `last_check_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_licenses_company_id_foreign` (`company_id`),
  KEY `company_licenses_license_status_index` (`license_status`),
  CONSTRAINT `company_licenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clients (Clientes)
DROP TABLE IF EXISTS `client_refs`;
DROP TABLE IF EXISTS `client_emails`;
DROP TABLE IF EXISTS `client_phones`;
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `type` ENUM('PF','PJ') NOT NULL DEFAULT 'PF',
  `external_code` VARCHAR(60) NULL DEFAULT NULL,
  `origin` VARCHAR(80) NULL DEFAULT NULL,
  `name` VARCHAR(190) NOT NULL,
  `nickname` VARCHAR(120) NULL DEFAULT NULL,
  `cpf_cnpj` VARCHAR(20) NULL DEFAULT NULL,
  `rg_ie` VARCHAR(30) NULL DEFAULT NULL,
  `birth_date` DATE NULL DEFAULT NULL,
  `cep` VARCHAR(9) NULL DEFAULT NULL,
  `city` VARCHAR(120) NULL DEFAULT NULL,
  `district` VARCHAR(120) NULL DEFAULT NULL,
  `address` VARCHAR(190) NULL DEFAULT NULL,
  `number` VARCHAR(20) NULL DEFAULT NULL,
  `complement` VARCHAR(120) NULL DEFAULT NULL,
  `father_name` VARCHAR(190) NULL DEFAULT NULL,
  `mother_name` VARCHAR(190) NULL DEFAULT NULL,
  `guardian_name` VARCHAR(190) NULL DEFAULT NULL,
  `guardian_relation` VARCHAR(60) NULL DEFAULT NULL,
  `profession` VARCHAR(120) NULL DEFAULT NULL,
  `default_adjust_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `income_family` VARCHAR(60) NULL DEFAULT NULL,
  `education_level` VARCHAR(60) NULL DEFAULT NULL,
  `sex` ENUM('M','F','NI') NOT NULL DEFAULT 'NI',
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_external_code_unique` (`external_code`),
  UNIQUE KEY `clients_cpf_cnpj_unique` (`cpf_cnpj`),
  KEY `idx_clients_search1` (`name`,`nickname`,`city`,`district`),
  KEY `idx_clients_created` (`created_at`),
  KEY `idx_clients_origin` (`origin`),
  KEY `idx_clients_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Phones
CREATE TABLE `client_phones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `type` ENUM('PHONE','MOBILE','WHATSAPP') NOT NULL DEFAULT 'PHONE',
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_phones_client_id_foreign` (`client_id`),
  CONSTRAINT `client_phones_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Emails
CREATE TABLE `client_emails` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_emails_client_id_foreign` (`client_id`),
  CONSTRAINT `client_emails_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Refs
CREATE TABLE `client_refs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `relationship` VARCHAR(60) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_refs_client_id_foreign` (`client_id`),
  CONSTRAINT `client_refs_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products (Produtos)
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `product_stocks`;
DROP TABLE IF EXISTS `product_prices`;
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref` VARCHAR(50) NULL DEFAULT NULL,
  `ean13` VARCHAR(13) NULL DEFAULT NULL,
  `name` VARCHAR(190) NOT NULL,
  `unit` ENUM('FR','KIT','PAR','PC','UN') NOT NULL DEFAULT 'UN',
  `brand_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `group_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `subgroup_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `supplier_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `color` VARCHAR(60) NULL DEFAULT NULL,
  `size` VARCHAR(60) NULL DEFAULT NULL,
  `shape` VARCHAR(60) NULL DEFAULT NULL,
  `sell_only_with_os` TINYINT(1) NOT NULL DEFAULT 0,
  `control_stock` TINYINT(1) NOT NULL DEFAULT 1,
  `showcase_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `archived` TINYINT(1) NOT NULL DEFAULT 0,
  `description` MEDIUMTEXT NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `label_code` BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_ref_unique` (`ref`),
  UNIQUE KEY `products_ean13_unique` (`ean13`),
  UNIQUE KEY `products_label_code_unique` (`label_code`),
  KEY `idx_products_search` (`name`,`ref`,`ean13`),
  KEY `idx_products_brand_group` (`brand_id`,`group_id`,`subgroup_id`),
  KEY `idx_products_archived` (`archived`),
  KEY `products_brand_id_foreign` (`brand_id`),
  KEY `products_group_id_foreign` (`group_id`),
  KEY `products_subgroup_id_foreign` (`subgroup_id`),
  KEY `products_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `product_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_subgroup_id_foreign` FOREIGN KEY (`subgroup_id`) REFERENCES `product_subgroups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Prices
CREATE TABLE `product_prices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_prices_product_id_foreign` (`product_id`),
  KEY `product_prices_store_id_foreign` (`store_id`),
  CONSTRAINT `product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_prices_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Stocks
CREATE TABLE `product_stocks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `min_quantity` INT NOT NULL DEFAULT 0,
  `max_quantity` INT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_stocks_product_id_foreign` (`product_id`),
  KEY `product_stocks_store_id_foreign` (`store_id`),
  CONSTRAINT `product_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stocks_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Images
CREATE TABLE `product_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store Counters
DROP TABLE IF EXISTS `store_counters`;
CREATE TABLE `store_counters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `counter_type` VARCHAR(60) NOT NULL,
  `last_value` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_counters_store_id_foreign` (`store_id`),
  CONSTRAINT `store_counters_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescriptions
DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE `prescriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_name` VARCHAR(190) NOT NULL,
  `crm` VARCHAR(30) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Orders
DROP TABLE IF EXISTS `service_order_prescriptions`;
DROP TABLE IF EXISTS `service_order_status_history`;
DROP TABLE IF EXISTS `service_order_images`;
DROP TABLE IF EXISTS `service_order_items`;
DROP TABLE IF EXISTS `service_orders`;
CREATE TABLE `service_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `os_number` VARCHAR(30) NOT NULL,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NOT NULL,
  `os_type` ENUM('OTICA') NOT NULL DEFAULT 'OTICA',
  `registered_at` DATETIME NOT NULL,
  `employee_id` BIGINT UNSIGNED NOT NULL,
  `client_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `source` VARCHAR(80) NULL DEFAULT NULL,
  `delivery_date` DATE NULL DEFAULT NULL,
  `delivery_time` TIME NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `status` ENUM('REGISTRADA','EM_PRODUCAO','PRONTA','ENTREGUE','CANCELADA','PERDA','VENDIDA','NAO_VENDIDA') NOT NULL DEFAULT 'REGISTRADA',
  `advance_type` ENUM('SEM','TOTAL','PARCIAL') NOT NULL DEFAULT 'SEM',
  `advance_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `cancel_reason` VARCHAR(190) NULL DEFAULT NULL,
  `loss_reason` VARCHAR(190) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_orders_os_number_unique` (`os_number`),
  KEY `service_orders_company_id_foreign` (`company_id`),
  KEY `service_orders_store_id_foreign` (`store_id`),
  KEY `service_orders_employee_id_foreign` (`employee_id`),
  KEY `service_orders_client_id_foreign` (`client_id`),
  KEY `service_orders_store_id_registered_at_index` (`store_id`,`registered_at`),
  KEY `service_orders_status_index` (`status`),
  KEY `service_orders_os_number_index` (`os_number`),
  CONSTRAINT `service_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `service_orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `service_orders_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `service_orders_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Order Items
CREATE TABLE `service_order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `description` VARCHAR(190) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_order_items_service_order_id_foreign` (`service_order_id`),
  KEY `service_order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `service_order_items_service_order_id_foreign` FOREIGN KEY (`service_order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Order Images
CREATE TABLE `service_order_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_order_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_order_images_service_order_id_foreign` (`service_order_id`),
  CONSTRAINT `service_order_images_service_order_id_foreign` FOREIGN KEY (`service_order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Order Status History
CREATE TABLE `service_order_status_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_order_id` BIGINT UNSIGNED NOT NULL,
  `old_status` VARCHAR(30) NULL DEFAULT NULL,
  `new_status` VARCHAR(30) NOT NULL,
  `changed_by` BIGINT UNSIGNED NOT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_order_status_history_service_order_id_foreign` (`service_order_id`),
  KEY `service_order_status_history_changed_by_foreign` (`changed_by`),
  CONSTRAINT `service_order_status_history_service_order_id_foreign` FOREIGN KEY (`service_order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_order_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Order Prescriptions
CREATE TABLE `service_order_prescriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_order_id` BIGINT UNSIGNED NOT NULL,
  `prescription_id` BIGINT UNSIGNED NOT NULL,
  `eye_type` ENUM('OD','OE','AMBOS') NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_order_prescriptions_service_order_id_foreign` (`service_order_id`),
  KEY `service_order_prescriptions_prescription_id_foreign` (`prescription_id`),
  CONSTRAINT `service_order_prescriptions_service_order_id_foreign` FOREIGN KEY (`service_order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_order_prescriptions_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier Contacts
CREATE TABLE `supplier_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `role` VARCHAR(120) NULL DEFAULT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `mobile` VARCHAR(30) NULL DEFAULT NULL,
  `email` VARCHAR(190) NULL DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_contacts_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `supplier_contacts_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier Representatives
CREATE TABLE `supplier_representatives` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) NULL DEFAULT NULL,
  `mobile` VARCHAR(30) NULL DEFAULT NULL,
  `email` VARCHAR(190) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_representatives_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `supplier_representatives_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mobile Integrations
DROP TABLE IF EXISTS `mobile_integrations`;
CREATE TABLE `mobile_integrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `store_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `device_name` VARCHAR(120) NOT NULL,
  `api_key` VARCHAR(255) NOT NULL,
  `token_label` VARCHAR(120) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_sync_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mobile_integrations_company_id_foreign` (`company_id`),
  KEY `idx_mobile_integrations_company_active` (`company_id`,`is_active`),
  CONSTRAINT `mobile_integrations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reference Tables (Client Sources, etc.)
DROP TABLE IF EXISTS `client_sources`;
CREATE TABLE `client_sources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- FIM DO SCRIPT PRINCIPAL
-- ============================================
-- NOTA: Para o módulo financeiro, execute o arquivo
-- database/finance_module.sql separadamente
-- ============================================

