<?php
include '../connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// =======================
// Helpers
// =======================
function json_out($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr);
  exit;
}

function require_csrf() {
  $csrf = $_REQUEST['csrf_token'] ?? '';
  if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    json_out(["status"=>"error","message"=>"CSRF invalide"], 403);
  }
}

// =======================
// Identify cart owner
// =======================
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (empty($_SESSION['guest_token'])) {
  $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}
$guestToken = $_SESSION['guest_token'];

// =======================
// GET: cart count
// =======================
if (isset($_GET['getCartCount'])) {
  require_csrf();

  if ($userId) {
    $st = mysqli_prepare($con, "SELECT COALESCE(SUM(quantite),0) c FROM panier WHERE user_id=?");
    mysqli_stmt_bind_param($st, "i", $userId);
  } else {
    $st = mysqli_prepare($con, "SELECT COALESCE(SUM(quantite),0) c FROM panier WHERE guest_token=?");
    mysqli_stmt_bind_param($st, "s", $guestToken);
  }

  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);
  $count = (int)(mysqli_fetch_assoc($res)['c'] ?? 0);

  json_out(["status"=>"success","cart_count"=>$count]);
}

// =======================
// GET: cart summary
// =======================
if (isset($_GET['getCartSummary'])) {
  require_csrf();

  if ($userId) {
    $sql = "SELECT COALESCE(SUM(p.quantite * pr.prix),0) subtotal
            FROM panier p
            JOIN produit pr ON pr.id = p.id_produit
            WHERE p.user_id=?";
    $st = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($st, "i", $userId);
  } else {
    $sql = "SELECT COALESCE(SUM(p.quantite * pr.prix),0) subtotal
            FROM panier p
            JOIN produit pr ON pr.id = p.id_produit
            WHERE p.guest_token=?";
    $st = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($st, "s", $guestToken);
  }

  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);
  $row = mysqli_fetch_assoc($res);

  $subtotal = (float)($row['subtotal'] ?? 0);
  $shipping = 7.000;
  $total = $subtotal + $shipping;

  json_out([
    "status"=>"success",
    "subtotal"=>$subtotal,
    "shipping"=>$shipping,
    "total"=>$total
  ]);
}

// =======================
// POST: DataTable cart (getPanier)
// =======================
if (isset($_POST['getPanier'])) {
  require_csrf();

  $draw  = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
  $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
  $len   = isset($_POST['length']) ? (int)$_POST['length'] : 10;

  // Total records
  if ($userId) {
    $stCount = mysqli_prepare($con, "SELECT COUNT(*) c FROM panier WHERE user_id=?");
    mysqli_stmt_bind_param($stCount, "i", $userId);
  } else {
    $stCount = mysqli_prepare($con, "SELECT COUNT(*) c FROM panier WHERE guest_token=?");
    mysqli_stmt_bind_param($stCount, "s", $guestToken);
  }
  mysqli_stmt_execute($stCount);
  $rc = mysqli_stmt_get_result($stCount);
  $recordsTotal = (int)(mysqli_fetch_assoc($rc)['c'] ?? 0);
  $recordsFiltered = $recordsTotal;

  // Data query
  if ($userId) {
    $sql = "SELECT p.id as cart_id, p.id_produit, p.couleur, p.taille, p.quantite,
                   pr.nom, pr.prix, pr.image_principale
            FROM panier p
            JOIN produit pr ON pr.id = p.id_produit
            WHERE p.user_id=?
            ORDER BY p.date_ajout DESC
            LIMIT ? OFFSET ?";
    $st = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($st, "iii", $userId, $len, $start);
  } else {
    $sql = "SELECT p.id as cart_id, p.id_produit, p.couleur, p.taille, p.quantite,
                   pr.nom, pr.prix, pr.image_principale
            FROM panier p
            JOIN produit pr ON pr.id = p.id_produit
            WHERE p.guest_token=?
            ORDER BY p.date_ajout DESC
            LIMIT ? OFFSET ?";
    $st = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($st, "sii", $guestToken, $len, $start);
  }

  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);

  $data = [];
  while ($row = mysqli_fetch_assoc($res)) {
    $cartId = (int)$row['cart_id'];
    $qty = (int)$row['quantite'];
    $prix = (float)$row['prix'];
    $total = $qty * $prix;

    $img = $row['image_principale'] ?: 'images/no-image.png';
    $nom = htmlspecialchars($row['nom']);

    $col = htmlspecialchars($row['couleur']);
    $tail = htmlspecialchars($row['taille']);

    $htmlProduit = '
      <div class="d-flex align-items-center gap-3">
        <img src="'.htmlspecialchars($img).'" style="width:70px;height:70px;object-fit:cover" class="rounded border"/>
        <div>
          <div class="fw-semibold">'.$nom.'</div>
          <div class="small text-muted">Couleur: '.$col.' | Taille: '.$tail.'</div>
        </div>
      </div>
    ';

    $htmlQty = '
      <div class="d-flex justify-content-center align-items-center">
        <button class="btn btn-sm btn-outline-dark me-2" onclick="decrementQuantity('.$cartId.','.$qty.')">
          <i class="bi bi-dash-lg"></i>
        </button>
        <span class="fw-semibold">'.$qty.'</span>
        <button class="btn btn-sm btn-outline-dark ms-2" onclick="incrementQuantity('.$cartId.','.$qty.')">
          <i class="bi bi-plus-lg"></i>
        </button>
      </div>
    ';

    $htmlDelete = '
      <button class="btn text-danger" onclick="removeFromCart('.$cartId.')">
        <i class="bi bi-trash3-fill"></i>
      </button>
    ';

    $data[] = [
      0 => $htmlProduit,
      1 => number_format($prix, 3) . " DT",
      2 => $htmlQty,
      3 => number_format($total, 3) . " DT",
      4 => $htmlDelete
    ];
  }

  json_out([
    "draw" => $draw,
    "recordsTotal" => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data" => $data
  ]);
}

// =======================
// POST: update qty
// =======================
if (isset($_POST['updateQty'])) {
  require_csrf();

  $cartId = (int)($_POST['cart_id'] ?? 0);
  $qte = (int)($_POST['quantite'] ?? 1);

  if ($cartId <= 0 || $qte < 1) {
    json_out(["status"=>"error","message"=>"Données invalides"], 400);
  }

  // Security: only update own cart row
  if ($userId) {
    $st = mysqli_prepare($con, "UPDATE panier SET quantite=? WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($st, "iii", $qte, $cartId, $userId);
  } else {
    $st = mysqli_prepare($con, "UPDATE panier SET quantite=? WHERE id=? AND guest_token=?");
    mysqli_stmt_bind_param($st, "iis", $qte, $cartId, $guestToken);
  }

  if (mysqli_stmt_execute($st)) {
    json_out(["status"=>"success"]);
  }
  json_out(["status"=>"error","message"=>"Erreur lors de la mise à jour"], 500);
}

// =======================
// POST: remove item
// =======================
if (isset($_POST['removeItem'])) {
  require_csrf();

  $cartId = (int)($_POST['cart_id'] ?? 0);
  if ($cartId <= 0) json_out(["status"=>"error","message"=>"ID invalide"], 400);

  if ($userId) {
    $st = mysqli_prepare($con, "DELETE FROM panier WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($st, "ii", $cartId, $userId);
  } else {
    $st = mysqli_prepare($con, "DELETE FROM panier WHERE id=? AND guest_token=?");
    mysqli_stmt_bind_param($st, "is", $cartId, $guestToken);
  }

  if (mysqli_stmt_execute($st)) {
    json_out(["status"=>"success"]);
  }
  json_out(["status"=>"error","message"=>"Suppression impossible"], 500);
}

// fallback
json_out(["status"=>"error","message"=>"Action non reconnue"], 400);
