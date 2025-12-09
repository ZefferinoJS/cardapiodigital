<?php
// Configuração da base de dados.
require_once __DIR__ . '/env.php';
load_env(__DIR__ . '/../.env');

// As credenciais vêm de variáveis de ambiente (definidas no servidor, no .env
// carregado acima, ou no docker-compose). Os valores depois de "?:"
// são apenas um fallback para desenvolvimento local — NÃO use isto em produção
// e NUNCA volte a commitar credenciais reais no código.
return [
    'dsn' => sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_NAME') ?: 'cardapio'
    ),
    'user' => getenv('DB_USER') ?: 'adminphp',
    'pass' => getenv('DB_PASS') ?: 'SenhaForte123!',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
