<?php
include '../connection.php';



if (isset($_POST['ajouterModalUser'])) { 
    // Récupérer les rôles depuis la table role
    $roles = [];
    $roleQuery = mysqli_query($con, "SELECT id, nom FROM role");
    if ($roleQuery && mysqli_num_rows($roleQuery) > 0) {
        while ($row = mysqli_fetch_assoc($roleQuery)) {
            $roles[] = $row;
        }
    }
?>
<div class="modal fade" id="ajouterUserModal" tabindex="-1" aria-labelledby="ajouterUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-sm rounded-3">
            <div class="modal-header bg-dark text-white rounded-top">
                <h5 class="modal-title text-white" id="ajouterUserModalLabel">Ajouter utilisateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="AddUser" enctype="multipart/form-data">
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom_user" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_user" name="nom_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prenom_user" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prenom_user" name="prenom_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email_user" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email_user" name="email_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tel_user" class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="tel_user" name="tel_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="login_user" class="form-label">Login <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="login_user" name="login_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password_user" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_user" name="password_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password_user" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password_user" name="confirm_password_user" required>
                        </div>
                        <div class="col-md-6">
                            <label for="statut_user" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select" id="statut_user" name="statut_user" required>
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="role_user" class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_user" name="role_user" required>
                                <option value="">Sélectionnez un rôle</option>
                                <?php foreach($roles as $role): ?>
                                    <option value="<?= $role['id']; ?>"><?= htmlspecialchars($role['nom']); ?></option>
                                <?php endforeach; ?>
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




if (isset($_POST['getAllUsers'])) {

    // Pagination sécurisée
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    // Sécurisation de la recherche
    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    // Construction de la requête de filtrage
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = "AND nom LIKE '%$searchValue%' OR prenom LIKE '%$searchValue%' OR email LIKE '%$searchValue%' OR tel LIKE '%$searchValue%' ";
    }

    // Récupération des produits avec pagination
    $sql = "SELECT u.*, r.nom as role FROM utilisateur as u
            LEFT JOIN role as r ON r.id = u.id_role
            where statut='actif'
            $searchQuery LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
       
        
        
        $actions = '
                <a href="javascript:void(0);" onclick="modifierUtilisateur(' . $row['id'] . ')" title="Modifier">
                    <i class="fa fa-edit text-dark" style="font-size: 18px;"></i> 
                </a>
                <a href="javascript:void(0);" onclick="supprimerUtilisateur(' . $row['id'] . ')" title="Supprimer">
                    <i class="fa fa-trash text-danger" style="font-size: 18px;"></i>
                </a>
            ';

        $data[] = [
            htmlspecialchars($row['nom']),
            htmlspecialchars($row['prenom']),
            htmlspecialchars($row['email']),
            htmlspecialchars($row['tel']),
            htmlspecialchars($row['role']),
            
            $actions
        ];
    }

    // Nombre total de produits sans filtre
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM utilisateur";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Nombre total de produits après filtre
    $totalFilteredRecordsQuery = "SELECT COUNT(id) AS total FROM utilisateur $searchQuery";
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

if (isset($_POST['getAllRoles'])) {

    // Pagination sécurisée
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;

    // Sécurisation de la recherche
    $searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($con, $_POST['search']['value']) : '';

    // Construction de la requête de filtrage
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchQuery = "WHERE nom LIKE '%$searchValue%'  ";
    }

    // Récupération des produits avec pagination
    $sql = "SELECT * FROM  role 
            $searchQuery LIMIT $limit OFFSET $offset";
    $result = mysqli_query($con, $sql);

    $data = [];

    while ($row = mysqli_fetch_assoc($result)) {
       
        
        
        $actions = '
                <a href="javascript:void(0);" onclick="modifierRole(' . $row['id'] . ')" title="Modifier">
                    <i class="fa fa-edit text-dark" style="font-size: 18px;"></i> 
                </a>
                <a href="javascript:void(0);" onclick="supprimerRole(' . $row['id'] . ')" title="Supprimer">
                    <i class="fa fa-trash text-danger" style="font-size: 18px;"></i>
                </a>
            ';

        $data[] = [
            htmlspecialchars($row['nom']),
            
            $actions
        ];
    }

    // Nombre total de produits sans filtre
    $totalRecordsQuery = "SELECT COUNT(id) AS total FROM role";
    $totalRecordsResult = mysqli_query($con, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Nombre total de produits après filtre
    $totalFilteredRecordsQuery = "SELECT COUNT(id) AS total FROM role $searchQuery";
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
if(isset($_POST['ajouterRole'])){?>
    <div class="modal fade" id="ajouterRoleModal" tabindex="-1" aria-labelledby="ajouterRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-sm rounded-3">
            <div class="modal-header bg-dark text-white rounded-top">
                <h5 class="modal-title text-white" id="ajouterRoleModalLabel">Ajouter role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <form id="AddRole" enctype="multipart/form-data">
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nom_role" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_role" name="nom_role" required>
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


if(isset($_POST['addRole'])) {

    // Récupérer et sécuriser les données
    $nom = mysqli_real_escape_string($con, $_POST['nom_role']);
    
    // Vérifier si le rôle existe déjà
    $checkSql = "SELECT id FROM role WHERE nom = '$nom' LIMIT 1";
    $checkQuery = mysqli_query($con, $checkSql);
    if(mysqli_num_rows($checkQuery) > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Ce rôle existe déjà.'
        ]);
        exit;
    }

    // Insertion du rôle
    $sql = "INSERT INTO role (nom) 
            VALUES ('$nom')";
    $query = mysqli_query($con, $sql);

    if($query) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Rôle ajouté avec succès.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de l’ajout du rôle.',
            'error' => mysqli_error($con)
        ]);
    }
}



if (isset($_POST['addUser'])) {
    // Récupérer et sécuriser les données du formulaire
    $nom = mysqli_real_escape_string($con, $_POST['nom_user']);
    $prenom = mysqli_real_escape_string($con, $_POST['prenom_user']);
    $email = mysqli_real_escape_string($con, $_POST['email_user']);
    $tel = mysqli_real_escape_string($con, $_POST['tel_user']);
    $login = mysqli_real_escape_string($con, $_POST['login_user']);
    $password = $_POST['password_user'];          // mot de passe en clair
    $confirmPassword = $_POST['confirm_password_user'];
    $statut = mysqli_real_escape_string($con, $_POST['statut_user']);
    $role_id = intval($_POST['role_user']);   // id du rôle depuis la table roles

    // Vérifier correspondance mot de passe
    if ($password !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'Les mots de passe ne correspondent pas.']);
        exit;
    }

    // Vérifier si le login existe déjà
    $checkLogin = mysqli_query($con, "SELECT id FROM utilisateur WHERE login='$login'");
    if (mysqli_num_rows($checkLogin) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Ce login est déjà utilisé.']);
        exit;
    }

    // Hacher le mot de passe (Argon2id recommandé)
    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);

    // Optionnel : créer un salt supplémentaire (facultatif)
    $salt = bin2hex(random_bytes(16)); // 32 caractères hexadécimaux

    // Insertion utilisateur
    $sql = "INSERT INTO utilisateur (nom, prenom, email, tel, login, password, statut, id_role, date_ajout)
            VALUES ('$nom', '$prenom', '$email', '$tel', '$login', '$passwordHash', '$statut', $role_id, NOW())";

    $query = mysqli_query($con, $sql);

    if ($query) {
        $userId = mysqli_insert_id($con);

        // Stocker le salt séparément si nécessaire
        $sqlSalt = "INSERT INTO salt (salt, id_user) VALUES ('$salt', $userId)";
        mysqli_query($con, $sqlSalt);

        echo json_encode(['status' => 'success', 'message' => 'Utilisateur ajouté avec succès.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l’ajout.', 'error' => mysqli_error($con)]);
    }
}






?>