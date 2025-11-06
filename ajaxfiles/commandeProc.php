<?php
include '../connection.php';
$id_user = '1';

if (isset($_POST['ValiderCommandeModal'])) { ?>

    <div class="modal fade" id="ValiderCommandeModal" tabindex="-1" aria-labelledby="ValiderCommandeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title text-white" id="ValiderCommandeModalLabel">Validation de la commande</h5>
            <button type="button" class="btn btn-close-white p-0" data-bs-dismiss="modal">✖</button>
          </div>
          <form id="ValiderCommande" class="p-3">
            <!-- Résumé de la commande -->
            <h6 class="mt-2 mb-3 text-center">Résumé de la commande</h6>
            <div class="card mb-3 shadow-sm">
              <div class="card-body">
                <?php
                $sous_total = 0;
                $sql = mysqli_query($con, "SELECT p.*, pr.* FROM panier as p LEFT JOIN produit as pr ON p.id_produit=pr.id WHERE p.id_user='$id_user'");
                if ($sql && mysqli_num_rows($sql)) {
                    while ($row_p = mysqli_fetch_assoc($sql)) {
                        $sous_total += $row_p['quantite'] * $row_p['prix'];
                    }
                }
                ?>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between">Sous-total <span><?php echo number_format($sous_total, 3); ?> DT</span></li>
                  <li class="list-group-item d-flex justify-content-between">Frais de livraison <span>7.000 DT</span></li>
                  <li class="list-group-item d-flex justify-content-between fw-bold">Total Général <span><?php echo number_format($sous_total + 7.000, 3); ?> DT</span></li>
                </ul>
              </div>
            </div>
    
            <!-- Informations personnelles -->
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
    
            <!-- Adresse de livraison -->
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
                  <!-- Ajouter les autres -->
                </select>
              </div>
              <div class="col-md-4">
                <input type="text" class="form-control" name="code_postal" placeholder="Code postal">
              </div>
            </div>
    
            <!-- Boutons -->
            <div class="modal-footer mt-3">
              <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button> -->
              <button type="submit" class="btn btn-lagos">Valider la commande</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <?php } 

if (isset($_POST['ValiderCommande'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $num = $_POST['numero'];
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $adresse = $_POST['adresse'];
    $gouvernorat = $_POST['gouvernorat'];
    $code_postal = isset($_POST['code_postal']) ? $_POST['code_postal'] : '';
    $date_commande = date("Y-m-d");
    $sous_total = 0;
    $frais_livraison = 7.000;

    $sql = mysqli_query($con, "SELECT p.*, pr.* FROM panier as p LEFT JOIN produit as pr ON p.id_produit = pr.id WHERE p.id_user = '" . $id_user . "'");
    if ($sql && mysqli_num_rows($sql)) {
        while ($row_p = mysqli_fetch_assoc($sql)) {
            $sous_total += $row_p['quantite'] * $row_p['prix'];
        }
    }
    $total = $sous_total + $frais_livraison;


    $sql_insert = mysqli_query($con, "
        INSERT INTO commandes (nom, prenom, numero, email, adresse, gouvernorat, code_postal, sous_total, frais_livraison, total, date_commande,statut,id_user)
        VALUES ('$nom', '$prenom', '$num', '$email', '$adresse', '$gouvernorat', '$code_postal', '$sous_total', '$frais_livraison', '$total', '$date_commande','En attente','$id_user')
    ");

    if ($sql_insert) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Commande validée avec succès!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de la validation de la commande!'
        ]);
    }
    
}

if (isset($_POST['getAllCommandes'])) {

    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = "WHERE nom LIKE '%$searchValue%' OR description LIKE '%$searchValue%'";
    }

    // Récupération des produits avec pagination
    $sql = "SELECT p.*, dp.* FROM commandes as c
            LEFT JOIN produit as p ON 
            LEFT JOIN details_produit as dp ON dp.id_produit = p.id
            $searchQuery LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $detailsString = '';
        $imagesHtml = '';
        $images = explode(',', $row['image_principale']);
        $imagesHtml .= '<div class="d-flex">'; // Ouvrir le conteneur d-flex

        foreach ($images as $image) {
            $imagePath = trim($image);
            if (file_exists($imagePath)) {
                $imagesHtml .= '<img src="' . $imagePath . '" alt="Image produit" style="max-width: 50px; margin-right: 5px; max-height: 50px;"/>';
            }
        }

        $imagesHtml .= '</div>';

        $details = json_decode($row['details'], true);
        foreach ($details as $detail) {
            $detailsString .= "Couleur: " . htmlspecialchars($detail['couleur']) . " | Taille: " . htmlspecialchars($detail['taille']) . " | Quantité: " . htmlspecialchars($detail['quantite']) . "<br>";
        }
        $actions = '
                <a href="javascript:void(0);" onclick="modifierProduit(' . $row['id'] . ')" title="Modifier">
                    <i class="fa fa-edit text-dark" style="font-size: 18px;"></i> 
                </a>
                <a href="javascript:void(0);" onclick="supprimerProduit(' . $row['id'] . ')" title="Supprimer">
                    <i class="fa fa-trash text-danger" style="font-size: 18px;"></i>
                </a>
            ';

        $data[] = [
            $imagesHtml,
            htmlspecialchars($row['nom']),
            number_format($row['prix'], 2) . " DT",
            $detailsString,
            htmlspecialchars($row['description']),
            ($row['statut'] == 'actif') ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-danger">Inactif</span>',
            $row['date_ajout'],
            $actions
        ];
    }

    // Nombre total de produits sans filtre
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM produit";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Nombre total de produits après filtre
    $totalFilteredRecordsQuery = "SELECT COUNT(id) AS total FROM produit $searchQuery";
    $totalFilteredRecordsResult = mysqli_query($con, $totalFilteredRecordsQuery);
    $totalFilteredRecords = mysqli_fetch_assoc($totalFilteredRecordsResult)['total'];

    // Réponse JSON pour DataTables
    $response = [
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalFilteredRecords,
        "data" => $data
    ];

    echo json_encode($response);
}
?>