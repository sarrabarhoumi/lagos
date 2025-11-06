<?php
include '../connection.php';


if (isset($_POST['ajouterModalCategorie'])) { ?>
    <div class="modal fade" id="ajouterCategorieModal" tabindex="-1" aria-labelledby="ajouterCategorieModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-sm rounded-3">
                <div class="modal-header bg-dark text-white rounded-top">
                    <h5 class="modal-title" id="ajouterCategorieModalLabel">Ajouter une catégorie</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <form id="AjouterCategorie" enctype="multipart/form-data">
                    <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                        <!-- Image -->
                        <div class="mb-3">
                            <label for="image_categorie" class="form-label">Image <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" id="image_categorie" name="image_categorie" required>
                            <div id="preview" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>

                        <!-- Nom & Statut -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="nom_categorie">Nom <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="nom_categorie" name="nom_categorie" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="statut_categorie">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" name="statut_categorie" id="statut_categorie" required>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
                            </div>
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
        // Prévisualisation image
        document.getElementById('image_categorie').addEventListener('change', function() {
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



if (isset($_POST['getAllCategorie'])) {

    // Pagination sécurisée
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    // Sécurisation de la recherche
    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    // Construction de la requête de filtrage
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = "WHERE nom LIKE '%$searchValue%' ";
    }

    // Récupération des produits avec pagination
    $sql = "SELECT * FROM categories 
            $searchQuery LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $detailsString = '';
        $imagesHtml = '';
        $images = explode(',', $row['image']);
        $imagesHtml .= '<div class="d-flex">'; // Ouvrir le conteneur d-flex

        foreach ($images as $image) {
            $imagePath = ($image);
            if (file_exists($imagePath)) {
                $imagePath = str_replace('../','',$imagePath);
                $imagesHtml .= '<img src="' . $imagePath . '" alt="Image produit" style="max-width: 50px; margin-right: 5px; max-height: 50px;"/>';
            }
        }

        $imagesHtml .= '</div>';

        
        $actions = '
                <a href="javascript:void(0);" onclick="modifierCategorie(' . $row['id'] . ')" title="Modifier">
                    <i class="fa fa-edit text-dark" style="font-size: 18px;"></i> 
                </a>
                <a href="javascript:void(0);" onclick="supprimerCategorie(' . $row['id'] . ')" title="Supprimer">
                    <i class="fa fa-trash text-danger" style="font-size: 18px;"></i>
                </a>
            ';

        $data[] = [
            $imagesHtml,
            htmlspecialchars($row['nom']),
            
            ($row['statut'] == 'actif') ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-danger">Inactif</span>',
            $row['date_ajout'],
            $actions
        ];
    }

    // Nombre total de produits sans filtre
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM categories";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Nombre total de produits après filtre
    $totalFilteredRecordsQuery = "SELECT COUNT(id) AS total FROM categories $searchQuery";
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


if (isset($_POST['ajouterCategorie'])) {

    // 🔄 Récupération des données du formulaire
    $nom = mysqli_real_escape_string($con, $_POST['nom_categorie']);
    $statut = mysqli_real_escape_string($con, $_POST['statut_categorie']);
    $today = date("Y-m-d");

    $path = "";

    // Vérifier si une image a été uploadée
    if (isset($_FILES['image_categorie']) && $_FILES['image_categorie']['error'] == 0) {
        $uploadDir = "../uploads/categories/"; // À adapter
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = basename($_FILES['image_categorie']['name']);
        $targetFile = $uploadDir . time() . "_" . $filename;
        if (move_uploaded_file($_FILES['image_categorie']['tmp_name'], $targetFile)) {
            $path = $targetFile;
        }
    }

    $sql = "INSERT INTO categories (nom, image, statut, date_ajout) 
            VALUES ('$nom', '$path', '$statut', '$today')";
    $query = mysqli_query($con, $sql);

    if ($query) {
        echo json_encode(['status' => 'success', 'message' => 'Catégorie ajoutée avec succès']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'ajout", 'error' => mysqli_error($con)]);
    }
}




?>