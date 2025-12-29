<?php
include 'connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// guest token
if (empty($_SESSION['guest_token'])) {
  $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}

// csrf
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Panier | LAGOS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<link rel="stylesheet" href="css/style.css">

<style>
  .breadcrumb-title { font-weight: 600; margin-bottom: 0.5rem; }
  .table thead th { background-color: #fff; font-weight: 600; }
  .table tbody tr:hover { background-color: #f1f1f1; }
  .card-summary { border-radius: 12px; padding: 20px; background-color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
  .card-summary h2 { font-weight: 600; margin-bottom: 1rem; }
  .list-group-item { background-color: #f8f9fa; border: 1px solid #e0e0e0; display:flex; justify-content:space-between; }
  .btn-lagos { background-color: #111; color: #fff; font-weight:500; border-radius:8px; width:100%; margin-top:1rem; transition:0.3s; }
  .btn-lagos:hover { background-color: #333; }

  .qty-box { display:flex; align-items:center; justify-content:center; gap:6px; }
  .qty-box button{ width:32px; height:32px; border-radius:8px; }
  .qty-box input{ width:64px; text-align:center; border-radius:8px; }

  .empty-cart {
    background:#fff; border-radius:12px; padding:30px; text-align:center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
</style>
</head>
<body>

<?php include 'header_user.php'; ?>

<div class="container py-5">
  <div id="update"></div><!-- ✅ commande.js injecte ici le modal -->

  <!-- Breadcrumb -->
  <div class="text-center mb-4">
    <h2 class="breadcrumb-title">Mon Panier</h2>
    <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="index.php" class="text-dark">Accueil</a></li>
        <li class="breadcrumb-item active" aria-current="page">Panier</li>
      </ol>
    </nav>
  </div>

  <div class="row g-4">
    <!-- Tableau -->
    <div class="col-lg-8">
      <div id="emptyCartBox" class="empty-cart d-none">
        <i class="bi bi-bag fs-1"></i>
        <h5 class="mt-2">Votre panier est vide</h5>
        <p class="text-muted mb-3">Ajoutez des articles pour continuer.</p>
        <a href="all_products.php" class="btn btn-dark">Voir les produits</a>
      </div>

      <div id="cartTableBox" class="table-responsive bg-white rounded shadow-sm p-3">
        <table id="myTablePanier" class="table table-hover text-center mb-0">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Variante</th>
              <th>Prix</th>
              <th>Quantité</th>
              <th>Total</th>
              <th>Supprimer</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- Résumé -->
    <div class="col-lg-4">
      <div class="card-summary h-100 d-flex flex-column justify-content-between">
        <div>
          <h2 class="text-center">Résumé de la Commande</h2>
          <ul class="list-group mt-3 mb-3" id="cart-summary">
            <li class="list-group-item">
              <span>Sous-total</span>
              <span id="subtotal">0 DT</span>
            </li>
            <li class="list-group-item">
              <span>Frais de livraison</span>
              <span id="shipping">7.000 DT</span>
            </li>
            <li class="list-group-item fw-bold">
              <span>Total Général</span>
              <span id="total-general">0 DT</span>
            </li>
          </ul>
        </div>

        <!-- ✅ utilise le modal commande.js -->
        <button class="btn btn-lagos" id="btnCheckout" onclick="ValiderCommande()">Confirmer l'achat</button>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
  window.CSRF_TOKEN = <?= json_encode($csrf, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
  window.userId = <?= $userId ? (int)$userId : 'null' ?>;

  const SHIPPING_FEE = 7.000;

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

  function money(x){
    const n = Number(x || 0);
    return n.toFixed(3) + " DT";
  }

  async function api(url, payload){
    const r = await fetch(url, {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify(payload)
    });
    const data = await r.json().catch(()=> ({}));
    return { ok: r.ok, data };
  }

  function escapeHtml(str){
    return String(str ?? "")
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }

  // ✅ helper badge (header)
  window.updateCartBadge = function(count){
    const badge = document.querySelector(".cart-count");
    if (!badge) return;
    badge.textContent = String(count || 0);
    badge.style.display = (count && count > 0) ? "inline-block" : "none";
  };

  function toggleEmpty(isEmpty){
    document.getElementById("emptyCartBox").classList.toggle("d-none", !isEmpty);
    document.getElementById("cartTableBox").classList.toggle("d-none", isEmpty);
    document.getElementById("btnCheckout").disabled = isEmpty;
  }

  function renderSummary(subtotal, cartCount){
    $("#subtotal").text(money(subtotal));
    $("#total-general").text(money(Number(subtotal) + SHIPPING_FEE));
    window.updateCartBadge(cartCount);
  }

  function renderTable(items){
    const $tbody = $("#myTablePanier tbody");
    $tbody.empty();

    items.forEach(it => {
      const img = it.image ? `<img src="${escapeHtml(it.image)}" style="width:46px;height:46px;object-fit:cover;border-radius:10px;">` : '';
      const prodHtml = `
        <div class="d-flex align-items-center gap-2 justify-content-center">
          ${img}
          <div class="text-start">
            <div class="fw-semibold">${escapeHtml(it.nom)}</div>
            <div class="text-muted small">#${it.product_id}</div>
          </div>
        </div>
      `;
      const variant = `<span class="badge text-bg-light">${escapeHtml(it.couleur)} / ${escapeHtml(it.taille)}</span>`;

      const qtyHtml = `
        <div class="qty-box">
          <button class="btn btn-light border" onclick="changeQty(${it.cart_id}, -1)">-</button>
          <input class="form-control" value="${it.quantite}" min="1" type="number"
                 onchange="setQty(${it.cart_id}, this.value)">
          <button class="btn btn-light border" onclick="changeQty(${it.cart_id}, 1)">+</button>
        </div>
      `;

      const tr = `
        <tr>
          <td>${prodHtml}</td>
          <td>${variant}</td>
          <td>${money(it.prix)}</td>
          <td>${qtyHtml}</td>
          <td class="fw-semibold">${money(it.line_total)}</td>
          <td>
            <button class="btn btn-outline-danger btn-sm" onclick="removeItem(${it.cart_id})">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
      $tbody.append(tr);
    });
  }

  async function loadCart(){
    const {ok, data} = await api("ajax_get_cart.php", {csrf_token: window.CSRF_TOKEN});
    if (!ok || !data.success){
      toast(data.message || "Impossible de charger le panier", false);
      toggleEmpty(true);
      return;
    }
    toggleEmpty(data.items.length === 0);
    renderTable(data.items);
    renderSummary(data.subtotal, data.cart_count);
  }

  async function changeQty(cartId, delta){
    const {ok, data} = await api("ajax_update_cart_qty.php", {
      csrf_token: window.CSRF_TOKEN,
      cart_id: cartId,
      delta: delta
    });
    if (!ok || !data.success){
      toast(data.message || "Erreur quantité", false);
      await loadCart();
      return;
    }
    await loadCart();
  }

  async function setQty(cartId, value){
    let q = parseInt(value || "1", 10);
    if (isNaN(q) || q < 1) q = 1;

    const {ok, data} = await api("ajax_update_cart_qty.php", {
      csrf_token: window.CSRF_TOKEN,
      cart_id: cartId,
      set_qty: q
    });
    if (!ok || !data.success){
      toast(data.message || "Erreur quantité", false);
      await loadCart();
      return;
    }
    await loadCart();
  }

  async function removeItem(cartId){
    const confirm = await Swal.fire({
      title: "Supprimer cet article ?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Oui, supprimer",
      cancelButtonText: "Annuler"
    });
    if (!confirm.isConfirmed) return;

    const {ok, data} = await api("ajax_remove_cart_item.php", {
      csrf_token: window.CSRF_TOKEN,
      cart_id: cartId
    });
    if (!ok || !data.success){
      toast(data.message || "Erreur suppression", false);
      return;
    }
    toast("Article supprimé ✅", true);
    await loadCart();
  }

  // expose globally
  window.loadCart = loadCart;
  window.changeQty = changeQty;
  window.setQty = setQty;
  window.removeItem = removeItem;

  loadCart();
</script>

<!-- ✅ commande.js (modal validation) -->
<script src="js/commande.js"></script>

</body>
</html>
