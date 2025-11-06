<?php include 'connection.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LAGOS | Boutique en ligne</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- SweetAlert2 -->
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


    .card-product {
      border: none;
      /* border-radius: 12px; */
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s;
    }




    .card-product:hover {
      transform: translateY(-5px);
    }




    .card-product img {
      height: 200px;
      object-fit: cover;
    }

    .card-body {
      text-align: center;
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


    /**** new product */
    .card-product {
      width: 220px;
      /* border-radius: 12px; */
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
    }

    .card-product:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
    }

    /* Partie image */
    .card-product .card-image {
      position: relative;
      overflow: hidden;
      height: 180px;
    }

    .card-product .card-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease;
    }

    .card-product:hover .card-image img {
      transform: scale(1.05);
    }

    /* Bouton panier sur l'image */
    .card-product .add-cart-btn {
      position: absolute;
      bottom: 10px;
      left: 50%;
      transform: translateX(-50%) translateY(20px);
      opacity: 0;
      transition: all 0.4s ease;
    }

    .card-product:hover .add-cart-btn {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    .add-cart-btn button {
      background-color: #111;
      color: #fff;
      border-radius: 6px;
      padding: 8px 14px;
      font-size: 0.85rem;
      border: none;
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .add-cart-btn button:hover {
      background-color: #186dc3;
    }

    /* Partie détails */
    .card-product .card-body {
      padding: 0.75rem;
      text-align: center;
    }
  </style>
</head>

<body>

  <?php include 'header_user.php'; ?>

  <!-- SECTION HERO -->
  <section class="hero">
    <h1>Bienvenue chez LAGOS</h1>
    <p>Découvrez nos nouvelles collections et profitez d’une expérience shopping élégante et fluide.</p>
    <a href="#categories" class="btn btn-lagos">Découvrir maintenant</a>
  </section>

  <!-- SECTION NOUVEAUX PRODUITS -->
  <section id="new-products" class="container my-5">
    <h2 class="section-title mb-4">Nouveautés</h2>

    <div class="d-flex overflow-auto pb-3" style="gap: 1.5rem; scroll-behavior: smooth;">
      <?php
      $result = mysqli_query($con, "SELECT * FROM produit ORDER BY date_ajout DESC LIMIT 10");

      if ($result && mysqli_num_rows($result) > 0) {
        while ($p = mysqli_fetch_assoc($result)) {
          $image = str_replace('../', '', $p['image_principale']);

          $detailsQuery = mysqli_query($con, "SELECT details FROM details_produit WHERE id_produit = {$p['id']} LIMIT 1");
          $details = [];
          $couleurs = [];
          $tailles = [];

          if ($detailsQuery && mysqli_num_rows($detailsQuery) > 0) {
            $detailsRow = mysqli_fetch_assoc($detailsQuery);
            $detailsData = json_decode($detailsRow['details'], true);

            if (is_array($detailsData)) {
              foreach ($detailsData as $d) {
                $details[] = [
                  'couleur' => $d['couleur'],
                  'taille' => $d['taille'],
                  'quantite' => (int)$d['quantite']
                ];
                if (!in_array($d['couleur'], $couleurs)) $couleurs[] = $d['couleur'];
                if (!in_array($d['taille'], $tailles)) $tailles[] = $d['taille'];
              }
            }
          }

          $jsData = json_encode([
            'id' => $p['id'],
            'nom' => $p['nom'],
            'prix' => $p['prix'],
            'image' => $image,
            'couleurs' => $couleurs,
            'tailles' => $tailles,
            'details' => $details // 🔹 IMPORTANT
          ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

      ?>
          <div class="card card-product" style="min-width:200px; cursor:pointer;">

            <!-- Partie image -->
            <div class="card-image position-relative" onclick="window.location.href='produit.php?id=<?= $p['id']; ?>';">
              <img src="<?= htmlspecialchars($image); ?>"
                alt="<?= htmlspecialchars($p['nom']); ?>"
                class="img-fluid w-100">
            </div>

            <!-- Partie détails -->
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h6 class="fw-semibold mb-1"><?= htmlspecialchars($p['nom']); ?></h6>
                <p class="text-muted mb-0"><?= number_format($p['prix'], 2); ?> DT</p>
              </div>
              <div>
                <button class="btn btn-sm p-1">
                  <i class="bi bi-cart-plus-fill fs-5 text-dark" style="cursor:pointer;"
                    onclick='openAddCartModal(<?= $jsData; ?>)'></i>
                </button>
              </div>
            </div>

          </div>
      <?php
        }
      } else {
        echo '<p class="text-center text-muted">Aucun nouveau produit pour le moment.</p>';
      }
      ?>
    </div>
  </section>


  <style>
    .card-product {
      border: none;
      transition: transform 0.3s;
    }

    .card-product:hover {
      transform: translateY(-5px);
    }

    .card-image img {
      height: 180px;
      object-fit: cover;
    }
  </style>


  <!-- SECTION CATÉGORIES -->
  <section id="categories" class="container py-5">
    <h2 class="section-title text-center mb-4">Nos catégories</h2>
    <?php
    $sql = "SELECT * FROM categories WHERE statut='actif' ORDER BY date_ajout DESC";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
      $count = 0;
      echo '<div class="row justify-content-center">'; // row globale
      while ($row = mysqli_fetch_assoc($result)) {
        $catName = htmlspecialchars($row['nom']);
        $catImage = str_replace('../', '', $row['image']);

        $catLink = 'all_products.php?categorie=' . urlencode($row['id']);

        echo '<div class="col-12 col-md-5 p-0 m-0">';
        echo '<a href="' . $catLink . '" class="category-card d-block position-relative overflow-hidden mb-4">';
        echo '<img src="' . $catImage . '" alt="' . $catName . '" class="w-100 h-100">';
        echo '<div class="overlay d-flex align-items-end justify-content-center">';
        echo '<span class="category-name text-white">' . $catName . '</span>';
        echo '</div></a>';
        echo '</div>';

        $count++;
        // Après 2 colonnes, fermer et rouvrir un row
        if ($count % 2 == 0 && $count != mysqli_num_rows($result)) {
          echo '</div><div class="row justify-content-center">';
        }
      }
      echo '</div>'; // fermer le dernier row
    } else {
      echo '<p class="text-muted text-center">Aucune catégorie disponible pour le moment.</p>';
    }
    ?>
  </section>









  <!-- SECTION POLITIQUES / INFORMATIONS -->
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



  <!-- Modal Ajouter au Panier -->
  <!-- Modal Ajouter au Panier -->
  <div class="modal fade" id="addCartModal" tabindex="-1" aria-labelledby="addCartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header border-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <!-- Image à droite -->
            <div class="col-md-5 text-center">
              <img id="modalProductImage" src="" alt="" class="img-fluid rounded" style="max-height:250px; object-fit:cover;">
            </div>
            <!-- Infos à gauche -->
            <div class="col-md-7">
              <h6 id="modalProductName" class="fw-semibold mb-2"></h6>
              <p>Prix unitaire: <span id="modalProductPrice"></span> DT</p>

              <div class="mb-2">
                <label class="form-label">Couleur :</label>
                <div id="modalProductColors" class="d-flex flex-wrap"></div>
              </div>

              <div class="mb-2">
                <label class="form-label">Taille :</label>
                <div id="modalProductSizes" class="d-flex flex-wrap"></div>
              </div>


              <div class="mb-3 d-flex align-items-center">
                <label for="modalProductQty" class="form-label me-2">Quantité :</label>

                <div class=" d-flex align-items-center border rounded" style="overflow: hidden; width: fit-content;">
                  <button type="button" class="btn btn-light border-end" onclick="decreaseQty()">-</button>
                  <input type="number" id="quantity" value="1" min="1" class="form-control text-center border-0" style="width:60px;">
                  <button type="button" class="btn btn-light border-start" onclick="increaseQty()">+</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-lagos w-100" id="modalAddCartBtn">Ajouter au panier</button>
        </div>
      </div>
    </div>
  </div>






  <footer>
    © <?php echo date('Y'); ?> Lagos — Votre boutique de confiance.
  </footer>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Toastify JS -->
  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

  <!-- Ton script produit.js -->
  <script src="js/produit.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const urlParams = new URLSearchParams(window.location.search);

      if (urlParams.get("logout") === "1") {
        // Afficher le toast
        Toastify({
          text: "Vous avez été déconnecté avec succès 👋",
          duration: 4000,
          gravity: "top",
          position: "right",
          backgroundColor: "black",
          close: true
        }).showToast();

        // Supprimer le paramètre 'logout' de l’URL sans recharger la page
        const newUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
      }
    });
  </script>

</body>

</html>