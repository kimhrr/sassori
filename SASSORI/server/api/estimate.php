<?php
// File: api/estimate.php
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

$device = $_GET['device_id'] ?? '';
if ($device === '') { http_response_code(400); echo json_encode(['error'=>'missing device_id']); exit; }

$q = $pdo->prepare("SELECT AVG(current) avgA FROM telemetry WHERE device_id=? AND DATE(ts)=CURDATE()");
$q->execute([$device]);
$avgA = (float)($q->fetch(PDO::FETCH_ASSOC)['avgA'] ?? 0);

// Assume 230V, power factor ~0.9
$watts = $avgA * 230 * 0.9;
$kWh_per_day = ($watts / 1000.0) * 24;
$tarrif = 0.505; // RM/kWh example rate
$rm_per_month = $kWh_per_day * 30 * $tarrif;

echo json_encode(['avgA'=>$avgA, 'est_rm_month'=>round($rm_per_month,2)]);
