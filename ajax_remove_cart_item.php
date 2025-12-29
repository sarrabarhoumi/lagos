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
if ($cartId <= 0) {
  http_response_code(400);
  echo json_encode(["success"=>false,"message"=>"Cart ID invalide"]);
  exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (empty($_SESSION['guest_token'])) $_SESSION['guest_token'] = bin2hex(random_bytes(16));
$guestToken = $_SESSION['guest_token'];

if ($userId) {
  $st = mysqli_prepare($con, "DELETE FROM panier WHERE id=? AND user_id=?");
  mysqli_stmt_bind_param($st, "ii", $cartId, $userId);
} else {
  $st = mysqli_prepare($con, "DELETE FROM panier WHERE id=? AND guest_token=?");
  mysqli_stmt_bind_param($st, "is", $cartId, $guestToken);
}

mysqli_stmt_execute($st);

echo json_encode(["success"=>true,"message"=>"Supprimé"]);
