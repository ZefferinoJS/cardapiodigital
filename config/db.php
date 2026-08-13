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
    'pass' => getenv('DB_PASS') ?: '',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Sem isto, PDOStatement::rowCount() após um UPDATE só conta linhas
        // cujo valor realmente mudou — não linhas encontradas pelo WHERE.
        // O código usa rowCount()===0 para decidir "registo não encontrado"
        // (ex: ao editar prato/mesa/categoria/pedido); sem esta opção, guardar
        // sem alterar nenhum valor fazia essas rotas responderem 404 mesmo
        // com o registo a existir. MYSQL_ATTR_FOUND_ROWS faz rowCount()
        // reportar linhas encontradas, como as outras queries já assumem.
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
    ],
];
