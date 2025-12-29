<?php
include 'connection.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// --- Helpers ---
function e($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// --- Validate id ---
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Location: index.php');
  exit;
}
$id = (int)$_GET['id'];

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// --- Fetch product + details in one query ---
$sql = "
  SELECT p.*,
         c.nom AS categorie_nom,
         dp.details AS details_json
  FROM produit p
  LEFT JOIN categories c ON c.id = p.id_categorie
  LEFT JOIN details_produit dp ON dp.id_produit = p.id
  WHERE p.id = ?
  LIMIT 1
";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
  http_response_code(404);
  echo "Produit introuvable.";
  exit;
}
$product = mysqli_fetch_assoc($res);

// --- Normalize image ---
$image = str_replace('../', '', $product['image_principale'] ?? '');
if (!$image) $image = "images/no-image.png";

// --- Variations JSON ---
$variations = [];
if (!empty($product['details_json'])) {
  $tmp = json_decode($product['details_json'], true);
  if (is_array($tmp)) $variations = $tmp;
}

// --- Colors array for initial rendering ---
$colors = [];
foreach ($variations as $v) {
  $c = trim($v['couleur'] ?? '');
  $t = trim($v['taille'] ?? '');
  $q = isset($v['quantite']) ? (int)$v['quantite'] : 0;

  if ($c === '' || $t === '') continue;

  $sizes = array_filter(array_map('trim', explode(',', $t)));

  foreach ($sizes as $oneSize) {
    $colors[$c][] = ['taille' => $oneSize, 'quantite' => $q];
  }
}


// --- Similar products (prepared) ---
$catId = (int)($product['id_categorie'] ?? 0);
$sqlSim = "
  SELECT id, nom, prix, image_principale
  FROM produit
  WHERE id_categorie = ?
    AND id != ?
  ORDER BY date_ajout DESC
  LIMIT 8
";
$stmt2 = mysqli_prepare($con, $sqlSim);
mysqli_stmt_bind_param($stmt2, "ii", $catId, $id);
mysqli_stmt_execute($stmt2);
$simRes = mysqli_stmt_get_result($stmt2);

// --- SEO basics ---
$nom = $product['nom'] ?? '';
$desc = $product['description'] ?? '';
$prix = (float)($product['prix'] ?? 0);
$categorieNom = $product['categorie_nom'] ?? 'Catégorie';



?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($nom) ?> | LAGOS</title>
  <meta name="description" content="<?= e(mb_strimwidth(strip_tags($desc), 0, 160, '...')) ?>" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />

  <style>
    :root {
      --brand: #111;
      --accent: #186dc3;
    }

    body {
      background: #fff;
    }

    .breadcrumb a {
      text-decoration: none;
    }

    .page-wrap {
      margin-top: 24px;
    }

    .gallery {
      border: 1px solid #eee;
      border-radius: 16px;
      overflow: hidden;
      background: #fafafa;
      position: relative;
    }

    .gallery img.main {
      width: 100%;
      height: 520px;
      object-fit: cover;
      display: block;
      background: #f3f3f3;
    }

    @media (max-width: 992px) {
      .gallery img.main {
        height: 360px;
      }
    }

    .thumbs {
      display: flex;
      gap: 10px;
      padding: 12px;
      border-top: 1px solid #eee;
      overflow: auto;
      background: #fff;
    }

    .thumb {
      width: 74px;
      height: 74px;
      border-radius: 12px;
      border: 2px solid transparent;
      overflow: hidden;
      flex: 0 0 auto;
      cursor: pointer;
      background: #f5f5f5;
    }

    .thumb.active {
      border-color: var(--brand);
    }

    .thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      background: #f6f7f8;
      border: 1px solid #eee;
      font-weight: 600;
      font-size: .9rem;
    }

    .selector-row {
      margin-top: 14px;
    }

    .color-selector {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .color-circle {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      border: 2px solid #ddd;
      cursor: pointer;
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #eee;
    }

    .color-circle.selected {
      border: 3px solid var(--brand);
    }

    .color-circle.disabled {
      opacity: .35;
      cursor: not-allowed;
    }

    .color-circle.disabled::after {
      content: "×";
      position: absolute;
      font-size: 20px;
      font-weight: 900;
      color: #dc3545;
      line-height: 1;
    }

    .size-selector {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .size-box {
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid #ddd;
      background: #fff;
      cursor: pointer;
      font-weight: 700;
      user-select: none;
      transition: .15s ease;
      min-width: 64px;
      text-align: center;
    }

    .size-box:hover {
      transform: translateY(-1px);
    }

    .size-box.selected {
      background: var(--brand);
      color: #fff;
      border-color: var(--brand);
    }

    .size-box.disabled {
      opacity: .45;
      cursor: not-allowed;
      border-color: #dc3545;
      color: #dc3545;
      background: #fff;
      transform: none;
    }

    .sticky-card {
      position: sticky;
      top: 18px;
      border: 1px solid #eee;
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, .06);
      background: #fff;
    }

    .price {
      font-size: 1.9rem;
      font-weight: 800;
      color: var(--accent);
      margin: 6px 0 12px;
    }

    .qty-wrap {
      display: flex;
      align-items: center;
      border: 1px solid #ddd;
      border-radius: 14px;
      overflow: hidden;
      width: fit-content;
      background: #fff;
    }

    .qty-wrap button {
      width: 44px;
      height: 44px;
      border: 0;
      background: #f6f7f8;
      font-weight: 900;
      cursor: pointer;
    }

    .qty-wrap input {
      width: 80px;
      height: 44px;
      border: 0;
      text-align: center;
      font-weight: 800;
      outline: none;
    }

    .btn-brand {
      background: var(--brand);
      color: #fff;
      border: 0;
      border-radius: 14px;
      padding: 12px 14px;
      font-weight: 800;
    }

    .btn-brand:hover {
      background: #000;
      color: #fff;
    }

    .btn-outline-brand {
      border: 2px solid var(--brand);
      border-radius: 14px;
      padding: 12px 14px;
      font-weight: 800;
    }

    .section-title {
      font-weight: 900;
      letter-spacing: .2px;
      margin: 22px 0 12px;
    }

    .similar-scroll {
      display: flex;
      gap: 14px;
      overflow: auto;
      scroll-behavior: smooth;
      padding-bottom: 8px;
    }

    .sim-card {
      min-width: 210px;
      flex: 0 0 auto;
      border: 0;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
      cursor: pointer;
      transition: .2s ease;
      background: #fff;
    }

    .sim-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, .12);
    }

    .sim-card img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      display: block;
    }

    .sim-card .p {
      padding: 12px;
    }

    .muted {
      color: #6c757d;
    }
  </style>
</head>

<body>
  <?php include 'header_user.php'; ?>

  <div class="container page-wrap" itemscope itemtype="https://schema.org/Product">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
        <li class="breadcrumb-item"><a href="all_products.php?categorie=<?= (int)$catId ?>"><?= e($categorieNom) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($nom) ?></li>
      </ol>
    </nav>

    <meta itemprop="name" content="<?= e($nom) ?>">
    <meta itemprop="image" content="<?= e($image) ?>">
    <meta itemprop="description" content="<?= e(strip_tags($desc)) ?>">

    <div class="row g-4">
      <!-- Left: Gallery + details -->
      <div class="col-lg-7">
        <div class="gallery">
          <img id="mainImage" class="main" src="<?= e($image) ?>" alt="<?= e($nom) ?>" loading="lazy" itemprop="image">
          <div class="thumbs" aria-label="Galerie images">
            <!-- Si tu as plusieurs images dans ta DB, ajoute-les ici. Pour l’instant on met l’image principale. -->
            <div class="thumb active" data-src="<?= e($image) ?>">
              <img src="<?= e($image) ?>" alt="<?= e($nom) ?> miniature" loading="lazy">
            </div>
          </div>
        </div>

        <div class="mt-3 d-flex flex-wrap gap-2">
          <span class="pill"><i class="bi bi-truck"></i> Livraison 48h Tunisie</span>
          <span class="pill"><i class="bi bi-shield-check"></i> Paiement sécurisé</span>
          <span class="pill"><i class="bi bi-arrow-repeat"></i> Retour 7 jours</span>
        </div>

        <!-- Accordions -->
        <h3 class="section-title">Description</h3>
        <p class="muted mb-4"><?= nl2br(e($desc)) ?></p>

        <div class="accordion" id="prodAcc">
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#c1">
                Détails & conseils
              </button>
            </h2>
            <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#prodAcc">
              <div class="accordion-body muted">
                • Choisis une couleur et une taille. <br>
                • La quantité est limitée au stock disponible. <br>
                • Pour les tailles en rupture, l’option est automatiquement désactivée.
              </div>
            </div>
          </div>

          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">
                Livraison & retours
              </button>
            </h2>
            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#prodAcc">
              <div class="accordion-body muted">
                Livraison généralement sous 24–48h selon la région. Retour/échange sous 7 jours (conditions selon politique).
              </div>
            </div>
          </div>
        </div>

        <!-- Similar -->
        <h3 class="section-title mt-4">Produits similaires</h3>
        <div class="similar-scroll">
          <?php if ($simRes && mysqli_num_rows($simRes) > 0): ?>
            <?php while ($s = mysqli_fetch_assoc($simRes)):
              $simImg = str_replace('../', '', $s['image_principale'] ?? '');
              if (!$simImg) $simImg = "images/no-image.png";
            ?>
              <div class="sim-card" onclick="window.location.href='produit.php?id=<?= (int)$s['id'] ?>'">
                <img src="<?= e($simImg) ?>" alt="<?= e($s['nom']) ?>" loading="lazy">
                <div class="p">
                  <div class="fw-bold"><?= e($s['nom']) ?></div>
                  <div class="muted"><?= number_format((float)$s['prix'], 2) ?> DT</div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="muted">Aucun produit similaire pour le moment.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: Sticky buy box -->
      <div class="col-lg-5">
        <div class="sticky-card">
          <h1 class="h4 fw-black mb-1"><?= e($nom) ?></h1>

          <div class="price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            <meta itemprop="priceCurrency" content="TND">
            <meta itemprop="price" content="<?= e(number_format($prix, 2, '.', '')) ?>">
            <?= number_format($prix, 2) ?> DT
          </div>

          <div class="muted mb-3" id="selectionHint">
            Sélectionne une couleur et une taille pour continuer.
          </div>

          <!-- Colors -->
          <div class="selector-row">
            <div class="fw-bold mb-2">Couleur</div>
            <div class="color-selector">
              <?php foreach ($colors as $color => $sizes):
                $allOut = true;
                foreach ($sizes as $sz) {
                  if ((int)$sz['quantite'] > 0) {
                    $allOut = false;
                    break;
                  }
                }
                $disabled = $allOut ? 'disabled' : '';
              ?>
                <button type="button"
                  class="color-circle <?= $disabled ?>"
                  data-color="<?= e($color) ?>"
                  style="background-color: <?= e($color) ?>;"
                  aria-label="Couleur <?= e($color) ?>"></button>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Sizes -->
          <div class="selector-row">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-bold">Taille</div>
              <small class="muted">Stock: <span id="stockText">—</span></small>
            </div>
            <div class="size-selector" id="sizeSelector"></div>
          </div>

          <!-- Qty -->
          <div class="selector-row d-flex align-items-center justify-content-between">
            <div class="fw-bold">Quantité</div>
            <div class="qty-wrap">
              <button type="button" id="qtyMinus">-</button>
              <input type="number" id="quantity" value="1" min="1" inputmode="numeric">
              <button type="button" id="qtyPlus">+</button>
            </div>
          </div>

          <hr class="my-3">

          <div class="d-grid gap-2">
            <button class="btn btn-brand" id="btnAddCart">
              <i class="bi bi-cart-plus"></i> Ajouter au panier
            </button>

            <button class="btn btn-outline-brand" id="btnBuyNow">
              <i class="bi bi-bag"></i> Acheter maintenant
            </button>
          </div>

          <input type="hidden" id="csrfToken" value="<?= e($csrf) ?>">
          <input type="hidden" id="productId" value="<?= (int)$product['id'] ?>">

          <div class="mt-3 muted">
            <i class="bi bi-lock"></i> Paiement sécurisé • Support 24/7
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <strong class="me-auto">LAGOS</strong>
        <small class="text-muted">maintenant</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="toastBody">—</div>
    </div>
  </div>

  <script>
    // --- Data from PHP ---
    const variations = <?= json_encode($variations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // stockMap[color][size] = qty
    // stockMap[color][size] = qty  ✅ tailles séparées
    const stockMap = {};

    variations.forEach(v => {
      const c = (v.couleur || "").trim();
      const rawSizes = (v.taille || "").trim(); // ex: "s,m,l"
      const q = parseInt(v.quantite || 0, 10);

      if (!c || !rawSizes) return;

      // ✅ transformer "s,m,l" => ["s","m","l"]
      const sizes = rawSizes
        .split(",")
        .map(x => x.trim())
        .filter(Boolean);

      if (!sizes.length) return;

      stockMap[c] ??= {};

      sizes.forEach(size => {
        // si la même taille apparaît plusieurs fois, on additionne
        stockMap[c][size] = (stockMap[c][size] ?? 0) + (isNaN(q) ? 0 : q);
      });
    });


    let selectedColor = null;
    let selectedSize = null;

    const sizeSelector = document.getElementById('sizeSelector');
    const stockText = document.getElementById('stockText');
    const qtyInput = document.getElementById('quantity');
    const hint = document.getElementById('selectionHint');

    const btnAdd = document.getElementById('btnAddCart');
    const btnBuy = document.getElementById('btnBuyNow');
    btnAdd.disabled = true;
    btnBuy.disabled = true;


    function showToast(msg) {
      document.getElementById('toastBody').textContent = msg;
      const toastEl = document.getElementById('liveToast');
      const toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
        delay: 2500
      });
      toast.show();
    }

    function getSelectedStock() {
      if (!selectedColor || !selectedSize) return 0;
      return stockMap?.[selectedColor]?.[selectedSize] ?? 0;
    }

    function clampQty() {
      const stock = getSelectedStock();
      let q = parseInt(qtyInput.value || "1", 10);
      if (isNaN(q) || q < 1) q = 1;
      if (stock > 0 && q > stock) q = stock;
      qtyInput.value = q;
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
    function setButtonsState() {
      const ok = !!selectedColor && !!selectedSize && getSelectedStock() > 0;
      btnAdd.disabled = !ok;
      btnBuy.disabled = !ok;
      hint.textContent = ok ?
        `Sélection: ${colorFamilyFromHex(selectedColor)} / ${selectedSize}` :
        "Sélectionne une couleur et une taille pour continuer.";
    }

    function renderSizes() {
      sizeSelector.innerHTML = '';
      selectedSize = null;
      stockText.textContent = '—';
      qtyInput.value = 1;

      if (!selectedColor) {
        setButtonsState();
        return;
      }

      const sizes = stockMap[selectedColor] || {};
      const keys = Object.keys(sizes);
      const order = ["xs", "s", "m", "l", "xl", "xxl", "xxxl"];
      keys.sort((a, b) => {
        const A = a.toLowerCase(),
          B = b.toLowerCase();
        const ia = order.indexOf(A),
          ib = order.indexOf(B);
        if (ia !== -1 && ib !== -1) return ia - ib;
        if (ia !== -1) return -1;
        if (ib !== -1) return 1;
        return A.localeCompare(B);
      });


      keys.forEach(size => {
        const qty = sizes[size];
        const disabled = qty <= 0;

        const b = document.createElement('button');
        b.type = "button";
        b.className = "size-box" + (disabled ? " disabled" : "");
        b.textContent = disabled ? `${size} (0)` : `${size} `;

        b.onclick = () => {
          if (disabled) return;
          selectedSize = size;

          document.querySelectorAll('.size-box').forEach(x => x.classList.remove('selected'));
          b.classList.add('selected');

          stockText.textContent = qty;
          qtyInput.max = qty;
          qtyInput.disabled = false;

          clampQty();
          setButtonsState();
        };

        sizeSelector.appendChild(b);
      });

      // auto-select first available
      const firstAvail = keys.find(k => sizes[k] > 0);
      if (firstAvail) {
        const btn = [...document.querySelectorAll('.size-box')].find(x => x.textContent.startsWith(firstAvail));
        btn?.click();
      } else {
        qtyInput.disabled = true;
        stockText.textContent = '0';
        setButtonsState();
      }
    }

    // Color clicks
    document.querySelectorAll('.color-circle').forEach(btn => {
      btn.addEventListener('click', () => {
        if (btn.classList.contains('disabled')) return;

        selectedColor = btn.dataset.color;
        document.querySelectorAll('.color-circle').forEach(x => x.classList.remove('selected'));
        btn.classList.add('selected');

        renderSizes();
      });
    });

    // Auto-select first available color
    (function autoSelectColor() {
      const first = [...document.querySelectorAll('.color-circle')].find(x => !x.classList.contains('disabled'));
      if (first) first.click();
    })();

    // Qty handlers
    document.getElementById('qtyPlus').onclick = () => {
      const stock = getSelectedStock();
      let q = parseInt(qtyInput.value || "1", 10);
      if (isNaN(q)) q = 1;
      if (stock > 0 && q < stock) qtyInput.value = q + 1;
    };

    document.getElementById('qtyMinus').onclick = () => {
      let q = parseInt(qtyInput.value || "1", 10);
      if (isNaN(q)) q = 1;
      if (q > 1) qtyInput.value = q - 1;
    };

    qtyInput.addEventListener('input', () => {
      clampQty();
    });

    function ensureSelection() {
      if (!selectedColor) {
        showToast("Choisis une couleur.");
        return false;
      }
      if (!selectedSize) {
        showToast("Choisis une taille.");
        return false;
      }
      const stock = getSelectedStock();
      if (stock <= 0) {
        showToast("Cette variante est en rupture.");
        return false;
      }
      clampQty();
      return true;
    }

    async function postJSON(url, payload) {
      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
      });
      let data = null;
      try {
        data = await res.json();
      } catch (e) {}
      if (!res.ok) throw new Error(data?.message || "Erreur serveur");
      return data;
    }

    // Add cart (AJAX)
    // ✅ Badge update (header_user.php)
    function updateCartBadge(count) {
      const badge = document.querySelector(".cart-count");
      if (!badge) return;
      badge.textContent = String(count || 0);
      badge.style.display = (count && count > 0) ? "inline-block" : "none";
    }

    // Add cart (AJAX) ✅ (compatible connecté + passager)
    btnAdd.addEventListener('click', async () => {
      if (!ensureSelection()) return;

      const payload = {
        csrf_token: document.getElementById('csrfToken').value,
        product_id: parseInt(document.getElementById('productId').value, 10),
        color: selectedColor,
        size: selectedSize,
        qty: parseInt(qtyInput.value, 10) // ✅ IMPORTANT: qty (pas quantity)
      };

      btnAdd.disabled = true;

      try {
        // ✅ IMPORTANT: endpoint correct
        const data = await postJSON("ajax_add_to_cart.php", payload);

        if (data?.success) {
          showToast(data.message || "Ajouté au panier ✅");

          // ✅ update badge
          if (typeof data.cart_count !== "undefined") {
            updateCartBadge(data.cart_count);
          }
        } else {
          showToast(data?.message || "Erreur ajout panier");
        }

      } catch (err) {
        showToast(err.message || "Impossible d'ajouter au panier.");
      } finally {
        setButtonsState();
      }
    });


    // Buy now (simple redirect – tu peux le faire en POST aussi)
    btnBuy.addEventListener('click', () => {
      if (!ensureSelection()) return;

      // Exemple: redirection vers checkout avec query
      const pid = document.getElementById('productId').value;
      const qty = qtyInput.value;
      const url = `checkout.php?id=${encodeURIComponent(pid)}&color=${encodeURIComponent(selectedColor)}&size=${encodeURIComponent(selectedSize)}&qty=${encodeURIComponent(qty)}`;
      window.location.href = url;
    });

    // Gallery thumbs (si tu ajoutes d'autres images)
    document.querySelectorAll('.thumb').forEach(t => {
      t.addEventListener('click', () => {
        document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        document.getElementById('mainImage').src = t.dataset.src;
      });
    });
    async function loadCartCountOnProductPage() {
      try {
        const res = await fetch("ajax_get_cart.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            csrf_token: document.getElementById("csrfToken").value
          })
        });

        const data = await res.json();
        if (res.ok && data.success) {
          updateCartBadge(data.cart_count);
        }
      } catch (e) {
        // silencieux (pas besoin de bloquer la page)
        console.log("Impossible de charger le compteur panier");
      }
    }


    // ✅ au chargement
    document.addEventListener("DOMContentLoaded", loadCartCountOnProductPage);
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>