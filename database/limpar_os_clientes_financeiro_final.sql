-- ============================================
-- SCRIPT PARA ZERAR OS, CLIENTES E FINANCEIRO
-- Execute este script no phpMyAdmin
-- ATENÇÃO: Esta operação é IRREVERSÍVEL!
-- ============================================

-- Desabilitar verificação de foreign keys temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- ORDEM CORRETA: Limpar tabelas filhas primeiro, depois as pais
-- ============================================

-- ============================================
-- 1. LIMPAR MÓDULO FINANCEIRO (que referencia clients e service_orders)
-- ============================================
-- Pagamentos de vendas (referencia sales)
DELETE FROM `sale_payments`;
-- Itens de vendas (referencia sales)
DELETE FROM `sale_items`;
-- Vendas (referencia clients)
DELETE FROM `sales`;

-- Pagamentos de contas a receber (referencia receivables)
DELETE FROM `receivable_payments`;
-- Contas a receber (referencia clients e service_orders)
DELETE FROM `receivables`;

-- Pagamentos de contas a pagar (referencia payables)
DELETE FROM `payable_payments`;
-- Contas a pagar (não referencia clients, mas limpar aqui)
DELETE FROM `payables`;

-- Itens de conciliação bancária (referencia bank_reconciliations e transactions)
DELETE FROM `bank_reconciliation_items`;
-- Conciliações bancárias (referencia accounts)
DELETE FROM `bank_reconciliations`;

-- Movimentos de caixa (referencia cash_sessions)
DELETE FROM `cash_movements`;
-- Sessões de caixa (referencia accounts e users)
DELETE FROM `cash_sessions`;

-- Transações contábeis (referencia accounts)
DELETE FROM `transactions`;

-- ============================================
-- 2. LIMPAR ORDENS DE SERVIÇO (OS)
-- ============================================
-- Nota: O nome correto da tabela é 'service_order_prescription' (singular, sem 's')
-- Prescrições da OS (referencia service_orders)
DELETE FROM `service_order_prescription`;
-- Histórico de status (referencia service_orders)
DELETE FROM `service_order_status_history`;
-- Imagens da OS (referencia service_orders)
DELETE FROM `service_order_images`;
-- Itens da OS (referencia service_orders)
DELETE FROM `service_order_items`;
-- OS principal (referencia clients)
DELETE FROM `service_orders`;

-- ============================================
-- 3. LIMPAR CLIENTES (por último, pois é referenciado por outras tabelas)
-- ============================================
-- Referências de clientes (referencia clients)
DELETE FROM `client_refs`;
-- Emails de clientes (referencia clients)
DELETE FROM `client_emails`;
-- Telefones de clientes (referencia clients)
DELETE FROM `client_phones`;
-- Clientes principal (agora pode ser deletado sem problemas)
DELETE FROM `clients`;

-- Reabilitar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- RESETAR AUTO_INCREMENT DAS TABELAS
-- ============================================
-- Isso garante que os próximos registros comecem do ID 1
ALTER TABLE `clients` AUTO_INCREMENT = 1;
ALTER TABLE `client_phones` AUTO_INCREMENT = 1;
ALTER TABLE `client_emails` AUTO_INCREMENT = 1;
ALTER TABLE `client_refs` AUTO_INCREMENT = 1;
ALTER TABLE `service_orders` AUTO_INCREMENT = 1;
ALTER TABLE `service_order_items` AUTO_INCREMENT = 1;
ALTER TABLE `service_order_images` AUTO_INCREMENT = 1;
ALTER TABLE `service_order_status_history` AUTO_INCREMENT = 1;
ALTER TABLE `service_order_prescription` AUTO_INCREMENT = 1;
ALTER TABLE `sales` AUTO_INCREMENT = 1;
ALTER TABLE `sale_items` AUTO_INCREMENT = 1;
ALTER TABLE `sale_payments` AUTO_INCREMENT = 1;
ALTER TABLE `receivables` AUTO_INCREMENT = 1;
ALTER TABLE `receivable_payments` AUTO_INCREMENT = 1;
ALTER TABLE `payables` AUTO_INCREMENT = 1;
ALTER TABLE `payable_payments` AUTO_INCREMENT = 1;
ALTER TABLE `transactions` AUTO_INCREMENT = 1;
ALTER TABLE `cash_sessions` AUTO_INCREMENT = 1;
ALTER TABLE `cash_movements` AUTO_INCREMENT = 1;
ALTER TABLE `bank_reconciliations` AUTO_INCREMENT = 1;
ALTER TABLE `bank_reconciliation_items` AUTO_INCREMENT = 1;

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
-- Se alguma tabela não existir, você verá um erro específico.
-- Nesse caso, comente ou remova a linha correspondente e execute novamente.
