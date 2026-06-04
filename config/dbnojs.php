<?php
$host   = "localhost";
$dbname = "cardapio";
$user   = "adminphp";
$pass   = "SenhaForte123!";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] ERRO BD: ' . $e->getMessage() . PHP_EOL,
              3, __DIR__ . '/logs/php_errors.log');
    http_response_code(500);
    exit('Erro de ligação à base de dados.');
}