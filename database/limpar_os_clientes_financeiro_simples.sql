-- ============================================
-- SCRIPT SIMPLIFICADO PARA ZERAR OS, CLIENTES E FINANCEIRO
-- Execute este script no phpMyAdmin
-- ATENÇÃO: Esta operação é IRREVERSÍVEL!
-- ============================================

-- Desabilitar verificação de foreign keys temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. LIMPAR ORDENS DE SERVIÇO (OS)
-- ============================================
-- Nota: O nome correto da tabela é 'service_order_prescription' (singular)
TRUNCATE TABLE IF EXISTS `service_order_prescription`;
TRUNCATE TABLE IF EXISTS `service_order_status_history`;
TRUNCATE TABLE IF EXISTS `service_order_images`;
TRUNCATE TABLE IF EXISTS `service_order_items`;
TRUNCATE TABLE IF EXISTS `service_orders`;

-- ============================================
-- 2. LIMPAR CLIENTES
-- ============================================
TRUNCATE TABLE IF EXISTS `client_refs`;
TRUNCATE TABLE IF EXISTS `client_emails`;
TRUNCATE TABLE IF EXISTS `client_phones`;
TRUNCATE TABLE IF EXISTS `clients`;

-- ============================================
-- 3. LIMPAR MÓDULO FINANCEIRO
-- ============================================
-- Pagamentos e vendas
TRUNCATE TABLE IF EXISTS `sale_payments`;
TRUNCATE TABLE IF EXISTS `sale_items`;
TRUNCATE TABLE IF EXISTS `sales`;

-- Contas a receber
TRUNCATE TABLE IF EXISTS `receivable_payments`;
TRUNCATE TABLE IF EXISTS `receivables`;

-- Contas a pagar
TRUNCATE TABLE IF EXISTS `payable_payments`;
TRUNCATE TABLE IF EXISTS `payables`;

-- Conciliação bancária
TRUNCATE TABLE IF EXISTS `bank_reconciliation_items`;
TRUNCATE TABLE IF EXISTS `bank_reconciliations`;

-- Caixa
TRUNCATE TABLE IF EXISTS `cash_movements`;
TRUNCATE TABLE IF EXISTS `cash_sessions`;

-- Transações contábeis
TRUNCATE TABLE IF EXISTS `transactions`;

-- Reabilitar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICAÇÃO FINAL
-- ============================================
SELECT 
    'OS' AS tabela,
    COALESCE((SELECT COUNT(*) FROM service_orders), 0) AS total
UNION ALL
SELECT 
    'Clientes' AS tabela,
    COALESCE((SELECT COUNT(*) FROM clients), 0) AS total
UNION ALL
SELECT 
    'Vendas' AS tabela,
    COALESCE((SELECT COUNT(*) FROM sales), 0) AS total
UNION ALL
SELECT 
    'Contas a Receber' AS tabela,
    COALESCE((SELECT COUNT(*) FROM receivables), 0) AS total
UNION ALL
SELECT 
    'Contas a Pagar' AS tabela,
    COALESCE((SELECT COUNT(*) FROM payables), 0) AS total
UNION ALL
SELECT 
    'Transações' AS tabela,
    COALESCE((SELECT COUNT(*) FROM transactions), 0) AS total;

-- ============================================
-- FIM DO SCRIPT
-- ============================================
