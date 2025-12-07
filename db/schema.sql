-- Schema SQL para sistema de Cardápio Digital (MySQL / MariaDB)
-- Local: /db/schema.sql
-- Use: mysql -u user -p database < db/schema.sql

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if exist (safe re-run)
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS item_ingredients;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS item_rating_aggregates;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS restaurants;

SET FOREIGN_KEY_CHECKS = 1;

-- Restaurants (multi-tenant ready)
CREATE TABLE restaurants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  timezone VARCHAR(64) DEFAULT 'UTC',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Physical tables where QR codes are placed
CREATE TABLE restaurant_tables (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  number VARCHAR(32) NOT NULL, -- mesa 1, A1, etc
  qr_code VARCHAR(255) DEFAULT NULL, -- optional; could store path or token
  description VARCHAR(255) DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin users (staff)
CREATE TABLE admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('manager','staff') DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories for menu items
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(191) NOT NULL,
  position INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  UNIQUE KEY restaurant_slug (restaurant_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu items (pratos)
CREATE TABLE menu_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(191) NOT NULL,
  slug VARCHAR(191) NOT NULL,
  description TEXT DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  available TINYINT(1) DEFAULT 1,
  image VARCHAR(255) DEFAULT NULL,
  cook_time_minutes INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_restaurant (restaurant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ingredients master list
CREATE TABLE ingredients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  allergen_flag TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Many-to-many: item -> ingredients (amount optional)
CREATE TABLE item_ingredients (
  item_id INT UNSIGNED NOT NULL,
  ingredient_id INT UNSIGNED NOT NULL,
  amount VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (item_id, ingredient_id),
  FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
  FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ratings (individual reviews) - customers can leave rating when ordering
CREATE TABLE ratings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
  INDEX (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aggregated rating cache (useful for fast reads)
CREATE TABLE item_rating_aggregates (
  item_id INT UNSIGNED PRIMARY KEY,
  avg_rating DECIMAL(3,2) DEFAULT 0.00,
  total_count INT UNSIGNED DEFAULT 0,
  counts JSON DEFAULT NULL, -- e.g. {"5": 80, "4": 25, "3": 10, "2": 3, "1": 2}
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Visits / Sessions: when customer scans QR we create a visit with table ref
CREATE TABLE visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  table_id INT UNSIGNED DEFAULT NULL,
  session_token VARCHAR(128) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL,
  INDEX (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders created from the table session (no auth required for guest)
CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  table_id INT UNSIGNED DEFAULT NULL,
  session_token VARCHAR(128) DEFAULT NULL,
  status ENUM('open','submitted','preparing','served','paid','cancelled') DEFAULT 'open',
  total DECIMAL(10,2) DEFAULT 0.00,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL,
  INDEX (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items
CREATE TABLE order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
  INDEX (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example seed data (restaurants, tables, categories, items, ingredients)
INSERT INTO restaurants (name, slug) VALUES ('Minha Lanchonete', 'minha-lanchonete');

INSERT INTO restaurant_tables (restaurant_id, number, qr_code) VALUES (1, '1', 'QR-TOKEN-1');
INSERT INTO restaurant_tables (restaurant_id, number, qr_code) VALUES (1, '2', 'QR-TOKEN-2');
INSERT INTO restaurant_tables (restaurant_id, number, qr_code) VALUES (1, '3', 'QR-TOKEN-3');

INSERT INTO categories (restaurant_id, name, slug) VALUES (1, 'Saladas', 'saladas');
INSERT INTO categories (restaurant_id, name, slug) VALUES (1, 'Hambúrgueres', 'hamburgueres');
INSERT INTO categories (restaurant_id, name, slug) VALUES (1, 'Bebidas', 'bebidas');

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 1, 'Salada Fresca', 'salada-fresca', 'Alface, tomate, cebola roxa e molho caseiro', 25.00, 'images/salada.jpg', 15);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 2, 'Hambúrguer Suculento', 'hamburguer-suculento', 'Hambúrguer com queijo e molho especial', 45.00, 'images/hamburguer.jpg', 20);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 1, 'Salada Grega', 'salada-grega', 'Salada com queijo feta, azeitonas e tomate', 28.00, 'images/salada-grega.jpg', 10);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 1, 'Salada Cesar', 'salada-cesar', 'Alface romana, croutons e molho cesar', 32.00, 'images/salada-cesar.jpg', 12);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 2, 'Hambúrguer Duplo', 'hamburguer-duplo', 'Dois hambúrgueres com queijo e bacon', 55.00, 'images/hamburguer-duplo.jpg', 25);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 2, 'Hambúrguer Vegetariano', 'hamburguer-vegetariano', 'Hambúrguer de grão de bico com legumes', 38.00, 'images/hamburguer-veg.jpg', 18);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 3, 'Suco Natural', 'suco-natural', 'Suco fresco de frutas da estação', 12.00, 'images/suco.jpg', 5);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 3, 'Refrigerante', 'refrigerante', 'Refrigerante gelado 330ml', 6.00, 'images/refrigerante.jpg', 2);

INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, cook_time_minutes)
VALUES (1, 3, 'Água Mineral', 'agua-mineral', 'Água mineral 500ml', 3.50, 'images/agua.jpg', 1);

INSERT INTO ingredients (name, allergen_flag) VALUES ('Alface', 0), ('Tomate', 0), ('Cebola roxa', 0), ('Molho caseiro', 0), ('Pão', 0), ('Carne', 0), ('Queijo', 1), ('Gelo', 0);

INSERT INTO item_ingredients (item_id, ingredient_id, amount) VALUES (1, 1, NULL), (1,2,NULL), (1,3,NULL), (1,4,NULL), (2,5,NULL), (2,6,NULL), (2,7,NULL);

-- Example precomputed rating aggregates for all items
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (1, 4.5, 120, '{"5":80,"4":25,"3":10,"2":3,"1":2}');
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (2, 4.8, 210, '{"5":160,"4":30,"3":12,"2":6,"1":2}');
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (3, 4.6, 95, '{"5":65,"4":20,"3":5,"2":3,"1":2}');
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (4, 4.7, 75, '{"5":50,"4":18,"3":4,"2":2,"1":1}');
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (5, 4.3, 60, '{"5":30,"4":20,"3":7,"2":2,"1":1}');
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (6, 4.4, 45, '{"5":25,"4":15,"3":3,"2":1,"1":1}');
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (7, 4.1, 30, '{"5":15,"4":10,"3":3,"2":1,"1":1}');
INSERT INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (8, 3.9, 25, '{"5":10,"4":8,"3":4,"2":2,"1":1}');

-- Useful indexes
CREATE INDEX idx_menu_item_slug ON menu_items(slug);
CREATE INDEX idx_category_slug ON categories(slug);

-- End of schema

