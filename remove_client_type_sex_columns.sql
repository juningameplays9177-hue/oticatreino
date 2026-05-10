-- SQL para remover as colunas 'type' e 'sex' da tabela 'clients'
-- Execute este script no phpMyAdmin ou no seu cliente MySQL

-- Remover a coluna 'type'
ALTER TABLE `clients` DROP COLUMN `type`;

-- Remover a coluna 'sex'
ALTER TABLE `clients` DROP COLUMN `sex`;

-- Verificar se as colunas foram removidas (opcional)
-- DESCRIBE `clients`;
