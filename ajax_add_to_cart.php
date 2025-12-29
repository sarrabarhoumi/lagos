<?php
include 'connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(["success"=>false, "message"=>"Requête invalide"]);
  exit;
}

// ✅ CSRF
$csrf = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
  http_response_code(403);
  echo json_encode(["success"=>false, "message"=>"CSRF invalide"]);
  exit;
}

// ✅ user connecté ou guest
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (empty($_SESSION['guest_token'])) {
  $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}
$guestToken = $_SESSION['guest_token'];

// ✅ inputs
$productId = (int)($input['product_id'] ?? 0);
$color = trim((string)($input['color'] ?? ''));
$size  = trim((string)($input['size'] ?? ''));
$qty   = (int)($input['qty'] ?? 0);

if ($productId <= 0 || $color === '' || $size === '' || $qty <= 0) {
  http_response_code(400);
  echo json_encode(["success"=>false, "message"=>"Données manquantes"]);
  exit;
}

/**
 * ✅ Helper: split tailles ("s,m,l") => ["s","m","l"]
 */
function splitSizes($raw) {
  $raw = trim((string)$raw);
  if ($raw === '') return [];
  $parts = preg_split('/\s*,\s*/', $raw);
  $parts = array_filter(array_map('trim', $parts), fn($x) => $x !== '');
  return array_values($parts);
}

/**
 * ✅ Récupérer variations JSON
 */
$sql = "SELECT details FROM details_produit WHERE id_produit = ? LIMIT 1";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
  http_response_code(404);
  echo json_encode(["success"=>false, "message"=>"Variations introuvables"]);
  exit;
}

$row = mysqli_fetch_assoc($res);
$details = json_decode($row['details'] ?? '[]', true);
if (!is_array($details)) $details = [];

/**
 * ✅ Construire stockMap[color][size] = qty
 * (supporte taille "m" OU "s,m,l")
 */
$stockMap = [];
foreach ($details as $v) {
  $c = trim((string)($v['couleur'] ?? ''));
  $t = (string)($v['taille'] ?? '');
  $q = (int)($v['quantite'] ?? 0);

  if ($c === '' || trim($t) === '') continue;

  $sizes = splitSizes($t); // ✅ split ici
  if (empty($sizes)) continue;

  foreach ($sizes as $oneSize) {
    if (!isset($stockMap[$c])) $stockMap[$c] = [];
    // si doublon, additionner
    $stockMap[$c][$oneSize] = ($stockMap[$c][$oneSize] ?? 0) + $q;
  }
}

$stock = $stockMap[$color][$size] ?? null;

if ($stock === null) {
  http_response_code(404);
  echo json_encode(["success"=>false, "message"=>"Variante invalide"]);
  exit;
}

if ($stock <= 0) {
  http_response_code(409);
  echo json_encode(["success"=>false, "message"=>"Variante en rupture"]);
  exit;
}

if ($qty > $stock) {
  http_response_code(409);
  echo json_encode(["success"=>false, "message"=>"Stock insuffisant"]);
  exit;
}

/**
 * ✅ Enregistrer panier
 * TABLE: panier (id_user, guest_token, id_produit, couleur, taille, quantite, date_ajout)
 */
if ($userId) {

  $sqlCheck = "SELECT id, quantite FROM panier
               WHERE user_id=? AND id_produit=? AND couleur=? AND taille=? LIMIT 1";
  $stmt2 = mysqli_prepare($con, $sqlCheck);
  mysqli_stmt_bind_param($stmt2, "iiss", $userId, $productId, $color, $size);
  mysqli_stmt_execute($stmt2);
  $res2 = mysqli_stmt_get_result($stmt2);

  if ($res2 && mysqli_num_rows($res2) > 0) {
    $p = mysqli_fetch_assoc($res2);
    $newQty = (int)$p['quantite'] + $qty;

    if ($newQty > $stock) {
      http_response_code(409);
      echo json_encode(["success"=>false, "message"=>"Quantité totale dépasse le stock"]);
      exit;
    }

    $sqlUp = "UPDATE panier SET quantite=? WHERE id=?";
    $stmtUp = mysqli_prepare($con, $sqlUp);
    mysqli_stmt_bind_param($stmtUp, "ii", $newQty, $p['id']);
    mysqli_stmt_execute($stmtUp);

  } else {
    $sqlIns = "INSERT INTO panier (user_id, guest_token, id_produit, couleur, taille, quantite, date_ajout)
               VALUES (?, NULL, ?, ?, ?, ?, NOW())";
    $stmt3 = mysqli_prepare($con, $sqlIns);
    mysqli_stmt_bind_param($stmt3, "iissi", $userId, $productId, $color, $size, $qty);
    mysqli_stmt_execute($stmt3);
  }

} else {

  $sqlCheck = "SELECT id, quantite FROM panier
               WHERE guest_token=? AND id_produit=? AND couleur=? AND taille=? LIMIT 1";
  $stmt2 = mysqli_prepare($con, $sqlCheck);
  mysqli_stmt_bind_param($stmt2, "siss", $guestToken, $productId, $color, $size);
  mysqli_stmt_execute($stmt2);
  $res2 = mysqli_stmt_get_result($stmt2);

  if ($res2 && mysqli_num_rows($res2) > 0) {
    $p = mysqli_fetch_assoc($res2);
    $newQty = (int)$p['quantite'] + $qty;

    if ($newQty > $stock) {
      http_response_code(409);
      echo json_encode(["success"=>false, "message"=>"Quantité totale dépasse le stock"]);
      exit;
    }

    $sqlUp = "UPDATE panier SET quantite=? WHERE id=?";
    $stmtUp = mysqli_prepare($con, $sqlUp);
    mysqli_stmt_bind_param($stmtUp, "ii", $newQty, $p['id']);
    mysqli_stmt_execute($stmtUp);

  } else {
    $sqlIns = "INSERT INTO panier (id_user, guest_token, id_produit, couleur, taille, quantite, date_ajout)
               VALUES (NULL, ?, ?, ?, ?, ?, NOW())";
    $stmt3 = mysqli_prepare($con, $sqlIns);
    mysqli_stmt_bind_param($stmt3, "sissi", $guestToken, $productId, $color, $size, $qty);
    mysqli_stmt_execute($stmt3);
  }
}

/**
 * ✅ Cart count badge
 */
if ($userId) {
  $q = mysqli_prepare($con, "SELECT COALESCE(SUM(quantite),0) as c FROM panier WHERE user_id=?");
  mysqli_stmt_bind_param($q, "i", $userId);
} else {
  $q = mysqli_prepare($con, "SELECT COALESCE(SUM(quantite),0) as c FROM panier WHERE guest_token=?");
  mysqli_stmt_bind_param($q, "s", $guestToken);
}

mysqli_stmt_execute($q);
$rq = mysqli_stmt_get_result($q);
$cartCount = (int)(mysqli_fetch_assoc($rq)['c'] ?? 0);

echo json_encode(["success"=>true, "message"=>"Ajouté", "cart_count"=>$cartCount]);
exit;
