<?php
// Cria a ligação PDO ($pdo) já pronta para uso, reaproveitando a configuração
// central de db.php (evita ter as credenciais duplicadas em dois ficheiros).
$config = require __DIR__ . '/db.php';

try {
    $pdo = new PDO(
        $config['dsn'],
        $config['user'],
        $config['pass'],
        $config['options']
    );
} catch (PDOException $e) {
    error_log(
        '[' . date('Y-m-d H:i:s') . '] ERRO BD: ' . $e->getMessage() . PHP_EOL,
        3,
        __DIR__ . '/logs/php_errors.log'
    );
    http_response_code(500);
    exit('Erro de ligação à base de dados.');
}
