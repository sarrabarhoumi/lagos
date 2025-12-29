<?php
include 'connection.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * PASSAGER / CONNECTÉ
 * - user_id si connecté
 * - sinon guest_token en session
 */
if (empty($_SESSION['guest_token'])) {
  $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}
$guestToken = $_SESSION['guest_token'];

$userId = $_SESSION['user_id'] ?? null;

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LAGOS | Boutique en ligne</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <link rel="stylesheet" href="css/style.css">

  <style>
    .hero {
      background: linear-gradient(to bottom right, #ffffff, #f8f9fa);
      text-align: center;
      padding: 5rem 1rem;
    }

    .hero h1 {
      font-weight: 700;
      letter-spacing: 1px;
    }

    .hero p {
      color: #666;
      max-width: 600px;
      margin: 0 auto 2rem;
    }

    .btn-lagos {
      background-color: #111;
      color: #fff;
      border-radius: 8px;
      padding: 0.7rem 1.5rem;
      font-weight: 500;
    }

    .section-title {
      text-align: center;
      margin: 3rem 0 2rem;
      font-weight: 600;
    }

    .new-products-scroll {
      scroll-behavior: smooth;
    }

    .card-product {
      width: 220px;
      border: none;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      cursor: pointer;
      flex: 0 0 auto;
      background: #fff;
    }

    .card-product:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
    }

    .card-product .card-image {
      position: relative;
      overflow: hidden;
      height: 190px;
    }

    .card-product .card-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease;
    }

    .card-product:hover .card-image img {
      transform: scale(1.06);
    }

    .card-product .add-cart-btn {
      position: absolute;
      bottom: 12px;
      left: 50%;
      transform: translateX(-50%) translateY(18px);
      opacity: 0;
      transition: 0.25s ease;
    }

    .card-product:hover .add-cart-btn {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    .add-cart-btn button {
      background-color: #111;
      color: #fff;
      border-radius: 10px;
      padding: 8px 14px;
      font-size: 0.85rem;
      border: none;
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .add-cart-btn button:hover {
      background-color: #186dc3;
    }

    .card-product .card-body {
      padding: 12px;
      text-align: center;
    }

    .product-name {
      font-weight: 600;
      margin-bottom: 4px;
    }

    .product-price {
      color: #666;
      font-size: .95rem;
    }

    @media (max-width: 768px) {
      .card-product .add-cart-btn {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
      }
    }

    .policy {
      text-align: center;
      padding: 2rem 1rem;
    }

    .policy i {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: #111;
    }

    footer {
      text-align: center;
      padding: 2rem;
      color: #777;
      border-top: 1px solid #eee;
      margin-top: 3rem;
    }

    /* Modal */
    .modal-color {
      width: 32px;
      height: 32px;
      border-radius: 999px;
      border: 2px solid #ddd;
      cursor: pointer;
      position: relative;
    }

    .modal-color.selected {
      border: 3px solid #111;
    }

    .modal-color.disabled {
      opacity: .35;
      cursor: not-allowed;
    }

    .modal-color.disabled::after {
      content: "×";
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #dc3545;
      font-weight: 900;
      font-size: 18px;
    }

    .modal-size {
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid #ddd;
      cursor: pointer;
      font-weight: 600;
      user-select: none;
    }

    .modal-size.selected {
      background: #111;
      color: #fff;
      border-color: #111;
    }

    .modal-size.disabled {
      opacity: .4;
      cursor: not-allowed;
      border-color: #dc3545;
      color: #dc3545;
    }
  </style>
</head>

<body>
  <?php include 'header_user.php'; ?>

  <section class="hero">
    <h1>Bienvenue chez LAGOS</h1>
    <p>Découvrez nos nouvelles collections et profitez d’une expérience shopping élégante et fluide.</p>
    <a href="#categories" class="btn btn-lagos">Découvrir maintenant</a>
  </section>

  <?php
  $sqlNew = "
    SELECT p.*,
           dp.details AS details_json
    FROM produit p
    LEFT JOIN details_produit dp ON dp.id_produit = p.id
    ORDER BY p.date_ajout DESC
    LIMIT 10
  ";
  $result = mysqli_query($con, $sqlNew);
  ?>

  <section id="new-products" class="container my-5">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="section-title mb-0">Nouveautés</h2>
      <a href="all_products.php" class="text-decoration-none text-dark fw-semibold">
        Voir tout <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <div class="new-products-scroll d-flex overflow-auto pb-3 gap-4">
      <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php
        function promo_is_valid($p): bool
        {
          if (empty($p['promo_active'])) return false;

          $today = date('Y-m-d');
          $start = !empty($p['promo_start']) ? $p['promo_start'] : null;
          $end   = !empty($p['promo_end']) ? $p['promo_end'] : null;

          if ($start && $today < $start) return false;
          if ($end && $today > $end) return false;

          $val = (float)($p['promo_value'] ?? 0);
          return $val > 0;
        }

        function promo_price(float $prix, string $type, float $value): float
        {
          if ($prix <= 0 || $value <= 0) return $prix;
          if ($type === 'amount') return max(0, $prix - $value);
          // percent
          return max(0, $prix - ($prix * ($value / 100)));
        }


        while ($p = mysqli_fetch_assoc($result)): ?>
          <?php
          $id = (int)$p['id'];
          $nom = htmlspecialchars($p['nom'] ?? '', ENT_QUOTES, 'UTF-8');
          $prixBase = (float)($p['prix'] ?? 0);

          $promoOk = promo_is_valid($p);
          $promoType  = $promoOk ? ($p['promo_type'] ?? 'percent') : 'percent';
          $promoValue = $promoOk ? (float)($p['promo_value'] ?? 0) : 0;

          $prixFinal = $promoOk ? promo_price($prixBase, $promoType, $promoValue) : $prixBase;

          $badgePromo = "";
          if ($promoOk) {
            if ($promoType === 'amount') {
              $badgePromo = "-" . number_format($promoValue, 2) . " DT";
            } else {
              $badgePromo = "-" . rtrim(rtrim(number_format($promoValue, 2), '0'), '.') . "%";
            }
          }

          $image = str_replace('../', '', $p['image_principale'] ?? '');
          if (!$image) $image = "images/no-image.png";

          $detailsData = [];
          $couleurs = [];
          $tailles = [];

          if (!empty($p['details_json'])) {
            $tmp = json_decode($p['details_json'], true);
            if (is_array($tmp)) {
              foreach ($tmp as $d) {
                $c = $d['couleur'] ?? null;
                $t = $d['taille'] ?? null;
                $q = isset($d['quantite']) ? (int)$d['quantite'] : 0;
                $detailsData[] = ['couleur' => $c, 'taille' => $t, 'quantite' => $q];
                if ($c && !in_array($c, $couleurs, true)) $couleurs[] = $c;
                if ($t && !in_array($t, $tailles, true)) $tailles[] = $t;
              }
            }
          }

          $jsData = json_encode([
            'id' => $id,
            'nom' => $p['nom'],
            'image' => $image,

            // ✅ prix base + final
            'prix' => $prixBase,        // compat ancienne
            'prix_base' => $prixBase,
            'prix_final' => $prixFinal,

            // ✅ promo
            'promo' => [
              'active' => $promoOk,
              'type'   => $promoType,
              'value'  => $promoValue,
              'start'  => $p['promo_start'] ?? null,
              'end'    => $p['promo_end'] ?? null
            ],

            'couleurs' => $couleurs,
            'tailles' => $tailles,
            'details' => $detailsData
          ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

          ?>

          <div class="card-product" onclick="window.location.href='produit.php?id=<?= $id ?>'">
            <div class="card-image">
              <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nom ?>" loading="lazy">
              <?php if ($promoOk): ?>
                <div class="position-absolute top-0 start-0 m-2 px-2 py-1 rounded text-white fw-bold"
                  style="background:#dc3545; font-size:.85rem;">
                  <?= htmlspecialchars($badgePromo, ENT_QUOTES, 'UTF-8') ?>
                </div>
              <?php endif; ?>

              <div class="add-cart-btn">
                <button type="button" onclick='event.stopPropagation(); openAddCartModal(<?= $jsData ?>)'>
                  <i class="bi bi-cart-plus"></i> Panier
                </button>
              </div>
            </div>

            <div class="card-body">
              <div class="product-name"><?= $nom ?></div>
              <div class="product-price">
                <?php if ($promoOk): ?>
                  <div class="text-muted text-decoration-line-through" style="font-size:.85rem;">
                    <?= number_format($prixBase, 2) ?> DT
                  </div>
                  <div class="fw-bold" style="color:#dc3545;">
                    <?= number_format($prixFinal, 2) ?> DT
                  </div>
                <?php else: ?>
                  <?= number_format($prixBase, 2) ?> DT
                <?php endif; ?>
              </div>

            </div>
          </div>

        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center text-muted w-100">Aucun nouveau produit pour le moment.</p>
      <?php endif; ?>
    </div>
  </section>

  <section id="categories" class="container py-5">
    <h2 class="section-title text-center mb-4">Nos catégories</h2>
    <?php
    $sql = "SELECT * FROM categories WHERE statut='actif' ORDER BY date_ajout DESC";
    $resCat = mysqli_query($con, $sql);

    if ($resCat && mysqli_num_rows($resCat) > 0) {
      echo '<div class="row justify-content-center g-4">';
      while ($row = mysqli_fetch_assoc($resCat)) {
        $catName = htmlspecialchars($row['nom'], ENT_QUOTES, 'UTF-8');
        $catImage = str_replace('../', '', $row['image']);
        $catLink = 'all_products.php?categorie=' . urlencode($row['id']);

        echo '<div class="col-12 col-md-6">';
        echo '  <a href="' . $catLink . '" class="category-card d-block position-relative overflow-hidden">';
        echo '    <img src="' . htmlspecialchars($catImage, ENT_QUOTES, 'UTF-8') . '" alt="' . $catName . '" class="w-100 h-100">';
        echo '    <div class="overlay d-flex align-items-end justify-content-center">';
        echo '      <span class="category-name text-white">' . $catName . '</span>';
        echo '    </div>';
        echo '  </a>';
        echo '</div>';
      }
      echo '</div>';
    } else {
      echo '<p class="text-muted text-center">Aucune catégorie disponible pour le moment.</p>';
    }
    ?>
  </section>

  <section class="container mt-5">
    <h2 class="section-title">Nos engagements</h2>
    <div class="row text-center">
      <div class="col-md-3 col-6 policy">
        <i class="bi bi-truck"></i>
        <h6>Livraison rapide</h6>
        <p>Partout en Tunisie sous 48h.</p>
      </div>
      <div class="col-md-3 col-6 policy">
        <i class="bi bi-arrow-repeat"></i>
        <h6>Retour facile</h6>
        <p>Échange ou remboursement sous 7 jours.</p>
      </div>
      <div class="col-md-3 col-6 policy">
        <i class="bi bi-shield-check"></i>
        <h6>Paiement sécurisé</h6>
        <p>Vos données sont protégées.</p>
      </div>
      <div class="col-md-3 col-6 policy">
        <i class="bi bi-chat-dots"></i>
        <h6>Support 24/7</h6>
        <p>Notre équipe est à votre écoute.</p>
      </div>
    </div>
  </section>

  <!-- MODAL -->
  <div class="modal fade" id="addCartModal" tabindex="-1" aria-labelledby="addCartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">

        <div class="modal-header border-0">
          <h6 class="modal-title fw-semibold" id="addCartModalLabel">Ajouter au panier</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3 align-items-start">
            <div class="col-md-5 text-center position-relative">

              <!-- ✅ Badge promo sur l’image -->
              <div id="modalPromoBadge"
                class="position-absolute top-0 start-0 m-2 px-2 py-1 rounded text-white fw-bold d-none"
                style="background:#dc3545; font-size:.85rem; z-index:2;">
                -10%
              </div>

              <img id="modalProductImage" src="" alt="" class="img-fluid rounded"
                style="max-height:260px; width:100%; object-fit:cover;">
            </div>

            <div class="col-md-7">
              <h5 id="modalProductName" class="fw-bold mb-1"></h5>

              <!-- ✅ Prix normal + prix remisé -->
              <div class="mb-3">
                <div class="text-muted small d-none" id="modalOldPrice"
                  style="text-decoration: line-through;">
                  0.00 DT
                </div>

                <div class="text-muted">
                  Prix unitaire:
                  <span class="fw-semibold" id="modalProductPrice"></span> DT
                </div>
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


  <footer>© <?= date('Y'); ?> Lagos — Votre boutique de confiance.</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

  <script>
    // ✅ CSRF token
    const CSRF_TOKEN = <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    // ✅ Badge panier global (utilisé par index + panier + autres pages)
    function updateCartBadge(count) {
      const badge = document.querySelector(".cart-count");
      if (!badge) return;
      const n = parseInt(count || 0, 10);
      badge.textContent = String(n);
      badge.style.display = (n > 0) ? "inline-block" : "none";
    }
    window.updateCartBadge = updateCartBadge;

    // ✅ Charger le badge panier au démarrage (besoin de ajax_get_cart_count.php)
    async function loadCartCount() {
      try {
        const r = await fetch("ajax_get_cart.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            csrf_token: CSRF_TOKEN
          })
        });
        const data = await r.json();
        if (r.ok && data.success) {
          updateCartBadge(data.cart_count);
        }
      } catch (e) {}
    }

    // ---------------- Modal state ----------------
    let modalState = {
      product: null,
      selectedColor: null,
      selectedSize: null,
      stock: 0,
      bsModal: null
    };

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

    function toast(msg, ok = true) {
      Toastify({
        text: msg,
        duration: 2500,
        gravity: "top",
        position: "right",
        backgroundColor: ok ? "#111" : "#dc3545",
        close: true
      }).showToast();
    }

    function showWarn(msg) {
      m.warn.textContent = msg;
      m.warn.classList.remove('d-none');
    }

    function hideWarn() {
      m.warn.textContent = '';
      m.warn.classList.add('d-none');
    }

    function buildStockMap(detailsArr) {
      const stockMap = {};

      (detailsArr || []).forEach(d => {
        const c = (d.couleur || "").trim();
        const raw = (d.taille || "").trim();
        const q = parseInt(d.quantite || 0, 10);

        if (!c || !raw) return;

        // ✅ split tailles: "s,m,l" => ["s","m","l"]
        const sizes = raw.split(",").map(x => x.trim()).filter(Boolean);

        stockMap[c] ??= {};

        sizes.forEach(sz => {
          // ⚠️ si tu veux répartir la quantité sur chaque taille:
          // stockMap[c][sz] = q;  (même q pour chaque taille)
          stockMap[c][sz] = (stockMap[c][sz] || 0) + q;
        });
      });

      return stockMap;
    }


    function updateBtnState() {
      const ok = !!(modalState.selectedColor && modalState.selectedSize && modalState.stock > 0);
      m.btnAdd.disabled = !ok;
    }

    function clampQty() {
      let q = parseInt(m.qty.value || "1", 10);
      if (isNaN(q) || q < 1) q = 1;
      if (modalState.stock > 0 && q > modalState.stock) q = modalState.stock;
      m.qty.value = q;
    }

    function renderSizes() {
      m.sizes.innerHTML = "";
      m.chosenSize.textContent = "—";

      modalState.selectedSize = null;
      modalState.stock = 0;
      m.stock.textContent = "—";

      m.qty.value = 1;
      m.qty.disabled = true;

      hideWarn();
      updateBtnState();

      if (!modalState.selectedColor) return;

      const sizesMap = (modalState.product?._stockMap?.[modalState.selectedColor]) || {};
      let keys = Object.keys(sizesMap);

      // ✅ tri tailles (xs, s, m, l, xl...)
      const order = ["xs", "s", "m", "l", "xl", "xxl", "xxxl"];
      keys.sort((a, b) => {
        const A = String(a).toLowerCase().trim();
        const B = String(b).toLowerCase().trim();

        const ia = order.indexOf(A);
        const ib = order.indexOf(B);

        if (ia !== -1 && ib !== -1) return ia - ib;
        if (ia !== -1) return -1;
        if (ib !== -1) return 1;
        return A.localeCompare(B);
      });

      if (keys.length === 0) {
        showWarn("Aucune taille disponible.");
        return;
      }

      keys.forEach(size => {
        const qty = parseInt(sizesMap[size] || 0, 10);
        const disabled = qty <= 0;

        const b = document.createElement("button");
        b.type = "button";
        b.className = "modal-size" + (disabled ? " disabled" : "");
        b.textContent = size; // ✅ affiche juste la taille

        // Option: afficher stock à côté (si tu veux)
        // b.textContent = disabled ? `${size} (0)` : `${size} (${qty})`;

        b.dataset.size = size;

        b.addEventListener("click", () => {
          if (disabled) return;

          document.querySelectorAll("#modalProductSizes .modal-size")
            .forEach(x => x.classList.remove("selected"));

          b.classList.add("selected");

          modalState.selectedSize = size;
          modalState.stock = qty;

          m.chosenSize.textContent = size;
          m.stock.textContent = String(qty);

          m.qty.disabled = false;
          m.qty.max = qty;
          clampQty();

          updateBtnState();
        });

        m.sizes.appendChild(b);
      });

      // ✅ auto-select première taille dispo
      const firstAvail = keys.find(k => parseInt(sizesMap[k] || 0, 10) > 0);
      if (firstAvail) {
        const btn = [...document.querySelectorAll("#modalProductSizes .modal-size")]
          .find(x => x.dataset.size === firstAvail);
        btn?.click();
      } else {
        showWarn("Rupture de stock.");
        m.stock.textContent = "0";
        m.qty.disabled = true;
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

    function renderColors() {
      m.colors.innerHTML = '';
      m.chosenColor.textContent = '—';
      modalState.selectedColor = null;

      const map = modalState.product._stockMap || {};
      const colorKeys = Object.keys(map);

      colorKeys.forEach(color => {
        const total = Object.values(map[color] || {}).reduce((a, b) => a + parseInt(b || 0, 10), 0);
        const disabled = total <= 0;

        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'modal-color' + (disabled ? ' disabled' : '');
        b.style.background = color;
        b.dataset.color = color;
        b.title = color;

        b.onclick = () => {
          if (disabled) return;
          document.querySelectorAll('#modalProductColors .modal-color').forEach(x => x.classList.remove('selected'));
          b.classList.add('selected');

          modalState.selectedColor = color;
          m.chosenColor.textContent = colorFamilyFromHex(color);

          renderSizes();
          updateBtnState();
        };

        m.colors.appendChild(b);
      });

      const firstAvail = colorKeys.find(c => {
        const total = Object.values(map[c] || {}).reduce((a, b) => a + parseInt(b || 0, 10), 0);
        return total > 0;
      });

      if (firstAvail) {
        const btn = [...document.querySelectorAll('#modalProductColors .modal-color')].find(x => x.dataset.color === firstAvail);
        btn?.click();
      } else {
        showWarn("Produit en rupture de stock.");
        m.qty.disabled = true;
        updateBtnState();
      }
    }

    // ✅ appelée depuis le HTML (onclick)
    function openAddCartModal(product) {
      modalState.product = product;
      modalState.product._stockMap = buildStockMap(product.details || []);
      modalState.selectedColor = null;
      modalState.selectedSize = null;
      modalState.stock = 0;

      m.img.src = product.image || "images/no-image.png";
      m.name.textContent = product.nom || "";

      // ✅ Promo UI dans le modal
      const oldPriceEl = document.getElementById("modalOldPrice");
      const badgeEl = document.getElementById("modalPromoBadge");

     

      // reset
      oldPriceEl.classList.add("d-none");
      oldPriceEl.textContent = "";
      badgeEl.classList.add("d-none");
      badgeEl.textContent = "";
       const prixBase = parseFloat(product.prix_base || 0);
      const prixFinal = parseFloat(product.prix_final || product.prix || 0);

      m.price.textContent = prixFinal.toFixed(2);

      if (product.promo?.active) {
        // afficher prix barré
        if (prixBase > prixFinal) {
          oldPriceEl.classList.remove("d-none");
          oldPriceEl.textContent = prixBase.toFixed(2) + " DT";
        }

        // badge
        const type = product.promo.type;
        const val = parseFloat(product.promo.value || 0);

        let label = "";
        if (type === "amount") label = "-" + val.toFixed(2) + " DT";
        else label = "-" + (Number.isInteger(val) ? val : val.toFixed(1)) + "%";

        badgeEl.textContent = label;
        badgeEl.classList.remove("d-none");
      }


      m.qty.value = 1;
      m.qty.min = 1;
      m.qty.disabled = false;
      m.stock.textContent = '—';
      hideWarn();

      renderColors();
      updateBtnState();

      if (!modalState.bsModal) {
        modalState.bsModal = new bootstrap.Modal(document.getElementById('addCartModal'));
      }
      modalState.bsModal.show();
    }
    window.openAddCartModal = openAddCartModal;

    // qty controls
    m.plus.addEventListener('click', () => {
      if (modalState.stock <= 0) return;
      let q = parseInt(m.qty.value || "1", 10);
      if (isNaN(q)) q = 1;
      if (q >= modalState.stock) return;
      m.qty.value = q + 1;
    });

    m.minus.addEventListener('click', () => {
      let q = parseInt(m.qty.value || "1", 10);
      if (isNaN(q) || q <= 1) return;
      m.qty.value = q - 1;
    });

    m.qty.addEventListener('input', clampQty);

    // ✅ AJAX add to cart (server gère connecté/passager)
    async function ajaxAddToCart() {
      if (!modalState.selectedColor || !modalState.selectedSize) {
        showWarn("Veuillez choisir couleur et taille.");
        return;
      }
      if (modalState.stock <= 0) {
        showWarn("Variante en rupture.");
        return;
      }

      clampQty();
      const qty = parseInt(m.qty.value, 10);

      try {
        const r = await fetch("ajax_add_to_cart.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            csrf_token: CSRF_TOKEN,
            product_id: modalState.product.id,
            color: modalState.selectedColor,
            size: modalState.selectedSize,
            qty: qty
          })
        });

        const data = await r.json();
        if (!r.ok || !data.success) {
          toast(data.message || "Erreur ajout panier", false);
          return;
        }

        // ✅ MAJ badge
        updateCartBadge(data.cart_count);

        toast("Ajouté au panier ✅", true);
        modalState.bsModal.hide();

      } catch (e) {
        toast("Connexion impossible. Réessaie.", false);
      }
    }

    m.btnAdd.addEventListener('click', ajaxAddToCart);

    // ✅ load cart badge at start
    document.addEventListener("DOMContentLoaded", () => {
      loadCartCount();

      // toast logout
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get("logout") === "1") {
        toast("Vous avez été déconnecté avec succès 👋", true);
        const newUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
      }
    });
  </script>
</body>

</html>