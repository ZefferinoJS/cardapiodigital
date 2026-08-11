-- Migração: controlo de inactividade da sessão de mesa (35 minutos).
--
-- Se já importou `database/cardapio.sql` antes desta versão, corra este
-- script na sua base de dados existente.

ALTER TABLE `visits`
    ADD COLUMN `last_seen_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `user_agent`;

UPDATE `visits` SET `last_seen_at` = `created_at` WHERE `last_seen_at` IS NULL;
