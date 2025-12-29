// ==============================
// PANIER.JS (UNIFIÉ DB)
// connecté => user_id
// passager => guest_token (session)
// ==============================

$(document).ready(function () {
  $('#myTableProducts').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajaxfiles/productQuery.php",
        type: "POST",
        data: function(d){
          d.getAllProduct = true;
        }
      },
      columnDefs: [
        { targets:[0,3,5,8], orderable:false },
        { targets:[0,3,5,8], searchable:false }
      ]
    });

  // -----------------------------------------
  // 1) DATATABLE PANIER (connecté + passager)
  // -----------------------------------------
  if ($('#myTablePanier').length) {
    window.cartTable = $('#myTablePanier').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajaxfiles/cartQuery.php",
        type: "POST",
        data: function (d) {
          d.getPanier = true;
          d.csrf_token = window.CSRF_TOKEN || '';
        }
      },
      columns: [
        { data: 0 }, // produit
        { data: 1 }, // prix
        { data: 2 }, // qty controls
        { data: 3 }, // total
        { data: 4 }  // delete
      ],
      searching: false,
      ordering: false,
      lengthChange: false,
      language: {
        emptyTable: "Aucune donnée disponible",
        info: "Affichage de _START_ à _END_ sur _TOTAL_ entrées",
        infoEmpty: "Affichage de 0 à 0 sur 0 entrées",
        infoFiltered: "(filtré à partir de _MAX_ entrées au total)",
        loadingRecords: "Chargement...",
        processing: "Traitement...",
        zeroRecords: "Aucun enregistrement correspondant trouvé",
        paginate: {
          first: "Premier",
          last: "Dernier",
          next: "Suivant",
          previous: "Précédent"
        }
      },
      drawCallback: function () {
        refreshCartSummary(); // update totals after each draw
        loadCartCount();      // update badge
      }
    });
  }

  // charger badge au démarrage
  loadCartCount();
  refreshCartSummary();
});


// ======================================================
// 2) BADGE COUNT (connecté + passager) depuis DB
// ======================================================
function updateCartUI(count) {
  const badge = document.querySelector('.cart-count');
  if (!badge) return;

  const c = parseInt(count || 0, 10);
  badge.textContent = c;
  badge.style.display = c > 0 ? 'inline-block' : 'none';
}

function loadCartCount() {
  $.get('ajaxfiles/cartQuery.php', { getCartCount: true, csrf_token: window.CSRF_TOKEN || '' }, function (res) {
    if (res && res.status === 'success') {
      updateCartUI(res.cart_count);
    }
  }, 'json');
}


// ======================================================
// 3) SUMMARY (sous-total / total) depuis DB
// ======================================================
function refreshCartSummary() {
  if (!$('#subtotal').length || !$('#total-general').length) return;

  $.get('ajaxfiles/cartQuery.php', { getCartSummary: true, csrf_token: window.CSRF_TOKEN || '' }, function (res) {
    if (res && res.status === 'success') {
      $('#subtotal').text(parseFloat(res.subtotal).toFixed(3) + " DT");
      $('#total-general').text(parseFloat(res.total).toFixed(3) + " DT");
    }
  }, 'json');
}


// ======================================================
// 4) UPDATE QUANTITY (connecté + passager DB)
// ======================================================
function updateQuantity(cartId, newQuantity) {
  newQuantity = parseInt(newQuantity || 1, 10);
  if (isNaN(newQuantity) || newQuantity < 1) return;

  $.ajax({
    url: 'ajaxfiles/cartQuery.php',
    type: 'POST',
    data: {
      updateQty: true,
      cart_id: cartId,
      quantite: newQuantity,
      csrf_token: window.CSRF_TOKEN || ''
    },
    dataType: 'json',
    success: function (res) {
      if (res.status === 'success') {
        if (window.cartTable) window.cartTable.ajax.reload(null, false);
        loadCartCount();
        refreshCartSummary();
      } else {
        Swal.fire("Erreur", res.message || "Impossible de modifier la quantité", "error");
      }
    },
    error: function () {
      Swal.fire("Erreur", "Serveur injoignable", "error");
    }
  });
}

function incrementQuantity(cartId, currentQty) {
  updateQuantity(cartId, parseInt(currentQty, 10) + 1);
}

function decrementQuantity(cartId, currentQty) {
  currentQty = parseInt(currentQty, 10);
  if (currentQty > 1) updateQuantity(cartId, currentQty - 1);
}


// ======================================================
// 5) REMOVE ITEM (connecté + passager DB)
// ======================================================
function removeFromCart(cartId) {
  Swal.fire({
    title: "Supprimer ?",
    text: "Ce produit sera retiré du panier.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Oui, supprimer",
    cancelButtonText: "Annuler"
  }).then((result) => {
    if (!result.isConfirmed) return;

    $.ajax({
      url: 'ajaxfiles/cartQuery.php',
      type: 'POST',
      data: {
        removeItem: true,
        cart_id: cartId,
        csrf_token: window.CSRF_TOKEN || ''
      },
      dataType: 'json',
      success: function (res) {
        if (res.status === 'success') {
          Toastify({
            text: "Produit supprimé ✅",
            duration: 2000,
            gravity: "top",
            position: "right",
            backgroundColor: "black",
            close: true
          }).showToast();

          if (window.cartTable) window.cartTable.ajax.reload(null, false);
          loadCartCount();
          refreshCartSummary();
        } else {
          Swal.fire("Erreur", res.message || "Suppression impossible", "error");
        }
      },
      error: function () {
        Swal.fire("Erreur", "Serveur injoignable", "error");
      }
    });
  });
}


// ======================================================
// 6) MODAL AJOUT PANIER (depuis index.php / produit.php)
//    => utilise ajax_add_to_cart.php (JSON)
// ======================================================
let CURRENT_PRODUCT = null;

function openAddCartModal(product) {
  CURRENT_PRODUCT = product;

  const prix = parseFloat(product.prix) || 0;
  $('#modalProductImage').attr('src', product.image || 'images/no-image.png').attr('alt', product.nom || '');
  $('#modalProductName').text(product.nom || '');
  $('#modalProductPrice').text(prix.toFixed(3));

  // Reset qty
  $('#quantity').val(1);

  // stocke productId dans bouton
  $('#modalAddCartBtn').data('productId', product.id);

  // render colors
  const $colors = $('#modalProductColors').empty();
  const $sizes = $('#modalProductSizes').empty();

  if (!Array.isArray(product.details)) product.details = [];

  // extraire couleurs uniques
  const colors = [...new Set(product.details.map(d => d.couleur))].filter(Boolean);

  colors.forEach((c, idx) => {
    const totalColorStock = product.details
      .filter(d => d.couleur === c)
      .reduce((sum, d) => sum + parseInt(d.quantite || 0, 10), 0);

    const disabled = totalColorStock <= 0;

    const $c = $('<div>')
      .addClass('color-circle me-2 mb-2')
      .attr('data-color', c)
      .css({
        width: '30px',
        height: '30px',
        borderRadius: '50%',
        backgroundColor: c,
        border: '1px solid #ccc',
        cursor: disabled ? 'not-allowed' : 'pointer',
        opacity: disabled ? 0.35 : 1,
        position: 'relative'
      });

    if (!disabled && idx === 0) $c.addClass('selected').css('border', '2px solid black');
    if (disabled) $c.append('<span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#dc3545;font-weight:900;">×</span>');

    $colors.append($c);
  });

  // première couleur sélectionnée => tailles
  const firstColor = $colors.find('.color-circle.selected').data('color');
  if (firstColor) updateSizes(firstColor, product);

  // click color
  $colors.off('click').on('click', '.color-circle', function () {
    if ($(this).css('cursor') === 'not-allowed') return;

    $colors.find('.color-circle').removeClass('selected').css('border', '1px solid #ccc');
    $(this).addClass('selected').css('border', '2px solid black');

    updateSizes($(this).data('color'), product);
  });

  // open modal
  const modalInstance = new bootstrap.Modal(document.getElementById('addCartModal'));
  modalInstance.show();
}

function updateSizes(selectedColor, product) {
  const $sizes = $('#modalProductSizes').empty();

  const sizes = product.details
    .filter(d => d.couleur === selectedColor)
    .map(d => ({ taille: d.taille, quantite: parseInt(d.quantite || 0, 10) }));

  sizes.forEach((s, idx) => {
    const disabled = s.quantite <= 0;

    const $s = $('<div>')
      .addClass('size-square me-2 mb-2')
      .attr('data-size', s.taille)
      .text((s.taille || '').toUpperCase() + (disabled ? " (0)" : ""))
      .css({
        padding: '6px 12px',
        border: '1px solid #ccc',
        borderRadius: '8px',
        cursor: disabled ? 'not-allowed' : 'pointer',
        opacity: disabled ? 0.4 : 1,
        userSelect: 'none'
      });

    if (!disabled && idx === 0) $s.addClass('selected').css({ background: '#111', color: '#fff', borderColor: '#111' });

    $sizes.append($s);
  });

  // click size
  $sizes.off('click').on('click', '.size-square', function () {
    if ($(this).css('cursor') === 'not-allowed') return;

    $sizes.find('.size-square').removeClass('selected').css({ background: '', color: '', borderColor: '#ccc' });
    $(this).addClass('selected').css({ background: '#111', color: '#fff', borderColor: '#111' });
  });
}


// ======================================================
// 7) SUBMIT ADD CART (JSON -> ajax_add_to_cart.php)
// ======================================================
$('#modalAddCartBtn').off('click').on('click', function () {
  const productId = $(this).data('productId');
  const qty = parseInt($('#quantity').val(), 10) || 1;

  const color = $('#modalProductColors .color-circle.selected').data('color') || null;
  const size = $('#modalProductSizes .size-square.selected').data('size') || null;

  if (!productId || !color || !size) {
    Swal.fire("Attention", "Veuillez choisir couleur + taille", "warning");
    return;
  }

  fetch('ajax_add_to_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      csrf_token: window.CSRF_TOKEN || '',
      product_id: productId,
      color: color,
      size: size,
      qty: qty
    })
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        updateCartUI(res.cart_count);

        Toastify({
          text: "Ajouté au panier ✅",
          duration: 2000,
          gravity: "top",
          position: "right",
          backgroundColor: "black",
          close: true
        }).showToast();

        const modal = bootstrap.Modal.getInstance(document.getElementById('addCartModal'));
        modal?.hide();

        // si on est sur panier.php -> refresh table
        if (window.cartTable) window.cartTable.ajax.reload(null, false);
        refreshCartSummary();

      } else {
        Swal.fire("Erreur", res.message || "Ajout impossible", "error");
      }
    })
    .catch(() => Swal.fire("Erreur", "Serveur injoignable", "error"));
});


// ======================================================
// 8) qty controls modal
// ======================================================
function increaseQty() {
  const qty = document.getElementById('quantity');
  qty.value = parseInt(qty.value || "1", 10) + 1;
}
function decreaseQty() {
  const qty = document.getElementById('quantity');
  const v = parseInt(qty.value || "1", 10);
  if (v > 1) qty.value = v - 1;
}



function buildDetailsFrom(containerSelector){
    const details = [];
    $(containerSelector).find('.detail-row').each(function(){
      const color = $(this).find('input[type="color"]').val();
      const size  = $(this).find('input[type="text"]').val();
      const qty   = parseInt($(this).find('input[type="number"]').val() || "0", 10);
      if (color && size) {
        details.push({ couleur: color, taille: size, quantite: isNaN(qty)?0:qty });
      }
    });
    return details;
  }

  function enableRemoveButtons(containerSelector){
    const rows = $(containerSelector).find('.detail-row');
    rows.find('.btn-remove-detail').hide();
    if (rows.length > 1) rows.find('.btn-remove-detail').show();
  }
  function previewImages(input, previewSelector){
    const preview = $(previewSelector);
    preview.html('');
    const files = input.files || [];
    Array.from(files).forEach(file => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = $('<img>').attr('src', e.target.result);
        preview.append(img);
      };
      reader.readAsDataURL(file);
    });
  }
  window.ajouter_produit = function(){
    $.ajax({
      url: 'ajaxfiles/productQuery.php',
      type: 'POST',
      data: { ajouterModalProduit: true },
      success: function(html){
        $('#update').html(html);

        // init remove buttons
        enableRemoveButtons('#details_produit_add');

        // show modal (Bootstrap 5)
        const modalEl = document.getElementById('ajouterProduitModal');
        if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
      }
    });
  };
  window.modifierProduit = function(id){
    $.ajax({
      url: 'ajaxfiles/productQuery.php',
      type: 'POST',
      data: { modifierModalProduit: true, id: id },
      success: function(html){
        $('#update').html(html);

        enableRemoveButtons('#details_produit_edit');

        const modalEl = document.getElementById('modifierProduitModal');
        if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
      }
    });
  };
  window.supprimerProduit = function(id){
    Swal.fire({
      title: "Supprimer ?",
      text: "Cette action est irréversible.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Oui, supprimer",
      cancelButtonText: "Annuler"
    }).then(async (r) => {
      if(!r.isConfirmed) return;

      const res = await fetch("ajaxfiles/productQuery.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: new URLSearchParams({ supprimerProduit: 1, id: id })
      });
      const data = await res.json();

      if (data.status === "success") {
        Swal.fire("OK", data.message, "success");
        $('#myTableProducts').DataTable().ajax.reload(null,false);
      } else {
        Swal.fire("Erreur", data.message || "Erreur suppression", "error");
      }
    });
  };
$(document).on('change', '#image_produit_add', function(){
    previewImages(this, '#preview_add');
  });

  $(document).on('click', '#add_details_product_add', function(){
    const row = `
      <div class="row g-2 align-items-end detail-row mt-2">
        <div class="col-md-4">
          <label class="form-label">Couleur</label>
          <input class="form-control" type="color" value="#000000" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Taille</label>
          <input class="form-control" type="text" placeholder="S, M, L..." required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Quantité</label>
          <input class="form-control" type="number" min="0" value="1" required>
        </div>
        <div class="col-md-1 text-end">
          <button type="button" class="btn btn-danger btn-sm btn-remove-detail">-</button>
        </div>
      </div>
    `;
    $('#details_produit_add').append(row);
    enableRemoveButtons('#details_produit_add');
  });

  $(document).on('click', '#details_produit_add .btn-remove-detail', function(){
    $(this).closest('.detail-row').remove();
    enableRemoveButtons('#details_produit_add');
  });

  $(document).on('submit', '#AjouterProduit', function(e){
    e.preventDefault();

    const details = buildDetailsFrom('#details_produit_add');
    const fd = new FormData(this);
    fd.append('ajouterProduit', 1);
    fd.append('details_produit', JSON.stringify(details));

    $('#btnSubmitAddProduct').prop('disabled', true);

    $.ajax({
      url: 'ajaxfiles/productQuery.php',
      type: 'POST',
      data: fd,
      contentType: false,
      processData: false,
      success: function(res){
        let r = null;
        try { r = (typeof res === "string") ? JSON.parse(res) : res; } catch(e){}

        if (r && r.status === 'success') {
          const modalEl = document.getElementById('ajouterProduitModal');
          if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();

          Swal.fire("Succès", r.message, "success");
          $('#myTableProducts').DataTable().ajax.reload(null,false);
        } else {
          Swal.fire("Erreur", r?.message || "Erreur ajout", "error");
        }
      },
      error: function(){
        Swal.fire("Erreur", "Erreur serveur", "error");
      },
      complete: function(){
        $('#btnSubmitAddProduct').prop('disabled', false);
      }
    });
  });

  // -----------------------------
  // Delegated events: Edit modal
  // -----------------------------
  $(document).on('change', '#image_produit_edit', function(){
    previewImages(this, '#preview_edit');
  });

  $(document).on('click', '#add_details_product_edit', function(){
    const row = `
      <div class="row g-2 align-items-end detail-row mt-2">
        <div class="col-md-4">
          <label class="form-label">Couleur</label>
          <input class="form-control" type="color" value="#000000" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Taille</label>
          <input class="form-control" type="text" placeholder="S, M, L..." required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Quantité</label>
          <input class="form-control" type="number" min="0" value="1" required>
        </div>
        <div class="col-md-1 text-end">
          <button type="button" class="btn btn-danger btn-sm btn-remove-detail">-</button>
        </div>
      </div>
    `;
    $('#details_produit_edit').append(row);
    enableRemoveButtons('#details_produit_edit');
  });

  $(document).on('click', '#details_produit_edit .btn-remove-detail', function(){
    $(this).closest('.detail-row').remove();
    enableRemoveButtons('#details_produit_edit');
  });
  $(document).on('submit', '#ModifierProduit', function(e){
    e.preventDefault();

    const details = buildDetailsFrom('#details_produit_edit');
    const fd = new FormData(this);
    fd.append('modifierProduit', 1);
    fd.append('details_produit', JSON.stringify(details));

    $('#btnSubmitEditProduct').prop('disabled', true);

    $.ajax({
      url: 'ajaxfiles/productQuery.php',
      type: 'POST',
      data: fd,
      contentType: false,
      processData: false,
      success: function(res){
        let r = null;
        try { r = (typeof res === "string") ? JSON.parse(res) : res; } catch(e){}

        if (r && r.status === 'success') {
          const modalEl = document.getElementById('modifierProduitModal');
          if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();

          Swal.fire("Succès", r.message, "success");
          $('#myTableProducts').DataTable().ajax.reload(null,false);
        } else {
          Swal.fire("Erreur", r?.message || "Erreur modification", "error");
        }
      },
      error: function(){
        Swal.fire("Erreur", "Erreur serveur", "error");
      },
      complete: function(){
        $('#btnSubmitEditProduct').prop('disabled', false);
      }
    });
  });

/* function ajouter_produit() { 
  $.ajax({ 
    url: 'ajaxfiles/productQuery.php', 
    type: 'POST', 
    data: { ajouterModalProduit: true }, 
    success: function (data) { 
      $('#update').html(data); 
      $('#ajouterProduitModal').modal('show'); 
    }, 
    error: function (xhr, status, error) { 
      console.error("Erreur AJAX:", error); 
    } 
  }); 
}

function modifierProduit(id) { 
  $.ajax({ 
    url: 'ajaxfiles/productQuery.php', 
    type: 'POST', 
    data: { 
      modifierModalProduit: true, 
      id: id 
    }, 
    success: function (data) { 
      $('#update').html(data); 
      $('#modifierProduitModal').modal('show'); 
    }, 
    error: function (xhr, status, error) { 
      console.error("Erreur AJAX:", error); 
    } 
  }); 
}
 */
/* $(document).on('submit', '#AjouterProduit', function (event) { 
  event.preventDefault(); 
  var formData = new FormData(this); 
  var productDetails = []; 
  // Collecte des détails 
   $('[id^=detail_]').each(function (i, item) { 
    var couleur = $(#couleur_produit_${i}).val(); 
    var taille = $(#taille_produit_${i}).val(); 
    var quantite = $(#quantite_produit_${i}).val(); 
    productDetails.push({ couleur: couleur, taille: taille, quantite: quantite }); 
  }); 
  formData.append('details_produit', JSON.stringify(productDetails)); 
  formData.append('ajouterProduit', true); 
  $.ajax({ 
    url: 'ajaxfiles/productQuery.php', 
    type: 'POST', 
    data: formData, processData: false, 
    contentType: false, dataType: 'JSON', 
    success: function (data) { 
      Swal.fire({ 
        icon: data.status === 'success' ? 'success' : 'error', 
        title: data.status === 'success' ? 'Produit ajouté ✅' : 'Erreur ❌', 
        text: data.message, 
        confirmButtonText: 'OK' 
      }).then(() => { 
        if (data.status === 'success') { 
          $('#ajouterProduitModal').modal('hide'); 
          $('.modal-backdrop').remove(); 
          $('#AjouterProduit')[0].reset(); 
          $('#preview').html(''); } }); 
          $('#myTableProducts').DataTable().ajax.reload(null, false); 
        }, error: function (xhr, status, error) { 
          console.error('Erreur AJAX:', error); 
          Swal.fire(
            'Erreur serveur', 
            'Impossible de contacter le serveur.', 
            'error'); 
          } 
        }); 
      }); */
