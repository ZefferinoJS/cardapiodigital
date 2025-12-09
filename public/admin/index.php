<?php
// Conexão com a base de dados
include_once  '../../config/dbnojs.php';

session_start();

$escape = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
function log_error(string $message): void
{
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, 3, __DIR__ . '/logs/php_errors.log');
}

function redirecionarPara(string $routa): never
{
    header("Location:index.php?routa={$routa}");
    exit;
}
if (($_POST['action'] ?? null) === 'logout') {
    session_destroy();
    session_unset();
    redirecionarPara('home');
}
/*
if (($_POST['action'] ?? null) === 'login') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = 'Email e senha são obrigatórios.';
        redirecionarPara('login');
    }

    try {
        
        $user = fetch_one(
            $pdo,
            'SELECT u.id, u.nome, u.email, u.senha, u.ativo, pr.nome AS perfil_nome
             FROM usuarios u
             INNER JOIN perfis pr ON pr.id = u.perfil_id
             WHERE u.email = :email
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
    $stored = trim((string) ($user['senha'] ?? ''));
    $ok = false;
    if ($stored !== '' && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon'))) {
        $ok = password_verify($password, $stored);
    } else {
        $ok = hash_equals($stored, $password);
    }

    $profile = app_profile_from_db((string) ($user['perfil_nome'] ?? ''));
    $ativo = (int) ($user['ativo'] ?? 0) === 1;

    if ($ok && $ativo && $profile !== null ) {
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'name' => (string) $user['nome'],
            'profile' => $profile,
        ];

        try {
            $stmt = $pdo->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id');
            $stmt->execute(['id' => (int) $user['id']]);
        } catch (Throwable $e) {
            log_error('login update ultimo_login error: ' . $e->getMessage());
        }

        log_error('login success: user_id=' . $user['id'] . ' profile=' . $profile . ' session=' . session_id());
        redirecionarPara('dashboard');
    }
    log_error('login failed: bad credentials or inactive for ' . $email);
    $_SESSION['login_error'] = 'Email ou senha incorretos, ou conta inativa.';
    redirecionarPara('login');
}
$routa  = (string) ($_GET['routa'] ?? 'home');
$auth = $_SESSION['auth'] ?? null;
if (!$auth) {

    if ($routa === 'login') {
        $loginError = (string) ($_SESSION['login_error'] ?? '');
        unset($_SESSION['login_error']);
        include 'login.php';
        exit;
    }

    include 'login.php';
    exit;
}*/

$profile = (string) $auth['profile'];

//Código da  Página de painel administrativo 

// ── Métricas para os summary cards ──────────────────────────────────────────
 
// 1. Total de pratos no cardápio
$stmt = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE restaurant_id = 1");
$total_pratos = $stmt->fetchColumn();
 
// 2. Média global de avaliações (calculada a partir dos agregados)
$stmt = $pdo->query("
    SELECT ROUND(AVG(avg_rating), 1)
    FROM item_rating_aggregates ira
    INNER JOIN menu_items m ON m.id = ira.item_id
    WHERE m.restaurant_id = 1
");
$media_avaliacao = $stmt->fetchColumn() ?? '—';
 
// 3. Pedidos de hoje (status != cancelled)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE restaurant_id = 1
      AND DATE(created_at) = CURDATE()
      AND status != 'cancelled'
");
$pedidos_hoje = $stmt->fetchColumn();
 
// 4. Receita de hoje (pedidos pagos/servidos hoje)
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0) FROM orders
    WHERE restaurant_id = 1
      AND DATE(created_at) = CURDATE()
      AND status IN ('served', 'paid')
");
$receita_hoje = number_format($stmt->fetchColumn(), 2, ',', '.');
 
// 5. Visitas hoje
$stmt = $pdo->query("
    SELECT COUNT(*) FROM visits
    WHERE restaurant_id = 1
      AND DATE(created_at) = CURDATE()
");
$visitas_hoje = $stmt->fetchColumn();
 
// ── Últimos pedidos (tabela resumida) ────────────────────────────────────────
$ultimos_pedidos = $pdo->query("
    SELECT o.id,
           rt.number AS mesa,
           o.status,
           o.total,
           o.created_at,
           COUNT(oi.id) AS num_itens
    FROM orders o
    LEFT JOIN restaurant_tables rt ON rt.id = o.table_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.restaurant_id = 1
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);
 
// ── Top 5 pratos mais pedidos ────────────────────────────────────────────────
$top_pratos = $pdo->query("
    SELECT m.name,
           m.price,
           SUM(oi.qty) AS total_vendido,
           COALESCE(ira.avg_rating, 0) AS avg_rating
    FROM order_items oi
    INNER JOIN menu_items m ON m.id = oi.item_id
    LEFT JOIN item_rating_aggregates ira ON ira.item_id = m.id
    WHERE m.restaurant_id = 1
    GROUP BY m.id
    ORDER BY total_vendido DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
 
// ── Pedidos por status (para mini-gráfico) ───────────────────────────────────
$por_status = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM orders
    WHERE restaurant_id = 1
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);
 
// Labels de status em português
$status_labels = [
    'open'       => 'Aberto',
    'submitted'  => 'Submetido',
    'preparing'  => 'A preparar',
    'served'     => 'Servido',
    'paid'       => 'Pago',
    'cancelled'  => 'Cancelado',
];
 
$status_colors = [
    'open'       => '#6B7280',
    'submitted'  => '#3B82F6',
    'preparing'  => '#F59E0B',
    'served'     => '#10B981',
    'paid'       => '#AEE950',
    'cancelled'  => '#E53935',
];
 


// Página de relatório financeiro
$titulo    = "Relatório financeiro";
$subtitulo = "";
$pagina_atual = "relatorio";
 

// ── Parâmetros de filtro ─────────────────────────────────────────────────────
$periodo = $_GET['periodo'] ?? 'diario';
$data    = $_GET['data']    ?? date('Y-m-d');
 
// Valida data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    $data = date('Y-m-d');
}
 
// Monta cláusula WHERE de data conforme período
switch ($periodo) {
    case 'semanal':
        $where_data = "DATE(o.created_at) BETWEEN DATE_SUB(:data, INTERVAL WEEKDAY(:data2) DAY)
                        AND DATE_ADD(DATE_SUB(:data3, INTERVAL WEEKDAY(:data4) DAY), INTERVAL 6 DAY)";
        $params = [':data' => $data, ':data2' => $data, ':data3' => $data, ':data4' => $data];
        $label_periodo = "Semana de " . date('d/m/Y', strtotime("monday this week", strtotime($data)));
        break;
    case 'mensal':
        $where_data = "YEAR(o.created_at) = YEAR(:data) AND MONTH(o.created_at) = MONTH(:data2)";
        $params = [':data' => $data, ':data2' => $data];
        $label_periodo = date('F Y', strtotime($data));
        break;
    case 'anual':
        $where_data = "YEAR(o.created_at) = YEAR(:data)";
        $params = [':data' => $data];
        $label_periodo = date('Y', strtotime($data));
        break;
    default: // diario
        $periodo = 'diario';
        $where_data = "DATE(o.created_at) = :data";
        $params = [':data' => $data];
        $label_periodo = date('d/m/Y', strtotime($data));
        break;
}
 
// ── KPIs do período filtrado ─────────────────────────────────────────────────
$sql_kpi = "
    SELECT
        COUNT(DISTINCT o.id)                                             AS total_pedidos,
        COALESCE(SUM(CASE WHEN o.status IN ('served','paid') THEN o.total ELSE 0 END), 0) AS receita_total,
        COALESCE(SUM(CASE WHEN o.status = 'cancelled'        THEN 1     ELSE 0 END), 0) AS cancelados,
        COALESCE(SUM(oi.qty), 0)                                         AS itens_vendidos,
        COUNT(DISTINCT o.table_id)                                       AS mesas_ativas
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.restaurant_id = 1
      AND o.status != 'cancelled'
      AND $where_data
";
$stmt = $pdo->prepare($sql_kpi);
$stmt->execute($params);
$kpi = $stmt->fetch(PDO::FETCH_ASSOC);
 
// Ticket médio
$ticket_medio = $kpi['total_pedidos'] > 0
    ? round($kpi['receita_total'] / $kpi['total_pedidos'], 2)
    : 0;
 
// ── Tabela de vendas por prato ────────────────────────────────────────────────
$sql_pratos = "
    SELECT m.name,
           c.name                    AS categoria,
           SUM(oi.qty)               AS qtd_vendida,
           m.price                   AS preco_unitario,
           SUM(oi.total_price)       AS receita_prato
    FROM order_items oi
    INNER JOIN orders o  ON o.id  = oi.order_id
    INNER JOIN menu_items m ON m.id = oi.item_id
    LEFT  JOIN categories c ON c.id = m.category_id
    WHERE o.restaurant_id = 1
      AND o.status IN ('served','paid','submitted','preparing')
      AND $where_data
    GROUP BY m.id
    ORDER BY receita_prato DESC
";
$stmt = $pdo->prepare($sql_pratos);
$stmt->execute($params);
$vendas_pratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// ── Tabela de pedidos detalhados ──────────────────────────────────────────────
$sql_pedidos = "
    SELECT o.id,
           rt.number  AS mesa,
           o.status,
           o.total,
           o.created_at,
           GROUP_CONCAT(m.name ORDER BY m.name SEPARATOR ', ') AS itens
    FROM orders o
    LEFT JOIN restaurant_tables rt ON rt.id = o.table_id
    LEFT JOIN order_items oi       ON oi.order_id = o.id
    LEFT JOIN menu_items m         ON m.id = oi.item_id
    WHERE o.restaurant_id = 1
      AND $where_data
    GROUP BY o.id
    ORDER BY o.created_at DESC
";
$stmt = $pdo->prepare($sql_pedidos);
$stmt->execute($params);
$pedidos_detalhados = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// ── Helpers ───────────────────────────────────────────────────────────────────
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
 
/*function status_badge(string $s, array $l, array $c): string {
    $label = $l[$s] ?? $s;
    $color = $c[$s] ?? '#888';
    return "<span class=\"status-badge\" style=\"background:{$color}20;color:{$color};border:1px solid {$color}40\">{$label}</span>";
}*/
// Helper: badge de status
function status_badge(string $status, array $labels, array $colors): string {
    $label = $labels[$status] ?? $status;
    $color = $colors[$status] ?? '#888';
    return "<span class=\"status-badge\" style=\"background:".htmlspecialchars($color)."20;color:".htmlspecialchars($color).";border:1px solid ".htmlspecialchars($color)."40\">$label</span>";
}



$menus = [
    'admin' => [
        ['routa' => 'dashboard', 'icon' => 'fa-solid fa-gauge', 'label' => 'Painel'],
        ['routa' => 'pratos', 'icon' => 'fa-solid fa-bell-concierge', 'label' => 'Pratos'],
        ['routa' => 'categorias', 'icon' => 'fas fa-list', 'label' => 'Categorias'],
        ['routa' => 'pedidos', 'icon' => 'fas fa-receipt', 'label' => 'Pedidos'],
        ['routa' => 'avaliacoes', 'icon' => 'fas fa-star', 'label' => 'Avaliações'],
        ['routa' => 'relatorio', 'icon' => 'relatorio.php', 'label' => 'Relatório'],
        ['routa' => 'usuarios', 'icon' => 'fa-solid fa-users', 'label' => 'Usuários'],
        ['routa' => 'perfil', 'icon' => 'fa-solid fa-user', 'label' => 'Perfil'],
        ['routa' => 'configuracoes', 'icon' => 'fa-solid fa-gear', 'label' => 'Configurações'],
        ['routa' => 'notificacoes', 'icon' => 'fa-solid fa-bell', 'label' => 'Notificações'],

    ]
];
$titles = [
    'dashboard' => 'Painel Administrativo',
    'pratos' => 'Gerenciar Pratos',
    'categorias' => 'Categorias',
    'pedidos' => 'Pedidos',
    'avaliacoes' => 'Avaliações',
    'configuracoes' => 'Configurações',
    'notificacoes' => 'Notificações',
    'relatorio' => 'Relatório',
    'usuarios' => 'Usuários',
    'perfil' => 'Perfil',
];
$subtitles = [
    'dashboard' => 'Visão geral das atividades executadas no restaurante',
    'pratos' => 'Gerencie os pratos do restaurante, suas categorias e disponibilidade.',
    'categorias' => 'Gerencie as categorias do restaurante, seus status e detalhes.',
    'pedidos' => 'Gerencie os pedidos do restaurante, seus status e detalhes.',
    'avaliacoes' => 'Gerencie as avaliações dos pratos do restaurante.',
    'relatorios' => 'Visualize e analise as vendas e receitas do restaurante.',
    'configuracoes' => 'Gerir preferências gerais da plataforma',
    'notificacoes' => 'Visualize e atualize as suas informações pessoais',
    'usuarios' => 'Gerir professores, secretários e administradores',
    'perfil' => 'Visualize e atualize as suas informações pessoais',
];

$allowed = array_column($menus[$profile] ?? [], 'routa');
if (!in_array($routa, $allowed, true)) {
    $routa = 'dashboard';
}
$pageTitle = $titles[$routa] ?? 'Dashboard';
$pageSubtitle=$subtitles[$routa] ?? 'Dashboard';
$menuItems = $menus[$profile] ?? [];
$contentTemplate = __DIR__ . '/templates/pages/' . $profile . '/' . $routa . '.php';
if (!is_file($contentTemplate)) {
    $contentTemplate = __DIR__ . '/templates/pages/' . $profile . '/dashboard.php';
}


include 'layoutAdmin.php';

?>