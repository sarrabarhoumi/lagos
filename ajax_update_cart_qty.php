<?php
include 'connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(["success"=>false,"message"=>"Requête invalide"]);
  exit;
}

$csrf = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
  http_response_code(403);
  echo json_encode(["success"=>false,"message"=>"CSRF invalide"]);
  exit;
}

$cartId = (int)($input['cart_id'] ?? 0);
$delta = isset($input['delta']) ? (int)$input['delta'] : null;
$setQty = isset($input['set_qty']) ? (int)$input['set_qty'] : null;

if ($cartId <= 0) {
  http_response_code(400);
  echo json_encode(["success"=>false,"message"=>"Cart ID invalide"]);
  exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (empty($_SESSION['guest_token'])) $_SESSION['guest_token'] = bin2hex(random_bytes(16));
$guestToken = $_SESSION['guest_token'];

// 1) Charger l’item + vérifier propriétaire
if ($userId) {
  $st = mysqli_prepare($con, "SELECT * FROM panier WHERE id=? AND user_id=? LIMIT 1");
  mysqli_stmt_bind_param($st, "ii", $cartId, $userId);
} else {
  $st = mysqli_prepare($con, "SELECT * FROM panier WHERE id=? AND guest_token=? LIMIT 1");
  mysqli_stmt_bind_param($st, "is", $cartId, $guestToken);
}
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
if (!$res || mysqli_num_rows($res) === 0) {
  http_response_code(404);
  echo json_encode(["success"=>false,"message"=>"Article introuvable"]);
  exit;
}
$item = mysqli_fetch_assoc($res);

$currentQty = (int)$item['quantite'];
$newQty = $currentQty;

if ($setQty !== null) {
  $newQty = $setQty;
} elseif ($delta !== null) {
  $newQty = $currentQty + $delta;
}

if ($newQty < 1) $newQty = 1;

$productId = (int)$item['id_produit'];
$color = (string)$item['couleur'];
$size  = (string)$item['taille'];

// 2) Vérifier stock JSON
$st2 = mysqli_prepare($con, "SELECT details FROM details_produit WHERE id_produit=? LIMIT 1");
mysqli_stmt_bind_param($st2, "i", $productId);
mysqli_stmt_execute($st2);
$r2 = mysqli_stmt_get_result($st2);

if (!$r2 || mysqli_num_rows($r2) === 0) {
  http_response_code(409);
  echo json_encode(["success"=>false,"message"=>"Variations introuvables"]);
  exit;
}

$row = mysqli_fetch_assoc($r2);
$details = json_decode($row['details'] ?? '[]', true);
if (!is_array($details)) $details = [];

$stock = null;
foreach ($details as $v) {
  if (($v['couleur'] ?? '') === $color && ($v['taille'] ?? '') === $size) {
    $stock = (int)($v['quantite'] ?? 0);
    break;
  }
}
if ($stock === null) {
  http_response_code(409);
  echo json_encode(["success"=>false,"message"=>"Variante invalide"]);
  exit;
}
if ($newQty > $stock) {
  http_response_code(409);
  echo json_encode(["success"=>false,"message"=>"Stock insuffisant (max: $stock)"]);
  exit;
}

// 3) Update qty
$st3 = mysqli_prepare($con, "UPDATE panier SET quantite=? WHERE id=?");
mysqli_stmt_bind_param($st3, "ii", $newQty, $cartId);
mysqli_stmt_execute($st3);

echo json_encode(["success"=>true,"message"=>"Quantité mise à jour"]);
