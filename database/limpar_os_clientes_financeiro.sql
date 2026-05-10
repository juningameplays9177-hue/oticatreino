-- ============================================
-- SCRIPT PARA ZERAR OS, CLIENTES E FINANCEIRO
-- Execute este script no phpMyAdmin
-- ATENÇÃO: Esta operação é IRREVERSÍVEL!
-- ============================================

-- Desabilitar verificação de foreign keys temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. LIMPAR ORDENS DE SERVIÇO (OS)
-- ============================================
-- Limpar tabelas relacionadas primeiro (filhas)
-- Nota: Verifica se a tabela existe antes de truncar
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'service_order_prescription');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `service_order_prescription`;', 'SELECT "Tabela service_order_prescription não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'service_order_status_history');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `service_order_status_history`;', 'SELECT "Tabela service_order_status_history não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'service_order_images');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `service_order_images`;', 'SELECT "Tabela service_order_images não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'service_order_items');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `service_order_items`;', 'SELECT "Tabela service_order_items não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar tabela principal
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'service_orders');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `service_orders`;', 'SELECT "Tabela service_orders não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. LIMPAR CLIENTES
-- ============================================
-- Limpar tabelas relacionadas primeiro (filhas)
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'client_refs');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `client_refs`;', 'SELECT "Tabela client_refs não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'client_emails');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `client_emails`;', 'SELECT "Tabela client_emails não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'client_phones');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `client_phones`;', 'SELECT "Tabela client_phones não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar tabela principal
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'clients');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `clients`;', 'SELECT "Tabela clients não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 3. LIMPAR MÓDULO FINANCEIRO
-- ============================================
-- Função auxiliar para truncar tabela se existir
-- Limpar pagamentos de vendas primeiro
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'sale_payments');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `sale_payments`;', 'SELECT "Tabela sale_payments não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar itens de vendas
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'sale_items');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `sale_items`;', 'SELECT "Tabela sale_items não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar vendas
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'sales');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `sales`;', 'SELECT "Tabela sales não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar pagamentos de contas a receber
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'receivable_payments');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `receivable_payments`;', 'SELECT "Tabela receivable_payments não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar contas a receber
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'receivables');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `receivables`;', 'SELECT "Tabela receivables não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar pagamentos de contas a pagar
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'payable_payments');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `payable_payments`;', 'SELECT "Tabela payable_payments não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar contas a pagar
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'payables');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `payables`;', 'SELECT "Tabela payables não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar itens de conciliação bancária
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bank_reconciliation_items');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `bank_reconciliation_items`;', 'SELECT "Tabela bank_reconciliation_items não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar conciliações bancárias
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bank_reconciliations');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `bank_reconciliations`;', 'SELECT "Tabela bank_reconciliations não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar movimentos de caixa
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'cash_movements');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `cash_movements`;', 'SELECT "Tabela cash_movements não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar sessões de caixa
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'cash_sessions');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `cash_sessions`;', 'SELECT "Tabela cash_sessions não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Limpar transações (lançamentos contábeis)
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transactions');
SET @sql = IF(@table_exists > 0, 'TRUNCATE TABLE `transactions`;', 'SELECT "Tabela transactions não existe, pulando..." AS message;');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- OBSERVAÇÕES IMPORTANTES:
-- ============================================
-- As seguintes tabelas NÃO foram limpas (são configurações):
-- - accounts (Contas Financeiras - mantidas)
-- - finance_categories (Categorias Financeiras - mantidas)
-- - cost_centers (Centros de Custo - mantidos)
-- 
-- Se você também quiser limpar essas tabelas de configuração,
-- descomente as linhas abaixo:
-- TRUNCATE TABLE `cost_centers`;
-- TRUNCATE TABLE `finance_categories`;
-- TRUNCATE TABLE `accounts`;

-- Reabilitar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- FIM DO SCRIPT
-- ============================================
-- Verificar se tudo foi limpo:
-- SELECT 
--     (SELECT COUNT(*) FROM service_orders) AS total_os,
--     (SELECT COUNT(*) FROM clients) AS total_clientes,
--     (SELECT COUNT(*) FROM sales) AS total_vendas,
--     (SELECT COUNT(*) FROM receivables) AS total_receber,
--     (SELECT COUNT(*) FROM payables) AS total_pagar,
--     (SELECT COUNT(*) FROM transactions) AS total_transacoes;
