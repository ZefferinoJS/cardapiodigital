<?php
// Database configuration for development
return [
    'dsn' => 'mysql:host=localhost;dbname=cardapio;charset=utf8mb4',
    'user' => 'adminphp',
    'pass' => 'SenhaForte123!',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
