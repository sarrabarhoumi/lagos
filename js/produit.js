$(document).ready(function () {
    $('#myTableProducts').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajaxfiles/productQuery.php",
            "type": "POST",
            "data": { 'getAllProduct': true },
        },
        "columns": [
            { "data": 0 },
            { "data": 1 },
            { "data": 2 },
            { "data": 3 },
            { "data": 4 },
            { "data": 5 },
            { "data": 6 },
            { "data": 7 },
            { "data": 8 }
        ],

    });



    const userId = window.userId || null; // Passée depuis PHP si connecté
    let cartGuest = JSON.parse(localStorage.getItem('cart')) || [];

    if (userId) {
        // Utilisateur connecté → DataTable côté serveur
        $('#myTablePanier').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajaxfiles/productQuery.php",
                type: "POST",
                data: { getPanier: true }
            },
            columns: [
                { data: 0 },
                { data: 1 },
                { data: 2 },
                { data: 3 },
                { data: 4 }
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
            }
        });
    } else {
        // Client passager → récupérer les infos complètes depuis la BDD
        $.post('ajaxfiles/cartQuery.php', { cart: cartGuest }, function (res) {
            if (res.status === 'success') {
                let dataGuest = res.cart.map(item => {
                    let total = (item.quantite * item.prix).toFixed(3) + ' DT';
                    let colors = item.couleurs.map(c => `<span class="badge bg-secondary me-1">${c}</span>`).join('');
                    let sizes = item.tailles.map(s => `<span class="badge bg-info me-1">${s}</span>`).join('');

                    return [
                        `<div class="d-flex align-items-center">
                        <img src="${item.image || 'placeholder.jpg'}" style="width:70px;height:70px;object-fit:cover" class="rounded border" />
                        <h5 class="mb-0 ms-3">${item.nom}<br>${colors}<br>${sizes}</h5>
                    </div>`,
                        item.prix + ' DT',
                        `<div class="d-flex justify-content-center align-items-center">
                        <button class="btn btn-sm btn-outline-dark me-2" onclick="decrementQuantityGuest(${item.id})">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <span id="quantite-guest-${item.id}">${item.quantite}</span>
                        <button class="btn btn-sm btn-outline-dark ms-2" onclick="incrementQuantityGuest(${item.id})">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>`,
                        total,
                        `<button class="btn text-danger" onclick="removeFromCartGuest(${item.id})">
                        <i class="bi bi-trash3-fill"></i>
                    </button>`
                    ];
                });

                $('#myTablePanier').DataTable({
                    data: dataGuest,
                    columns: [
                        { title: "Produit" },
                        { title: "Prix" },
                        { title: "Quantité" },
                        { title: "Total" },
                        { title: "Supprimer" }
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
                    }
                });
            }
        }, 'json');
    }


});


function ajouter_produit() {
    $.ajax({
        url: 'ajaxfiles/productQuery.php',
        type: 'POST',
        data: {
            ajouterModalProduit: true
        },
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
$(document).on('submit', '#AjouterProduit', function (event) {
    event.preventDefault();

    var formData = new FormData(this);
    var productDetails = [];

    // Collecte des détails
    $('[id^=detail_]').each(function (i, item) {
        var couleur = $(`#couleur_produit_${i}`).val();
        var taille = $(`#taille_produit_${i}`).val();
        var quantite = $(`#quantite_produit_${i}`).val();

        productDetails.push({
            couleur: couleur,
            taille: taille,
            quantite: quantite
        });
    });

    formData.append('details_produit', JSON.stringify(productDetails));
    formData.append('ajouterProduit', true);

    $.ajax({
        url: 'ajaxfiles/productQuery.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'JSON',
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
                    $('#preview').html('');
                }
            });
            $('#myTableProducts').DataTable().ajax.reload(null, false);
        },
        error: function (xhr, status, error) {
            console.error('Erreur AJAX:', error);
            Swal.fire('Erreur serveur', 'Impossible de contacter le serveur.', 'error');
        }
    });
});


let selectedFiles = [];


$(document).on('change', '#image_produit', function () {
    let files = this.files;


    if (files.length > 0) {
        $.each(files, function (index, file) {

            selectedFiles.push(file);

            let reader = new FileReader();
            reader.onload = function (e) {
                let imgDiv = `
                    <div class="image-container position-relative m-2">
                        <img src="${e.target.result}" class="img-thumbnail" width="100">
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 remove-img" data-index="${selectedFiles.length - 1}">✖</button>
                    </div>
                `;
                $('#preview').append(imgDiv);
            };
            reader.readAsDataURL(file);
        });
    }
});

$(document).on('click', '.remove-img', function () {

    let index = $(this).data('index');
    selectedFiles.splice(index, 1);

    $(this).parent().remove();
    console.log(selectedFiles);
});

$(document).on('click', "#add_details_product", function () {
    let detailsCount = $('#details_produit .d-flex').length;
    console.log('clicked');
    let newDetails = `
            <div id="detail_${detailsCount}" class="d-flex">
                <div class="col-4">
                    <label class="label" for="couleur_produit_${detailsCount}">Couleurs: <span class="required">*</span></label>
                    <input class="form-control select2" type="color" id="couleur_produit_${detailsCount}" name="couleur_produit_${detailsCount}[]" multiple required placeholder="">
                </div>
                <div class="col-4">
                    <label class="label" for="taille_produit_${detailsCount}">Tailles: <span class="required">*</span></label>
                    <input class="form-control select2" type="text" id="taille_produit_${detailsCount}" name="taille_produit_${detailsCount}[]" multiple required placeholder="">
                </div>
                <div class="col-3">
                    <label class="label" for="quantite_produit_${detailsCount}">Quantité: <span class="required">*</span></label>
                    <input class="form-control" type="number" id="quantite_produit_${detailsCount}" name="quantite_produit_${detailsCount}" required>
                </div>
                <div class="align-content-end col-1">
                    <button type="button" class="btn btn-danger p-2" id="remove_details_${detailsCount}">-</button>
                </div>
            </div>
        `;
    $('#details_produit').append(newDetails);
    detailsCount++;
});

function openAddCartModal(product) {
    
    var prix = parseFloat(product.prix) || 0;

    // 🖼️ Image
    $('#modalProductImage').attr('src', product.image).attr('alt', product.nom);

    // 🏷️ Nom et prix
    $('#modalProductName').text(product.nom);
    $('#modalProductPrice').text(prix.toFixed(3));

    // 🎨 Couleurs
    var $colorsContainer = $('#modalProductColors');
    $colorsContainer.empty();

    if (Array.isArray(product.couleurs) && product.couleurs.length > 0) {
        product.couleurs.forEach((c, idx) => {
            // Chercher quantité disponible pour cette couleur
            var hasQty = product.details.some(d => d.couleur === c && parseInt(d.quantite) > 0);

            var colorDiv = $('<div>')
                .addClass('color-circle me-2 mb-2')
                .css({
                    width: '30px',
                    height: '30px',
                    'border-radius': '50%',
                    'background-color': c,
                    cursor: 'pointer',
                    border: idx === 0 ? '2px solid black' : '1px solid #ccc',
                    opacity: hasQty ? 1 : 0.4,
                    position: 'relative'
                })
                .attr('data-color', c);

            if (!hasQty) {
                colorDiv.append('<div style="position:absolute;top:0;left:0;width:100%;height:100%;background:red;opacity:0.6;border-radius:50%;"></div>');
            }

            $colorsContainer.append(colorDiv);
        });
    }

    // 🔄 Sélectionner première couleur par défaut
    var firstColor = product.couleurs[0];
    updateSizes(firstColor, product);

    // Gestion clic couleur
    $colorsContainer.off('click').on('click', '.color-circle', function() {
        var selectedColor = $(this).data('color');

        // Mettre en surbrillance
        $colorsContainer.find('.color-circle').css('border', '1px solid #ccc');
        $(this).css('border', '2px solid black');

        // Mettre à jour les tailles
        updateSizes(selectedColor, product);
    });

    // 🔢 Quantité
    //$('#modalProductQty').val(1);

    // 💾 Stocker infos pour bouton
    $('#modalAddCartBtn')
        .data('productId', product.id)
        .data('price', prix);

    // 🪟 Ouvrir modal
    var modalInstance = new bootstrap.Modal(document.getElementById('addCartModal'));
    modalInstance.show();
}

// Fonction pour mettre à jour les tailles selon couleur
function updateSizes(selectedColor, product) {
    var $sizesContainer = $('#modalProductSizes');
    $sizesContainer.empty();

    var sizes = product.details
        .filter(d => d.couleur === selectedColor)
        .map(d => ({ taille: d.taille, quantite: parseInt(d.quantite) }));

    if (sizes.length > 0) {
        sizes.forEach(s => {
            var sizeDiv = $('<div>')
                .addClass('size-square')
                .text(s.taille.toUpperCase())
                .attr('data-size', s.taille);

            if (s.quantite <= 0) {
                sizeDiv.addClass('out-of-stock');
            }

            $sizesContainer.append(sizeDiv);
        });

        // Sélectionner par défaut le premier en stock
        var firstAvailable = $sizesContainer.find('.size-square').not('.out-of-stock').first();
        firstAvailable.addClass('selected');
    } else {
        $sizesContainer.append('<div class="size-square disabled">-</div>');
    }

    // Gestion clic sur les tailles
    $sizesContainer.off('click').on('click', '.size-square', function() {
        if ($(this).hasClass('out-of-stock')) return; // ignorer si rupture
        $sizesContainer.find('.size-square').removeClass('selected');
        $(this).addClass('selected');
    });
}









$('#modalAddCartBtn').on('click', function () {
    const productId = $(this).data('productId');
    const price = $(this).data('price');
    const color = $('#modalProductColor').val();
    const size = $('#modalProductSize').val();
    const qty = parseInt($('#quantity').val()) || 1;

    addCart(productId, price, color, size, qty);

    // Ferme le modal après ajout
    const modal = bootstrap.Modal.getInstance(document.getElementById('addCartModal'));
    modal.hide();
});



function addCart(productId, price, color = null, size = null, qty = 1) {
    const userId = window.userId || null; // Passée depuis PHP si connecté

    if (userId) {
        // UTILISATEUR CONNECTÉ → envoi au serveur
        $.post('ajaxfiles/cartQuery.php', {
            ajouterPanier: true,
            id: productId,
            prix: price,
            couleur: color,
            taille: size,
            quantite: qty
        }, function (res) {
            if (res.status === 'success') {
                updateCartUI(res.cart_count); // MAJ du badge
                showToast(res.message, 'black');
            } else if (res.status === 'exists') {
                showToast('Produit déjà dans le panier', 'orange');
            } else {
                showToast(res.message || 'Erreur', 'red');
            }
        }, 'json');
    } else {
        // CLIENT PASSAGER → stockage local
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        let found = cart.find(p => p.id == productId && p.couleur == color && p.taille == size);
        if (found) {
            // Produit déjà présent → ne rien incrémenter
            showToast('Produit déjà dans le panier', 'orange');
        } else {
            cart.push({
                id: productId,
                prix: price,
                couleur: color,
                taille: size,
                quantite: 1
            });
            localStorage.setItem('cart', JSON.stringify(cart));

            // Met à jour le badge avec le nombre de produits différents
            const totalItems = cart.length;
            updateCartUI(totalItems);

            showToast('Produit ajouté au panier !', 'black');
        }
    }
}



// --- MAJ du badge panier ---
function updateCartUI(count) {
    const badge = document.querySelector('.cart-count');
    if (!badge) return;

    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'inline-block';
    } else {
        badge.textContent = '0';
        badge.style.display = 'none';
    }
}

// --- Charger le panier au démarrage (pour garder cohérence) ---
document.addEventListener('DOMContentLoaded', () => {
    const userId = window.userId || null;

    if (userId) {
        // Si connecté → récupérer depuis le serveur
        $.get('ajaxfiles/cartQuery.php', { getCartCount: true }, function (res) {
            if (res.status === 'success') {
                // Ici on suppose que le serveur renvoie déjà le nombre de produits différents
                updateCartUI(res.cart_count);
            }
        }, 'json');
    } else {
        // Sinon depuis localStorage
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        // Nombre de produits différents
        const totalItems = cart.length;
        updateCartUI(totalItems);
    }
});



// Toast simple
function showToast(message, bgColor = 'black') {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: bgColor,
        close: true
    }).showToast();
}



function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) {
        return;
    }

    $.ajax({
        url: 'ajaxfiles/cartQuery.php',
        type: 'POST',
        data: { modifierQuantite: true, id_produit: productId, quantite: newQuantity },
        dataType: 'JSON',
        success: function (response) {
            if (response.status === "success") {
                $('#myTablePanier').DataTable().ajax.reload();
                /* $("#quantite-" + productId).text(newQuantity);
                
                let prix = parseFloat($("#prix-" + productId).text().replace(" DT", ""));
                $("#total-" + productId).text((newQuantity * prix).toFixed(2) + " DT"); */
            } else {
                Swal.fire("Erreur", response.message, "error");
            }
        },
        error: function () {
            Swal.fire("Erreur", "Impossible de mettre à jour la quantité.", "error");
        }
    });
}

function incrementQuantity(productId) {
    let currentQuantity = parseInt($("#quantite-" + productId).text());
    updateQuantity(productId, currentQuantity + 1);
}

function decrementQuantity(productId) {
    let currentQuantity = parseInt($("#quantite-" + productId).text());
    if (currentQuantity > 1) {
        updateQuantity(productId, currentQuantity - 1);
    }
}



// Quantité
function increaseQty() {
    let qty = document.getElementById('quantity');
    qty.value = parseInt(qty.value) + 1;
}

function decreaseQty() {
    let qty = document.getElementById('quantity');
    if (parseInt(qty.value) > 1) qty.value = parseInt(qty.value) - 1;
}
