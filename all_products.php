<?php 
include 'connection.php'; 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les produits | LAGOS</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Toastify -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        .card-product {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card-product:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
        }
        .card-product img {
            height: 200px;
            object-fit: cover;
        }
        .card-product .card-body h5 {
            font-weight: 600;
            font-size: 1rem;
        }
        .card-product .card-text {
            font-size: 0.85rem;
            color: #555;
            height: 38px;
            overflow: hidden;
        }
        .card-product .card-footer button, 
        .card-product .card-footer a {
            transition: all 0.2s;
        }
        .card-product .card-footer button:hover, 
        .card-product .card-footer a:hover {
            transform: scale(1.1);
        }
        #filter {
            width: 100%;
        }
        .breadcrumb-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>

<?php include 'header_user.php'; ?>

<?php
// ✅ Récupération du filtre catégorie (nom ou ID)
$categorieFiltre = isset($_GET['categorie']) ? mysqli_real_escape_string($con, $_GET['categorie']) : '';
$categorieNom = '';

// Vérifier si c'est un ID ou un nom
if (!empty($categorieFiltre)) {
    // Vérifier si c’est un ID numérique ou texte
    if (is_numeric($categorieFiltre)) {
        $catQuery = mysqli_query($con, "SELECT nom FROM categories WHERE id = '$categorieFiltre' LIMIT 1");
    } else {
        $catQuery = mysqli_query($con, "SELECT nom FROM categories WHERE nom = '$categorieFiltre' LIMIT 1");
    }

    if ($catQuery && mysqli_num_rows($catQuery) > 0) {
        $categorieNom = mysqli_fetch_assoc($catQuery)['nom'];
    }
}
?>

<div class="container-fluid p-0">

    <!-- 🧭 Fil d’Ariane -->
    <div class="row bg-light py-3">
        <div class="col text-center">
            <h2 class="breadcrumb-title">
                <?= $categorieNom ? htmlspecialchars($categorieNom) : 'Tous les produits'; ?>
            </h2>
            <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-dark">Accueil</a></li>
                    <?php if($categorieNom): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($categorieNom) ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page">Tous les produits</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>

    <!-- 🎯 Filtre -->
    <div class="bg-white p-4">
        <div class="row mb-4">
            <div class="col-md-3">
                <label for="filter" class="form-label">Filtrer par</label>
                <select id="filter">
                    <option value="" disabled selected>-- Sélectionner --</option>
                    <option value="recent">La plus récente</option>
                    <option value="oldest">La plus ancienne</option>
                    <option value="highest_price">Le plus cher</option>
                    <option value="lowest_price">Le moins cher</option>
                </select>
            </div>
        </div>

        <!-- 🛍️ Liste des produits -->
        <div class="row" id="products-list">
            <?php 
            // Construction de la requête selon le filtre catégorie
            if (!empty($categorieNom)) {
                $sql = mysqli_query($con, "
                    SELECT p.* FROM produit p 
                    INNER JOIN categories c ON c.id = p.id_categorie
                    WHERE c.nom = '$categorieNom'
                    ORDER BY p.date_ajout DESC
                ");
            } else {
                $sql = mysqli_query($con, "SELECT * FROM produit ORDER BY date_ajout DESC");
            }

            if ($sql && mysqli_num_rows($sql) > 0) {
                while ($row = mysqli_fetch_assoc($sql)) {
                    $img = htmlspecialchars($row['image_principale']);
                    $nom = htmlspecialchars($row['nom']);
                    $desc = htmlspecialchars($row['description']);
                    $prix = number_format($row['prix'], 2).' DT';
                    $id = (int)$row['id'];
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4 d-flex justify-content-center">
                <div class="card card-product" style="width: 100%; max-width: 230px;">
                    <img src="<?php echo $img; ?>" class="card-img-top" alt="<?php echo $nom; ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo $nom; ?></h5>
                        <p class="card-text text-truncate"><?php echo $desc; ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="h5 mb-0"><?php echo $prix; ?></span>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between bg-light">
                        <button class="btn btn-outline-dark btn-sm" onclick="addCart(<?php echo $id; ?>);" title="Ajouter au panier">
                            <i class="bi bi-cart"></i>
                        </button>
                        <a href="produit.php?id=<?php echo $id; ?>" class="btn btn-outline-dark btn-sm" title="Voir le produit">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn btn-outline-dark btn-sm" title="Ajouter aux favoris">
                            <i class="bi bi-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php 
                } 
            } else { 
                echo '<p class="text-center text-muted">Aucun produit trouvé dans cette catégorie.</p>';
            } 
            ?>
        </div>
    </div>
</div>

<!-- 📦 JS Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<!-- Custom JS -->
<script src="js/produit.js"></script>

<script>
$(document).ready(function() {
    // 🎚️ Initialisation du filtre
    $('#filter').select2({
        placeholder: "-- Sélectionner --",
        allowClear: true
    });

    // 🔁 Tri côté client
    $('#filter').on('change', function() {
        const val = $(this).val();
        console.log(val);
        const cards = $('#products-list .card-product').parent();
        cards.show(); // réaffiche tout

        if(val === 'lowest_price') {
            cards.sort(function(a,b){
                return parseFloat($(a).find('.h5').text()) - parseFloat($(b).find('.h5').text());
            }).appendTo('#products-list');
        }
        if(val === 'highest_price') {
            cards.sort(function(a,b){
                return parseFloat($(b).find('.h5').text()) - parseFloat($(a).find('.h5').text());
            }).appendTo('#products-list');
        }
        if(val === 'recent') {
            cards.sort(function(a,b){
                return new Date($(b).data('date')) - new Date($(a).data('date'));
            }).appendTo('#products-list');
        }
        if(val === 'oldest') {
            cards.sort(function(a,b){
                return new Date($(a).data('date')) - new Date($(b).data('date'));
            }).appendTo('#products-list');
        }
    });
});
</script>

</body>
</html>
