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

 
// ── Dados de Utilizadores ─────────────────────────────────────────────────────
if ($routa === 'usuarios') {
 
    $filtro_role = $_GET['filtro_role'] ?? '';
    $filtro_q    = trim($_GET['filtro_q'] ?? '');
 
    // ── Acções POST ───────────────────────────────────────────────────────────
    $post_action = $_POST['action'] ?? '';
 
    if ($post_action === 'criar_usuario') {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $role     = $_POST['role']          ?? 'staff';
        $password = $_POST['password']      ?? '';
        if ($name && $email && $password && in_array($role, ['manager','staff'], true)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO admin_users (restaurant_id, name, email, password_hash, role)
                    VALUES (:rid, :name, :email, :hash, :role)
                ");
                $stmt->execute([
                    ':rid'   => RESTAURANT_ID,
                    ':name'  => $name,
                    ':email' => $email,
                    ':hash'  => $hash,
                    ':role'  => $role,
                ]);
                log_error("[usuarios] criado user_id=" . $pdo->lastInsertId() . " email={$email}");
            } catch (Throwable $e) {
                log_error("[usuarios] ERRO criar: " . $e->getMessage());
            }
        }
        header('Location: /admin.php?routa=usuarios'); exit;
    }
 
    if ($post_action === 'editar_usuario') {
        $uid      = (int)($_POST['usuario_id'] ?? 0);
        $name     = trim($_POST['name']        ?? '');
        $email    = trim($_POST['email']       ?? '');
        $role     = $_POST['role']             ?? 'staff';
        $password = $_POST['password']         ?? '';
        if ($uid && $name && $email && in_array($role, ['manager','staff'], true)) {
            try {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("
                        UPDATE admin_users SET name=:name, email=:email, role=:role,
                               password_hash=:hash WHERE id=:id AND restaurant_id=:rid
                    ");
                    $stmt->execute([':name'=>$name,':email'=>$email,':role'=>$role,
                                    ':hash'=>$hash,':id'=>$uid,':rid'=>RESTAURANT_ID]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE admin_users SET name=:name, email=:email, role=:role
                        WHERE id=:id AND restaurant_id=:rid
                    ");
                    $stmt->execute([':name'=>$name,':email'=>$email,':role'=>$role,
                                    ':id'=>$uid,':rid'=>RESTAURANT_ID]);
                }
                log_error("[usuarios] editado user_id={$uid}");
            } catch (Throwable $e) {
                log_error("[usuarios] ERRO editar: " . $e->getMessage());
            }
        }
        header('Location: /admin.php?routa=usuarios'); exit;
    }
 
    if ($post_action === 'apagar_usuario') {
        $uid = (int)($_POST['usuario_id'] ?? 0);
        if ($uid && $uid !== (int)$auth['id']) {
            try {
                $stmt = $pdo->prepare("
                    DELETE FROM admin_users WHERE id=:id AND restaurant_id=:rid
                ");
                $stmt->execute([':id'=>$uid, ':rid'=>RESTAURANT_ID]);
                log_error("[usuarios] apagado user_id={$uid}");
            } catch (Throwable $e) {
                log_error("[usuarios] ERRO apagar: " . $e->getMessage());
            }
        }
        header('Location: /admin.php?routa=usuarios'); exit;
    }
 
    // ── Queries de leitura ────────────────────────────────────────────────────
    try {
        // KPIs
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*)                                                          AS total,
                SUM(role = 'manager')                                             AS managers,
                SUM(role = 'staff')                                               AS staff,
                SUM(YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())) AS novos_mes
            FROM admin_users
            WHERE restaurant_id = :rid
        ");
        $stmt->execute([':rid' => RESTAURANT_ID]);
        $kpi_usuarios = $stmt->fetch() ?: ['total'=>0,'managers'=>0,'staff'=>0,'novos_mes'=>0];
        log_error("[usuarios] KPI ok: total={$kpi_usuarios['total']}");
    } catch (Throwable $e) {
        log_error("[usuarios] ERRO kpi: " . $e->getMessage());
        $kpi_usuarios = ['total'=>0,'managers'=>0,'staff'=>0,'novos_mes'=>0];
    }
 
    try {
        // Lista filtrada
        $where_parts = ['restaurant_id = :rid'];
        $params      = [':rid' => RESTAURANT_ID];
 
        if ($filtro_role !== '') {
            $where_parts[]     = 'role = :role';
            $params[':role']   = $filtro_role;
        }
        if ($filtro_q !== '') {
            $where_parts[]   = '(name LIKE :q OR email LIKE :q)';
            $params[':q']    = '%' . $filtro_q . '%';
        }
 
        $where_sql = implode(' AND ', $where_parts);
        $stmt = $pdo->prepare("
            SELECT id, name, email, role, created_at
            FROM admin_users
            WHERE {$where_sql}
            ORDER BY created_at DESC
        ");
        $stmt->execute($params);
        $lista_usuarios = $stmt->fetchAll();
        log_error("[usuarios] lista: " . count($lista_usuarios) . " registo(s)");
    } catch (Throwable $e) {
        log_error("[usuarios] ERRO lista: " . $e->getMessage());
        $lista_usuarios = [];
    }
}
 
 
// ── Dados de Notificações ─────────────────────────────────────────────────────
if ($routa === 'notificacoes') {
 
    $filtro_tipo   = $_GET['filtro_tipo']  ?? '';
    $filtro_lida   = $_GET['filtro_lida']  ?? '';
    $notif_pagina_atual = max(1, (int)($_GET['pagina'] ?? 1));
    $notif_por_pagina   = 20;
 
    // ── Acções POST ───────────────────────────────────────────────────────────
    $post_action = $_POST['action'] ?? '';
 
    if ($post_action === 'marcar_lida') {
        $nid = (int)($_POST['notificacao_id'] ?? 0);
        if ($nid) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE notifications SET lida=1
                    WHERE id=:id AND (user_id=:uid OR user_id IS NULL) AND restaurant_id=:rid
                ");
                $stmt->execute([':id'=>$nid,':uid'=>$auth['id'],':rid'=>RESTAURANT_ID]);
                log_error("[notificacoes] marcada lida id={$nid}");
            } catch (Throwable $e) {
                log_error("[notificacoes] ERRO marcar_lida: " . $e->getMessage());
            }
        }
        header('Location: /admin.php?routa=notificacoes'); exit;
    }
 
    if ($post_action === 'marcar_todas_lidas') {
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications SET lida=1
                WHERE (user_id=:uid OR user_id IS NULL) AND restaurant_id=:rid
            ");
            $stmt->execute([':uid'=>$auth['id'],':rid'=>RESTAURANT_ID]);
            log_error("[notificacoes] todas marcadas lidas user_id={$auth['id']}");
        } catch (Throwable $e) {
            log_error("[notificacoes] ERRO marcar_todas: " . $e->getMessage());
        }
        header('Location: /admin.php?routa=notificacoes'); exit;
    }
 
    if ($post_action === 'apagar_notificacao') {
        $nid = (int)($_POST['notificacao_id'] ?? 0);
        if ($nid) {
            try {
                $stmt = $pdo->prepare("
                    DELETE FROM notifications
                    WHERE id=:id AND (user_id=:uid OR user_id IS NULL) AND restaurant_id=:rid
                ");
                $stmt->execute([':id'=>$nid,':uid'=>$auth['id'],':rid'=>RESTAURANT_ID]);
                log_error("[notificacoes] apagada id={$nid}");
            } catch (Throwable $e) {
                log_error("[notificacoes] ERRO apagar: " . $e->getMessage());
            }
        }
        header('Location: /admin.php?routa=notificacoes'); exit;
    }
 
    // ── Helper: tempo relativo ────────────────────────────────────────────────
    if (!function_exists('notif_tempo_relativo')) {
        function notif_tempo_relativo(string $datetime): string {
            $diff = time() - strtotime($datetime);
            if ($diff <    60) return 'Agora mesmo';
            if ($diff <  3600) return (int)($diff / 60) . ' min atrás';
            if ($diff < 86400) return (int)($diff / 3600) . ' h atrás';
            return date('d/m/Y H:i', strtotime($datetime));
        }
    }
 
    // ── Queries de leitura ────────────────────────────────────────────────────
    try {
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*)                       AS total,
                SUM(lida = 0)                  AS nao_lidas,
                SUM(tipo = 'pedido')           AS pedidos,
                SUM(tipo = 'avaliacao')        AS avaliacoes
            FROM notifications
            WHERE restaurant_id = :rid
              AND (user_id = :uid OR user_id IS NULL)
        ");
        $stmt->execute([':rid'=>RESTAURANT_ID, ':uid'=>$auth['id']]);
        $kpi_notif = $stmt->fetch() ?: ['total'=>0,'nao_lidas'=>0,'pedidos'=>0,'avaliacoes'=>0];
        $notificacoes_nao_lidas = $kpi_notif['nao_lidas'] > 0;
        log_error("[notificacoes] KPI ok: total={$kpi_notif['total']} nao_lidas={$kpi_notif['nao_lidas']}");
    } catch (Throwable $e) {
        log_error("[notificacoes] ERRO kpi: " . $e->getMessage());
        $kpi_notif = ['total'=>0,'nao_lidas'=>0,'pedidos'=>0,'avaliacoes'=>0];
        $notificacoes_nao_lidas = false;
    }
 
    try {
        $where_parts = [
            'restaurant_id = :rid',
            '(user_id = :uid OR user_id IS NULL)',
        ];
        $params = [':rid'=>RESTAURANT_ID, ':uid'=>$auth['id']];
 
        if ($filtro_tipo !== '') {
            $where_parts[]     = 'tipo = :tipo';
            $params[':tipo']   = $filtro_tipo;
        }
        if ($filtro_lida !== '') {
            $where_parts[]     = 'lida = :lida';
            $params[':lida']   = (int)$filtro_lida;
        }
 
        $where_sql = implode(' AND ', $where_parts);
 
        // Total para paginação
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE {$where_sql}");
        $stmt->execute($params);
        $notif_total    = (int)$stmt->fetchColumn();
        $notif_paginas  = max(1, (int)ceil($notif_total / $notif_por_pagina));
        $notif_offset   = ($notif_pagina_atual - 1) * $notif_por_pagina;
 
        $stmt = $pdo->prepare("
            SELECT id, tipo, titulo, mensagem, lida, created_at
            FROM notifications
            WHERE {$where_sql}
            ORDER BY lida ASC, created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        // LIMIT/OFFSET precisam de bind por tipo
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $notif_por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $notif_offset,     PDO::PARAM_INT);
        $stmt->execute();
        $lista_notificacoes = $stmt->fetchAll();
        log_error("[notificacoes] lista: " . count($lista_notificacoes) . " registo(s) pagina={$notif_pagina_atual}");
    } catch (Throwable $e) {
        log_error("[notificacoes] ERRO lista: " . $e->getMessage());
        $lista_notificacoes = [];
        $notif_paginas = 1;
    }
}
 
 
// ── Dados de Configurações ────────────────────────────────────────────────────
if ($routa === 'configuracoes') {
 
    $config_sucesso = '';
    $config_erro    = '';
    $post_action    = $_POST['action'] ?? '';
 
    // ── Acções POST ───────────────────────────────────────────────────────────
    if ($post_action === 'salvar_restaurante') {
        $nome     = trim($_POST['cfg_nome']     ?? '');
        $slug     = trim($_POST['cfg_slug']     ?? '');
        $timezone = trim($_POST['cfg_timezone'] ?? 'UTC');
        if ($nome && $slug && preg_match('/^[a-z0-9\-]+$/', $slug)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE restaurants SET name=:name, slug=:slug, timezone=:tz
                    WHERE id=:rid
                ");
                $stmt->execute([':name'=>$nome,':slug'=>$slug,':tz'=>$timezone,':rid'=>RESTAURANT_ID]);
                $config_sucesso = 'Dados do restaurante actualizados com sucesso.';
                log_error("[config] restaurante actualizado: nome={$nome} slug={$slug} tz={$timezone}");
            } catch (Throwable $e) {
                $config_erro = 'Erro ao guardar: ' . $e->getMessage();
                log_error("[config] ERRO salvar_restaurante: " . $e->getMessage());
            }
        } else {
            $config_erro = 'Nome e slug são obrigatórios. O slug só pode conter letras, números e hífens.';
        }
    }
 
    if ($post_action === 'salvar_sistema') {
        $senha_atual      = $_POST['senha_atual']      ?? '';
        $senha_nova       = $_POST['senha_nova']       ?? '';
        $senha_confirmar  = $_POST['senha_confirmar']  ?? '';
 
        if ($senha_nova !== '') {
            if ($senha_nova !== $senha_confirmar) {
                $config_erro = 'As senhas não coincidem.';
            } elseif (strlen($senha_nova) < 8) {
                $config_erro = 'A nova senha deve ter no mínimo 8 caracteres.';
            } else {
                try {
                    $row = fetch_one($pdo,
                        'SELECT password_hash FROM admin_users WHERE id=:id',
                        [':id' => $auth['id']]
                    );
                    if ($row && password_verify($senha_atual, $row['password_hash'])) {
                        $novo_hash = password_hash($senha_nova, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash=:h WHERE id=:id");
                        $stmt->execute([':h'=>$novo_hash,':id'=>$auth['id']]);
                        $config_sucesso = 'Senha actualizada com sucesso.';
                        log_error("[config] senha alterada user_id={$auth['id']}");
                    } else {
                        $config_erro = 'Senha actual incorrecta.';
                        log_error("[config] senha actual errada user_id={$auth['id']}");
                    }
                } catch (Throwable $e) {
                    $config_erro = 'Erro ao alterar senha.';
                    log_error("[config] ERRO salvar_sistema: " . $e->getMessage());
                }
            }
        }
        // Preferências (expandir conforme necessário)
        log_error("[config] preferencias guardadas user_id={$auth['id']}");
    }
 
    if ($post_action === 'criar_mesa' || $post_action === 'editar_mesa') {
        $mid    = (int)($_POST['mesa_id']    ?? 0);
        $number = trim($_POST['number']      ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        if ($number) {
            try {
                if ($post_action === 'criar_mesa') {
                    $qr   = 'QR-' . strtoupper(bin2hex(random_bytes(8)));
                    $stmt = $pdo->prepare("
                        INSERT INTO restaurant_tables (restaurant_id, number, description, qr_code, active)
                        VALUES (:rid, :num, :desc, :qr, :active)
                    ");
                    $stmt->execute([':rid'=>RESTAURANT_ID,':num'=>$number,':desc'=>$desc,
                                    ':qr'=>$qr,':active'=>$active]);
                    log_error("[config] mesa criada number={$number}");
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE restaurant_tables
                        SET number=:num, description=:desc, active=:active
                        WHERE id=:id AND restaurant_id=:rid
                    ");
                    $stmt->execute([':num'=>$number,':desc'=>$desc,':active'=>$active,
                                    ':id'=>$mid,':rid'=>RESTAURANT_ID]);
                    log_error("[config] mesa editada id={$mid}");
                }
            } catch (Throwable $e) {
                log_error("[config] ERRO mesa: " . $e->getMessage());
            }
        }
        header('Location: /admin.php?routa=configuracoes'); exit;
    }
 
    if ($post_action === 'apagar_mesa') {
        $mid = (int)($_POST['mesa_id'] ?? 0);
        if ($mid) {
            try {
                $stmt = $pdo->prepare("
                    DELETE FROM restaurant_tables WHERE id=:id AND restaurant_id=:rid
                ");
                $stmt->execute([':id'=>$mid, ':rid'=>RESTAURANT_ID]);
                log_error("[config] mesa apagada id={$mid}");
            } catch (Throwable $e) {
                log_error("[config] ERRO apagar_mesa: " . $e->getMessage());
            }
        }
        header('Location: /admin.php?routa=configuracoes'); exit;
    }
 
    if ($post_action === 'limpar_pedidos') {
        try {
            $stmt = $pdo->prepare("DELETE FROM orders WHERE restaurant_id=:rid");
            $stmt->execute([':rid'=>RESTAURANT_ID]);
            $config_sucesso = 'Histórico de pedidos eliminado.';
            log_error("[config] LIMPAR PEDIDOS executado por user_id={$auth['id']}");
        } catch (Throwable $e) {
            $config_erro = 'Erro ao limpar pedidos.';
            log_error("[config] ERRO limpar_pedidos: " . $e->getMessage());
        }
    }
 
    // ── Queries de leitura ────────────────────────────────────────────────────
    try {
        $config = fetch_one($pdo,
            'SELECT name, slug, timezone FROM restaurants WHERE id=:rid',
            [':rid' => RESTAURANT_ID]
        ) ?: ['name'=>'','slug'=>'','timezone'=>'UTC'];
        // Preferências em JSON (opcional — expandir se tiver tabela settings)
        $config['notif_novos_pedidos'] = true;
        $config['notif_avaliacoes']    = true;
        log_error("[config] dados restaurante carregados");
    } catch (Throwable $e) {
        log_error("[config] ERRO ler restaurante: " . $e->getMessage());
        $config = ['name'=>'','slug'=>'','timezone'=>'UTC',
                   'notif_novos_pedidos'=>true,'notif_avaliacoes'=>true];
    }
 
    try {
        $stmt = $pdo->prepare("
            SELECT id, number, description, qr_code, active, created_at
            FROM restaurant_tables
            WHERE restaurant_id=:rid
            ORDER BY CAST(number AS UNSIGNED), number
        ");
        $stmt->execute([':rid'=>RESTAURANT_ID]);
        $lista_mesas = $stmt->fetchAll();
        log_error("[config] mesas: " . count($lista_mesas) . " registo(s)");
    } catch (Throwable $e) {
        log_error("[config] ERRO ler mesas: " . $e->getMessage());
        $lista_mesas = [];
    }
}

// ── Carrega template de layout ────────────────────────────────────────────────
$contentTemplate = __DIR__ . '/templates/pages/' . $profile . '/' . $routa . '.php';
if (!is_file($contentTemplate)) {
    log_error("[template] ficheiro não encontrado: {$contentTemplate} — fallback para dashboard");
    $contentTemplate = __DIR__ . '/templates/pages/' . $profile . '/dashboard.php';
}

require __DIR__ . '/templates/layout_portal.php';
