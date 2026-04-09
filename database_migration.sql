-- Adicionar coluna google_id à tabela usuarios (se não existir)
-- Execute este comando no seu banco de dados

ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL;

-- Ou se quiser uma abordagem mais segura que não gera erro se a coluna já existir:
-- ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL;
