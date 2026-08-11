-- Migração: bloqueio de conta por tentativas falhadas de login.
--
-- Se já importou `database/cardapio.sql` antes desta versão, corra este
-- script na sua base de dados existente (o cardapio.sql novo já traz estas
-- colunas para quem for fazer uma importação do zero).

ALTER TABLE `admin_users`
    ADD COLUMN `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `role`,
    ADD COLUMN `locked_until` TIMESTAMP NULL DEFAULT NULL AFTER `failed_login_attempts`;
