<?php
// Simple API front controller for cardapio digital
// Routes:
// POST /public/api/index.php/visits        -> create visit (qr_token)
// GET  /public/api/index.php/menu         -> list menu by restaurant (query: slug or restaurant_id)
// POST /public/api/index.php/orders       -> create order
// POST /public/api/index.php/ratings      -> submit rating

require __DIR__ . '/../../config/db.php';
$config = require __DIR__ . '/../../config/db.php';
try{
    $pdo = new PDO($config['dsn'], $config['user'], $config['pass'], $config['options']);
}catch(PDOException $e){
    http_response_code(500);
    echo json_encode(['error'=>'DB connection failed','details'=>$e->getMessage()]);
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

// POST /visits { qr_token } OR { table_number, restaurant_slug }
if($method === 'POST' && $resource === 'visits'){
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
            'id'=>(int)$it['id'], 'name'=>$it['name'], 'slug'=>$it['slug'], 'description'=>$it['description'], 'price'=>(float)$it['price'], 'image'=>$it['image'], 'cook_time'=> $it['cook_time_minutes'], 'rating'=>$aggregate
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
    if(!$session || !$items) return json(['error'=>'session_token and items required'],400);

    // find visit to get restaurant/table
    $stmt = $pdo->prepare('SELECT restaurant_id, table_id FROM visits WHERE session_token = ? LIMIT 1');
    $stmt->execute([$session]); $visit = $stmt->fetch();
    if(!$visit) return json(['error'=>'invalid_session'],404);

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
    }catch(Exception $e){ $pdo->rollBack(); json(['error'=>'order_failed','details'=>$e->getMessage()],500); }
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
    }catch(Exception $e){ json(['error'=>'rating_failed','details'=>$e->getMessage()],500); }
}

// default
json(['error'=>'unknown_endpoint','path'=>$path,'method'=>$method],404);
