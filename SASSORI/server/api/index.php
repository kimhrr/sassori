<?php
// File: api/index.php
header('Content-Type: application/json');

try {
  $pdo = new PDO('mysql:host=localhost;dbname=smartcampus;charset=utf8','dbuser','dbpass', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error'=>'db_connect_failed','detail'=>$e->getMessage()]);
  exit;
}

$path = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

function body() {
  $raw = file_get_contents('php://input');
  $b = json_decode($raw, true);
  if ($b === null && json_last_error() !== JSON_ERROR_NONE) {
    return [];
  }
  return $b;
}

// POST /api/telemetry
if (preg_match('#/api/telemetry#', $path) && $method === 'POST') {
  $b = body();
  if (!isset($b['device_id'])) { http_response_code(400); echo json_encode(['error'=>'missing device_id']); exit; }
  $stmt = $pdo->prepare("INSERT INTO telemetry(device_id,temp,hum,occupancy,current) VALUES(?,?,?,?,?)");
  $stmt->execute([
    $b['device_id'],
    $b['temp'] ?? null, $b['hum'] ?? null,
    $b['occupancy'] ?? 0, $b['current'] ?? null
  ]);
  echo json_encode(['ok'=>true]); exit;
}

// GET/POST /api/commands
if (preg_match('#/api/commands#', $path)) {
  if ($method === 'GET') {
    $device = $_GET['device_id'] ?? '';
    $stmt = $pdo->prepare("SELECT light,aircond,curtain FROM commands WHERE device_id=?");
    $stmt->execute([$device]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $row = ['light'=>'off','aircond'=>'off','curtain'=>'stop']; }
    echo json_encode($row); exit;
  }
  if ($method === 'POST') {
    $b = body();
    if (!isset($b['device_id'])) { http_response_code(400); echo json_encode(['error'=>'missing device_id']); exit; }
    $stmt = $pdo->prepare("INSERT INTO commands(device_id,light,aircond,curtain) VALUES(?,?,?,?)
      ON DUPLICATE KEY UPDATE light=VALUES(light), aircond=VALUES(aircond), curtain=VALUES(curtain)");
    $stmt->execute([$b['device_id'], $b['light'] ?? 'off', $b['aircond'] ?? 'off', $b['curtain'] ?? 'stop']);
    echo json_encode(['ok'=>true]); exit;
  }
}

// GET /api/floor (latest per device)
if (preg_match('#/api/floor#', $path) && $method === 'GET') {
  $q = $pdo->query("
    SELECT t1.* FROM telemetry t1
    INNER JOIN (
      SELECT device_id, MAX(ts) ts FROM telemetry GROUP BY device_id
    ) t2 ON t1.device_id=t2.device_id AND t1.ts=t2.ts
    ORDER BY t1.device_id
  ");
  echo json_encode($q->fetchAll(PDO::FETCH_ASSOC)); exit;
}

// POST /api/gateway (passthrough from gateway)
if (preg_match('#/api/gateway#', $path) && $method === 'POST') {
  $b = body();
  if (!isset($b['device_id'])) { http_response_code(400); echo json_encode(['error'=>'missing device_id']); exit; }
  $stmt = $pdo->prepare("INSERT INTO telemetry(device_id,temp,hum,occupancy,current) VALUES(?,?,?,?,?)");
  $stmt->execute([$b['device_id'],$b['temp'] ?? null,$b['hum'] ?? null,$b['occupancy'] ?? 0,$b['current'] ?? null]);
  echo json_encode(['ok'=>true]); exit;
}

http_response_code(404);
echo json_encode(['error'=>'not found']);
