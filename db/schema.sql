-- phpMyAdmin SQL Dump
-- version 5.2.2deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 28, 2026 at 02:50 PM
-- Server version: 8.4.8-0ubuntu0.25.10.1
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cardapio`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('manager','staff') DEFAULT 'staff',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `position` int DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `restaurant_id`, `name`, `slug`, `position`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Saladas', 'saladas', 0, 1, '2025-12-05 19:38:07', '2025-12-05 19:38:07'),
(2, 1, 'Hambúrgueres', 'hamburgueres', 0, 1, '2025-12-05 19:38:07', '2025-12-05 19:38:07'),
(3, 1, 'Bebidas', 'bebidas', 0, 1, '2025-12-05 19:38:07', '2025-12-05 19:38:07'),
(4, 1, 'pateis', 'Pateis', 3, 1, '2025-12-20 19:52:46', '2026-05-25 09:03:18'),
(5, 1, 'Veganos', 'Veganos', 5, 1, '2025-12-20 22:40:16', '2026-05-25 09:02:44'),
(6, 1, 'Lanches', 'lanches', 1, 1, '2025-12-20 23:11:15', '2025-12-20 23:11:15'),
(7, 1, 'Pratos', 'pratos', 3, 1, '2025-12-20 23:11:15', '2025-12-20 23:11:15'),
(8, 1, 'Sobremesas', 'sobremesas', 4, 1, '2025-12-20 23:11:15', '2025-12-20 23:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `allergen_flag` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `item_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `name`, `allergen_flag`, `created_at`, `item_id`) VALUES
(1, 'Alface', 0, '2025-12-05 19:38:08', 0),
(2, 'Tomate', 0, '2025-12-05 19:38:08', 0),
(3, 'Cebola roxa', 0, '2025-12-05 19:38:08', 0),
(4, 'Molho caseiro', 0, '2025-12-05 19:38:08', 0),
(5, 'Pão', 0, '2025-12-05 19:38:08', 0),
(6, 'Carne', 0, '2025-12-05 19:38:08', 0),
(7, 'Queijo', 1, '2025-12-05 19:38:08', 0),
(8, 'Gelo', 0, '2025-12-05 19:38:08', 0),
(17, 'Cebola', 0, '2026-05-24 12:04:28', 10),
(18, 'Malagueta', 0, '2026-05-24 12:04:29', 10),
(19, 'Cenoura', 0, '2026-05-24 12:04:29', 10),
(20, 'Pimenta', 0, '2026-05-24 12:04:29', 10);

-- --------------------------------------------------------

--
-- Table structure for table `item_ingredients`
--

CREATE TABLE `item_ingredients` (
  `item_id` int UNSIGNED NOT NULL,
  `ingredient_id` int UNSIGNED NOT NULL,
  `amount` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `item_ingredients`
--

INSERT INTO `item_ingredients` (`item_id`, `ingredient_id`, `amount`) VALUES
(1, 1, NULL),
(1, 2, NULL),
(1, 3, NULL),
(1, 4, NULL),
(2, 5, NULL),
(2, 6, NULL),
(2, 7, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `item_rating_aggregates`
--

CREATE TABLE `item_rating_aggregates` (
  `item_id` int UNSIGNED NOT NULL,
  `avg_rating` decimal(3,2) DEFAULT '0.00',
  `total_count` int UNSIGNED DEFAULT '0',
  `counts` json DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `item_rating_aggregates`
--

INSERT INTO `item_rating_aggregates` (`item_id`, `avg_rating`, `total_count`, `counts`, `updated_at`) VALUES
(1, 4.50, 120, '{\"1\": 2, \"2\": 3, \"3\": 10, \"4\": 25, \"5\": 80}', '2025-12-05 19:38:08'),
(2, 4.00, 3, '{\"2\": 1, \"5\": 2}', '2025-12-20 23:28:55'),
(3, 4.60, 95, '{\"1\": 2, \"2\": 3, \"3\": 5, \"4\": 20, \"5\": 65}', '2025-12-05 19:38:08'),
(4, 4.70, 75, '{\"1\": 1, \"2\": 2, \"3\": 4, \"4\": 18, \"5\": 50}', '2025-12-05 19:38:08'),
(5, 4.00, 1, '{\"4\": 1}', '2025-12-31 14:21:38'),
(6, 5.00, 1, '{\"5\": 1}', '2025-12-31 14:21:55'),
(7, 4.10, 30, '{\"1\": 1, \"2\": 1, \"3\": 3, \"4\": 10, \"5\": 15}', '2025-12-05 19:38:08'),
(8, 3.90, 25, '{\"1\": 1, \"2\": 2, \"3\": 4, \"4\": 8, \"5\": 10}', '2025-12-05 19:38:08'),
(9, 2.00, 1, '{\"2\": 1}', '2025-12-20 23:29:17'),
(10, 5.00, 1, '{\"5\": 1}', '2025-12-31 14:13:53');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `available` tinyint(1) DEFAULT '1',
  `image` varchar(255) DEFAULT NULL,
  `cook_time_minutes` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `featured` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `restaurant_id`, `category_id`, `name`, `slug`, `description`, `price`, `available`, `image`, `cook_time_minutes`, `created_at`, `updated_at`, `featured`) VALUES
(1, 1, 1, 'Salada Fresca', '', 'Alface, tomate, cebola roxa e molho caseiro', 2500.00, 1, 'images/salada.webp', 15, '2025-12-05 19:38:07', '2026-05-26 23:42:49', 0),
(2, 1, 2, 'Hambúrguer Suculento', '', 'Hambúrguer com queijo e molho especial', 3500.00, 1, 'images/hamburguer.webp', 20, '2025-12-05 19:38:07', '2026-05-26 23:42:35', 0),
(3, 1, 1, 'Salada Grega', '', 'Salada com queijo feta, azeitonas e tomate', 2800.00, 1, 'images/salada-grega.webp', 10, '2025-12-05 19:38:07', '2026-05-26 23:42:18', 0),
(4, 1, 1, 'Salada Cesar', '', 'Alface romana, croutons e molho cesar', 3200.00, 1, 'images/salada-cesar.webp', 12, '2025-12-05 19:38:07', '2026-05-26 23:42:04', 0),
(5, 1, 2, 'Hambúrguer Duplo', '', 'Dois hambúrgueres com queijo e bacon', 3500.00, 1, 'images/hamburguer-duplo.webp', 25, '2025-12-05 19:38:07', '2026-05-26 23:41:06', 0),
(6, 1, 5, 'Hambúrguer Vegetariano', '', 'Hambúrguer de grão de bico com legumes', 3800.00, 1, 'images/hamburguer-veg.webp', 18, '2025-12-05 19:38:07', '2026-05-26 23:40:49', 0),
(7, 1, 3, 'Suco Natural', '', 'Suco fresco de frutas da estação', 1200.00, 1, 'images/suco.webp', 5, '2025-12-05 19:38:07', '2026-05-26 23:40:33', 0),
(8, 1, 3, 'Refrigerante', '', 'Refrigerante gelado 330ml', 650.00, 1, 'images/refrigerante.webp', 2, '2025-12-05 19:38:07', '2026-05-26 23:40:01', 0),
(9, 1, 3, 'Água Mineral', '', 'Água mineral 500ml', 350.00, 1, 'images/agua.webp', 1, '2025-12-05 19:38:08', '2026-05-26 23:39:40', 0),
(10, 1, 4, 'Ressois', '', 'Feito com trigo sem glutem e com recheio de carne de cordeiro com malagueta, cenoura e queijo', 4000.00, 1, 'images/crispy-baked-meat-potatoes.webp', 23, '2025-12-20 20:41:45', '2026-05-24 12:03:37', 0),
(11, 1, 6, 'Hambúrguer Clássico', '', 'Pão, carne, queijo e molho especial', 3500.00, 1, 'images/hamburguer-classico.webp', 15, '2025-12-20 23:11:15', '2026-05-26 23:39:19', 0),
(12, 1, 6, 'Sanduíche Natural', '', 'Peito de frango, alface, tomate e maionese', 2200.00, 1, 'images/sanduiche-natural.webp', 8, '2025-12-20 23:11:15', '2026-05-26 23:39:06', 0),
(13, 1, 7, 'Frango Grelhado', '', 'Frango marinado, grelhado com legumes', 4800.00, 1, 'images/frango-grelhado.webp', 20, '2025-12-20 23:11:15', '2026-05-26 23:38:51', 0),
(14, 1, 7, 'Feijoada', '', 'Feijoada completa com arroz e farofa', 5500.00, 1, 'images/feijoada.webp', 30, '2025-12-20 23:11:15', '2026-05-26 23:38:39', 0),
(15, 1, 3, 'Suco de Maracujá', '', 'Suco natural de maracujá 300ml', 8000.00, 1, 'images/suco-maracuja.webp', NULL, '2025-12-20 23:11:15', '2026-05-26 23:38:25', 0),
(16, 1, 3, 'Refrigerante Lata', '', 'Refrigerante gelado 350ml', 6000.00, 1, 'images/refrigerante.webp', NULL, '2025-12-20 23:11:15', '2026-05-26 23:38:12', 0),
(17, 1, 8, 'Bolo de Chocolate', '', 'Fatia de bolo com cobertura de chocolate', 18000.00, 1, 'images/bolo-chocolate.webp', NULL, '2025-12-20 23:11:15', '2026-05-26 23:38:00', 0),
(18, 1, 8, 'Mousse de Maracujá', '', 'Mousse suave com calda de maracujá', 6000.00, 1, 'images/mousse-maracuja.webp', NULL, '2025-12-20 23:11:15', '2026-05-26 23:37:33', 0),
(19, 1, 1, 'Salada saudável', '-alada-saud-vel', 'Salada é uma comida nutricionalmente completa que contém todas as 27 vitaminas essenciais e minerais, proteina, gorduras ácidas essenciais, carboidratos, fibras e fitonutrientes.', 7000.00, 1, 'images/autumn-salad.webp', 15, '2026-01-08 22:53:26', '2026-01-08 22:53:26', 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `table_id` int UNSIGNED DEFAULT NULL,
  `session_token` varchar(128) DEFAULT NULL,
  `status` enum('open','submitted','preparing','served','paid','cancelled') DEFAULT 'open',
  `total` decimal(10,2) DEFAULT '0.00',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `restaurant_id`, `table_id`, `session_token`, `status`, `total`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '3f25c9dea12f7c6c88aed03e386b921f', 'cancelled', 7000.00, NULL, '2026-01-12 19:22:57', '2026-01-12 21:00:05'),
(2, 1, 1, '3ce3d0cc88a3ebc18adaf6c072c612f8', 'served', 97.00, NULL, '2026-01-12 19:38:48', '2026-01-12 21:05:39'),
(3, 1, 1, 'c7a96691dcabf6b15d009e6c8483f298', 'submitted', 25.00, NULL, '2026-05-23 22:54:16', '2026-05-23 22:54:16');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `qty` int UNSIGNED NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `item_id`, `qty`, `unit_price`, `total_price`, `notes`, `created_at`) VALUES
(1, 1, 19, 1, 7000.00, 7000.00, NULL, '2026-01-12 19:22:57'),
(2, 2, 7, 1, 12.00, 12.00, NULL, '2026-01-12 19:38:48'),
(3, 2, 4, 1, 32.00, 32.00, NULL, '2026-01-12 19:38:48'),
(4, 2, 3, 1, 28.00, 28.00, NULL, '2026-01-12 19:38:48'),
(5, 2, 1, 1, 25.00, 25.00, NULL, '2026-01-12 19:38:48'),
(6, 3, 1, 1, 25.00, 25.00, NULL, '2026-05-23 22:54:16');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` bigint UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `item_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 5, '', '2025-12-20 23:28:44'),
(2, 2, 5, '', '2025-12-20 23:28:50'),
(3, 2, 2, '', '2025-12-20 23:28:54'),
(4, 9, 2, '', '2025-12-20 23:29:17'),
(5, 10, 5, '', '2025-12-31 14:13:53'),
(6, 5, 4, '', '2025-12-31 14:21:38'),
(7, 6, 5, '', '2025-12-31 14:21:55');

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT 'UTC',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `slug`, `timezone`, `created_at`, `updated_at`) VALUES
(1, 'Minha Lanchonete', 'minha-lanchonete', 'UTC', '2025-12-05 19:38:07', '2025-12-05 19:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_tables`
--

CREATE TABLE `restaurant_tables` (
  `id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `number` varchar(32) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `restaurant_id`, `number`, `qr_code`, `description`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, '1', 'QR-TOKEN-1', NULL, 1, '2025-12-05 19:38:07', '2025-12-05 19:38:07'),
(2, 1, '2', 'QR-TOKEN-2', NULL, 1, '2025-12-05 19:38:07', '2025-12-05 19:38:07'),
(3, 1, '3', 'QR-TOKEN-3', NULL, 1, '2025-12-05 19:38:07', '2025-12-05 19:38:07'),
(4, 1, '4', 'QR-FE27B81C772507AF', 'Próximo à porta', 1, '2025-09-25 17:26:23', '2025-09-25 17:26:23');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `table_id` int UNSIGNED DEFAULT NULL,
  `session_token` varchar(128) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `restaurant_id`, `table_id`, `session_token`, `ip`, `user_agent`, `created_at`) VALUES
(1, 1, 1, '53e595185498eae8fea7fb690530df91', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 17:02:44'),
(2, 1, 3, '26d8050f52624bafef680dbdae0dff3d', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 19:22:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `restaurant_slug` (`restaurant_id`,`slug`),
  ADD KEY `idx_category_slug` (`slug`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_name` (`name`);

--
-- Indexes for table `item_ingredients`
--
ALTER TABLE `item_ingredients`
  ADD PRIMARY KEY (`item_id`,`ingredient_id`),
  ADD KEY `ingredient_id` (`ingredient_id`);

--
-- Indexes for table `item_rating_aggregates`
--
ALTER TABLE `item_rating_aggregates`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_restaurant` (`restaurant_id`),
  ADD KEY `idx_menu_item_slug` (`slug`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `session_token` (`session_token`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `session_token` (`session_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `item_ingredients`
--
ALTER TABLE `item_ingredients`
  ADD CONSTRAINT `item_ingredients_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_ingredients_ibfk_2` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `item_rating_aggregates`
--
ALTER TABLE `item_rating_aggregates`
  ADD CONSTRAINT `item_rating_aggregates_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD CONSTRAINT `restaurant_tables_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
