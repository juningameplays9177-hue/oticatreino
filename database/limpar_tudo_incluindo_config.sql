-- ============================================
-- SCRIPT PARA ZERAR TUDO (INCLUINDO CONFIGURAÇÕES)
-- Execute este script no phpMyAdmin
-- ATENÇÃO: Esta operação é IRREVERSÍVEL!
-- Este script limpa TUDO, incluindo contas, categorias e centros de custo
-- ============================================

-- Desabilitar verificação de foreign keys temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. LIMPAR ORDENS DE SERVIÇO (OS)
-- ============================================
TRUNCATE TABLE `service_order_prescriptions`;
TRUNCATE TABLE `service_order_status_history`;
TRUNCATE TABLE `service_order_images`;
TRUNCATE TABLE `service_order_items`;
TRUNCATE TABLE `service_orders`;

-- ============================================
-- 2. LIMPAR CLIENTES
-- ============================================
TRUNCATE TABLE `client_refs`;
TRUNCATE TABLE `client_emails`;
TRUNCATE TABLE `client_phones`;
TRUNCATE TABLE `clients`;

-- ============================================
-- 3. LIMPAR MÓDULO FINANCEIRO COMPLETO
-- ============================================
-- Pagamentos e vendas
TRUNCATE TABLE `sale_payments`;
TRUNCATE TABLE `sale_items`;
TRUNCATE TABLE `sales`;

-- Contas a receber
TRUNCATE TABLE `receivable_payments`;
TRUNCATE TABLE `receivables`;

-- Contas a pagar
TRUNCATE TABLE `payable_payments`;
TRUNCATE TABLE `payables`;

-- Conciliação bancária
TRUNCATE TABLE `bank_reconciliation_items`;
TRUNCATE TABLE `bank_reconciliations`;

-- Caixa
TRUNCATE TABLE `cash_movements`;
TRUNCATE TABLE `cash_sessions`;

-- Transações contábeis
TRUNCATE TABLE `transactions`;

-- ============================================
-- 4. LIMPAR CONFIGURAÇÕES FINANCEIRAS
-- ============================================
-- ATENÇÃO: Isso remove TODAS as contas, categorias e centros de custo!
TRUNCATE TABLE `cost_centers`;
TRUNCATE TABLE `finance_categories`;
TRUNCATE TABLE `accounts`;

-- Reabilitar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICAÇÃO FINAL
-- ============================================
SELECT 
    'OS' AS tabela,
    COUNT(*) AS total
FROM service_orders
UNION ALL
SELECT 
    'Clientes' AS tabela,
    COUNT(*) AS total
FROM clients
UNION ALL
SELECT 
    'Vendas' AS tabela,
    COUNT(*) AS total
FROM sales
UNION ALL
SELECT 
    'Contas a Receber' AS tabela,
    COUNT(*) AS total
FROM receivables
UNION ALL
SELECT 
    'Contas a Pagar' AS tabela,
    COUNT(*) AS total
FROM payables
UNION ALL
SELECT 
    'Transações' AS tabela,
    COUNT(*) AS total
FROM transactions
UNION ALL
SELECT 
    'Contas Financeiras' AS tabela,
    COUNT(*) AS total
FROM accounts
UNION ALL
SELECT 
    'Categorias Financeiras' AS tabela,
    COUNT(*) AS total
FROM finance_categories;

-- ============================================
-- FIM DO SCRIPT
-- ============================================
