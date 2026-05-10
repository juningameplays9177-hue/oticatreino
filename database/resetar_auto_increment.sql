-- ============================================
-- SCRIPT PARA RESETAR AUTO_INCREMENT
-- Execute este script se já limpou os dados
-- mas os novos cadastros não estão funcionando
-- ============================================

-- Resetar AUTO_INCREMENT das tabelas principais
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
-- VERIFICAÇÃO (opcional - pode dar erro de permissão)
-- ============================================
-- Se você tiver permissão, pode executar esta consulta para verificar:
-- SELECT TABLE_NAME, AUTO_INCREMENT
-- FROM information_schema.TABLES
-- WHERE TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME IN (
--         'clients', 'client_phones', 'client_emails', 'client_refs',
--         'service_orders', 'service_order_items', 'service_order_images',
--         'service_order_status_history', 'service_order_prescription',
--         'sales', 'sale_items', 'sale_payments',
--         'receivables', 'receivable_payments',
--         'payables', 'payable_payments',
--         'transactions', 'cash_sessions', 'cash_movements',
--         'bank_reconciliations', 'bank_reconciliation_items'
--     )
-- ORDER BY TABLE_NAME;
