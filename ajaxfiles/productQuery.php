<?php
session_start();
include '../connection.php';
//$id_user = '1';
$id_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] :'';
if (isset($_POST['ajouterModalProduit'])) { ?>
    <div class="modal fade" id="ajouterProduitModal" tabindex="-1" aria-labelledby="ajouterProduitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg ">
            <div class="modal-content border-0 shadow-sm rounded-3">
                <div class="modal-header bg-dark text-white rounded-top">
                    <h5 class="modal-title" id="ajouterProduitModalLabel">Ajouter un produit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <form id="AjouterProduit" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <!-- Images -->
                        <div class="mb-3">
                            <label for="image_produit" class="form-label">Images <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" id="image_produit" name="image_produit[]" multiple required>
                            <div id="preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>

                        <!-- Nom & Description -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nom_produit">Nom <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="nom_produit" name="nom_produit" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="description_produit">Description <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="description_produit" name="description_produit" required>
                            </div>
                        </div>

                        <!-- Prix & Statut -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="prix_produit">Prix <span class="text-danger">*</span></label>
                                <input class="form-control" type="number" id="prix_produit" name="prix_produit" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="statut_produit">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" name="statut_produit" id="statut_produit" required>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
                            </div>
                        </div>
                        <!-- catégorie -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="categorie">Catégorie <span class="text-danger">*</span></label>
                                <select name="categorie" id="categorie" class="form-select" required>
                                    <option value="" disabled selected>-- Sélectionnez une catégorie --</option>
                                    <?php
                                    // ✅ Récupération des catégories actives
                                    $sql_categorie = mysqli_query($con, "SELECT id, nom FROM categories WHERE statut = 'actif' ORDER BY nom ASC");

                                    if ($sql_categorie && mysqli_num_rows($sql_categorie) > 0) {
                                        while ($cat = mysqli_fetch_assoc($sql_categorie)) {
                                            echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['nom']) . '</option>';
                                        }
                                    } else {
                                        echo '<option disabled>Aucune catégorie disponible</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>


                        <!-- Détails produit -->
                        <div class="mb-3 p-3 border rounded-3 bg-light">
                            <h6>Détails du produit</h6>
                            <div id="details_produit">
                                <div id="detail_0" class="row g-2 align-items-end mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label" for="couleur_produit_0">Couleur <span class="text-danger">*</span></label>
                                        <input class="form-control" type="color" id="couleur_produit_0" name="couleur_produit_0[]" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="taille_produit_0">Taille <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" id="taille_produit_0" name="taille_produit_0[]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="quantite_produit_0">Quantité <span class="text-danger">*</span></label>
                                        <input class="form-control" type="number" id="quantite_produit_0" name="quantite_produit_0" required>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-danger btn-sm mt-2" id="remove_details_0" style="display:none;">-</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-dark btn-sm mt-2" id="add_details_product">+ Ajouter un détail</button>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        #preview img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
    </style>

    <script>
        // Prévisualisation images
        document.getElementById('image_produit').addEventListener('change', function() {
            const preview = document.getElementById('preview');
            preview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
<?php }


if (isset($_POST['modifierModalProduit'])) {
    $id = $_POST['id'];
    $sql_produit = mysqli_query($con, "SELECT * FROM produit WHERE id='" . $id . "'");
    $row = mysqli_fetch_assoc($sql_produit);

?>
    <div class="modal fade" id="modifierProduitModal" tabindex="-1" aria-labelledby="modifierProduitModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h6 class="modal-title fs-5 text-white" id="exampleModalLabel">Modifier produit</h1>
                        <button type="button" class="btn btn-danger p-1" data-dismiss="modal">✖</button>
                </div>
                <style>
                    .image-container {
                        position: relative;
                        display: inline-block;
                    }

                    .image-container button {
                        font-size: 12px;
                        padding: 2px 6px;
                    }
                </style>
                <form id="ModifierProduit" enctype="multipart/form-data">
                    <div class="modal-body m-3">
                        <div class="row">
                            <div class="col-6">
                                <label class="label" for="image_produit">Images : <span class="required">*</span></label>
                                <input class="form-control" type="file" id="image_produit" name="image_produit[]" multiple required>
                            </div>
                            <div id="preview" class="mt-3 d-flex flex-wrap">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="label" for="">Nom:<span class="required">*</span></label>
                                <input class="form-control" type="text" id="nom_produit" name="nom_produit" value="<?php echo $row['nom']; ?>" required placeholder="">
                            </div>
                            <div class="col-6">
                                <label class="label" for="">Description:<span class="required">*</span></label>
                                <input class="form-control" type="text" id="description_produit" name="description_produit" value="<?php echo $row['description']; ?>" required placeholder="">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="label" for="">prix:<span class="required">*</span></label>
                                <input class="form-control" type="number" id="prix_produit" name="prix_produit" value="<?php echo $row['prix']; ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="label" for="">Statut:<span class="required">*</span></label>
                                <select class="form-control" name="statut_produit" id="statut_produit">
                                    <option value="actif" value="<?php echo ($row['statut'] == 'actif') ? 'selected' : ''; ?>">Actif</option>
                                    <option value="inactif" value="<?php echo ($row['statut'] == 'inactif') ? 'selected' : ''; ?>">Inactif</option>
                                </select>
                            </div>
                        </div>
                        <!-- catégorie -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="categorie">Catégorie <span class="text-danger">*</span></label>
                                <select name="categorie" id="categorie" class="form-select" required>
                                    <option value="" disabled selected>-- Sélectionnez une catégorie --</option>
                                    <?php
                                    // ✅ Récupération des catégories actives
                                    $sql_categorie = mysqli_query($con, "SELECT id, nom FROM categories WHERE statut = 'actif' ORDER BY nom ASC");
                                    
                                    if ($sql_categorie && mysqli_num_rows($sql_categorie) > 0) {
                                        while ($cat = mysqli_fetch_assoc($sql_categorie)) {
                                            $selected = ($cat['id'] == $row['id_categorie']) ? 'selected' :'' ;
                                            echo '<option value="' . $cat['id'] . '" '.$selected.'>' . htmlspecialchars($cat['nom']) . '</option>';
                                        }
                                    } else {
                                        echo '<option disabled>Aucune catégorie disponible</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="p-3 row">
                            <div class="border-dashed p-2">
                                <div id="details_produit">
                                    <?php
                                    $nb = 0;
                                    $sql_details = mysqli_query($con, "SELECT * FROM details_produit WHERE id_produit='" . $id . "'");

                                    if ($sql_details && mysqli_num_rows($sql_details) > 0) {
                                        $row_d = mysqli_fetch_assoc($sql_details);
                                        $details = json_decode($row_d['details'], true);
                                        foreach ($details as $detail) {

                                    ?>
                                            <div id="detail_<?= $nb ?>" class="d-flex">
                                                <div class="col-4">
                                                    <label class="label" for="">Couleurs:<span class="required">*</span></label>
                                                    <input value="<?php echo $detail['couleur']; ?>" class="form-control select2" type="color" id="couleur_produit_<?= $nb ?>" name="couleur_produit_<?= $nb ?>[]" multiple="" required="" placeholder="">
                                                </div>
                                                <div class="col-4">
                                                    <label class="label" for="">Tailles:<span class="required">*</span></label>
                                                    <input value="<?php echo $detail['taille']; ?>" class="form-control select2" type="text" id="taille_produit_<?= $nb ?>" name="taille_produit_<?= $nb ?>[]" multiple="" required="" placeholder="">
                                                </div>
                                                <div class="col-3">
                                                    <label class="label" for="">Quantité:<span class="required">*</span></label>
                                                    <input value="<?php echo $detail['quantite']; ?>" class="form-control" type="number" id="quantite_produit_<?= $nb ?>" name="quantite_produit_<?= $nb ?>" required="">
                                                </div>
                                                <div class="align-content-end col-1">
                                                    <button type="button" class="btn btn-danger d-none p-2" id="remove_details_<?= $nb ?>">-</button>
                                                </div>

                                            </div>
                                    <?php
                                            $nb++;
                                        }
                                    } ?>
                                </div>
                                <div class="">
                                    <div class="col">
                                        <button type="button" class="btn btn-dark mt-2 p-1" id="add_details_product">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php }
if (isset($_POST['ajouterProduit'])) {

    $uploadDir = __DIR__ . '../uploads/produit/'; // chemin absolu serveur
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $images = $_FILES['image_produit'];
    $images_str = [];

    foreach ($images['name'] as $key => $imageName) {
        $imageTmpName = $images['tmp_name'][$key];
        $imageType = $images['type'][$key];

        if (in_array($imageType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {

            $uniqueName = uniqid('prod_', true) . '_' . basename($imageName);
            $serverPath = $uploadDir . $uniqueName;      // chemin serveur absolu
            $relativePath = '../uploads/produit/' . $uniqueName; // chemin pour la BDD

            if (move_uploaded_file($imageTmpName, $serverPath)) {
                $images_str[] = $relativePath;
            } else {
                echo json_encode(['status' => 'error', 'message' => "Erreur lors du transfert de $imageName"]);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => "Format non autorisé pour $imageName"]);
            exit;
        }
    }

    // Champs du formulaire
    $nom = mysqli_real_escape_string($con, $_POST['nom_produit']);
    $description = mysqli_real_escape_string($con, $_POST['description_produit']);
    $prix = floatval($_POST['prix_produit']);
    $statut = mysqli_real_escape_string($con, $_POST['statut_produit']);
    $categorie = mysqli_real_escape_string($con, $_POST['categorie']);
    $details_produit = mysqli_real_escape_string($con, $_POST['details_produit']);
    $image_str = implode(',', $images_str);
    $today = date("Y-m-d");

    // Insertion produit
    $sql = "INSERT INTO produit (nom, description, image_principale, prix, statut, date_ajout,id_categorie)
            VALUES ('$nom', '$description', '$image_str', '$prix', '$statut', '$today','$categorie')";
    $query = mysqli_query($con, $sql);

    if ($query) {
        $id_produit = mysqli_insert_id($con);
        $sql_detail = "INSERT INTO details_produit (id_produit, details, date_ajout)
                       VALUES ('$id_produit', '$details_produit', '$today')";
        $query_detail = mysqli_query($con, $sql_detail);

        if ($query_detail) {
            echo json_encode(['status' => 'success', 'message' => 'Produit ajouté avec succès ✅']);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'ajout des détails."]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'ajout du produit."]);
    }
}



if (isset($_POST['getAllProduct'])) {

    // ✅ Pagination sécurisée
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    // ✅ Sécurisation de la recherche
    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    // ✅ Construction de la requête de filtrage
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = "WHERE p.nom LIKE '%$searchValue%' OR p.description LIKE '%$searchValue%'";
    }

    // ✅ Récupération des produits avec leurs détails
    $sql = "SELECT p.*, dp.details  , c.nom as categorie
            FROM produit AS p
            LEFT JOIN details_produit AS dp ON dp.id_produit = p.id
            LEFT JOIN categories as c ON p.id_categorie = c.id
            $searchQuery
            LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        // 🖼️ Traitement des images produits
        $imagesHtml = '<div class="d-flex flex-wrap gap-2 align-items-center">';
        $images = explode(',', $row['image_principale']);

        foreach ($images as $image) {
            $imagePath = htmlspecialchars(trim($image)); // Ex: img/produit/mon_image.jpg
            $imagePath= str_replace('../','',$imagePath);
            $imagesHtml .= '
                <img src="' . $imagePath . '" 
                     alt="Produit" 
                     class="rounded border"
                     style="width:50px; height:50px; object-fit:cover;">
            ';
        }
        $imagesHtml .= '</div>';

        // 📦 Traitement des détails produits
        $detailsString = '';
        if (!empty($row['details'])) {
            $details = json_decode($row['details'], true);

            if (is_array($details)) {
                foreach ($details as $detail) {
                    $couleur = htmlspecialchars($detail['couleur'] ?? '');
                    $taille = htmlspecialchars($detail['taille'] ?? '');
                    $quantite = htmlspecialchars($detail['quantite'] ?? '');

                    $detailsString .= "
                        <div class='mb-1'>
                            <span>Couleur : <input disabled type='color' value='$couleur' style='border:none; width:25px; height:25px; vertical-align:middle;'></span> | 
                            <span>Taille : $taille</span> | 
                            <span>Qté : $quantite</span>
                        </div>";
                }
            }
        }

        // ⚙️ Actions
        $actions = '
            <div class="d-flex gap-2">
                <a href="javascript:void(0);" onclick="modifierProduit(' . $row['id'] . ')" title="Modifier">
                    <i class="fa fa-edit text-primary fs-5"></i>
                </a>
                <a href="javascript:void(0);" onclick="supprimerProduit(' . $row['id'] . ')" title="Supprimer">
                    <i class="fa fa-trash text-danger fs-5"></i>
                </a>
            </div>
        ';

        // 🧩 Données pour DataTable
        $data[] = [
            $imagesHtml,
            htmlspecialchars($row['nom']),
            number_format($row['prix'], 2) . " DT",
            $detailsString,
            htmlspecialchars($row['description']),
            ($row['statut'] === 'actif')
                ? '<span class="badge bg-success">Actif</span>'
                : '<span class="badge bg-danger">Inactif</span>',
                htmlspecialchars($row['categorie']),
            $row['date_ajout'],
            $actions
        ];
    }

    // ✅ Comptage total
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM produit";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // ✅ Comptage filtré
    $totalFilteredRecordsQuery = "SELECT COUNT(p.id) AS total FROM produit AS p $searchQuery";
    $totalFilteredRecordsResult = mysqli_query($con, $totalFilteredRecordsQuery);
    $totalFilteredRecords = mysqli_fetch_assoc($totalFilteredRecordsResult)['total'];

    // ✅ Réponse JSON pour DataTables
    $response = [
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalFilteredRecords,
        "data" => $data
    ];

    echo json_encode($response);
}

if (isset($_POST['getPanier'])) {
    // Pagination sécurisée
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    // Sécurisation de la recherche
    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    // Construction de la requête de filtrage
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = "WHERE quantite LIKE '%$searchValue%' ";
    }

    // Récupération des produits avec pagination
    $sql = "SELECT p.* , pr.*
                                                        FROM panier as p 
                                                        LEFT JOIN produit as pr ON p.id_produit=pr.id
            $searchQuery AND p.id_user='" . $id_user . "' LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $imagePath= str_replace('../','',$row['image_principale']);
        $img = '<div class="d-flex align-items-center">
        <img class="rounded border" style="width: 70px; height: 70px; object-fit: cover;"
            src="' . $imagePath . '" alt="Produit">
        <h5 class="mb-0 ms-3">' . $row['nom'] . '</h5>
    </div>';
        $prix = $row['prix'] . ' DT';
        $quantite = '<div class="d-flex align-items-center justify-content-center">
                                                <button class="p-1 btn btn-sm btn-outline-dark me-2" onclick="decrementQuantity(' . $row['id'] . ')">
                                                    <i class="bi bi-dash-lg"></i>
                                                </button>
                                                <span class="fw-bold" id="quantite-' . $row['id'] . '">
                                                    ' . $row['quantite'] . '
                                                </span>
                                                <button class="p-1 btn btn-sm btn-outline-dark ms-2" onclick="incrementQuantity(' . $row['id'] . ')">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>';
        $total = $row['quantite'] * $row['prix'] . ' DT';
        $action = ' <button class="btn">
                                                <i class="bi bi-trash3-fill text-danger"></i>
                                            </button>';




        $data[] = [
            $img,
            $prix,
            $quantite,
            $total,
            $action
        ];
    }

    // Nombre total de produits sans filtre
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM panier ";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Nombre total de produits après filtre
    $totalFilteredRecordsQuery = "SELECT COUNT(id) AS total FROM panier $searchQuery";
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

if (isset($_GET['getCartCount'])) {
    $count = 0;

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT SUM(quantite) AS total FROM panier WHERE id_user = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $count = intval($result['total'] ?? 0);
    }

    echo json_encode(['status' => 'success', 'cart_count' => $count]);
    exit;
}
?>