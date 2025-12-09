<?php
/**
 * Loader mínimo de variáveis de ambiente a partir de um ficheiro .env,
 * sem depender de nenhuma biblioteca externa (não há acesso à internet
 * neste servidor para correr "composer install").
 *
 * Variáveis já definidas no ambiente real (Apache SetEnv, docker-compose,
 * painel de hospedagem, etc.) têm sempre prioridade — o .env só preenche
 * o que ainda não estiver definido. Isso permite usar o mesmo código em
 * desenvolvimento (com .env) e em produção (com variáveis reais do servidor).
 */
function load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        // Remove aspas simples/duplas envolventes, se existirem.
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Não sobrepor variáveis já definidas no ambiente real do servidor.
        if (getenv($name) !== false) {
            continue;
        }

        putenv("{$name}={$value}");
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
    }
}
