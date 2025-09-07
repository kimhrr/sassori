<?php
// File: api/health.php
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

$rows = $pdo->query("
  SELECT device_id, MAX(ts) last_seen
  FROM telemetry GROUP BY device_id
")->fetchAll(PDO::FETCH_ASSOC);

$now = time();
foreach ($rows as &$r) {
  $ls = strtotime($r['last_seen']);
  $diff = $now - $ls;
  $r['status'] = $diff < 60 ? 'online' : ($diff < 600 ? 'idle' : 'offline');
}
echo json_encode($rows);
