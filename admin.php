<?php

/**
 * admin.php — Controlador principal do painel administrativo
 *
 * Alterações em relação à versão anterior:
 *  1. Autenticação alinhada com admin_users (role) em vez de usuarios/perfis
 *  2. Função status_badge duplicada removida
 *  3. Queries de order_items corrigidas: total_price → qty * unit_price
 *  4. RESTAURANT_ID centralizado como constante
 *  5. Código de dashboard e relatório separados em blocos claros
 *  6. $titulo/$subtitulo/$pagina_atual removidos do meio do ficheiro
 *  7. Ícone do menu "relatorio" corrigido
 *  8. app_profile_from_db() definida localmente
 *  9. Prepared statements em todas as queries com parâmetros externos
 * 10. Helper fetch_one() com fallback caso não esteja em dbnojs.php
 */

// ── Dependências ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/dbnojs.php';

session_start();

// ── Constante de restaurante ──────────────────────────────────────────────────
// Altere aqui (ou mova para config) se o painel servir vários restaurantes.
define('RESTAURANT_ID', 1);
$nomesistema = "O cardápio";

// ── Helpers globais ───────────────────────────────────────────────────────────
$escape = static fn(mixed $v): string =>
htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

function log_error(string $message): void
{
    error_log(
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        3,
        __DIR__ . '/logs/php_errors.log'
    );
}

function redirecionarPara(string $routa): never
{
    header("Location: admin.php?routa={$routa}");
    exit;
}

/**
 * Garante compatibilidade caso fetch_one não esteja definida em dbnojs.php.
 */
if (!function_exists('fetch_one')) {
    function fetch_one(PDO $pdo, string $sql, array $params = []): array|false
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

/**
 * Mapeia o campo `role` de admin_users para o perfil interno da aplicação.
 * Retorna null se o role não for reconhecido (conta bloqueada/inválida).
 */
function app_profile_from_db(string $role): ?string
{
    return match ($role) {
        'superadmin', 'manager' => 'admin',
        'staff'                 => 'staff',
        'kitchen'               => 'kitchen',
        default                 => null,
    };
}

/**
 * Gera um badge HTML para um status de pedido.
 */
function status_badge(string $status, array $labels, array $colors): string
{
    $label = htmlspecialchars($labels[$status] ?? $status, ENT_QUOTES, 'UTF-8');
    $color = preg_match('/^#[0-9A-Fa-f]{6}$/', $colors[$status] ?? '') ? $colors[$status] : '#888888';
    return "<span class=\"status-badge\" style=\"background:{$color}20;color:{$color};border:1px solid {$color}40\">{$label}</span>";
}

// ── Acções POST ───────────────────────────────────────────────────────────────
if (($_POST['action'] ?? null) === 'logout') {
    session_destroy();
    session_unset();
    redirecionarPara('home');
}

if (($_POST['action'] ?? null) === 'login') {
    $email    = trim((string) ($_POST['email']    ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = 'Email e senha são obrigatórios.';
        redirecionarPara('login');
    }

    try {
        // Usa admin_users (schema actual) em vez da tabela usuarios
        $user = fetch_one(
            $pdo,
            'SELECT id, name, email, password_hash, role, restaurant_id
             FROM admin_users
             WHERE email = :email
             LIMIT 1',
            ['email' => $email]
        );
    } catch (Throwable $e) {
        log_error('login fetch error: ' . $e->getMessage());
        $_SESSION['login_error'] = 'Erro ao autenticar.';
        redirecionarPara('login');
    }

    if (!$user) {
        log_error('login failed: user not found for ' . $email);
        $_SESSION['login_error'] = 'Email ou senha incorretos.';
        redirecionarPara('login');
    }

    // Verifica hash bcrypt/argon2 ou comparação directa (legado)
    $stored = trim((string) ($user['password_hash'] ?? ''));
    $ok     = false;
    if ($stored !== '' && (
        str_starts_with($stored, '$2y$') ||
        str_starts_with($stored, '$2a$') ||
        str_starts_with($stored, '$argon')
    )) {
        $ok = password_verify($password, $stored);
    } else {
        $ok = hash_equals($stored, $password);
    }

    $profile = app_profile_from_db((string) ($user['role'] ?? ''));

    if ($ok && $profile !== null) {
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'id'            => (int)    $user['id'],
            'email'         => (string) $user['email'],
            'name'          => (string) $user['name'],
            'profile'       => $profile,
            'restaurant_id' => (int)   ($user['restaurant_id'] ?? RESTAURANT_ID),
        ];

        // Nota: admin_users não tem último_login; adicione a coluna se necessário.
        log_error('login success: user_id=' . $user['id'] . ' profile=' . $profile . ' session=' . session_id());
        redirecionarPara('dashboard');
    }

    log_error('login failed: bad credentials for ' . $email);
    $_SESSION['login_error'] = 'Email ou senha incorretos.';
    redirecionarPara('login');
}

// ── Rota e autenticação ───────────────────────────────────────────────────────
$routa = (string) ($_GET['routa'] ?? 'home');
$auth  = $_SESSION['auth'] ?? null;

if (!$auth) {
    $loginError = (string) ($_SESSION['login_error'] ?? '');
    unset($_SESSION['login_error']);
    $pageTitle = "Login";
    require __DIR__ . '/templates/login.php';
    exit;
}

$profile = (string) $auth['profile'];

// ── Menus, títulos e subtítulos ───────────────────────────────────────────────
$menus = [
    'admin' => [
        ['routa' => 'dashboard',    'icon' => 'fa-solid fa-gauge',          'label' => 'Painel'],
        ['routa' => 'pratos',       'icon' => 'fa-solid fa-bell-concierge', 'label' => 'Pratos'],
        ['routa' => 'categorias',   'icon' => 'fas fa-list',                'label' => 'Categorias'],
        ['routa' => 'pedidos',      'icon' => 'fas fa-receipt',             'label' => 'Pedidos'],
        ['routa' => 'avaliacoes',   'icon' => 'fas fa-star',                'label' => 'Avaliações'],
        ['routa' => 'relatorio',    'icon' => 'fa-solid fa-chart-line',     'label' => 'Relatório'],   // ← ícone corrigido
        ['routa' => 'usuarios',     'icon' => 'fa-solid fa-users',          'label' => 'Utilizadores'],
        ['routa' => 'configuracoes', 'icon' => 'fa-solid fa-gear',           'label' => 'Configurações'],
        ['routa' => 'notificacoes', 'icon' => 'fa-solid fa-bell',           'label' => 'Notificações'],
    ],
    'staff' => [
        ['routa' => 'pedidos',    'icon' => 'fas fa-receipt',         'label' => 'Pedidos'],
        ['routa' => 'notificacoes', 'icon' => 'fa-solid fa-bell','label' => 'Notificações'],
    ],
    'kitchen' => [
        ['routa' => 'pedidos',    'icon' => 'fas fa-receipt',         'label' => 'Pedidos'],
    ],
];

$titles = [
    'dashboard'    => 'Painel Administrativo',
    'pratos'       => 'Gerenciar Pratos',
    'categorias'   => 'Categorias',
    'pedidos'      => 'Pedidos',
    'avaliacoes'   => 'Avaliações',
    'relatorio'    => 'Relatório Financeiro',
    'usuarios'     => 'Utilizadores',
    'perfil'       => 'Perfil',
    'configuracoes' => 'Configurações',
    'notificacoes' => 'Notificações',
];

$subtitles = [
    'dashboard'    => 'Visão geral das atividades executadas no restaurante',
    'pratos'       => 'Gerencie os pratos do restaurante, categorias e disponibilidade.',
    'categorias'   => 'Gerencie as categorias, os seus estados e detalhes.',
    'pedidos'      => 'Gerencie os pedidos, os seus estados e detalhes.',
    'avaliacoes'   => 'Gerencie as avaliações dos pratos do restaurante.',
    'relatorio'    => 'Visualize e analise as vendas e receitas do restaurante.',
    'usuarios'     => 'Gerir utilizadores: superadmin, manager, staff e kitchen.',
    'perfil'       => 'Visualize e atualize as suas informações pessoais.',
    'configuracoes' => 'Gerir preferências gerais da plataforma.',
    'notificacoes' => 'Visualize as suas notificações.',
];

// ── Validação de rota ─────────────────────────────────────────────────────────
$allowed = array_column($menus[$profile] ?? [], 'routa');
// LINHA 225 — substituir:
if (!in_array($routa, $allowed, true)) {
    log_error("[rota] rota '{$routa}' não permitida para profile='{$profile}', redirecionando para dashboard");
    $routa = 'dashboard';
}

$pageTitle    = $titles[$routa]    ?? 'Dashboard';
$pageSubtitle = $subtitles[$routa] ?? '';
$menuItems    = $menus[$profile]   ?? [];

// ── Labels e cores de status (partilhados pelas duas secções abaixo) ──────────
$status_labels = [
    'open'      => 'Aberto',
    'submitted' => 'Submetido',
    'preparing' => 'A preparar',
    'served'    => 'Servido',
    'paid'      => 'Pago',
    'cancelled' => 'Cancelado',
];
$status_colors = [
    'open'      => '#6B7280',
    'submitted' => '#3B82F6',
    'preparing' => '#F59E0B',
    'served'    => '#10B981',
    'paid'      => '#AEE950',
    'cancelled' => '#E53935',
];

// ── Dados do dashboard ────────────────────────────────────────────────────────
if ($routa === 'dashboard') {

    // 1. Total de pratos no cardápio
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE restaurant_id = :rid");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $total_pratos = (int) $stmt->fetchColumn();

    // 2. Média global de avaliações (via agregados)
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(ira.avg_rating), 1)
        FROM item_rating_aggregates ira
        INNER JOIN menu_items m ON m.id = ira.item_id
        WHERE m.restaurant_id = :rid
    ");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $media_avaliacao = $stmt->fetchColumn() ?? '—';

    // 3. Pedidos de hoje (excluindo cancelados)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders
        WHERE restaurant_id = :rid
          AND DATE(created_at) = CURDATE()
          AND status != 'cancelled'
    ");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $pedidos_hoje = (int) $stmt->fetchColumn();

    // 4. Receita de hoje (pedidos servidos ou pagos)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) FROM orders
        WHERE restaurant_id = :rid
          AND DATE(created_at) = CURDATE()
          AND status IN ('served', 'paid')
    ");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $receita_hoje = number_format((float) $stmt->fetchColumn(), 2, ',', '.');

    // 5. Visitas hoje
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM visits
        WHERE restaurant_id = :rid
          AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $visitas_hoje = (int) $stmt->fetchColumn();

    // 6. Últimos pedidos (tabela resumida)
    // total_price foi removido do schema; calcula-se qty * unit_price
    $stmt = $pdo->prepare("
        SELECT o.id,
               rt.number          AS mesa,
               o.status,
               o.total,
               o.created_at,
               COUNT(oi.id)        AS num_itens
        FROM orders o
        LEFT JOIN restaurant_tables rt ON rt.id = o.table_id
        LEFT JOIN order_items oi       ON oi.order_id = o.id
        WHERE o.restaurant_id = :rid
        GROUP BY o.id, rt.number, o.status, o.total, o.created_at
        ORDER BY o.created_at DESC
        LIMIT 8
    ");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $ultimos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Top 5 pratos mais pedidos
    // Receita calculada com unit_price (sem total_price removido do schema)
    $stmt = $pdo->prepare("
        SELECT m.name,
               m.price,
               SUM(oi.qty)                          AS total_vendido,
               SUM(oi.qty * oi.unit_price)           AS receita_prato,
               COALESCE(ira.avg_rating, 0)           AS avg_rating
        FROM order_items oi
        INNER JOIN menu_items m              ON m.id = oi.item_id
        LEFT  JOIN item_rating_aggregates ira ON ira.item_id = m.id
        WHERE m.restaurant_id = :rid
        GROUP BY m.id, m.name, m.price, ira.avg_rating
        ORDER BY total_vendido DESC
        LIMIT 5
    ");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $top_pratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Pedidos por status (mini-gráfico)
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS total
        FROM orders
        WHERE restaurant_id = :rid
        GROUP BY status
    ");
    $stmt->execute([':rid' => RESTAURANT_ID]);
    $por_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// ── Dados do relatório financeiro ─────────────────────────────────────────────
if ($routa === 'relatorio') {

    $data    = $_GET['data']    ?? date('Y-m-d');

    $periodos_validos = ['diario', 'semanal', 'mensal', 'anual'];
    $periodo = in_array($_GET['periodo'] ?? '', $periodos_validos, true)
        ? $_GET['periodo']
        : 'diario';
    log_error("[relatorio] inicio: periodo={$periodo} data={$data} user_id=" . ($auth['id'] ?? '?'));

    // Valida formato de data
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        $data = date('Y-m-d');
    }

    // Monta cláusula WHERE e parâmetros conforme período
    switch ($periodo) {
        case 'semanal':
            $where_data = "DATE(o.created_at) BETWEEN
                           DATE_SUB(:data,  INTERVAL WEEKDAY(:data2) DAY)
                       AND DATE_ADD(DATE_SUB(:data3, INTERVAL WEEKDAY(:data4) DAY), INTERVAL 6 DAY)";
            $params_data   = [':data' => $data, ':data2' => $data, ':data3' => $data, ':data4' => $data];
            $label_periodo = 'Semana de ' . date('d/m/Y', strtotime('monday this week', strtotime($data)));
            break;

        case 'mensal':
            $where_data    = "YEAR(o.created_at) = YEAR(:data) AND MONTH(o.created_at) = MONTH(:data2)";
            $params_data   = [':data' => $data, ':data2' => $data];
            $meses = [
                'Janeiro',
                'Fevereiro',
                'Março',
                'Abril',
                'Maio',
                'Junho',
                'Julho',
                'Agosto',
                'Setembro',
                'Outubro',
                'Novembro',
                'Dezembro'
            ];
            $dt = new DateTime($data);
            $label_periodo = $meses[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
            log_error("[relatorio] label_periodo mensal: {$label_periodo}");
            break;

        case 'anual':
            $where_data    = "YEAR(o.created_at) = YEAR(:data)";
            $params_data   = [':data' => $data];
            $label_periodo = date('Y', strtotime($data));
            break;

        default:
            $periodo       = 'diario';
            $where_data    = "DATE(o.created_at) = :data";
            $params_data   = [':data' => $data];
            $label_periodo = date('d/m/Y', strtotime($data));
            break;
    }

    // KPIs do período
    $sql_kpi = "
        SELECT
            COUNT(DISTINCT CASE WHEN o.status != 'cancelled' THEN o.id END) AS total_pedidos,
            COALESCE(SUM(CASE WHEN o.status IN ('served','paid') THEN o.total ELSE 0 END), 0) AS receita_total,
            COALESCE(SUM(CASE WHEN o.status = 'cancelled'       THEN 1      ELSE 0 END), 0) AS cancelados,
            COALESCE(SUM(CASE WHEN o.status != 'cancelled'      THEN oi.qty ELSE 0 END), 0) AS itens_vendidos,
            COUNT(DISTINCT CASE WHEN o.status != 'cancelled' THEN o.table_id END) AS mesas_ativas
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE o.restaurant_id = :rid
        AND $where_data
    ";
    try {
        $stmt = $pdo->prepare($sql_kpi);
        $stmt->execute(array_merge([':rid' => RESTAURANT_ID], $params_data));
        $kpi = $stmt->fetch(PDO::FETCH_ASSOC);
        log_error("[relatorio] KPI ok: pedidos={$kpi['total_pedidos']} receita={$kpi['receita_total']} cancelados={$kpi['cancelados']}");
    } catch (Throwable $e) {
        log_error("[relatorio] ERRO sql_kpi: " . $e->getMessage());
        $kpi = ['total_pedidos' => 0, 'receita_total' => 0, 'cancelados' => 0, 'itens_vendidos' => 0, 'mesas_ativas' => 0];
    }


    $ticket_medio = ($kpi['total_pedidos'] > 0)
        ? round($kpi['receita_total'] / $kpi['total_pedidos'], 2)
        : 0;

    // Vendas por prato — usa qty * unit_price (total_price foi removido do schema)


    try {

        $sql_pratos = "
            SELECT m.name,
                c.name                          AS categoria,
                SUM(oi.qty)                     AS qtd_vendida,
                m.price                         AS preco_unitario,
                SUM(oi.qty * oi.unit_price)     AS receita_prato
            FROM order_items oi
            INNER JOIN orders o      ON o.id  = oi.order_id
            INNER JOIN menu_items m  ON m.id  = oi.item_id
            LEFT  JOIN categories c  ON c.id  = m.category_id
            WHERE o.restaurant_id = :rid
            AND o.status IN ('served','paid','submitted','preparing')
            AND $where_data
            GROUP BY m.id, m.name, c.name, m.price
            ORDER BY receita_prato DESC
        ";

        $stmt = $pdo->prepare($sql_pratos);
        $stmt->execute(array_merge([':rid' => RESTAURANT_ID], $params_data));
        $vendas_pratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        log_error("[relatorio] vendas_pratos: " . count($vendas_pratos) . " linha(s) para periodo={$periodo}");
    } catch (Throwable $e) {
        log_error("[relatorio] ERRO sql_pratos: " . $e->getMessage() . " | SQL: {$sql_pratos}");
        $vendas_pratos = [];
    }





    // Pedidos detalhados
    try {
        $sql_pedidos = "
        SELECT o.id,
               rt.number  AS mesa,
               o.status,
               o.total,
               o.created_at,
               o.closed_at,
               GROUP_CONCAT(m.name ORDER BY m.name SEPARATOR ', ') AS itens
        FROM orders o
        LEFT JOIN restaurant_tables rt ON rt.id     = o.table_id
        LEFT JOIN order_items oi       ON oi.order_id = o.id
        LEFT JOIN menu_items m         ON m.id       = oi.item_id
        WHERE o.restaurant_id = :rid
          AND $where_data
        GROUP BY o.id, rt.number, o.status, o.total, o.created_at, o.closed_at
        ORDER BY o.created_at DESC
    ";



        $stmt = $pdo->prepare($sql_pedidos);
        $stmt->execute(array_merge([':rid' => RESTAURANT_ID], $params_data));
        $pedidos_detalhados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        log_error("[relatorio] pedidos_detalhados: " . count($pedidos_detalhados) . " linha(s)");
    } catch (Throwable $e) {
        log_error("[relatorio] ERRO sql_pedidos: " . $e->getMessage() . " | SQL: {$sql_pedidos}");
        $pedidos_detalhados = [];
    }
}

// ── Carrega template de layout ────────────────────────────────────────────────
$contentTemplate = __DIR__ . '/templates/pages/' . $profile . '/' . $routa . '.php';
if (!is_file($contentTemplate)) {
    log_error("[template] ficheiro não encontrado: {$contentTemplate} — fallback para dashboard");
    $contentTemplate = __DIR__ . '/templates/pages/' . $profile . '/dashboard.php';
}

require __DIR__ . '/templates/layout_portal.php';
