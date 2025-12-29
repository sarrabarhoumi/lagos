<?php
include '../connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function json_response($arr, $code = 200)
{
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr);
  exit;
}

function ensure_csrf()
{
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
}

function require_csrf()
{
  ensure_csrf();
  $csrf = $_POST['csrf_token'] ?? '';
  if (empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    json_response(["status" => "error", "message" => "CSRF invalide"], 403);
  }
}

function get_guest_token()
{
  if (empty($_SESSION['guest_token'])) {
    $_SESSION['guest_token'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['guest_token'];
}
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
function compute_cart(mysqli $con, ?int $userId, string $guestToken): array
{
  $items = [];
  $sous_total = 0.0;

  if ($userId) {
    $sql = "SELECT pa.id_produit, pa.couleur, pa.taille, pa.quantite,
                   pr.nom, pr.prix, pr.image_principale
            FROM panier pa
            JOIN produit pr ON pr.id = pa.id_produit
            WHERE pa.user_id = ?
            ORDER BY pa.id DESC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $userId);
  } else {
    $sql = "SELECT pa.id_produit, pa.couleur, pa.taille, pa.quantite,
                   pr.nom, pr.prix, pr.image_principale
            FROM panier pa
            JOIN produit pr ON pr.id = pa.id_produit
            WHERE pa.guest_token = ?
            ORDER BY pa.id DESC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $guestToken);
  }

  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
    $qty = (int)$row['quantite'];
    $price = (float)$row['prix'];
    $line_total = $qty * $price;

    $img = trim((string)($row['image_principale'] ?? ""));
    $img = $img ? str_replace('../', '', explode(',', $img)[0]) : "images/no-image.png";

    $items[] = [
      "id_produit" => (int)$row['id_produit'],
      "nom" => $row['nom'],
      "prix" => $price,
      "quantite" => $qty,
      "couleur" => $row['couleur'] ?? '',
      "taille" => $row['taille'] ?? '',
      "image" => $img,
      "total_ligne" => $line_total,
    ];

    $sous_total += $line_total;
  }

  return [$items, $sous_total];
}


function get_stock_for_variant($con, $productId, $color, $size)
{
  $stmt = mysqli_prepare($con, "SELECT details FROM details_produit WHERE id_produit=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "i", $productId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  if (!$res || mysqli_num_rows($res) === 0) return [null, []];

  $row = mysqli_fetch_assoc($res);
  $details = json_decode($row['details'] ?? '[]', true);
  if (!is_array($details)) $details = [];

  foreach ($details as $v) {
    if (($v['couleur'] ?? '') === $color && ($v['taille'] ?? '') === $size) {
      return [(int)($v['quantite'] ?? 0), $details];
    }
  }
  return [null, $details];
}

function set_variant_stock(&$details, $color, $size, $newQty)
{
  foreach ($details as &$v) {
    if (($v['couleur'] ?? '') === $color && ($v['taille'] ?? '') === $size) {
      $v['quantite'] = (int)$newQty;
      return true;
    }
  }
  return false;
}

// ---------- Identify
ensure_csrf();
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$guestToken = get_guest_token();

// =====================
// 1) Modal HTML
// =====================
if (isset($_POST['ValiderCommandeModal'])) {
  require_csrf();

  [$items, $sous_total] = compute_cart($con, $userId, $guestToken);
  $frais_livraison = 7.000;
  $total = $sous_total + $frais_livraison;

  if (count($items) === 0) {
    echo '<div class="alert alert-warning">Votre panier est vide.</div>';
    exit;
  }
?>
  <div class="modal fade" id="ValiderCommandeModal" tabindex="-1" aria-labelledby="ValiderCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title text-white" id="ValiderCommandeModalLabel">Validation de la commande</h5>
          <button type="button" class="btn btn-close-white p-0" data-bs-dismiss="modal">✖</button>
        </div>

        <form id="ValiderCommande" class="p-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

          <h6 class="mt-2 mb-3 text-center">Résumé de la commande</h6>
          <div class="card mb-3 shadow-sm">
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">Sous-total <span><?= number_format($sous_total, 3) ?> DT</span></li>
                <li class="list-group-item d-flex justify-content-between">Frais de livraison <span><?= number_format($frais_livraison, 3) ?> DT</span></li>
                <li class="list-group-item d-flex justify-content-between fw-bold">Total Général <span><?= number_format($total, 3) ?> DT</span></li>
              </ul>

              <!-- <div class="alert alert-info mt-3 mb-0">
                <?= $userId ? "Client connecté ✅" : "Client passager ✅" ?>
              </div> -->
            </div>
          </div>
          <h6 class="mt-3 mb-3 text-center">Détails de la commande</h6>

          <div class="card mb-3 shadow-sm">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Produit</th>
                      <th class="text-center">Couleur</th>
                      <th class="text-center">Taille</th>
                      <th class="text-center">Qté</th>
                      <th class="text-end">Prix</th>
                      <th class="text-end">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php

                    foreach ($items as $it): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <img src="<?= htmlspecialchars($it['image']) ?>"
                              style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid #eee;">
                            <div>
                              <div class="fw-semibold"><?= htmlspecialchars($it['nom']) ?></div>
                              <small class="text-muted">ID: <?= (int)$it['id_produit'] ?></small>
                            </div>
                          </div>
                        </td>

                        <td class="text-center">
                          <span class="d-inline-flex align-items-center gap-2">
                            <span style="width:16px;height:16px;border-radius:50%;background:<?= htmlspecialchars($it['couleur']) ?>;border:1px solid #ddd;"></span>
                            <small class="text-muted"><?= htmlspecialchars(colorFamilyFromHex($it['couleur'])) ?></small>
                          </span>
                        </td>

                        <td class="text-center">
                          <span class="badge bg-dark"><?= htmlspecialchars($it['taille']) ?></span>
                        </td>

                        <td class="text-center fw-bold"><?= (int)$it['quantite'] ?></td>

                        <td class="text-end"><?= number_format((float)$it['prix'], 3) ?> DT</td>

                        <td class="text-end fw-bold"><?= number_format((float)$it['total_ligne'], 3) ?> DT</td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>


          <h6 class="mt-3 mb-3 text-center">Informations personnelles</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <input type="text" class="form-control" name="nom" placeholder="Nom" required>
            </div>
            <div class="col-md-6">
              <input type="text" class="form-control" name="prenom" placeholder="Prénom" required>
            </div>
            <div class="col-md-6">
              <input type="tel" class="form-control" name="numero" placeholder="Téléphone" required>
            </div>
            <div class="col-md-6">
              <input type="email" class="form-control" name="email" placeholder="Email">
            </div>
          </div>

          <h6 class="mt-3 mb-3 text-center">Adresse de livraison</h6>
          <div class="row g-3">
            <div class="col-md-4">
              <input type="text" class="form-control" name="adresse" placeholder="Adresse" required>
            </div>
            <div class="col-md-4">
              <select class="form-select" name="gouvernorat" required>
                <option value="">Gouvernorat</option>
                <option value="Tunis">Tunis</option>
                <option value="Ariana">Ariana</option>
                <option value="Sousse">Sousse</option>
              </select>
            </div>
            <div class="col-md-4">
              <input type="text" class="form-control" name="code_postal" placeholder="Code postal">
            </div>
          </div>

          <div class="modal-footer mt-3">
            <button type="submit" class="btn btn-dark w-100">Valider la commande</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php
  exit;
}

// =====================
// 2) Validate order
// =====================
if (isset($_POST['ValiderCommande'])) {
  require_csrf();

  $nom = trim($_POST['nom'] ?? '');
  $prenom = trim($_POST['prenom'] ?? '');
  $numero = trim($_POST['numero'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $adresse = trim($_POST['adresse'] ?? '');
  $gouvernorat = trim($_POST['gouvernorat'] ?? '');
  $code_postal = trim($_POST['code_postal'] ?? '');

  if ($nom === '' || $prenom === '' || $numero === '' || $adresse === '' || $gouvernorat === '') {
    json_response(["status" => "error", "message" => "Champs obligatoires manquants."], 400);
  }

  [$items, $sous_total] = compute_cart($con, $userId, $guestToken);
  if (count($items) === 0) {
    json_response(["status" => "error", "message" => "Panier vide."], 400);
  }

  $frais_livraison = 7.000;
  $total = $sous_total + $frais_livraison;

  $sous_total_str = number_format($sous_total, 3, '.', '');
  $frais_str = number_format($frais_livraison, 3, '.', '');
  $total_str = number_format($total, 3, '.', '');

  $date_commande = date("Y-m-d H:i:s");
  $statut = "En attente";

  mysqli_begin_transaction($con);

  try {
    // A) vérifier stock
    foreach ($items as $it) {
      [$stock, $details] = get_stock_for_variant($con, $it['id_produit'], $it['couleur'], $it['taille']);
      if ($stock === null) throw new Exception("Variante introuvable (produit {$it['id_produit']}).");
      if ($stock < $it['quantite']) throw new Exception("Stock insuffisant pour une variante (max: $stock).");
    }

    // B) insert commande
    $id_user_str = $userId ? (string)$userId : "0";
    $guest_id_int = 0; // ta colonne guest_id est nullable, mais on met 0 si pas de table clients_passagers

    $sql = "
      INSERT INTO commandes
      (nom, prenom, numero, email, adresse, gouvernorat, code_postal,
       sous_total, frais_livraison, total, date_commande, statut, id_user, guest_id)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $st = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param(
      $st,
      "sssssssssssssi",
      $nom,
      $prenom,
      $numero,
      $email,
      $adresse,
      $gouvernorat,
      $code_postal,
      $sous_total_str,
      $frais_str,
      $total_str,
      $date_commande,
      $statut,
      $id_user_str,
      $guest_id_int
    );
    mysqli_stmt_execute($st);

    $orderId = mysqli_insert_id($con);
    if ($orderId <= 0) throw new Exception("Création commande échouée.");

    // C) décrémenter stock JSON
    foreach ($items as $it) {
      [$stock, $details] = get_stock_for_variant($con, $it['id_produit'], $it['couleur'], $it['taille']);
      $newStock = $stock - $it['quantite'];
      if ($newStock < 0) $newStock = 0;

      $ok = set_variant_stock($details, $it['couleur'], $it['taille'], $newStock);
      if (!$ok) throw new Exception("Erreur mise à jour stock JSON.");

      $json = json_encode($details, JSON_UNESCAPED_UNICODE);
      $stU = mysqli_prepare($con, "UPDATE details_produit SET details=? WHERE id_produit=?");
      mysqli_stmt_bind_param($stU, "si", $json, $it['id_produit']);
      mysqli_stmt_execute($stU);
    }

    // D) vider panier
    if ($userId) {
      $stD = mysqli_prepare($con, "DELETE FROM panier WHERE user_id=?");
      mysqli_stmt_bind_param($stD, "i", $userId);
    } else {
      $stD = mysqli_prepare($con, "DELETE FROM panier WHERE guest_token=?");
      mysqli_stmt_bind_param($stD, "s", $guestToken);
    }
    mysqli_stmt_execute($stD);

    mysqli_commit($con);

    json_response(["status" => "success", "message" => "Commande validée avec succès!", "order_id" => $orderId]);
  } catch (Exception $e) {
    mysqli_rollback($con);
    json_response(["status" => "error", "message" => $e->getMessage()], 500);
  }
}

// Default
echo "No action";
