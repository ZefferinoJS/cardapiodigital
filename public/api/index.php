<?php
// Simple API front controller for cardapio digital
// Routes:
// POST /public/api/index.php/visits            -> create visit (qr_token)
// POST /public/api/index.php/visits/heartbeat  -> refresh last_seen_at da sessão de mesa
// GET  /public/api/index.php/menu              -> list menu by restaurant (query: slug or restaurant_id)
// POST /public/api/index.php/orders            -> create order
// POST /public/api/index.php/checkout          -> create order a partir do carrinho
// POST /public/api/index.php/ratings           -> submit rating

$config = require_once __DIR__ .'/../../config/db.php';
try{
    $pdo = new PDO($config['dsn'], $config['user'], $config['pass'], $config['options']);
}catch(PDOException $e){
    http_response_code(500);
    echo json_encode(['error'=>'DB connection failed','details'=>friendly_error($e)]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'],'/') : '';
// If PATH_INFO not set, try to parse from REQUEST_URI
if($path === ''){
    $script = $_SERVER['SCRIPT_NAME'];
    $uri = $_SERVER['REQUEST_URI'];
    $path = substr($uri, strlen(dirname($script)));
    $path = trim($path, '/');
}
$parts = explode('/', $path);
$resource = $parts[0] ?? '';

// helper
function json($data,$code=200){ http_response_code($code); echo json_encode($data); exit; }

/**
 * Traduz erros comuns do MySQL/PDO (chave duplicada, FK em falta, etc.)
 * para uma mensagem legível. Sem isto, um "Duplicate entry '5' for key
 * ...'restaurant_tables.uq_restaurant_table_number'" aparecia tal e qual
 * num alert() do painel — confuso para quem não é programador.
 */
function friendly_error(Throwable $e): string
{
    $msg = $e->getMessage();
    if (str_contains($msg, 'Duplicate entry')) {
        if (str_contains($msg, 'table_number') || str_contains($msg, 'uq_restaurant_table')) {
            return 'Já existe uma mesa com esse número/nome.';
        }
        if (str_contains($msg, 'slug')) {
            return 'Já existe um registo com este slug/identificador.';
        }
        return 'Já existe um registo com esses dados.';
    }
    if (str_contains($msg, 'a foreign key constraint fails')) {
        return 'Não é possível concluir: este registo está associado a outros dados (ex: pedidos, itens).';
    }
    return 'Ocorreu um erro ao processar o pedido.';
}

// Minutos de inactividade após os quais a sessão de mesa (visit) expira e o
// cliente tem de voltar a indicar o código/QR da mesa. Aplica-se tanto no
// checkout como na criação directa de pedidos.
const TABLE_SESSION_TIMEOUT_MINUTES = 35;

/**
 * Vai buscar a "visit" (sessão de mesa) associada a um session_token e
 * confirma que ainda está dentro da janela de inactividade permitida.
 * Devolve a visita (array) se válida, ou termina o pedido com json(erro).
 */
function require_valid_table_session(PDO $pdo, ?string $sessionToken): array
{
    if (!$sessionToken) {
        json(['error' => 'session_required', 'message' => 'É necessário indicar o código da mesa (QR ou manual) antes de finalizar o pedido.'], 400);
    }

    $stmt = $pdo->prepare('SELECT id, restaurant_id, table_id, session_token, last_seen_at, created_at FROM visits WHERE session_token = ? LIMIT 1');
    $stmt->execute([$sessionToken]);
    $visit = $stmt->fetch();

    if (!$visit) {
        json(['error' => 'session_invalid', 'message' => 'Sessão de mesa não encontrada. Por favor, indique o código da mesa novamente.'], 404);
    }

    $lastSeen = $visit['last_seen_at'] ?? $visit['created_at'];
    $minutesInactive = (time() - strtotime((string) $lastSeen)) / 60;

    if ($minutesInactive > TABLE_SESSION_TIMEOUT_MINUTES) {
        json(['error' => 'session_expired', 'message' => 'A sessão desta mesa expirou por inactividade. Por favor, indique o código da mesa novamente.'], 401);
    }

    return $visit;
}

// POST /visits { qr_token } OR { table_number, restaurant_slug }
// POST /visits/heartbeat { session_token } — refresca last_seen_at para a
// sessão continuar válida enquanto o cliente estiver activo no site.
if($method === 'POST' && $resource === 'visits' && ($parts[1] ?? '') === 'heartbeat'){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $session = $body['session_token'] ?? null;
    if(!$session) return json(['error'=>'session_token required'],400);

    $stmt = $pdo->prepare('SELECT id, last_seen_at, created_at FROM visits WHERE session_token = ? LIMIT 1');
    $stmt->execute([$session]);
    $visit = $stmt->fetch();
    if(!$visit) return json(['error'=>'session_invalid'],404);

    $lastSeen = $visit['last_seen_at'] ?? $visit['created_at'];
    $minutesInactive = (time() - strtotime((string) $lastSeen)) / 60;
    if ($minutesInactive > TABLE_SESSION_TIMEOUT_MINUTES) {
        return json(['error'=>'session_expired','message'=>'A sessão desta mesa expirou por inactividade.'],401);
    }

    $pdo->prepare('UPDATE visits SET last_seen_at = NOW() WHERE id = ?')->execute([$visit['id']]);
    json(['ok'=>true]);
}

if($method === 'POST' && $resource === 'visits' && !isset($parts[1])){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $qr = $body['qr_token'] ?? null;
    $table_number = $body['table_number'] ?? null;
    $restaurant_slug = $body['restaurant_slug'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // find table by qr_code OR by restaurant_slug + table_number
    if($qr){
        $stmt = $pdo->prepare('SELECT rt.id as table_id, rt.restaurant_id FROM restaurant_tables rt WHERE rt.qr_code = ? AND rt.active = 1 LIMIT 1');
        $stmt->execute([$qr]);
        $row = $stmt->fetch();
        if(!$row) return json(['error'=>'invalid_qr'],404);
        $table_id = $row['table_id']; $restaurant_id = $row['restaurant_id'];
    } else {
        if(!$table_number) return json(['error'=>'table_number required when no qr_token'],400);
        // resolve restaurant id from slug if provided
        if($restaurant_slug){
            $s = $pdo->prepare('SELECT id FROM restaurants WHERE slug = ? LIMIT 1');
            $s->execute([$restaurant_slug]); $r = $s->fetch();
            if(!$r) return json(['error'=>'restaurant_not_found'],404);
            $restaurant_id = $r['id'];
        } else {
            // fallback: use first restaurant
            $s = $pdo->query('SELECT id FROM restaurants LIMIT 1'); $r = $s->fetch();
            if(!$r) return json(['error'=>'no_restaurant_configured'],500);
            $restaurant_id = $r['id'];
        }
        // find table by number and restaurant
        $stmt = $pdo->prepare('SELECT id as table_id, active FROM restaurant_tables WHERE restaurant_id = ? AND number = ? LIMIT 1');
        $stmt->execute([$restaurant_id, $table_number]);
        $row = $stmt->fetch();
        if(!$row) return json(['error'=>'table_not_found'],404);
        if(!$row['active']) return json(['error'=>'table_inactive'],400);
        $table_id = $row['table_id'];
    }

    // check if table already has a recent visit (e.g., last 6 hours)
    $check = $pdo->prepare('SELECT id, session_token, created_at FROM visits WHERE table_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR) ORDER BY created_at DESC LIMIT 1');
    $check->execute([$table_id]); $recent = $check->fetch();
    $in_use = $recent ? true : false;

    // create new session regardless (optionally you could reuse recent.session_token)
    $session = bin2hex(random_bytes(16));
    $ins = $pdo->prepare('INSERT INTO visits (restaurant_id, table_id, session_token, ip, user_agent) VALUES (?,?,?,?,?)');
    $ins->execute([$restaurant_id, $table_id, $session, $ip, $ua]);

    json(['session_token'=>$session,'restaurant_id'=>$restaurant_id,'table_id'=>$table_id,'in_use'=>$in_use]);
}

// GET /menu?slug=... or ?restaurant_id=...
if($method === 'GET' && $resource === 'menu'){
    $slug = $_GET['slug'] ?? null;
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;
    if($slug){
        $s = $pdo->prepare('SELECT id FROM restaurants WHERE slug = ? LIMIT 1');
        $s->execute([$slug]); $r = $s->fetch();
        if(!$r) return json(['error'=>'restaurant_not_found'],404);
        $restaurant_id = $r['id'];
    }
    if(!$restaurant_id) return json(['error'=>'restaurant_id or slug required'],400);

    // fetch categories
    $cats = $pdo->prepare('SELECT id,name,slug FROM categories WHERE restaurant_id = ? AND active=1 ORDER BY position');
    $cats->execute([$restaurant_id]);
    $categories = $cats->fetchAll();

    // fetch menu items per category, include aggregates
    $itemsStmt = $pdo->prepare('SELECT mi.*, ira.avg_rating, ira.total_count, ira.counts FROM menu_items mi LEFT JOIN item_rating_aggregates ira ON ira.item_id = mi.id WHERE mi.restaurant_id = ? AND mi.available = 1 ORDER BY mi.name');
    $itemsStmt->execute([$restaurant_id]);
    $items = $itemsStmt->fetchAll();

    // Ingredientes de todos os pratos deste restaurante, agrupados por
    // item_id, numa única query (evita N+1 SELECTs dentro do foreach).
    // Sem isto, o modal do prato na página do cliente nunca tinha
    // ingredientes para mostrar — a API simplesmente não os enviava.
    $ingredientsByItem = [];
    if ($items) {
        $itemIds = array_column($items, 'id');
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $ingStmt = $pdo->prepare("SELECT item_id, name FROM ingredients WHERE item_id IN ($placeholders) ORDER BY id");
        $ingStmt->execute($itemIds);
        foreach ($ingStmt->fetchAll() as $row) {
            $ingredientsByItem[(int) $row['item_id']][] = $row['name'];
        }
    }

    // organize
    $byCat = [];
    foreach($categories as $c){ $byCat[$c['id']] = $c; $byCat[$c['id']]['items'] = []; }
    foreach($items as $it){
        $cat = $it['category_id'] ? $it['category_id'] : 0;
        $aggregate = null;
        if($it['avg_rating'] !== null){
            $aggregate = ['avg'=> (float)$it['avg_rating'], 'total'=>(int)$it['total_count'], 'counts'=> json_decode($it['counts'], true) ?: new stdClass()];
        }
        $payload = [
            'id'=>(int)$it['id'], 'name'=>$it['name'], 'slug'=>$it['slug'], 'description'=>$it['description'], 'price'=>(float)$it['price'], 'image'=>$it['image'], 'cook_time'=> $it['cook_time_minutes'], 'rating'=>$aggregate,
            'ingredients'=> $ingredientsByItem[(int) $it['id']] ?? []
        ];
        if($cat && isset($byCat[$cat])) $byCat[$cat]['items'][] = $payload;
        else { // uncategorized
            if(!isset($byCat[0])) $byCat[0] = ['id'=>0,'name'=>'Outros','items'=>[]];
            $byCat[0]['items'][] = $payload;
        }
    }

    json(array_values($byCat));
}

// POST /orders { session_token, items: [{item_id, qty, notes}], notes }
if($method === 'POST' && $resource === 'orders'){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $session = $body['session_token'] ?? null;
    $items = $body['items'] ?? [];
    $notes = $body['notes'] ?? null;
    if(!$items) return json(['error'=>'session_token and items required'],400);

    // Sessão de mesa tem de existir e não pode estar inactiva há mais de
    // TABLE_SESSION_TIMEOUT_MINUTES (ver require_valid_table_session()).
    $visit = require_valid_table_session($pdo, $session);

    // compute total and create order
    $pdo->beginTransaction();
    try{
        $total = 0.0;
        $insOrder = $pdo->prepare('INSERT INTO orders (restaurant_id, table_id, session_token, status, total, notes) VALUES (?,?,?,?,?,?)');
        // temporary total 0, we'll update
        $insOrder->execute([$visit['restaurant_id'], $visit['table_id'], $session, 'submitted', 0.00, $notes]);
        $orderId = $pdo->lastInsertId();

        $insItem = $pdo->prepare('INSERT INTO order_items (order_id, item_id, qty, unit_price, total_price, notes) VALUES (?,?,?,?,?,?)');
        $getPrice = $pdo->prepare('SELECT price FROM menu_items WHERE id = ? LIMIT 1');
        foreach($items as $it){
            $itemId = (int)$it['item_id']; $qty = max(1, (int)($it['qty'] ?? 1)); $itNotes = $it['notes'] ?? null;
            $getPrice->execute([$itemId]); $row = $getPrice->fetch();
            if(!$row) continue;
            $unit = (float)$row['price']; $lineTotal = $unit * $qty; $total += $lineTotal;
            $insItem->execute([$orderId, $itemId, $qty, $unit, $lineTotal, $itNotes]);
        }
        // update order total
        $upd = $pdo->prepare('UPDATE orders SET total = ? WHERE id = ?'); $upd->execute([$total, $orderId]);
        $pdo->commit();
        json(['order_id'=>$orderId,'total'=>$total],201);
    }catch(Exception $e){ $pdo->rollBack(); json(['error'=>'order_failed','details'=>friendly_error($e)],500); }
}

// POST /ratings { item_id, rating, comment }
if($method === 'POST' && $resource === 'ratings'){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $item_id = (int)($body['item_id'] ?? 0);
    $rating = (int)($body['rating'] ?? 0);
    $comment = $body['comment'] ?? null;
    if(!$item_id || $rating < 1 || $rating > 5) return json(['error'=>'item_id and rating(1-5) required'],400);

    try{
        $ins = $pdo->prepare('INSERT INTO ratings (item_id, rating, comment) VALUES (?,?,?)');
        $ins->execute([$item_id, $rating, $comment]);
        // update aggregates: simplistic approach - recompute from ratings table
        $agg = $pdo->prepare('SELECT rating, COUNT(*) as c FROM ratings WHERE item_id = ? GROUP BY rating');
        $agg->execute([$item_id]); $counts = [];$total=0;$sum=0;
        while($r = $agg->fetch()){
            $counts[(string)$r['rating']] = (int)$r['c']; $total += (int)$r['c']; $sum += ((int)$r['rating'])*(int)$r['c'];
        }
        $avg = $total ? round($sum / $total, 2) : 0.00;
        $up = $pdo->prepare('REPLACE INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (?,?,?,?)');
        $up->execute([$item_id, $avg, $total, json_encode($counts, JSON_UNESCAPED_UNICODE)]);
        json(['status'=>'ok','avg'=>$avg,'total'=>$total,'counts'=>$counts]);
    }catch(Exception $e){ json(['error'=>'rating_failed','details'=>friendly_error($e)],500); }
}

// ========== ADMIN ENDPOINTS ==========
//
// Todas as rotas abaixo (/admin/...) exigem sessão autenticada — reaproveita
// a MESMA sessão criada pelo login em admin.php (mesmo domínio, mesmo
// cookie de sessão). Sem isto, qualquer pessoa que soubesse o URL podia
// criar/editar/apagar pratos, categorias e mesas, e ver todos os pedidos e
// avaliações, sem fazer login.
if ($resource === 'admin') {
    session_start();
    $auth = $_SESSION['auth'] ?? null;

    if (!$auth) {
        json(['error' => 'Não autenticado. Faça login em /admin.php.'], 401);
    }

    $adminProfile = (string) ($auth['profile'] ?? '');
    $subResource  = $parts[1] ?? '';

    // 'orders' pode ser gerido por admin, staff e kitchen (é a área de
    // trabalho deles). Tudo o resto (pratos, categorias, mesas, avaliações,
    // métricas) fica reservado ao perfil admin.
    $allowedForOrders = ['admin', 'staff', 'kitchen'];
    $isOrdersRoute    = ($subResource === 'orders');

    if ($isOrdersRoute) {
        if (!in_array($adminProfile, $allowedForOrders, true)) {
            json(['error' => 'Sem permissão para aceder a pedidos.'], 403);
        }
    } else {
        if ($adminProfile !== 'admin') {
            json(['error' => 'Apenas administradores podem aceder a este recurso.'], 403);
        }
    }

    // Nunca confiar no restaurant_id vindo do cliente (query string ou
    // corpo do pedido) — força sempre o restaurante da sessão autenticada,
    // para um utilizador de um restaurante nunca conseguir ler/alterar
    // dados de outro só por trocar o parâmetro na URL.
    $authRestaurantId       = (int) ($auth['restaurant_id'] ?? 0);
    $_GET['restaurant_id']  = (string) $authRestaurantId;
}

// GET /admin/items?restaurant_id=1&category_id=&available=&search=
// (only list when no id segment is present)
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'items' && !isset($parts[2])){
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
    $category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
    $available = isset($_GET['available']) && $_GET['available'] !== '' ? (int)$_GET['available'] : null;
    $search = $_GET['search'] ?? '';

    $sql = 'SELECT mi.*, c.name as category_name, ira.avg_rating, ira.total_count 
            FROM menu_items mi 
            LEFT JOIN categories c ON c.id = mi.category_id 
            LEFT JOIN item_rating_aggregates ira ON ira.item_id = mi.id 
            WHERE mi.restaurant_id = ?';
    $params = [$restaurant_id];

    if($category_id !== null){
        $sql .= ' AND mi.category_id = ?';
        $params[] = $category_id;
    }
    if($available !== null){
        $sql .= ' AND mi.available = ?';
        $params[] = $available;
    }
    if($search !== ''){
        $sql .= ' AND (mi.name LIKE ? OR mi.description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= ' ORDER BY mi.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    $result = [];
    foreach($items as $item){
        $result[] = [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'slug' => $item['slug'],
            'description' => $item['description'],
            'price' => (float)$item['price'],
            'image' => $item['image'],
            'category_id' => (int)$item['category_id'],
            'category_name' => $item['category_name'],
            'available' => (bool)$item['available'],
            'featured' => (bool)$item['featured'],
            'prep_time_minutes' => $item['cook_time_minutes'],
            'avg_rating' => $item['avg_rating'] ? (float)$item['avg_rating'] : null,
            'total_ratings' => $item['total_count'] ? (int)$item['total_count'] : 0
        ];
    }
    json($result);
}

// GET /admin/categories?restaurant_id=1
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'categories'){
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE restaurant_id = ? ORDER BY position, name');
    $stmt->execute([$restaurant_id]);
    json($stmt->fetchAll());
}

// GET /admin/metrics - Aggregated dashboard metrics
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'metrics'){
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
    $date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d');
    try{
        // total items
        $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM menu_items WHERE restaurant_id = ?');
        $stmt->execute([$restaurant_id]);
        $total_items = (int)$stmt->fetchColumn();

        // average rating across items
        $avgStmt = $pdo->prepare('SELECT AVG(r.rating) as avg_rating FROM ratings r JOIN menu_items mi ON mi.id = r.item_id WHERE mi.restaurant_id = ?');
        $avgStmt->execute([$restaurant_id]);
        $avgRow = $avgStmt->fetch();
        $average_rating = $avgRow && $avgRow['avg_rating'] !== null ? round((float)$avgRow['avg_rating'], 2) : 0.00;

        // orders today: count and revenue
        $ordStmt = $pdo->prepare('SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as revenue FROM orders WHERE restaurant_id = ? AND DATE(created_at) = ?');
        $ordStmt->execute([$restaurant_id, $date]);
        $ordRow = $ordStmt->fetch();
        $orders_today = (int)($ordRow['cnt'] ?? 0);
        $revenue_today = (float)($ordRow['revenue'] ?? 0);

        json([
            'total_items' => $total_items,
            'average_rating' => $average_rating,
            'orders_today' => $orders_today,
            'revenue_today' => $revenue_today,
            'date' => $date
        ]);
    }catch(Exception $e){
        json(['error'=>'metrics_failed','details'=>friendly_error($e)],500);
    }
}

// POST /admin/items - Create new item
if($method === 'POST' && $resource === 'admin' && ($parts[1] ?? '') === 'items'){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $restaurant_id = $authRestaurantId;
    $image = $body['image_url'] ?? null;
    $category_id = isset($body['category_id']) && $body['category_id'] ? (int)$body['category_id'] : null;
    $available = isset($body['is_available']) ? (int)(bool)$body['is_available'] : 1;
    $prep_time = isset($body['prep_time_minutes']) ? (int)$body['prep_time_minutes'] : null;

    if(!$name || $price <= 0) return json(['error'=>'name and price required'],400);

    try{
        // Ensure ingredients table exists with required columns
        $pdo->exec('CREATE TABLE IF NOT EXISTS ingredients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            name VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
        )');
        // If table existed without item_id column, try to add it (ignore duplicate errors)
        try { $pdo->exec('ALTER TABLE ingredients ADD COLUMN item_id INT NOT NULL'); } catch(Exception $e) { /* column already exists */ }

        // Ensure featured column exists (add if missing)
        try { $pdo->exec('ALTER TABLE menu_items ADD COLUMN featured INT DEFAULT 0'); } catch(Exception $e) { /* column already exists */ }

        $sql = 'INSERT INTO menu_items (restaurant_id, category_id, name, slug, description, price, image, available, cook_time_minutes) VALUES (?,?,?,?,?,?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$restaurant_id, $category_id, $name, $slug, $description, $price, $image, $available, $prep_time]);
        $itemId = $pdo->lastInsertId();

        // handle ingredients if provided
        $ingredients = $body['ingredients'] ?? '';
        if($ingredients){
            $ingredientsList = array_map('trim', explode(',', $ingredients));
            $insIng = $pdo->prepare('INSERT INTO ingredients (item_id, name) VALUES (?,?)');
            foreach($ingredientsList as $ing){
                if($ing) $insIng->execute([$itemId, $ing]);
            }
        }

        json(['id'=>$itemId, 'message'=>'Item created successfully'], 201);
    }catch(Exception $e){
        json(['error'=>'create_failed','details'=>friendly_error($e)],500);
    }
}

// PUT /admin/items/{id} - Update item
if($method === 'PUT' && $resource === 'admin' && ($parts[1] ?? '') === 'items' && isset($parts[2])){
    $itemId = (int)$parts[2];
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    
    $name = trim($body['name'] ?? '');
    $slug = trim($body['slug'] ?? '');
    $description = $body['description'] ?? null;
    $price = isset($body['price']) ? (float)$body['price'] : null;
    $image = $body['image_url'] ?? null;
    $category_id = isset($body['category_id']) && $body['category_id'] ? (int)$body['category_id'] : null;
    $available = isset($body['is_available']) ? (int)(bool)$body['is_available'] : null;
    $prep_time = isset($body['prep_time_minutes']) ? (int)$body['prep_time_minutes'] : null;

    if(!$name || !$price) return json(['error'=>'name and price required'],400);

    try{
        // Ensure ingredients table exists with required columns
        $pdo->exec('CREATE TABLE IF NOT EXISTS ingredients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            name VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
        )');
        // If table existed without item_id column, try to add it (ignore duplicate errors)
        try { $pdo->exec('ALTER TABLE ingredients ADD COLUMN item_id INT NOT NULL'); } catch(Exception $e) { /* column already exists */ }

        // Ensure featured column exists (add if missing)
        try { $pdo->exec('ALTER TABLE menu_items ADD COLUMN featured INT DEFAULT 0'); } catch(Exception $e) { /* column already exists */ }

        $sql = 'UPDATE menu_items SET name=?, slug=?, description=?, price=?, image=?, category_id=?, available=?, cook_time_minutes=? WHERE id=? AND restaurant_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $slug, $description, $price, $image, $category_id, $available, $prep_time, $itemId, $authRestaurantId]);
        if ($stmt->rowCount() === 0) return json(['error'=>'Prato não encontrado'],404);

        // update ingredients: delete old, insert new
        $pdo->prepare('DELETE FROM ingredients WHERE item_id = ?')->execute([$itemId]);
        $ingredients = $body['ingredients'] ?? '';
        if($ingredients){
            $ingredientsList = array_map('trim', explode(',', $ingredients));
            $insIng = $pdo->prepare('INSERT INTO ingredients (item_id, name) VALUES (?,?)');
            foreach($ingredientsList as $ing){
                if($ing) $insIng->execute([$itemId, $ing]);
            }
        }

        json(['message'=>'Item updated successfully']);
    }catch(Exception $e){
        json(['error'=>'update_failed','details'=>friendly_error($e)],500);
    }
}

// DELETE /admin/items/{id} - Delete item
if($method === 'DELETE' && $resource === 'admin' && ($parts[1] ?? '') === 'items' && isset($parts[2])){
    $itemId = (int)$parts[2];
    try{
        $owner = $pdo->prepare('SELECT id FROM menu_items WHERE id = ? AND restaurant_id = ?');
        $owner->execute([$itemId, $authRestaurantId]);
        if (!$owner->fetch()) return json(['error'=>'Prato não encontrado'],404);

        // Ensure ingredients table exists with required columns
        $pdo->exec('CREATE TABLE IF NOT EXISTS ingredients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            name VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
        )');
        // If table existed without item_id column, try to add it (ignore duplicate errors)
        try { $pdo->exec('ALTER TABLE ingredients ADD COLUMN item_id INT NOT NULL'); } catch(Exception $e) { /* column already exists */ }
        
        // delete ingredients first
        $pdo->prepare('DELETE FROM ingredients WHERE item_id = ?')->execute([$itemId]);
        // delete ratings
        $pdo->prepare('DELETE FROM ratings WHERE item_id = ?')->execute([$itemId]);
        // delete aggregates
        $pdo->prepare('DELETE FROM item_rating_aggregates WHERE item_id = ?')->execute([$itemId]);
        // delete item
        $pdo->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$itemId]);
        json(['message'=>'Item deleted successfully']);
    }catch(Exception $e){
        json(['error'=>'delete_failed','details'=>friendly_error($e)],500);
    }
}

// GET /admin/items/{id} - Get single item with ingredients
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'items' && isset($parts[2])){
    $itemId = (int)$parts[2];
    // Ensure ingredients table exists and has item_id column
    $pdo->exec('CREATE TABLE IF NOT EXISTS ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
    )');
    try { $pdo->exec('ALTER TABLE ingredients ADD COLUMN item_id INT NOT NULL'); } catch(Exception $e) { /* column already exists */ }

    $stmt = $pdo->prepare('SELECT mi.*, c.name as category_name FROM menu_items mi LEFT JOIN categories c ON c.id = mi.category_id WHERE mi.id = ? LIMIT 1');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if(!$item) return json(['error'=>'item_not_found'],404);

    // get ingredients
    $ingStmt = $pdo->prepare('SELECT name FROM ingredients WHERE item_id = ? ORDER BY id');
    $ingStmt->execute([$itemId]);
    $ingredients = $ingStmt->fetchAll(PDO::FETCH_COLUMN);

    $result = [
        'id' => (int)$item['id'],
        'name' => $item['name'],
        'slug' => $item['slug'],
        'description' => $item['description'],
        'price' => (float)$item['price'],
        'image' => $item['image'],
        'category_id' => $item['category_id'] ? (int)$item['category_id'] : null,
        'category_name' => $item['category_name'],
        'available' => (bool)$item['available'],
        'featured' => (bool)$item['featured'],
        'prep_time_minutes' => $item['cook_time_minutes'],
        'ingredients' => implode(', ', $ingredients)
    ];
    json($result);
}

// ========== CATEGORIES ADMIN ENDPOINTS ==========

// POST /admin/categories - Create category
if($method === 'POST' && $resource === 'admin' && ($parts[1] ?? '') === 'categories'){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $restaurant_id = $authRestaurantId;

    if(!$name) return json(['error'=>'name required'],400);

    try{
        $sql = 'INSERT INTO categories (restaurant_id, name, slug, position, active) VALUES (?,?,?,?,?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$restaurant_id, $name, $slug, $position, $active]);
        json(['id'=>$pdo->lastInsertId(), 'message'=>'Category created successfully'], 201);
    }catch(Exception $e){
        json(['error'=>'create_failed','details'=>friendly_error($e)],500);
    }
}

// PUT /admin/categories/{id} - Update category
if($method === 'PUT' && $resource === 'admin' && ($parts[1] ?? '') === 'categories' && isset($parts[2])){
    $categoryId = (int)$parts[2];
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    
    $name = trim($body['name'] ?? '');
    $slug = trim($body['slug'] ?? '');
    $position = isset($body['position']) ? (int)$body['position'] : null;
    $active = isset($body['active']) ? (int)(bool)$body['active'] : null;

    if(!$name) return json(['error'=>'name required'],400);

    try{
        $sql = 'UPDATE categories SET name=?, slug=?, position=?, active=? WHERE id=? AND restaurant_id=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $slug, $position, $active, $categoryId, $authRestaurantId]);
        if ($stmt->rowCount() === 0) return json(['error'=>'Categoria não encontrada'],404);
        json(['message'=>'Category updated successfully']);
    }catch(Exception $e){
        json(['error'=>'update_failed','details'=>friendly_error($e)],500);
    }
}

// DELETE /admin/categories/{id} - Delete category
if($method === 'DELETE' && $resource === 'admin' && ($parts[1] ?? '') === 'categories' && isset($parts[2])){
    $categoryId = (int)$parts[2];
    try{
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ? AND restaurant_id = ?');
        $stmt->execute([$categoryId, $authRestaurantId]);
        if ($stmt->rowCount() === 0) return json(['error'=>'Categoria não encontrada'],404);
        json(['message'=>'Category deleted successfully']);
    }catch(Exception $e){
        json(['error'=>'delete_failed','details'=>friendly_error($e)],500);
    }
}

// ========== TABLES ADMIN ENDPOINTS ==========

// GET /admin/tables - List all tables
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'tables'){
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
    
    $stmt = $pdo->prepare('SELECT * FROM restaurant_tables WHERE restaurant_id = ? ORDER BY number');
    $stmt->execute([$restaurant_id]);
    $tables = $stmt->fetchAll();
    
    json($tables);
}

// POST /admin/tables - Create new table
if($method === 'POST' && $resource === 'admin' && ($parts[1] ?? '') === 'tables'){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $restaurant_id = $authRestaurantId;
    $number = $body['number'] ?? null;
    $description = $body['description'] ?? null;
    $active = isset($body['active']) ? (int)$body['active'] : 1;
    
    if(!$number) return json(['error'=>'number required'],400);
    
    try{
        // Check if table number already exists
        $check = $pdo->prepare('SELECT id FROM restaurant_tables WHERE restaurant_id = ? AND number = ?');
        $check->execute([$restaurant_id, $number]);
        if($check->fetch()) return json(['error'=>'table_number_exists'],400);
        
        // Generate QR code token
        $qr_code = 'QR-' . strtoupper(bin2hex(random_bytes(8)));
        
        $stmt = $pdo->prepare('INSERT INTO restaurant_tables (restaurant_id, number, description, qr_code, active) VALUES (?,?,?,?,?)');
        $stmt->execute([$restaurant_id, $number, $description, $qr_code, $active]);
        $tableId = $pdo->lastInsertId();
        
        json(['id'=>$tableId, 'qr_code'=>$qr_code, 'message'=>'Table created successfully'],201);
    }catch(Exception $e){
        json(['error'=>'create_failed','details'=>friendly_error($e)],500);
    }
}

// PUT /admin/tables/{id} - Update table
if($method === 'PUT' && $resource === 'admin' && ($parts[1] ?? '') === 'tables' && isset($parts[2])){
    $tableId = (int)$parts[2];
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $number = $body['number'] ?? null;
    $description = $body['description'] ?? null;
    $active = isset($body['active']) ? (int)$body['active'] : null;
    
    if(!$number) return json(['error'=>'number required'],400);
    
    try{
        // Check if table number already exists (excluding current table)
        $check = $pdo->prepare('SELECT id FROM restaurant_tables WHERE restaurant_id = ? AND number = ? AND id != ?');
        $check->execute([$authRestaurantId, $number, $tableId]);
        if($check->fetch()) return json(['error'=>'table_number_exists'],400);
        
        $stmt = $pdo->prepare('UPDATE restaurant_tables SET number=?, description=?, active=? WHERE id=? AND restaurant_id=?');
        $stmt->execute([$number, $description, $active, $tableId, $authRestaurantId]);
        if ($stmt->rowCount() === 0) return json(['error'=>'Mesa não encontrada'],404);
        
        json(['message'=>'Table updated successfully']);
    }catch(Exception $e){
        json(['error'=>'update_failed','details'=>friendly_error($e)],500);
    }
}

// DELETE /admin/tables/{id} - Delete table
if($method === 'DELETE' && $resource === 'admin' && ($parts[1] ?? '') === 'tables' && isset($parts[2])){
    $tableId = (int)$parts[2];
    try{
        $stmt = $pdo->prepare('DELETE FROM restaurant_tables WHERE id = ? AND restaurant_id = ?');
        $stmt->execute([$tableId, $authRestaurantId]);
        if ($stmt->rowCount() === 0) return json(['error'=>'Mesa não encontrada'],404);
        json(['message'=>'Table deleted successfully']);
    }catch(Exception $e){
        json(['error'=>'delete_failed','details'=>friendly_error($e)],500);
    }
}

// ========== ORDERS ADMIN ENDPOINTS ==========

// GET /admin/orders/{id} - Get order details with items (MUST BE BEFORE the list endpoint)
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'orders' && isset($parts[2])){
    $orderId = (int)$parts[2];
    $stmt = $pdo->prepare('SELECT o.id, o.restaurant_id, o.table_id, o.session_token, o.status, o.total, o.notes, o.created_at, o.updated_at, rt.number as table_number FROM orders o LEFT JOIN restaurant_tables rt ON rt.id = o.table_id WHERE o.id = ? AND o.restaurant_id = ? LIMIT 1');
    $stmt->execute([$orderId, $authRestaurantId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$order) return json(['error'=>'order_not_found'],404);

    // get items (LEFT JOIN to handle items not in menu)
    $itemsStmt = $pdo->prepare('SELECT oi.id, oi.order_id, oi.item_id, oi.qty, oi.unit_price, oi.total_price, oi.notes, oi.created_at, COALESCE(mi.name, "Item Removido") as name FROM order_items oi LEFT JOIN menu_items mi ON mi.id = oi.item_id WHERE oi.order_id = ?');
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    json([
        'id' => (int)$order['id'],
        'table_number' => $order['table_number'],
        'status' => $order['status'],
        'total' => (float)$order['total'],
        'notes' => $order['notes'],
        'created_at' => $order['created_at'],
        'items' => $items
    ]);
}

// GET /admin/orders - List orders with filters
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'orders' && !isset($parts[2])){
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
    $status = $_GET['status'] ?? '';
    $date = $_GET['date'] ?? '';

    $sql = 'SELECT o.id, o.restaurant_id, o.table_id, o.session_token, o.status, o.total, o.notes, o.created_at, o.updated_at, rt.number as table_number FROM orders o LEFT JOIN restaurant_tables rt ON rt.id = o.table_id WHERE o.restaurant_id = ?';
    $params = [$restaurant_id];

    if($status !== ''){
        $sql .= ' AND o.status = ?';
        $params[] = $status;
    }
    if($date !== ''){
        $sql .= ' AND DATE(o.created_at) = ?';
        $params[] = $date;
    }
    $sql .= ' ORDER BY o.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach($orders as $order){
        $result[] = [
            'id' => (int)$order['id'],
            'table_number' => $order['table_number'],
            'status' => $order['status'],
            'total' => (float)$order['total'],
            'notes' => $order['notes'],
            'created_at' => $order['created_at'],
            'item_count' => (int)$order['id'] // placeholder, will count items
        ];
    }
    json($result);
}

// PUT /admin/orders/{id} - Update order status
if($method === 'PUT' && $resource === 'admin' && ($parts[1] ?? '') === 'orders' && isset($parts[2])){
    $orderId = (int)$parts[2];
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $status = $body['status'] ?? null;

    if(!$status) return json(['error'=>'status required'],400);

    // Normalize UI statuses to DB enum
    $map = [
        'ready' => 'served',
        'completed' => 'paid'
    ];
    if(isset($map[$status])){ $status = $map[$status]; }

    $allowed = ['open','submitted','preparing','served','paid','cancelled'];
    if(!in_array($status, $allowed, true)){
        return json(['error'=>'invalid_status','allowed'=>$allowed],400);
    }

    try{
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ? AND restaurant_id = ?');
        $stmt->execute([$status, $orderId, $authRestaurantId]);
        if ($stmt->rowCount() === 0) return json(['error'=>'Pedido não encontrado'],404);
        json(['message'=>'Order status updated','status'=>$status]);
    }catch(Exception $e){
        json(['error'=>'update_failed','details'=>friendly_error($e)],500);
    }
}

// ========== RATINGS ADMIN ENDPOINTS ==========

// GET /admin/ratings - List all ratings
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'ratings'){
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;
    $rating = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int)$_GET['rating'] : null;
    $search = $_GET['search'] ?? '';

    $sql = 'SELECT r.*, mi.name as item_name FROM ratings r JOIN menu_items mi ON mi.id = r.item_id WHERE mi.restaurant_id = ?';
    $params = [$restaurant_id];

    if($rating !== null){
        $sql .= ' AND r.rating = ?';
        $params[] = $rating;
    }
    if($search !== ''){
        $sql .= ' AND mi.name LIKE ?';
        $params[] = "%$search%";
    }
    $sql .= ' ORDER BY r.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ratings = $stmt->fetchAll();

    $result = [];
    foreach($ratings as $rat){
        $result[] = [
            'id' => (int)$rat['id'],
            'item_id' => (int)$rat['item_id'],
            'item_name' => $rat['item_name'],
            'rating' => (int)$rat['rating'],
            'comment' => $rat['comment'],
            'created_at' => $rat['created_at']
        ];
    }
    json($result);
}

// GET /admin/ratings/stats - Get rating statistics
if($method === 'GET' && $resource === 'admin' && ($parts[1] ?? '') === 'ratings' && ($parts[2] ?? '') === 'stats'){
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 1;

    $sql = 'SELECT r.rating, COUNT(*) as count FROM ratings r 
            JOIN menu_items mi ON mi.id = r.item_id 
            WHERE mi.restaurant_id = ? 
            GROUP BY r.rating';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$restaurant_id]);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $total = array_sum($counts);
    $avg = 0;
    if($total > 0){
        $sumStmt = $pdo->prepare('SELECT SUM(r.rating) as sum FROM ratings r 
                                  JOIN menu_items mi ON mi.id = r.item_id 
                                  WHERE mi.restaurant_id = ?');
        $sumStmt->execute([$restaurant_id]);
        $sumRow = $sumStmt->fetch();
        $avg = round($sumRow['sum'] / $total, 2);
    }

    json([
        'average' => $avg,
        'total' => $total,
        'by_rating' => [
            '5' => (int)($counts[5] ?? 0),
            '4' => (int)($counts[4] ?? 0),
            '3' => (int)($counts[3] ?? 0),
            '2' => (int)($counts[2] ?? 0),
            '1' => (int)($counts[1] ?? 0)
        ]
    ]);
}

// DELETE /admin/ratings/{id} - Delete rating
if($method === 'DELETE' && $resource === 'admin' && ($parts[1] ?? '') === 'ratings' && isset($parts[2])){
    $ratingId = (int)$parts[2];
    try{
        // get item_id first (só se pertencer a um prato do restaurante autenticado)
        $ratStmt = $pdo->prepare('SELECT r.item_id FROM ratings r JOIN menu_items mi ON mi.id = r.item_id WHERE r.id = ? AND mi.restaurant_id = ? LIMIT 1');
        $ratStmt->execute([$ratingId, $authRestaurantId]);
        $rat = $ratStmt->fetch();
        if(!$rat) return json(['error'=>'rating_not_found'],404);

        // delete rating
        $pdo->prepare('DELETE FROM ratings WHERE id = ?')->execute([$ratingId]);
        
        // recompute aggregates for that item
        $agg = $pdo->prepare('SELECT rating, COUNT(*) as c FROM ratings WHERE item_id = ? GROUP BY rating');
        $agg->execute([$rat['item_id']]); $counts = [];$total=0;$sum=0;
        while($r = $agg->fetch()){
            $counts[(string)$r['rating']] = (int)$r['c']; $total += (int)$r['c']; $sum += ((int)$r['rating'])*(int)$r['c'];
        }
        $avg = $total ? round($sum / $total, 2) : 0.00;
        $up = $pdo->prepare('REPLACE INTO item_rating_aggregates (item_id, avg_rating, total_count, counts) VALUES (?,?,?,?)');
        $up->execute([$rat['item_id'], $avg, $total, json_encode($counts, JSON_UNESCAPED_UNICODE)]);

        json(['message'=>'Rating deleted successfully']);
    }catch(Exception $e){
        json(['error'=>'delete_failed','details'=>friendly_error($e)],500);
    }
}

// ========== CHECKOUT ENDPOINT ==========
// POST /checkout - Process cart checkout and create order
// Body: { cart: [{id, title, price, priceValue, img, qty}, ...], tableNumber (optional), restaurantSlug (optional) }
if($method === 'POST' && $resource === 'checkout'){
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $cart = $body['cart'] ?? [];

    if(empty($cart)) return json(['error'=>'cart_empty'],400);

    // O pedido só pode ser finalizado se o cliente tiver uma sessão de mesa
    // válida (criada via QR code da mesa ou por introdução manual do código
    // da mesa em /visits) e essa sessão não pode estar inactiva há mais de
    // TABLE_SESSION_TIMEOUT_MINUTES. O restaurante e a mesa vêm SEMPRE da
    // sessão validada no servidor — nunca do que o cliente enviar no corpo
    // do pedido, para não ser possível finalizar um pedido "para a mesa 1"
    // só porque é o valor por omissão.
    $sessionToken = $body['session_token'] ?? null;
    $visit = require_valid_table_session($pdo, $sessionToken);
    $restaurant_id = (int) $visit['restaurant_id'];
    $table_id      = $visit['table_id'] !== null ? (int) $visit['table_id'] : null;
    $session_token = $visit['session_token'];

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Create order
        $total = 0.0;
        $orderStmt = $pdo->prepare('INSERT INTO orders (restaurant_id, table_id, session_token, status, total) VALUES (?,?,?,?,?)');
        $orderStmt->execute([$restaurant_id, $table_id, $session_token, 'submitted', 0.00]);
        $orderId = $pdo->lastInsertId();

        // Insert order items
        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, item_id, qty, unit_price, total_price) VALUES (?,?,?,?,?)');
        
        foreach($cart as $cartItem) {
            $item_id = (int)($cartItem['id'] ?? 0);
            $qty = max(1, (int)($cartItem['qty'] ?? 1));
            
            // Try to get price from database, fallback to cart price
            $priceStmt = $pdo->prepare('SELECT id, price FROM menu_items WHERE id = ? LIMIT 1');
            $priceStmt->execute([$item_id]);
            $menuItem = $priceStmt->fetch();
            
            if(!$menuItem) {
                // Item not in database, use cart price
                $unit_price = isset($cartItem['priceValue']) ? (float)$cartItem['priceValue'] : 0.0;
                if($unit_price == 0 && isset($cartItem['price'])) {
                    // Parse price string like "AO 25,00"
                    $priceStr = $cartItem['price'];
                    $priceStr = preg_replace('/[^0-9,.]/', '', $priceStr);
                    $priceStr = str_replace('.', '', $priceStr);
                    $priceStr = str_replace(',', '.', $priceStr);
                    $unit_price = (float)$priceStr;
                }
            } else {
                $unit_price = (float)$menuItem['price'];
            }
            
            $lineTotal = $unit_price * $qty;
            $total += $lineTotal;
            
            $itemStmt->execute([$orderId, $item_id, $qty, $unit_price, $lineTotal]);
        }

        // Update order total
        $updateTotalStmt = $pdo->prepare('UPDATE orders SET total = ? WHERE id = ?');
        $updateTotalStmt->execute([$total, $orderId]);

        $pdo->commit();

        json(['success'=>true, 'order_id'=>$orderId, 'total'=>$total, 'message'=>'Pedido criado com sucesso!'],201);
    } catch(Exception $e) {
        $pdo->rollBack();
        json(['error'=>'checkout_failed','details'=>friendly_error($e)],500);
    }
}

// default
json(['error'=>'unknown_endpoint','path'=>$path,'method'=>$method],404);
