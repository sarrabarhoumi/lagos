<?php
include '../connection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);


if (isset($_POST['getAllMessages'])) {

    // Pagination sécurisée
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    // Sécurisation de la recherche
    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    // Construction de la requête de filtrage
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = " WHERE message LIKE '%$searchValue%'  ";
    }

    // Récupération des produits avec pagination
    $sql = "SELECT *   FROM top_bar_info 
            
     $searchQuery LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {



        $actions = '
                <a href="javascript:void(0);" onclick="modifierMessageBar(' . $row['id'] . ')" title="Modifier">
                    <i class="fa fa-edit text-dark" style="font-size: 18px;"></i> 
                </a>
                <a href="javascript:void(0);" onclick="supprimerMessageBar(' . $row['id'] . ')" title="Supprimer">
                    <i class="fa fa-trash text-danger" style="font-size: 18px;"></i>
                </a>
            ';

        $data[] = [
            htmlspecialchars($row['message']),
            htmlspecialchars($row['statut']),

            $actions
        ];
    }

    // Nombre total de produits sans filtre
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM top_bar_info";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Nombre total de produits après filtre
    $totalFilteredRecordsQuery = "SELECT COUNT(id) AS total FROM top_bar_info $searchQuery";
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


if (isset($_POST['getAllContactMsg'])) {

    // Pagination sécurisée
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    // Sécurisation de la recherche
    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    // Construction de la requête de filtrage
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = " WHERE message LIKE '%$searchValue%' OR email LIKE '%$searchValue%' OR nom LIKE '%$searchValue%' OR num_tel LIKE '%$searchValue%'  ";
    }

    // Récupération des produits avec pagination
    $sql = "SELECT *   FROM contact_message 
            
     $searchQuery LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {



        $actions = '
                <a href="javascript:void(0);" onclick="sendMailContact(' . $row['id'] . ')" title="send">
                    <i class="fa fa-edit text-dark" style="font-size: 18px;"></i> 
                </a>
            ';

        $data[] = [
            htmlspecialchars($row['message']),
            htmlspecialchars($row['email']),
            htmlspecialchars($row['nom']),
            htmlspecialchars($row['num_tel']),

            $actions
        ];
    }

    // Nombre total de produits sans filtre
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM top_bar_info";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Nombre total de produits après filtre
    $totalFilteredRecordsQuery = "SELECT COUNT(id) AS total FROM top_bar_info $searchQuery";
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


if (isset($_POST['ModalTopBar'])) { ?>

    <div class="modal fade" id="ajouterTopBarModal" tabindex="-1" aria-labelledby="ajouterTopBarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-sm rounded-3">
                <div class="modal-header bg-dark text-white rounded-top">
                    <h5 class="modal-title text-white" id="ajouterTopBarModalLabel">Ajouter message</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <form id="AddMessageTopBar" enctype="multipart/form-data">
                    <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="message" class="form-label">message <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="3" name="message" id="message"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="statut" class="form-label">statut <span class="text-danger">*</span></label>
                                <select name="statut" id="statut" class="form-control">
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
<?php }

if (isset($_POST['ajouterMessageBar'])) {

    $message = isset($_POST['message']) ? $_POST['message'] : '';
    $statut = isset($_POST['statut']) ? $_POST['statut'] : '';

$sql = mysqli_query($con, "INSERT INTO top_bar_info (message, statut, date_ajout) VALUES ('" . $message . "', '" . $statut . "', NOW())");

    if ($sql) {
        echo json_encode(['status' => 'success', 'message' => 'message ajoutée avec succès']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'ajout", 'error' => mysqli_error($con)]);
    }
}

if (isset($_POST['deleteMessageBar'])) {
    $id = intval($_POST['id']); // sécurisation

    $result = mysqli_query($con, "DELETE FROM top_bar_info WHERE id = $id");

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Message supprimé avec succès']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Erreur lors de la suppression", 'error' => mysqli_error($con)]);
    }
}


if (isset($_POST['modalUpdateMessage'])) {
    $query = "SELECT * FROM top_bar_info WHERE id='" . $_POST['id'] . "'";
    $sql = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($sql);
?>

    <div class="modal fade" id="modifierTopBarModal" tabindex="-1" aria-labelledby="modifierTopBarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-sm rounded-3">
                <div class="modal-header bg-dark text-white rounded-top">
                    <h5 class="modal-title text-white" id="modifierTopBarModalLabel">modifier message</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <form id="UpdateMessageTopBar" enctype="multipart/form-data">
                    <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="message" class="form-label">message <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="3" name="message" id="message"><?php echo $row['message']; ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="statut" class="form-label">statut <span class="text-danger">*</span></label>
                                <select name="statut" id="statut" class="form-control">
                                    <option value="actif" <?php echo ($row['statut'] == "actif") ? 'selected' : ''; ?>>Actif</option>
                                    <option value="inactif" <?php echo ($row['statut'] == "inactif") ? 'selected' : ''; ?>>Inactif</option>
                                </select>
                            </div>


                        </div>
                    </div>
                    <input type="hidden" name="id" value="<?php echo $_POST['id']; ?>">

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php }

    if (isset($_POST['updateMessage'])) {

        $sql_update = mysqli_query($con, "UPDATE top_bar_info SET `message`='" . $_POST['message'] . "', `statut`='" . $_POST['statut'] . "' WHERE id='" . $_POST['id'] . "'");

        if ($sql_update) {
            echo json_encode(['status' => 'success', 'message' => 'Message modifié avec succès']);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Erreur lors de la modification", 'error' => mysqli_error($con)]);
        }
    }

