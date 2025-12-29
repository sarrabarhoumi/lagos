<?php
include 'connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * PASSAGER / CONNECTÉ
 */
if (empty($_SESSION['guest_token'])) {
  $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}

/**
 * CSRF
 */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/**
 * Filtre catégorie (?categorie=ID ou NOM)
 */
$categorieFiltre = $_GET['categorie'] ?? '';
$categorieFiltre = trim($categorieFiltre);

$categorieNom = '';
$categorieId  = null;

if ($categorieFiltre !== '') {
  if (ctype_digit($categorieFiltre)) {
    $categorieId = (int)$categorieFiltre;
    $stmt = mysqli_prepare($con, "SELECT id, nom FROM categories WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $categorieId);
  } else {
    $stmt = mysqli_prepare($con, "SELECT id, nom FROM categories WHERE nom=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $categorieFiltre);
  }

  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res && mysqli_num_rows($res) > 0) {
    $cat = mysqli_fetch_assoc($res);
    $categorieId = (int)$cat['id'];
    $categorieNom = $cat['nom'];
  }
}

/**
 * Charger produits + details JSON (pour le modal variations)
 */
if ($categorieId) {
  $sql = "
    SELECT p.*, dp.details AS details_json
    FROM produit p
    LEFT JOIN details_produit dp ON dp.id_produit = p.id
    WHERE p.id_categorie = ?
    ORDER BY p.date_ajout DESC
  ";
  $stmtP = mysqli_prepare($con, $sql);
  mysqli_stmt_bind_param($stmtP, "i", $categorieId);
} else {
  $sql = "
    SELECT p.*, dp.details AS details_json
    FROM produit p
    LEFT JOIN details_produit dp ON dp.id_produit = p.id
    ORDER BY p.date_ajout DESC
  ";
  $stmtP = mysqli_prepare($con, $sql);
}

mysqli_stmt_execute($stmtP);
$resP = mysqli_stmt_get_result($stmtP);

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function promo_is_valid(array $p): bool {
  if (empty($p['promo_active'])) return false;

  $today = date('Y-m-d');
  $start = !empty($p['promo_start']) ? $p['promo_start'] : null;
  $end   = !empty($p['promo_end']) ? $p['promo_end'] : null;

  if ($start && $today < $start) return false;
  if ($end && $today > $end) return false;

  $val = (float)($p['promo_value'] ?? 0);
  return $val > 0;
}

function promo_price(float $prix, string $type, float $value): float {
  if ($prix <= 0 || $value <= 0) return $prix;
  if ($type === 'amount') return max(0, $prix - $value);
  // percent
  return max(0, $prix - ($prix * ($value / 100)));
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $categorieNom ? e($categorieNom) : "Tous les produits" ?> | LAGOS</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- Toastify -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <!-- Custom -->
  <link rel="stylesheet" href="css/style.css">

  <style>
    body { background: #fff; }

    .breadcrumb-title { font-weight: 700; margin-bottom: .35rem; }

    .toolbar {
      background: #fff;
      border: 1px solid #eee;
      border-radius: 14px;
      padding: 14px;
      box-shadow: 0 6px 20px rgba(0,0,0,.04);
    }

    .card-product {
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid #eee;
      box-shadow: 0 6px 18px rgba(0,0,0,0.06);
      transition: transform .2s ease, box-shadow .2s ease;
      background:#fff;
      height: 100%;
    }
    .card-product:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 30px rgba(0,0,0,0.10);
    }
    .card-product img {
      height: 220px;
      object-fit: cover;
      background:#f6f6f6;
    }
    .product-name { font-weight: 700; font-size: 1rem; }
    .product-desc { font-size: .88rem; color:#666; min-height: 40px; }
    .product-price { font-weight: 800; }

    .btn-icon {
      width: 38px; height: 38px;
      display:flex; align-items:center; justify-content:center;
      border-radius: 12px;
    }
    .btn-lagos {
      background:#111; color:#fff; border-radius: 12px; border:0;
      padding: 10px 14px; font-weight: 800;
    }
    .btn-lagos:hover { background:#000; color:#fff; }

    /* Modal choices */
    .modal-color {
      width: 34px; height: 34px; border-radius: 999px;
      border: 2px solid #ddd; cursor: pointer; position: relative;
    }
    .modal-color.selected { border: 3px solid #111; }
    .modal-color.disabled { opacity: .35; cursor: not-allowed; }
    .modal-color.disabled::after{
      content:"×"; position:absolute; inset:0;
      display:flex; align-items:center; justify-content:center;
      color:#dc3545; font-weight:900; font-size:18px;
    }

    .modal-size {
      padding: 8px 12px; border-radius: 999px;
      border: 1px solid #ddd; cursor: pointer;
      font-weight: 700; user-select:none;
    }
    .modal-size.selected { background:#111; color:#fff; border-color:#111; }
    .modal-size.disabled { opacity: .4; cursor: not-allowed; border-color:#dc3545; color:#dc3545; }

    .empty-box {
      background:#fff; border:1px solid #eee;
      border-radius: 14px; padding: 30px; text-align:center;
      box-shadow: 0 6px 18px rgba(0,0,0,.04);
    }
  </style>
</head>

<body>
<?php include 'header_user.php'; ?>

<div class="container py-4">

  <!-- Breadcrumb -->
  <div class="text-center mb-4">
    <h2 class="breadcrumb-title"><?= $categorieNom ? e($categorieNom) : "Tous les produits" ?></h2>
    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Accueil</a></li>
        <li class="breadcrumb-item active" aria-current="page">
          <?= $categorieNom ? e($categorieNom) : "Tous les produits" ?>
        </li>
      </ol>
    </nav>
  </div>

  <!-- Toolbar -->
  <div class="filters-bar mb-4">
  <div class="filters-left">
    <div class="search-pill">
      <i class="bi bi-search"></i>
      <input id="searchInput" type="text" placeholder="Rechercher un produit...">
      <button class="clear-btn" type="button" id="btnClearSearch" title="Effacer">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="sort-pill">
      <i class="bi bi-filter"></i>
      <select id="filter">
        <option value="recent" selected>Récent</option>
        <option value="oldest">Ancien</option>
        <option value="highest_price">Prix ↑</option>
        <option value="lowest_price">Prix ↓</option>
      </select>
    </div>
  </div>

  <div class="filters-right">
    

    <button class="btn-reset2" id="btnReset" type="button">
      <i class="bi bi-arrow-counterclockwise"></i>
      Réinitialiser
    </button>
  </div>
</div>

  <style>
    .filters-bar{
  display:flex;
  gap:12px;
  align-items:center;
  justify-content:space-between;
  padding:14px;
  border:1px solid rgba(0,0,0,.08);
  border-radius:18px;
  background:#fff;
  box-shadow:0 10px 30px rgba(0,0,0,.05);
}

.filters-left, .filters-right{
  display:flex;
  gap:10px;
  align-items:center;
  flex-wrap:wrap;
}

.search-pill, .sort-pill{
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px 12px;
  border:1px solid rgba(0,0,0,.10);
  border-radius:999px;
  background:#fafafa;
}

.search-pill i, .sort-pill i{
  color:rgba(0,0,0,.55);
}

.search-pill input{
  border:0;
  outline:0;
  background:transparent;
  min-width:260px;
  font-weight:600;
}

.search-pill .clear-btn{
  border:0;
  background:transparent;
  color:rgba(0,0,0,.55);
  padding:0 6px;
  cursor:pointer;
}
.search-pill .clear-btn:hover{ color:#000; }

.sort-pill select{
  border:0;
  outline:0;
  background:transparent;
  font-weight:700;
  cursor:pointer;
}

.chip{
  border:1px solid rgba(0,0,0,.10);
  background:#fff;
  padding:10px 12px;
  border-radius:999px;
  font-weight:800;
  display:flex;
  align-items:center;
  gap:8px;
  cursor:pointer;
  transition:.15s ease;
}
.chip:hover{ transform:translateY(-1px); }
.chip.active{
  background:#111;
  color:#fff;
  border-color:#111;
}

.btn-reset2{
  border:0;
  background:#111;
  color:#fff;
  padding:10px 14px;
  border-radius:999px;
  font-weight:800;
  display:flex;
  align-items:center;
  gap:8px;
  cursor:pointer;
  transition:.15s ease;
}
.btn-reset2:hover{ background:#000; }

@media (max-width: 768px){
  .filters-bar{ flex-direction:column; align-items:stretch; }
  .search-pill input{ min-width: 140px; width:100%; }
  .filters-left, .filters-right{ justify-content:space-between; }
}

  </style>

  <!-- Products grid -->
  <div class="row g-4" id="products-list">
    <?php if ($resP && mysqli_num_rows($resP) > 0): ?>
      <?php while ($row = mysqli_fetch_assoc($resP)): ?>
        <?php
          $id = (int)$row['id'];
          $nom = $row['nom'] ?? '';
          $desc = $row['description'] ?? '';
          $prix = (float)($row['prix'] ?? 0);
          $prixBase = $prix;

$promoOk    = promo_is_valid($row);
$promoType  = $promoOk ? ($row['promo_type'] ?? 'percent') : 'percent';
$promoValue = $promoOk ? (float)($row['promo_value'] ?? 0) : 0;

$prixFinal = $promoOk ? promo_price($prixBase, $promoType, $promoValue) : $prixBase;

$badgePromo = "";
if ($promoOk) {
  if ($promoType === 'amount') {
    $badgePromo = "-" . number_format($promoValue, 2) . " DT";
  } else {
    $badgePromo = "-" . rtrim(rtrim(number_format($promoValue, 2), '0'), '.') . "%";
  }
}

          $date = $row['date_ajout'] ?? '';

          $img = str_replace('../','', $row['image_principale'] ?? '');
          if (!$img) $img = "images/no-image.png";

          $detailsArr = [];
          if (!empty($row['details_json'])) {
            $tmp = json_decode($row['details_json'], true);
            if (is_array($tmp)) $detailsArr = $tmp;
          }

          // construire data JS pour modal
          $jsData = json_encode([
  "id" => $id,
  "nom" => $nom,
  "image" => $img,

  // ✅ prix
  "prix" => $prixBase,           // compat
  "prix_base" => $prixBase,
  "prix_final" => $prixFinal,

  // ✅ promo
  "promo" => [
    "active" => $promoOk,
    "type"   => $promoType,
    "value"  => $promoValue,
    "start"  => $row['promo_start'] ?? null,
    "end"    => $row['promo_end'] ?? null,
  ],

  "details" => array_map(function($d){
    return [
      "couleur" => $d["couleur"] ?? null,
      "taille" => $d["taille"] ?? null,
      "quantite" => (int)($d["quantite"] ?? 0),
    ];
  }, $detailsArr)
], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);

        ?>

        <div class="col-12 col-sm-6 col-md-4 col-lg-3 product-col"
             data-price="<?= e(number_format($prix, 3, '.', '')) ?>"
             data-date="<?= e($date) ?>"
             data-name="<?= e(mb_strtolower($nom)) ?>"
             data-desc="<?= e(mb_strtolower($desc)) ?>">

          <div class="card card-product">
            <a href="produit.php?id=<?= $id ?>" class="text-decoration-none text-dark position-relative d-block">
  <img src="<?= e($img) ?>" class="w-100" alt="<?= e($nom) ?>" loading="lazy">

  <?php if ($promoOk): ?>
    <div class="position-absolute top-0 start-0 m-2 px-2 py-1 rounded text-white fw-bold"
         style="background:#dc3545; font-size:.85rem;">
      <?= e($badgePromo) ?>
    </div>
  <?php endif; ?>
</a>


            <div class="p-3">
              <div class="product-name mb-1"><?= e($nom) ?></div>
              <div class="product-desc mb-2"><?= e($desc) ?></div>

              <div class="d-flex align-items-center justify-content-between">
                <div class="product-price h5 mb-0">
  <?php if ($promoOk): ?>
    <div class="text-muted text-decoration-line-through" style="font-size:.9rem; font-weight:700;">
      <?= number_format($prixBase, 2) ?> DT
    </div>
    <div style="color:#dc3545;">
      <?= number_format($prixFinal, 2) ?> DT
    </div>
  <?php else: ?>
    <?= number_format($prixBase, 2) ?> DT
  <?php endif; ?>
</div>

                <div class="d-flex gap-2">
                  <!-- Ajouter au panier (modal) -->
                  <button class="btn btn-outline-dark btn-icon"
                          type="button"
                          title="Ajouter au panier"
                          onclick='openAddCartModal(<?= $jsData ?>)'>
                    <i class="bi bi-cart-plus"></i>
                  </button>

                  <!-- Voir produit -->
                  <a class="btn btn-outline-dark btn-icon"
                     href="produit.php?id=<?= $id ?>"
                     title="Voir le produit">
                    <i class="bi bi-eye"></i>
                  </a>

                  <!-- Favori (optionnel) -->
                  <button class="btn btn-outline-dark btn-icon" type="button" title="Favoris (à implémenter)">
                    <i class="bi bi-heart"></i>
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>

      <?php endwhile; ?>

    <?php else: ?>
      <div class="col-12">
        <div class="empty-box">
          <i class="bi bi-bag fs-1"></i>
          <h5 class="mt-2">Aucun produit</h5>
          <p class="text-muted mb-0">Aucun produit trouvé dans cette catégorie.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL Ajouter au panier -->
<div class="modal fade" id="addCartModal" tabindex="-1" aria-labelledby="addCartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header border-0">
        <h6 class="modal-title fw-semibold" id="addCartModalLabel">Ajouter au panier</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 align-items-start">
          <div class="col-md-5 text-center">
            <img id="modalProductImage" src="" alt="" class="img-fluid rounded"
                 style="max-height:260px; width:100%; object-fit:cover;">
          </div>

          <div class="col-md-7">
            <h5 id="modalProductName" class="fw-bold mb-1"></h5>
            <div class="text-muted mb-3">
              Prix unitaire: <span class="fw-semibold" id="modalProductPrice"></span> DT
            </div>

            <div class="mb-2">
              <label class="form-label fw-semibold mb-1">Couleur</label>
              <div id="modalProductColors" class="d-flex flex-wrap gap-2"></div>
              <small class="text-muted d-block mt-1">Choisie: <span id="modalChosenColor">—</span></small>
            </div>

            <div class="mb-2">
              <label class="form-label fw-semibold mb-1">Taille</label>
              <div id="modalProductSizes" class="d-flex flex-wrap gap-2"></div>
              <small class="text-muted d-block mt-1">Choisie: <span id="modalChosenSize">—</span></small>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <small class="text-muted">
                Stock: <span class="fw-bold" id="modalStock">—</span>
              </small>

              <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Quantité</span>
                <div class="d-flex align-items-center border rounded" style="overflow:hidden; width:fit-content;">
                  <button type="button" class="btn btn-light border-end" id="modalQtyMinus">-</button>
                  <input type="number" id="modalQty" value="1" min="1"
                         class="form-control text-center border-0" style="width:70px;">
                  <button type="button" class="btn btn-light border-start" id="modalQtyPlus">+</button>
                </div>
              </div>
            </div>

            <div class="alert alert-warning mt-3 py-2 d-none" id="modalWarn"></div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0">
        <button type="button" class="btn btn-lagos w-100" id="modalAddCartBtn" disabled>
          Ajouter au panier
        </button>
      </div>

    </div>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
  const CSRF_TOKEN = <?= json_encode($csrfToken, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

  function toast(msg, ok=true){
    Toastify({
      text: msg,
      duration: 2500,
      gravity: "top",
      position: "right",
      backgroundColor: ok ? "#111" : "#dc3545",
      close: true
    }).showToast();
  }

  // Badge panier (header_user.php)
  function updateCartBadge(count){
    const badge = document.querySelector(".cart-count");
    if (!badge) return;
    badge.textContent = String(count || 0);
    badge.style.display = (count && count > 0) ? "inline-block" : "none";
  }

  // Charger compteur au chargement page
  async function loadCartCount(){
    try{
      const r = await fetch("ajax_get_cart.php", {
        method:"POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({ csrf_token: CSRF_TOKEN })
      });
      const data = await r.json();
      if (r.ok && data.success) updateCartBadge(data.cart_count);
    }catch(e){}
  }
  document.addEventListener("DOMContentLoaded", loadCartCount);

  // ==========================
  // FILTRE + RECHERCHE
  // ==========================
  function getCols(){
    return Array.from(document.querySelectorAll("#products-list .product-col"));
  }

  function applySearch(){
    const q = (document.getElementById("searchInput").value || "").trim().toLowerCase();
    getCols().forEach(col=>{
      const name = col.dataset.name || "";
      const desc = col.dataset.desc || "";
      const ok = (q === "") || name.includes(q) || desc.includes(q);
      col.style.display = ok ? "" : "none";
    });
  }

  function applySort(){
    const val = $("#filter").val();
    const list = document.getElementById("products-list");
    const cols = getCols();

    cols.sort((a,b)=>{
      const pa = parseFloat(a.dataset.price || "0");
      const pb = parseFloat(b.dataset.price || "0");
      const da = new Date(a.dataset.date || "1970-01-01").getTime();
      const db = new Date(b.dataset.date || "1970-01-01").getTime();

      if (val === "lowest_price") return pa - pb;
      if (val === "highest_price") return pb - pa;
      if (val === "oldest") return da - db;
      return db - da; // recent
    });

    cols.forEach(c => list.appendChild(c));
  }

  $(document).ready(function(){
    $("#filter").select2({ minimumResultsForSearch: Infinity });
    $("#filter").on("change", applySort);
    $("#searchInput").on("input", applySearch);

    $("#btnReset").on("click", ()=>{
      $("#searchInput").val("");
      $("#filter").val("recent").trigger("change");
      applySearch();
    });

    applySort();
  });

  // ==========================
  // MODAL AJOUT PANIER (variantes)
  // ==========================
  let modalState = { product: null, selectedColor: null, selectedSize: null, stock: 0, bsModal: null };

  const m = {
    img: document.getElementById('modalProductImage'),
    name: document.getElementById('modalProductName'),
    price: document.getElementById('modalProductPrice'),
    colors: document.getElementById('modalProductColors'),
    sizes: document.getElementById('modalProductSizes'),
    chosenColor: document.getElementById('modalChosenColor'),
    chosenSize: document.getElementById('modalChosenSize'),
    stock: document.getElementById('modalStock'),
    qty: document.getElementById('modalQty'),
    minus: document.getElementById('modalQtyMinus'),
    plus: document.getElementById('modalQtyPlus'),
    btnAdd: document.getElementById('modalAddCartBtn'),
    warn: document.getElementById('modalWarn')
  };

  function showWarn(msg){ m.warn.textContent = msg; m.warn.classList.remove('d-none'); }
  function hideWarn(){ m.warn.textContent = ""; m.warn.classList.add('d-none'); }

  function buildStockMap(detailsArr){
  const stockMap = {};

  (detailsArr || []).forEach(d=>{
    const c = (d.couleur || "").trim();
    const rawSizes = (d.taille || "").trim();   // ex: "s,m,l"
    const q = parseInt(d.quantite || 0, 10);

    if (!c || !rawSizes) return;

    const sizes = rawSizes
      .split(",")
      .map(x => x.trim())
      .filter(Boolean);

    if (!sizes.length) return;

    stockMap[c] ??= {};

    sizes.forEach(size => {
      // additionner si la taille existe déjà
      stockMap[c][size] = (stockMap[c][size] ?? 0) + (isNaN(q) ? 0 : q);
    });
  });

  return stockMap;
}


  function clampQty(){
    let q = parseInt(m.qty.value || "1", 10);
    if (isNaN(q) || q < 1) q = 1;
    if (modalState.stock > 0 && q > modalState.stock) q = modalState.stock;
    m.qty.value = q;
  }

  function updateBtnState(){
    const ok = !!(modalState.selectedColor && modalState.selectedSize && modalState.stock > 0);
    m.btnAdd.disabled = !ok;
  }

  function renderSizes(){
    m.sizes.innerHTML = "";
    m.chosenSize.textContent = "—";
    modalState.selectedSize = null;
    modalState.stock = 0;
    m.stock.textContent = "—";
    updateBtnState();

    if (!modalState.selectedColor) return;

    const sizesMap = modalState.product._stockMap[modalState.selectedColor] || {};
    const keys = Object.keys(sizesMap);
    const order = ["xs","s","m","l","xl","xxl","xxxl"];
keys.sort((a,b)=>{
  const A = a.toLowerCase(), B = b.toLowerCase();
  const ia = order.indexOf(A), ib = order.indexOf(B);
  if (ia !== -1 && ib !== -1) return ia - ib;
  if (ia !== -1) return -1;
  if (ib !== -1) return 1;
  return A.localeCompare(B);
});


    if (keys.length === 0){
      showWarn("Aucune taille disponible.");
      return;
    }
    hideWarn();

    keys.forEach(size=>{
      const qty = sizesMap[size];
      const disabled = qty <= 0;

      const b = document.createElement("button");
      b.type = "button";
      b.className = "modal-size" + (disabled ? " disabled" : "");
      b.textContent = disabled ? `${size} (0)` : `${size}`;
      b.dataset.size = size;

      b.onclick = ()=>{
        if (disabled) return;
        document.querySelectorAll("#modalProductSizes .modal-size").forEach(x=>x.classList.remove("selected"));
        b.classList.add("selected");

        modalState.selectedSize = size;
        modalState.stock = qty;

        m.chosenSize.textContent = size;
        m.stock.textContent = qty;

        m.qty.disabled = false;
        m.qty.max = qty;
        clampQty();
        updateBtnState();
      };

      m.sizes.appendChild(b);
    });

    const firstAvail = keys.find(s => (sizesMap[s] || 0) > 0);
    if (firstAvail){
      const btn = [...document.querySelectorAll("#modalProductSizes .modal-size")]
        .find(x => x.dataset.size === firstAvail);
      btn?.click();
    } else {
      m.qty.disabled = true;
      m.qty.value = 1;
      m.stock.textContent = "0";
      showWarn("Rupture de stock.");
      updateBtnState();
    }
  }
function hexToRgb(hex) {
      hex = hex.replace('#', '');
      if (hex.length === 3) {
        hex = hex.split('').map(x => x + x).join('');
      }
      return {
        r: parseInt(hex.substring(0, 2), 16),
        g: parseInt(hex.substring(2, 4), 16),
        b: parseInt(hex.substring(4, 6), 16)
      };
    }

    function colorFamilyFromHex(hex) {
      if (!hex) return "Inconnu";
      const rgb = hexToRgb(hex);

      const families = {
        "Noir": [0, 0, 0],
        "Blanc": [255, 255, 255],
        "Gris": [128, 128, 128],
        "Rouge": [220, 20, 60],
        "Bordeaux": [128, 0, 32],
        "Rose": [255, 105, 180],
        "Orange": [255, 140, 0],
        "Jaune": [255, 215, 0],
        "Vert": [34, 139, 34],
        "Vert clair": [144, 238, 144],
        "Bleu": [30, 144, 255],
        "Bleu foncé": [0, 0, 139],
        "Violet": [138, 43, 226],
        "Marron": [139, 69, 19],
        "Beige": [245, 245, 220]
      };

      let minDist = Infinity;
      let closest = "Autre";

      for (const [name, ref] of Object.entries(families)) {
        const d = Math.pow(rgb.r - ref[0], 2) +
          Math.pow(rgb.g - ref[1], 2) +
          Math.pow(rgb.b - ref[2], 2);
        if (d < minDist) {
          minDist = d;
          closest = name;
        }
      }
      return closest;
    }
  function renderColors(){
    m.colors.innerHTML = "";
    m.chosenColor.textContent = "—";
    modalState.selectedColor = null;

    const map = modalState.product._stockMap || {};
    const colorKeys = Object.keys(map);

    colorKeys.forEach(color=>{
      const total = Object.values(map[color] || {}).reduce((a,b)=>a + parseInt(b||0,10), 0);
      const disabled = total <= 0;

      const b = document.createElement("button");
      b.type = "button";
      b.className = "modal-color" + (disabled ? " disabled" : "");
      b.style.background = color;
      b.dataset.color = color;

      b.onclick = ()=>{
        if (disabled) return;
        document.querySelectorAll("#modalProductColors .modal-color").forEach(x=>x.classList.remove("selected"));
        b.classList.add("selected");

        modalState.selectedColor = color;
        m.chosenColor.textContent = colorFamilyFromHex(color);

        renderSizes();
        updateBtnState();
      };

      m.colors.appendChild(b);
    });

    const firstAvail = colorKeys.find(c=>{
      const total = Object.values(map[c] || {}).reduce((a,b)=>a + parseInt(b||0,10), 0);
      return total > 0;
    });

    if (firstAvail){
      const btn = [...document.querySelectorAll("#modalProductColors .modal-color")]
        .find(x => x.dataset.color === firstAvail);
      btn?.click();
    } else {
      showWarn("Produit en rupture de stock.");
      m.qty.disabled = true;
      updateBtnState();
    }
  }

  function openAddCartModal(product){
    modalState.product = product;
    modalState.product._stockMap = buildStockMap(product.details || []);
    modalState.selectedColor = null;
    modalState.selectedSize = null;
    modalState.stock = 0;

    m.img.src = product.image || "images/no-image.png";
    m.name.textContent = product.nom || "";
    m.price.textContent = (parseFloat(product.prix || 0)).toFixed(2);

    m.qty.value = 1;
    m.qty.min = 1;
    m.qty.disabled = false;
    m.stock.textContent = "—";
    hideWarn();

    renderColors();
    updateBtnState();

    if (!modalState.bsModal) modalState.bsModal = new bootstrap.Modal(document.getElementById("addCartModal"));
    modalState.bsModal.show();
  }
  window.openAddCartModal = openAddCartModal;

  // qty controls modal
  m.plus.addEventListener("click", ()=>{
    if (modalState.stock <= 0) return;
    let q = parseInt(m.qty.value || "1", 10);
    if (isNaN(q)) q = 1;
    if (q < modalState.stock) m.qty.value = q + 1;
  });
  m.minus.addEventListener("click", ()=>{
    let q = parseInt(m.qty.value || "1", 10);
    if (isNaN(q)) q = 1;
    if (q > 1) m.qty.value = q - 1;
  });
  m.qty.addEventListener("input", clampQty);

  async function postJSON(url, payload){
    const r = await fetch(url, {
      method:"POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify(payload)
    });
    const data = await r.json().catch(()=> ({}));
    if (!r.ok) throw new Error(data?.message || "Erreur serveur");
    return data;
  }

  m.btnAdd.addEventListener("click", async ()=>{
    if (!modalState.selectedColor || !modalState.selectedSize){
      showWarn("Choisis couleur et taille.");
      return;
    }
    if (modalState.stock <= 0){
      showWarn("Variante en rupture.");
      return;
    }

    clampQty();
    const qty = parseInt(m.qty.value, 10);

    try {
      const data = await postJSON("ajax_add_to_cart.php", {
        csrf_token: CSRF_TOKEN,
        product_id: modalState.product.id,
        color: modalState.selectedColor,
        size: modalState.selectedSize,
        qty: qty
      });

      if (data.success){
        toast("Ajouté au panier ✅", true);
        if (typeof data.cart_count !== "undefined") updateCartBadge(data.cart_count);
        modalState.bsModal.hide();
      } else {
        toast(data.message || "Erreur ajout panier", false);
      }
    } catch(e){
      toast(e.message || "Erreur ajout panier", false);
    }
  });
</script>

</body>
</html>
