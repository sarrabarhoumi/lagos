<?php
include 'connection.php';

// Vérifier si l'id est passé
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);

// Récupérer les informations du produit
$sql = "SELECT * FROM produit WHERE id = $id LIMIT 1";
$result = mysqli_query($con, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Produit introuvable.";
    exit;
}

$product = mysqli_fetch_assoc($result);

// Récupérer les détails JSON
$details_sql = "SELECT details FROM details_produit WHERE id_produit = $id";
$details_result = mysqli_query($con, $details_sql);
$variations = [];
if ($details_result && mysqli_num_rows($details_result) > 0) {
    $row = mysqli_fetch_assoc($details_result);
    $variations = json_decode($row['details'], true); // Tableau de variations
}

// Organiser par couleur
$colors = [];
foreach ($variations as $v) {
    $colors[$v['couleur']][] = ['taille' => $v['taille'], 'quantite' => $v['quantite']];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['nom']); ?> | LAGOS</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
  <link rel="stylesheet" href="css/style.css">
    <style>
        .product-container {
            margin-top: 3rem;
        }

        .product-images {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
        }

        .product-images img {
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-images:hover img {
            transform: scale(1.05);
        }

        .add-cart-btn {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            opacity: 0;
            background-color: #111;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-images:hover .add-cart-btn {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .product-info h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .product-info .price {
            font-size: 1.3rem;
            color: #186dc3;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .color-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .color-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .color-circle.disabled {
            border: 2px solid red;
            opacity: 0.5;
            cursor: not-allowed;
        }

        .size-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .size-box {
            padding: 5px 10px;
            border: 1px solid #ccc;
            cursor: pointer;
            border-radius: 4px;
            user-select: none;
        }

        .size-box.disabled {
            border: 1px solid red;
            color: red;
            cursor: not-allowed;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .quantity-selector button {
            width: 30px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            background: #fff;
            cursor: pointer;
        }

        .quantity-selector input {
            width: 50px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn-secondary {
            background-color: #186dc3;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #111;
        }
    </style>
</head>

<body>

    <?php include 'header_user.php'; ?>

    <div class="container product-container">
        <div class="row g-4">
            <!-- Image avec bouton Ajouter au panier -->
            <div class="col-lg-6">
                <div class="product-images">
                    <img src="<?= htmlspecialchars($product['image_principale']); ?>" alt="<?= htmlspecialchars($product['nom']); ?>">
                    <button class="add-cart-btn" id="imgAddCart">
                        <i class="bi bi-cart"></i> Ajouter au panier
                    </button>
                </div>
            </div>

            <!-- Informations -->
            <div class="col-lg-6 product-info">
                <h2 class="fw-bold mb-3"><?= htmlspecialchars($product['nom']); ?></h2>
                <!-- Description -->
                <div class="product-description text-muted">
                    <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                <!-- Prix -->
                <div class="price fs-3 fw-semibold text-primary mb-4"><?= number_format($product['prix'], 2); ?> DT</div>

                <!-- Couleurs -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Couleurs :</label>
                    <div class="color-selector d-flex gap-2">
                        <?php foreach ($colors as $color => $sizes):
                            $allOut = true;
                            foreach ($sizes as $s) {
                                if ($s['quantite'] > 0) $allOut = false;
                            }
                            $disabledClass = $allOut ? 'disabled' : '';
                        ?>
                            <div class="color-circle <?= $disabledClass ?>"
                                data-color="<?= $color ?>"
                                style="background-color: <?= $color ?>;"
                                title="<?= $color ?>">
                                <?php if ($disabledClass) echo '<i class="bi bi-x text-danger"></i>'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tailles -->
                <div class="mb-3 ">
                    <label class="form-label fw-semibold">Tailles :</label>
                    <div class="size-selector d-flex gap-2" id="sizeSelector"></div>
                </div>

                <!-- Quantité -->
<div class="mb-4 d-flex align-items-center gap-3">
    <label class="form-label fw-semibold mb-0">Quantité :</label>
    <div class=" d-flex align-items-center border rounded" style="overflow: hidden; width: fit-content;">
        <button type="button" class="btn btn-light border-end" onclick="decreaseQty()">-</button>
        <input type="number" id="quantity" value="1" min="1" class="form-control text-center border-0" style="width:60px;">
        <button type="button" class="btn btn-light border-start" onclick="increaseQty()">+</button>
    </div>
</div>


                <!-- Boutons -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <!-- Acheter maintenant : bouton noir -->
                    <button class="btn btn-dark btn-lg d-flex align-items-center gap-2" onclick="buyNow(<?= $product['id']; ?>);">
                        <i class="bi bi-bag"></i> Acheter maintenant
                    </button>

                    <!-- Ajouter au panier : bouton blanc avec bordure noire -->
                    <button class="btn btn-outline-dark btn-lg d-flex align-items-center gap-2" onclick="addCart(<?= $product['id']; ?>);">
                        <i class="bi bi-cart"></i> Ajouter au panier
                    </button>
                </div>



            </div>


            <!-- Produits similaires -->
            <div class="container mt-5">
                <h4 class="mb-3">Produits similaires</h4>
                <div class="d-flex overflow-auto" style="gap:1rem; scroll-behavior: smooth;">
                    <?php
                    $cat = intval($product['id_categorie']);
                    $similar = mysqli_query($con, "SELECT * FROM produit WHERE id_categorie='$cat' AND id != $id LIMIT 6");
                    if ($similar && mysqli_num_rows($similar) > 0) {
                        while ($s = mysqli_fetch_assoc($similar)) {
                            echo '
                <div class="card card-product" style="min-width:180px; flex:0 0 auto; cursor:pointer;" onclick="window.location.href=\'produit.php?id=' . $s['id'] . '\'">
                    <img src="' . htmlspecialchars($s['image_principale']) . '" class="card-img-top" style="height:150px; object-fit:cover; border-radius:8px;">
                    <div class="card-body text-center">
                        <h6 class="fw-semibold mb-1">' . htmlspecialchars($s['nom']) . '</h6>
                        <span class="text-muted">' . number_format($s['prix'], 2) . ' DT</span>
                    </div>
                </div>';
                        }
                    } else {
                        echo '<p class="text-muted">Aucun produit similaire pour le moment.</p>';
                    }
                    ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        const variations = <?= json_encode($variations); ?>;
        let selectedColor = null;
        let selectedSize = null;

        // Afficher les tailles selon couleur
        function updateSizes() {
            const sizeContainer = document.getElementById('sizeSelector');
            sizeContainer.innerHTML = '';
            if (!selectedColor) return;

            variations.forEach(v => {
                if (v.couleur === selectedColor) {
                    const isDisabled = parseInt(v.quantite) <= 0;
                    const div = document.createElement('div');
                    div.className = 'size-box' + (isDisabled ? ' disabled' : '');
                    div.textContent = v.taille;
                    div.dataset.size = v.taille;
                    div.onclick = () => {
                        if (isDisabled) return;
                        selectedSize = v.taille;
                        document.querySelectorAll('.size-box').forEach(el => el.classList.remove('selected'));
                        div.classList.add('selected');
                    }
                    sizeContainer.appendChild(div);
                }
            });
        }

        // Gestion couleur
        document.querySelectorAll('.color-circle').forEach(c => {
            c.onclick = () => {
                if (c.classList.contains('disabled')) return;
                selectedColor = c.dataset.color;
                document.querySelectorAll('.color-circle').forEach(el => el.classList.remove('selected'));
                c.classList.add('selected');
                selectedSize = null;
                updateSizes();
            }
        });

        /* // Quantité
        function increaseQty() {
            let qty = document.getElementById('quantity');
            qty.value = parseInt(qty.value) + 1;
        }

        function decreaseQty() {
            let qty = document.getElementById('quantity');
            if (parseInt(qty.value) > 1) qty.value = parseInt(qty.value) - 1;
        } */

        // Ajouter au panier
        document.getElementById('imgAddCart').onclick = () => {
            let qty = document.getElementById('quantity').value;
            console.log('Ajouter au panier:', <?= $product['id']; ?>, 'Couleur:', selectedColor, 'Taille:', selectedSize, 'Quantité:', qty);
            alert('Produit ajouté au panier ! (simulation)');
        }

        // Acheter maintenant
        function buyNow(productId) {
            let qty = document.getElementById('quantity').value;
            console.log('Acheter maintenant:', productId, 'Couleur:', selectedColor, 'Taille:', selectedSize, 'Quantité:', qty);
            alert('Acheter maintenant ! (simulation)');
        }
    </script>

</body>

</html>