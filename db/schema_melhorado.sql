-- ============================================================
-- "O Cardápio" — Schema Melhorado
-- Alterações aplicadas com base na revisão de 2026-05-30:
--   1. Removido item_id de ingredients (usar só item_ingredients)
--   2. ratings com ENGINE=InnoDB + vínculo a order_id
--   3. admin_users: roles expandidos (superadmin, manager, staff, kitchen)
--   4. Collation uniformizada para utf8mb4_0900_ai_ci em todas as tabelas
--   5. menu_items.slug: NOT NULL + unique por (restaurant_id, slug)
--   6. order_items: removido total_price redundante
--   7. restaurant_tables: qr_code com UNIQUE KEY
--   8. orders: adicionado closed_at
--   9. restaurants: enriquecida com logo, phone, address, currency, primary_color, is_active
--  10. Índice composto orders(restaurant_id, status)
--  11. visits: user_agent substituído por device_type ENUM
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- TABELA: restaurants
-- Melhorias: campos de configuração adicionados; collation corrigida
-- ============================================================
CREATE TABLE `restaurants` (
  `id`            int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          varchar(191)  NOT NULL,
  `slug`          varchar(191)  NOT NULL,
  `timezone`      varchar(64)   DEFAULT 'UTC',
  `logo`          varchar(255)  DEFAULT NULL,           -- caminho/URL do logótipo
  `address`       varchar(255)  DEFAULT NULL,
  `phone`         varchar(32)   DEFAULT NULL,
  `currency`      varchar(8)    DEFAULT 'AOA',          -- código ISO da moeda
  `primary_color` varchar(7)    DEFAULT '#FF6600',      -- cor principal da UI (#RRGGBB)
  `is_active`     tinyint(1)    DEFAULT '1',
  `created_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `restaurants`
  (`id`, `name`, `slug`, `timezone`, `currency`, `primary_color`, `is_active`)
VALUES
  (1, 'Minha Lanchonete', 'minha-lanchonete', 'UTC', 'AOA', '#FF6600', 1);

-- ============================================================
-- TABELA: admin_users
-- Melhorias: role expandido para incluir superadmin e kitchen
-- ============================================================
CREATE TABLE `admin_users` (
  `id`            int UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` int UNSIGNED DEFAULT NULL,
  `name`          varchar(191)  NOT NULL,
  `email`         varchar(191)  NOT NULL,
  `password_hash` varchar(255)  NOT NULL,
  `role`          enum('superadmin','manager','staff','kitchen') DEFAULT 'staff',
  `created_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `admin_users_ibfk_1`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- TABELA: restaurant_tables
-- Melhorias: qr_code com UNIQUE KEY para impedir tokens repetidos/adivinháveis
-- ============================================================
CREATE TABLE `restaurant_tables` (
  `id`            int UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` int UNSIGNED NOT NULL,
  `number`        varchar(32)   NOT NULL,
  `qr_code`       varchar(255)  DEFAULT NULL,   -- token único gerado aleatoriamente pela app
  `description`   varchar(255)  DEFAULT NULL,
  `active`        tinyint(1)    DEFAULT '1',
  `created_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qr_code` (`qr_code`),          -- ← garante tokens únicos
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `restaurant_tables_ibfk_1`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `restaurant_tables`
  (`id`, `restaurant_id`, `number`, `qr_code`, `description`, `active`)
VALUES
  (1, 1, '1', 'a3f9d2c1e4b7f0a8d5c2e9b6f3a0d7c4', NULL, 1),
  (2, 1, '2', 'b4e0c3d2f5a8e1b9c6d3f0a7e4b1c8d5', NULL, 1),
  (3, 1, '3', 'c5f1d4e3a6b9f2c0d7e4a1b8f5c2d9e6', NULL, 1),
  (4, 1, '4', 'QR-FE27B81C772507AF', 'Próximo à porta', 1);

-- ============================================================
-- TABELA: categories
-- ============================================================
CREATE TABLE `categories` (
  `id`            int UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` int UNSIGNED NOT NULL,
  `name`          varchar(191)  NOT NULL,
  `slug`          varchar(191)  NOT NULL,
  `position`      int           DEFAULT '0',
  `active`        tinyint(1)    DEFAULT '1',
  `created_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `restaurant_slug` (`restaurant_id`, `slug`),
  KEY `idx_category_slug` (`slug`),
  CONSTRAINT `categories_ibfk_1`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `categories`
  (`id`, `restaurant_id`, `name`, `slug`, `position`, `active`)
VALUES
  (1, 1, 'Saladas',      'saladas',      0, 1),
  (2, 1, 'Hambúrgueres', 'hamburgueres', 0, 1),
  (3, 1, 'Bebidas',      'bebidas',      0, 1),
  (4, 1, 'Pateis',       'pateis',       3, 1),
  (5, 1, 'Veganos',      'veganos',      5, 1),
  (6, 1, 'Lanches',      'lanches',      1, 1),
  (7, 1, 'Pratos',       'pratos',       3, 1),
  (8, 1, 'Sobremesas',   'sobremesas',   4, 1);

-- ============================================================
-- TABELA: menu_items
-- Melhorias:
--   - slug NOT NULL
--   - UNIQUE KEY composta (restaurant_id, slug)
-- ============================================================
CREATE TABLE `menu_items` (
  `id`               int UNSIGNED  NOT NULL AUTO_INCREMENT,
  `restaurant_id`    int UNSIGNED  NOT NULL,
  `category_id`      int UNSIGNED  DEFAULT NULL,
  `name`             varchar(191)  NOT NULL,
  `slug`             varchar(191)  NOT NULL,              -- ← NOT NULL
  `description`      text,
  `price`            decimal(10,2) NOT NULL DEFAULT '0.00',
  `available`        tinyint(1)    DEFAULT '1',
  `featured`         tinyint(1)    DEFAULT '0',           -- ← tinyint em vez de int
  `image`            varchar(255)  DEFAULT NULL,
  `cook_time_minutes` int          DEFAULT NULL,
  `created_at`       timestamp     NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       timestamp     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_restaurant_slug` (`restaurant_id`, `slug`),  -- ← unique por restaurante
  KEY `category_id` (`category_id`),
  KEY `idx_restaurant` (`restaurant_id`),
  CONSTRAINT `menu_items_ibfk_1`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_ibfk_2`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `menu_items`
  (`id`, `restaurant_id`, `category_id`, `name`, `slug`, `description`, `price`, `available`, `image`, `cook_time_minutes`, `featured`)
VALUES
  (1,  1, 1, 'Salada Fresca',        'salada-fresca',        'Alface, tomate, cebola roxa e molho caseiro',                    2500.00, 1, 'images/salada.webp',              15, 0),
  (2,  1, 2, 'Hambúrguer Suculento', 'hamburguer-suculento', 'Hambúrguer com queijo e molho especial',                         3500.00, 1, 'images/hamburguer.webp',          20, 0),
  (3,  1, 1, 'Salada Grega',         'salada-grega',         'Salada com queijo feta, azeitonas e tomate',                     2800.00, 1, 'images/salada-grega.webp',        10, 0),
  (4,  1, 1, 'Salada Cesar',         'salada-cesar',         'Alface romana, croutons e molho cesar',                          3200.00, 1, 'images/salada-cesar.webp',        12, 0),
  (5,  1, 2, 'Hambúrguer Duplo',     'hamburguer-duplo',     'Dois hambúrgueres com queijo e bacon',                           3500.00, 1, 'images/hamburguer-duplo.webp',    25, 0),
  (6,  1, 5, 'Hambúrguer Vegetariano','hamburguer-vegetariano','Hambúrguer de grão de bico com legumes',                       3800.00, 1, 'images/hamburguer-veg.webp',      18, 0),
  (7,  1, 3, 'Suco Natural',         'suco-natural',         'Suco fresco de frutas da estação',                               1200.00, 1, 'images/suco.webp',                 5, 0),
  (8,  1, 3, 'Refrigerante',         'refrigerante',         'Refrigerante gelado 330ml',                                       650.00, 1, 'images/refrigerante.webp',         2, 0),
  (9,  1, 3, 'Água Mineral',         'agua-mineral',         'Água mineral 500ml',                                              350.00, 1, 'images/agua.webp',                 1, 0),
  (10, 1, 4, 'Ressois',              'ressois',              'Feito com trigo sem glúten, recheio de carne de cordeiro com malagueta, cenoura e queijo', 4000.00, 1, 'images/crispy-baked-meat-potatoes.webp', 23, 0),
  (11, 1, 6, 'Hambúrguer Clássico',  'hamburguer-classico',  'Pão, carne, queijo e molho especial',                            3500.00, 1, 'images/hamburguer-classico.webp', 15, 0),
  (12, 1, 6, 'Sanduíche Natural',    'sanduiche-natural',    'Peito de frango, alface, tomate e maionese',                     2200.00, 1, 'images/sanduiche-natural.webp',    8, 0),
  (13, 1, 7, 'Frango Grelhado',      'frango-grelhado',      'Frango marinado, grelhado com legumes',                          4800.00, 1, 'images/frango-grelhado.webp',     20, 0),
  (14, 1, 7, 'Feijoada',             'feijoada',             'Feijoada completa com arroz e farofa',                           5500.00, 1, 'images/feijoada.webp',            30, 0),
  (15, 1, 3, 'Suco de Maracujá',     'suco-de-maracuja',     'Suco natural de maracujá 300ml',                                 8000.00, 1, 'images/suco-maracuja.webp',     NULL, 0),
  (16, 1, 3, 'Refrigerante Lata',    'refrigerante-lata',    'Refrigerante gelado 350ml',                                      6000.00, 1, 'images/refrigerante.webp',      NULL, 0),
  (17, 1, 8, 'Bolo de Chocolate',    'bolo-de-chocolate',    'Fatia de bolo com cobertura de chocolate',                      18000.00, 1, 'images/bolo-chocolate.webp',    NULL, 0),
  (18, 1, 8, 'Mousse de Maracujá',   'mousse-de-maracuja',   'Mousse suave com calda de maracujá',                             6000.00, 1, 'images/mousse-maracuja.webp',   NULL, 0),
  (19, 1, 1, 'Salada Saudável',      'salada-saudavel',      'Salada nutricionalmente completa com vitaminas, minerais e fitonutrientes.', 7000.00, 1, 'images/autumn-salad.webp', 15, 0);

-- ============================================================
-- TABELA: ingredients
-- Melhorias: removido item_id (relação gerida exclusivamente por item_ingredients)
-- ============================================================
CREATE TABLE `ingredients` (
  `id`           int UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`         varchar(191)  NOT NULL,
  `allergen_flag` tinyint(1)   DEFAULT '0',
  `created_at`   timestamp     NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `ingredients` (`id`, `name`, `allergen_flag`) VALUES
  (1,  'Alface',      0),
  (2,  'Tomate',      0),
  (3,  'Cebola roxa', 0),
  (4,  'Molho caseiro',0),
  (5,  'Pão',         0),
  (6,  'Carne',       0),
  (7,  'Queijo',      1),
  (8,  'Gelo',        0),
  (17, 'Cebola',      0),
  (18, 'Malagueta',   0),
  (19, 'Cenoura',     0),
  (20, 'Pimenta',     0);

-- ============================================================
-- TABELA: item_ingredients
-- Única fonte de verdade para a relação prato ↔ ingrediente
-- ============================================================
CREATE TABLE `item_ingredients` (
  `item_id`       int UNSIGNED NOT NULL,
  `ingredient_id` int UNSIGNED NOT NULL,
  `amount`        varchar(64)  DEFAULT NULL,
  PRIMARY KEY (`item_id`, `ingredient_id`),
  KEY `ingredient_id` (`ingredient_id`),
  CONSTRAINT `item_ingredients_ibfk_1`
    FOREIGN KEY (`item_id`)       REFERENCES `menu_items`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `item_ingredients_ibfk_2`
    FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `item_ingredients` (`item_id`, `ingredient_id`, `amount`) VALUES
  (1, 1, NULL),
  (1, 2, NULL),
  (1, 3, NULL),
  (1, 4, NULL),
  (2, 5, NULL),
  (2, 6, NULL),
  (2, 7, NULL),
  (10, 17, NULL),
  (10, 18, NULL),
  (10, 19, NULL),
  (10, 20, NULL);

-- ============================================================
-- TABELA: orders
-- Melhorias: adicionado closed_at
-- ============================================================
CREATE TABLE `orders` (
  `id`            bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` int UNSIGNED    NOT NULL,
  `table_id`      int UNSIGNED    DEFAULT NULL,
  `session_token` varchar(128)    DEFAULT NULL,
  `status`        enum('open','submitted','preparing','served','paid','cancelled') DEFAULT 'open',
  `total`         decimal(10,2)   DEFAULT '0.00',
  `notes`         text,
  `closed_at`     timestamp       NULL DEFAULT NULL,   -- ← preenchido quando status = paid/cancelled
  `created_at`    timestamp       NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    timestamp       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `table_id` (`table_id`),
  KEY `session_token` (`session_token`),
  KEY `idx_order_status` (`restaurant_id`, `status`),  -- ← índice para filtros no painel
  CONSTRAINT `orders_ibfk_1`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`       (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2`
    FOREIGN KEY (`table_id`)      REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `orders`
  (`id`, `restaurant_id`, `table_id`, `session_token`, `status`, `total`, `closed_at`, `created_at`, `updated_at`)
VALUES
  (1, 1, 1, '3f25c9dea12f7c6c88aed03e386b921f', 'cancelled', 7000.00, '2026-01-12 21:00:05', '2026-01-12 19:22:57', '2026-01-12 21:00:05'),
  (2, 1, 1, '3ce3d0cc88a3ebc18adaf6c072c612f8', 'served',    97.00,   NULL,                  '2026-01-12 19:38:48', '2026-01-12 21:05:39'),
  (3, 1, 1, 'c7a96691dcabf6b15d009e6c8483f298', 'submitted', 25.00,   NULL,                  '2026-05-23 22:54:16', '2026-05-23 22:54:16');

-- ============================================================
-- TABELA: order_items
-- Melhorias: removido total_price (redundante; calcular na app como qty × unit_price)
-- ============================================================
CREATE TABLE `order_items` (
  `id`         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   bigint UNSIGNED NOT NULL,
  `item_id`    int UNSIGNED    NOT NULL,
  `qty`        int UNSIGNED    NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2)   NOT NULL DEFAULT '0.00',   -- preço snapshot no momento do pedido
  `notes`      varchar(255)    DEFAULT NULL,
  `created_at` timestamp       NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id`  (`item_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1`
    FOREIGN KEY (`order_id`) REFERENCES `orders`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2`
    FOREIGN KEY (`item_id`)  REFERENCES `menu_items`  (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `order_items`
  (`id`, `order_id`, `item_id`, `qty`, `unit_price`, `notes`)
VALUES
  (1, 1, 19, 1, 7000.00, NULL),
  (2, 2,  7, 1,   12.00, NULL),
  (3, 2,  4, 1,   32.00, NULL),
  (4, 2,  3, 1,   28.00, NULL),
  (5, 2,  1, 1,   25.00, NULL),
  (6, 3,  1, 1,   25.00, NULL);

-- ============================================================
-- TABELA: ratings
-- Melhorias:
--   - ENGINE=InnoDB explícito
--   - order_id nullable (valida que veio de quem consumiu)
--   - CONSTRAINT de rating entre 1 e 5
-- ============================================================
CREATE TABLE `ratings` (
  `id`         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`    int UNSIGNED    NOT NULL,
  `order_id`   bigint UNSIGNED DEFAULT NULL,   -- ← vínculo opcional ao pedido
  `rating`     tinyint         NOT NULL,
  `comment`    text,
  `created_at` timestamp       NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id`  (`item_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `ratings_chk_rating` CHECK (`rating` BETWEEN 1 AND 5),
  CONSTRAINT `ratings_ibfk_1`
    FOREIGN KEY (`item_id`)  REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_2`
    FOREIGN KEY (`order_id`) REFERENCES `orders`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `ratings` (`id`, `item_id`, `order_id`, `rating`, `comment`) VALUES
  (1, 2, NULL, 5, ''),
  (2, 2, NULL, 5, ''),
  (3, 2, NULL, 2, ''),
  (4, 9, NULL, 2, ''),
  (5, 10, NULL, 5, ''),
  (6, 5, NULL, 4, ''),
  (7, 6, NULL, 5, '');

-- ============================================================
-- TABELA: item_rating_aggregates
-- (sem alterações estruturais)
-- ============================================================
CREATE TABLE `item_rating_aggregates` (
  `item_id`     int UNSIGNED  NOT NULL,
  `avg_rating`  decimal(3,2)  DEFAULT '0.00',
  `total_count` int UNSIGNED  DEFAULT '0',
  `counts`      json          DEFAULT NULL,
  `updated_at`  timestamp     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  CONSTRAINT `item_rating_aggregates_ibfk_1`
    FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `item_rating_aggregates` (`item_id`, `avg_rating`, `total_count`, `counts`) VALUES
  (1,  4.50, 120, '{"1": 2, "2": 3,  "3": 10, "4": 25, "5": 80}'),
  (2,  4.00,   3, '{"2": 1, "5": 2}'),
  (3,  4.60,  95, '{"1": 2, "2": 3,  "3":  5, "4": 20, "5": 65}'),
  (4,  4.70,  75, '{"1": 1, "2": 2,  "3":  4, "4": 18, "5": 50}'),
  (5,  4.00,   1, '{"4": 1}'),
  (6,  5.00,   1, '{"5": 1}'),
  (7,  4.10,  30, '{"1": 1, "2": 1,  "3":  3, "4": 10, "5": 15}'),
  (8,  3.90,  25, '{"1": 1, "2": 2,  "3":  4, "4":  8, "5": 10}'),
  (9,  2.00,   1, '{"2": 1}'),
  (10, 5.00,   1, '{"5": 1}');

-- ============================================================
-- TABELA: visits
-- Melhorias: user_agent substituído por device_type enum (privacidade)
-- ============================================================
CREATE TABLE `visits` (
  `id`            bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` int UNSIGNED    NOT NULL,
  `table_id`      int UNSIGNED    DEFAULT NULL,
  `session_token` varchar(128)    DEFAULT NULL,
  `ip`            varchar(45)     DEFAULT NULL,
  `device_type`   enum('mobile','desktop','tablet','unknown') DEFAULT 'unknown',  -- ← em vez de user_agent completo
  `created_at`    timestamp       NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `table_id`      (`table_id`),
  KEY `session_token` (`session_token`),
  CONSTRAINT `visits_ibfk_1`
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`       (`id`) ON DELETE CASCADE,
  CONSTRAINT `visits_ibfk_2`
    FOREIGN KEY (`table_id`)      REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `visits`
  (`id`, `restaurant_id`, `table_id`, `session_token`, `ip`, `device_type`)
VALUES
  (1, 1, 1, '53e595185498eae8fea7fb690530df91', '127.0.0.1', 'desktop'),
  (2, 1, 3, '26d8050f52624bafef680dbdae0dff3d', '127.0.0.1', 'desktop');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
