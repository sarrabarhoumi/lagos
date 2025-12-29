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

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (empty($_SESSION['guest_token'])) {
  $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}
$guestToken = $_SESSION['guest_token'];
function hexToRgb($hex)
{
  $hex = str_replace('#', '', $hex);

  if (strlen($hex) === 3) {
    $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
  }

  return [
    'r' => hexdec(substr($hex, 0, 2)),
    'g' => hexdec(substr($hex, 2, 2)),
    'b' => hexdec(substr($hex, 4, 2)),
  ];
}

function colorFamilyFromHex($hex)
{
  if (!$hex) return 'Inconnu';

  $rgb = hexToRgb($hex);

  // 🎨 Couleurs mères (palette de référence)
  $families = [
    'Noir'   => [0, 0, 0],
    'Blanc'  => [255, 255, 255],
    'Gris'   => [128, 128, 128],
    'Rouge'  => [220, 20, 60],
    'Bordeaux' => [128, 0, 32],
    'Rose'   => [255, 105, 180],
    'Orange' => [255, 140, 0],
    'Jaune'  => [255, 215, 0],
    'Vert'   => [34, 139, 34],
    'Vert clair' => [144, 238, 144],
    'Bleu'   => [30, 144, 255],
    'Bleu foncé' => [0, 0, 139],
    'Violet' => [138, 43, 226],
    'Marron' => [139, 69, 19],
    'Beige'  => [245, 245, 220],
  ];

  $closest = 'Autre';
  $minDistance = PHP_INT_MAX;

  foreach ($families as $name => $ref) {
    $distance = pow($rgb['r'] - $ref[0], 2)
      + pow($rgb['g'] - $ref[1], 2)
      + pow($rgb['b'] - $ref[2], 2);

    if ($distance < $minDistance) {
      $minDistance = $distance;
      $closest = $name;
    }
  }

  return $closest;
}
if ($userId) {
  $sql = "SELECT pa.id as cart_id, pa.id_produit, pa.couleur, pa.taille, pa.quantite,
                 pr.nom, pr.prix, pr.image_principale
          FROM panier pa
          JOIN produit pr ON pr.id = pa.id_produit
          WHERE pa.user_id = ?
          ORDER BY pa.date_ajout DESC";
  $st = mysqli_prepare($con, $sql);
  mysqli_stmt_bind_param($st, "i", $userId);
} else {
  $sql = "SELECT pa.id as cart_id, pa.id_produit, pa.couleur, pa.taille, pa.quantite,
                 pr.nom, pr.prix, pr.image_principale
          FROM panier pa
          JOIN produit pr ON pr.id = pa.id_produit
          WHERE pa.guest_token = ?
          ORDER BY pa.date_ajout DESC";
  $st = mysqli_prepare($con, $sql);
  mysqli_stmt_bind_param($st, "s", $guestToken);
}

mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);

$items = [];
$subtotal = 0.0;
$cartCount = 0;

while ($row = mysqli_fetch_assoc($res)) {
  $qty = (int)$row['quantite'];
  $price = (float)$row['prix'];
  $line = $qty * $price;

  $subtotal += $line;
  $cartCount += $qty;

  $items[] = [
    "cart_id" => (int)$row['cart_id'],
    "product_id" => (int)$row['id_produit'],
    "nom" => $row['nom'],
    "image" => $row['image_principale'],
    "couleur" => colorFamilyFromHex($row['couleur']),
    "taille" => $row['taille'],
    "quantite" => $qty,
    "prix" => $price,
    "line_total" => $line
  ];
}

echo json_encode([
  "success" => true,
  "items" => $items,
  "subtotal" => $subtotal,
  "cart_count" => $cartCount
]);
