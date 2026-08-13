-- Migração: impede duas mesas com o mesmo número/nome no mesmo restaurante.
--
-- Se já tem mesas duplicadas na base de dados, esta migração falha —
-- corrija/apague os duplicados primeiro (SELECT restaurant_id, number,
-- COUNT(*) FROM restaurant_tables GROUP BY restaurant_id, number HAVING
-- COUNT(*) > 1) e depois corra este script.

ALTER TABLE `restaurant_tables`
    ADD UNIQUE KEY `uq_restaurant_table_number` (`restaurant_id`, `number`);
