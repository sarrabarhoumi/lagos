<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

include '../connection.php';

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$id_user = $_SESSION['user_id'] ?? null;

/**
 * =========================
 *  MODAL: Ajouter Produit
 * =========================
 */
if (isset($_POST['ajouterModalProduit'])) {

    // catégories actives
    $cats = [];
    $q = mysqli_query($con, "SELECT id, nom FROM categories WHERE statut='actif' ORDER BY nom ASC");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) $cats[] = $r;
    }

?>
    <div class="modal fade" id="ajouterProduitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm rounded-3">
                <div class="modal-header bg-dark text-white rounded-top">
                    <h5 class="modal-title">Ajouter un produit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <form id="AjouterProduit" enctype="multipart/form-data">
                    <div class="modal-body p-4">

                        <div class="mb-3">
                            <label class="form-label">Images <span class="text-danger">*</span></label>
                            <input class="form-control" type="file" id="image_produit_add" name="image_produit[]" multiple required accept="image/*">
                            <div id="preview_add" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="nom_produit" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="description_produit" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Prix <span class="text-danger">*</span></label>
                                <input class="form-control" type="number" name="prix_produit" step="0.001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" name="statut_produit" required>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 p-3 border rounded-3 bg-light">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="promo_active_add" name="promo_active" value="1">
                                <label class="form-check-label fw-semibold" for="promo_active_add">Activer une promotion</label>
                            </div>

                            <div class="row g-3" id="promo_box_add" style="display:none;">
                                <div class="col-md-4">
                                    <label class="form-label">Type de remise</label>
                                    <select class="form-select" name="promo_type">
                                        <option value="percent">Pourcentage (%)</option>
                                        <option value="amount">Montant (DT)</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Valeur remise</label>
                                    <input class="form-control" type="number" step="0.001" min="0" name="promo_value" value="0.000">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Prix après remise (preview)</label>
                                    <input class="form-control" type="text" id="promo_preview_add" readonly value="—">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Début promo (optionnel)</label>
                                    <input class="form-control" type="date" name="promo_start">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fin promo (optionnel)</label>
                                    <input class="form-control" type="date" name="promo_end">
                                </div>
                            </div>

                            <small class="text-muted d-block mt-2">
                                * Si aucune date, la promo est active tant que tu ne la désactives pas.
                            </small>
                        </div>


                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                                <select name="categorie" class="form-select" required>
                                    <option value="" disabled selected>-- Sélectionnez une catégorie --</option>
                                    <?php if (!empty($cats)): ?>
                                        <?php foreach ($cats as $c): ?>
                                            <option value="<?= (int)$c['id'] ?>"><?= e($c['nom']) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option disabled>Aucune catégorie disponible</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- DETAILS -->
                        <div class="mb-3 p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Détails du produit</h6>
                                <button type="button" class="btn btn-dark btn-sm" id="add_details_product_add">+ Ajouter</button>
                            </div>

                            <div id="details_produit_add">
                                <div class="row g-2 align-items-end detail-row">
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
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-detail" style="display:none;">-</button>
                                    </div>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-2">
                                * Ces détails seront enregistrés en JSON dans <code>details_produit.details</code>.
                            </small>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitAddProduct">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        #preview_add img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
    <script>
        (function() {
            const chk = document.getElementById("promo_active_add");
            const box = document.getElementById("promo_box_add");
            const price = document.querySelector('input[name="prix_produit"]');
            const type = document.querySelector('select[name="promo_type"]');
            const val = document.querySelector('input[name="promo_value"]');
            const prev = document.getElementById("promo_preview_add");

            function calc() {
                const p = parseFloat(price?.value || "0");
                const v = parseFloat(val?.value || "0");
                const t = type?.value || "percent";

                if (!chk.checked || p <= 0) {
                    prev.value = "—";
                    return;
                }

                let out = p;
                if (t === "percent") out = p - (p * (v / 100));
                else out = p - v;

                if (out < 0) out = 0;
                prev.value = out.toFixed(3) + " DT";
            }

            chk?.addEventListener("change", () => {
                box.style.display = chk.checked ? "" : "none";
                calc();
            });
            price?.addEventListener("input", calc);
            type?.addEventListener("change", calc);
            val?.addEventListener("input", calc);
        })();
    </script>

<?php

    exit;
}

/**
 * =========================
 *  MODAL: Modifier Produit
 * =========================
 */
if (isset($_POST['modifierModalProduit'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit;

    $stmt = $con->prepare("
    SELECT p.*, c.nom AS categorie_nom, dp.details AS details_json
    FROM produit p
    LEFT JOIN categories c ON c.id = p.id_categorie
    LEFT JOIN details_produit dp ON dp.id_produit = p.id
    WHERE p.id = ?
    LIMIT 1
  ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) exit;

    $p = $res->fetch_assoc();

    $details = [];
    if (!empty($p['details_json'])) {
        $tmp = json_decode($p['details_json'], true);
        if (is_array($tmp)) $details = $tmp;
    }

    // catégories actives
    $cats = [];
    $q = mysqli_query($con, "SELECT id, nom FROM categories WHERE statut='actif' ORDER BY nom ASC");
    if ($q) while ($r = mysqli_fetch_assoc($q)) $cats[] = $r;

?>
    <div class="modal fade" id="modifierProduitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm rounded-3">
                <div class="modal-header bg-dark text-white rounded-top">
                    <h5 class="modal-title">Modifier produit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <form id="ModifierProduit" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <div class="modal-body p-4">

                        <div class="mb-3">
                            <label class="form-label">Ajouter de nouvelles images (optionnel)</label>
                            <input class="form-control" type="file" id="image_produit_edit" name="image_produit[]" multiple accept="image/*">
                            <div id="preview_edit" class="mt-2 d-flex flex-wrap gap-2"></div>
                            <small class="text-muted">Si tu ajoutes des images ici, elles remplaceront l’ancien <code>image_principale</code>.</small>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input class="form-control" type="text" name="nom_produit" required value="<?= e($p['nom']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description</label>
                                <input class="form-control" type="text" name="description_produit" required value="<?= e($p['description']) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Prix</label>
                                <input class="form-control" type="number" step="0.001" name="prix_produit" required value="<?= e($p['prix']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="statut_produit" required>
                                    <option value="actif" <?= ($p['statut'] === 'actif') ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactif" <?= ($p['statut'] === 'inactif') ? 'selected' : '' ?>>Inactif</option>
                                </select>
                            </div>
                        </div>
                        <?php
                        $promo_active = (int)($p['promo_active'] ?? 0);
                        $promo_type   = $p['promo_type'] ?? 'percent';
                        $promo_value  = (float)($p['promo_value'] ?? 0);
                        $promo_start  = $p['promo_start'] ?? '';
                        $promo_end    = $p['promo_end'] ?? '';
                        ?>
                        <div class="mb-3 p-3 border rounded-3 bg-light">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="promo_active_edit" name="promo_active" value="1"
                                    <?= $promo_active ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="promo_active_edit">Activer une promotion</label>
                            </div>

                            <div class="row g-3" id="promo_box_edit" style="<?= $promo_active ? '' : 'display:none;' ?>">
                                <div class="col-md-4">
                                    <label class="form-label">Type de remise</label>
                                    <select class="form-select" name="promo_type">
                                        <option value="percent" <?= $promo_type === 'percent' ? 'selected' : '' ?>>Pourcentage (%)</option>
                                        <option value="amount" <?= $promo_type === 'amount' ? 'selected' : '' ?>>Montant (DT)</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Valeur remise</label>
                                    <input class="form-control" type="number" step="0.001" min="0" name="promo_value"
                                        value="<?= htmlspecialchars(number_format($promo_value, 3, '.', '')) ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Prix après remise (preview)</label>
                                    <input class="form-control" type="text" id="promo_preview_edit" readonly value="—">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Début promo</label>
                                    <input class="form-control" type="date" name="promo_start" value="<?= htmlspecialchars($promo_start) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Fin promo</label>
                                    <input class="form-control" type="date" name="promo_end" value="<?= htmlspecialchars($promo_end) ?>">
                                </div>
                            </div>
                        </div>

                        <script>
                            (function() {
                                const chk = document.getElementById("promo_active_edit");
                                const box = document.getElementById("promo_box_edit");
                                const price = document.querySelector('#ModifierProduit input[name="prix_produit"]');
                                const type = document.querySelector('#ModifierProduit select[name="promo_type"]');
                                const val = document.querySelector('#ModifierProduit input[name="promo_value"]');
                                const prev = document.getElementById("promo_preview_edit");

                                function calc() {
                                    const p = parseFloat(price?.value || "0");
                                    const v = parseFloat(val?.value || "0");
                                    const t = type?.value || "percent";
                                    if (!chk.checked || p <= 0) {
                                        prev.value = "—";
                                        return;
                                    }

                                    let out = p;
                                    if (t === "percent") out = p - (p * (v / 100));
                                    else out = p - v;

                                    if (out < 0) out = 0;
                                    prev.value = out.toFixed(3) + " DT";
                                }

                                chk?.addEventListener("change", () => {
                                    box.style.display = chk.checked ? "" : "none";
                                    calc();
                                });
                                price?.addEventListener("input", calc);
                                type?.addEventListener("change", calc);
                                val?.addEventListener("input", calc);

                                // calcul initial
                                calc();
                            })();
                        </script>


                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Catégorie</label>
                                <select name="categorie" class="form-select" required>
                                    <option value="" disabled>-- Sélectionnez une catégorie --</option>
                                    <?php foreach ($cats as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$p['id_categorie'] === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= e($c['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- DETAILS -->
                        <div class="mb-3 p-3 border rounded-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Détails du produit</h6>
                                <button type="button" class="btn btn-dark btn-sm" id="add_details_product_edit">+ Ajouter</button>
                            </div>

                            <div id="details_produit_edit">
                                <?php if (!empty($details)): ?>
                                    <?php foreach ($details as $i => $d): ?>
                                        <?php
                                        $c = $d['couleur'] ?? '#000000';
                                        $t = $d['taille'] ?? '';
                                        $qte = (int)($d['quantite'] ?? 0);
                                        ?>
                                        <div class="row g-2 align-items-end detail-row">
                                            <div class="col-md-4">
                                                <label class="form-label">Couleur</label>
                                                <input class="form-control" type="color" value="<?= e($c) ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Taille</label>
                                                <input class="form-control" type="text" value="<?= e($t) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Quantité</label>
                                                <input class="form-control" type="number" min="0" value="<?= (int)$qte ?>" required>
                                            </div>
                                            <div class="col-md-1 text-end">
                                                <button type="button" class="btn btn-danger btn-sm btn-remove-detail" <?= ($i === 0 ? 'style="display:none;"' : '') ?>>-</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row g-2 align-items-end detail-row">
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
                                            <button type="button" class="btn btn-danger btn-sm btn-remove-detail" style="display:none;">-</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <small class="text-muted d-block mt-2">* Sauvegardé en JSON.</small>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitEditProduct">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        #preview_edit img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
<?php
    exit;
}

/**
 * =========================
 *  ADD PRODUCT (AJAX)
 * =========================
 */
if (isset($_POST['ajouterProduit'])) {
    header('Content-Type: application/json; charset=utf-8');

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // ✅ Champs
        $nom        = trim($_POST['nom_produit'] ?? '');
        $desc       = trim($_POST['description_produit'] ?? '');
        $prix       = (float)($_POST['prix_produit'] ?? 0);
        $statut     = trim($_POST['statut_produit'] ?? 'actif');
        $categorie  = (int)($_POST['categorie'] ?? 0);
        $detailsRaw = $_POST['details_produit'] ?? '[]';

        // ✅ Promo
        $promo_active = isset($_POST['promo_active']) ? 1 : 0;
        $promo_type   = trim($_POST['promo_type'] ?? 'percent');
        $promo_value  = (float)($_POST['promo_value'] ?? 0);
        $promo_start  = !empty($_POST['promo_start']) ? trim($_POST['promo_start']) : null;
        $promo_end    = !empty($_POST['promo_end']) ? trim($_POST['promo_end']) : null;

        // sécuriser promo
        if (!in_array($promo_type, ['percent', 'amount'], true)) $promo_type = 'percent';
        if ($promo_value < 0) $promo_value = 0;

        // ✅ si promo désactivée => reset champs dates/valeur
        if ($promo_active === 0) {
            $promo_type = 'percent';
            $promo_value = 0;
            $promo_start = null;
            $promo_end = null;
        }

        // ✅ Validate
        if ($nom === '' || $desc === '' || $prix <= 0 || $categorie <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Champs invalides (nom/desc/prix/catégorie).']);
            exit;
        }

        // ✅ Parse JSON details
        $details = json_decode($detailsRaw, true);
        if (!is_array($details)) {
            echo json_encode(['status' => 'error', 'message' => 'details_produit JSON invalide.']);
            exit;
        }

        // ✅ Image obligatoire
        if (empty($_FILES['image_produit']) || empty($_FILES['image_produit']['name'][0])) {
            echo json_encode(['status' => 'error', 'message' => 'Veuillez ajouter au moins une image.']);
            exit;
        }

        // ✅ Upload dir
        $uploadDir = __DIR__ . '/../uploads/produit/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                echo json_encode(['status' => 'error', 'message' => "Impossible de créer le dossier uploads/produit."]);
                exit;
            }
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $images_str = [];

        foreach ($_FILES['image_produit']['name'] as $k => $name) {
            $tmp  = $_FILES['image_produit']['tmp_name'][$k];
            $type = $_FILES['image_produit']['type'][$k];

            if (!in_array($type, $allowed, true)) {
                echo json_encode(['status' => 'error', 'message' => "Format image non autorisé: $name"]);
                exit;
            }

            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
            $unique   = uniqid('prod_', true) . '_' . $safeName;

            $serverPath   = $uploadDir . $unique;         // ✅ serveur
            $relativePath = 'uploads/produit/' . $unique; // ✅ BDD

            if (!move_uploaded_file($tmp, $serverPath)) {
                echo json_encode(['status' => 'error', 'message' => "Erreur upload: $name"]);
                exit;
            }

            $images_str[] = $relativePath;
        }

        $image_str = implode(',', $images_str);
        $today = date("Y-m-d");

        // ✅ Transaction (produit + details)
        $con->begin_transaction();

        // ✅ Insert produit (AVEC PROMO)
        $stmt = $con->prepare("
            INSERT INTO produit
              (nom, description, image_principale, prix, statut, date_ajout, id_categorie,
               promo_active, promo_type, promo_value, promo_start, promo_end)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // promo_start / promo_end peuvent être NULL => on passe variables null OK
        $stmt->bind_param(
            "sssdssissdss",
            $nom,
            $desc,
            $image_str,
            $prix,
            $statut,
            $today,
            $categorie,
            $promo_active,
            $promo_type,
            $promo_value,
            $promo_start,
            $promo_end
        );

        $stmt->execute();
        $id_produit = $stmt->insert_id;

        // ✅ Insert details_produit
        $details_encoded = json_encode($details, JSON_UNESCAPED_UNICODE);

        $stmtCheck = $con->prepare("SELECT id FROM details_produit WHERE id_produit=? LIMIT 1");
        $stmtCheck->bind_param("i", $id_produit);
        $stmtCheck->execute();
        $exists = $stmtCheck->get_result()->num_rows > 0;

        if ($exists) {
            $stmt2 = $con->prepare("UPDATE details_produit SET details=?, date_ajout=? WHERE id_produit=?");
            $stmt2->bind_param("ssi", $details_encoded, $today, $id_produit);
            $stmt2->execute();
        } else {
            $stmt2 = $con->prepare("INSERT INTO details_produit (id_produit, details, date_ajout) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $id_produit, $details_encoded, $today);
            $stmt2->execute();
        }

        $con->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Produit ajouté avec succès ✅',
            'id_produit' => $id_produit
        ]);
        exit;

    } catch (Throwable $e) {
        try { $con->rollback(); } catch (Throwable $ex) {}
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ]);
        exit;
    }
}



/**
 * =========================
 *  UPDATE PRODUCT (AJAX)
 * =========================
 */
/**
 * =========================
 *  UPDATE PRODUCT (AJAX) - COMPLET + PROMO
 * =========================
 */
if (isset($_POST['modifierProduit'])) {
    header('Content-Type: application/json; charset=utf-8');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $id_produit = (int)($_POST['id'] ?? 0);

        // ✅ Champs
        $nom        = trim($_POST['nom_produit'] ?? '');
        $desc       = trim($_POST['description_produit'] ?? '');
        $prix       = (float)($_POST['prix_produit'] ?? 0);
        $statut     = trim($_POST['statut_produit'] ?? 'actif');
        $categorie  = (int)($_POST['categorie'] ?? 0);
        $detailsRaw = $_POST['details_produit'] ?? '[]';

        // ✅ Promo
        $promo_active = isset($_POST['promo_active']) ? 1 : 0;
        $promo_type   = trim($_POST['promo_type'] ?? 'percent');
        $promo_value  = (float)($_POST['promo_value'] ?? 0);
        $promo_start  = !empty($_POST['promo_start']) ? trim($_POST['promo_start']) : null; // YYYY-MM-DD
        $promo_end    = !empty($_POST['promo_end']) ? trim($_POST['promo_end']) : null;     // YYYY-MM-DD

        // ✅ Sécuriser promo
        if (!in_array($promo_type, ['percent', 'amount'], true)) $promo_type = 'percent';
        if ($promo_value < 0) $promo_value = 0;

        // ✅ si promo désactivée => reset champs
        if ($promo_active === 0) {
            $promo_type  = 'percent';
            $promo_value = 0;
            $promo_start = null;
            $promo_end   = null;
        }

        // ✅ Validations
        if ($id_produit <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID produit invalide.']);
            exit;
        }
        if ($nom === '' || $desc === '' || $prix <= 0 || $categorie <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Champs invalides (nom/desc/prix/catégorie).']);
            exit;
        }

        // ✅ Parse JSON details
        $details = json_decode($detailsRaw, true);
        if (!is_array($details)) {
            echo json_encode(['status' => 'error', 'message' => 'details_produit JSON invalide.']);
            exit;
        }
        $details_encoded = json_encode($details, JSON_UNESCAPED_UNICODE);

        // ✅ vérifier si produit existe + images actuelles
        $stmt0 = $con->prepare("SELECT id, image_principale FROM produit WHERE id=? LIMIT 1");
        $stmt0->bind_param("i", $id_produit);
        $stmt0->execute();
        $res0 = $stmt0->get_result();
        if (!$res0 || $res0->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Produit introuvable.']);
            exit;
        }
        $prodRow   = $res0->fetch_assoc();
        $oldImages = $prodRow['image_principale'] ?? '';

        // ✅ Images: optionnel
        $image_str_final = $oldImages;
        $hasNewImages = !empty($_FILES['image_produit']) && !empty($_FILES['image_produit']['name'][0]);

        if ($hasNewImages) {
            $uploadDir = __DIR__ . '/../uploads/produit/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    echo json_encode(['status' => 'error', 'message' => "Impossible de créer le dossier uploads/produit."]);
                    exit;
                }
            }

            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $images_str = [];

            foreach ($_FILES['image_produit']['name'] as $k => $name) {
                $tmp  = $_FILES['image_produit']['tmp_name'][$k];
                $type = $_FILES['image_produit']['type'][$k];

                if (!in_array($type, $allowed, true)) {
                    echo json_encode(['status' => 'error', 'message' => "Format image non autorisé: $name"]);
                    exit;
                }

                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
                $unique   = uniqid('prod_', true) . '_' . $safeName;

                $serverPath   = $uploadDir . $unique;
                $relativePath = 'uploads/produit/' . $unique;

                if (!move_uploaded_file($tmp, $serverPath)) {
                    echo json_encode(['status' => 'error', 'message' => "Erreur upload: $name"]);
                    exit;
                }

                $images_str[] = $relativePath;
            }

            $image_str_final = implode(',', $images_str);
        }

        $today = date("Y-m-d");

        // ✅ Transaction
        $con->begin_transaction();

        /**
         * ✅ Update produit (AVEC PROMO)
         * colonnes attendues :
         * promo_active INT, promo_type VARCHAR, promo_value DOUBLE, promo_start DATE NULL, promo_end DATE NULL
         */
        $sqlUp = "
            UPDATE produit
            SET nom=?,
                description=?,
                prix=?,
                statut=?,
                id_categorie=?,
                image_principale=?,
                promo_active=?,
                promo_type=?,
                promo_value=?,
                promo_start=?,
                promo_end=?
            WHERE id=?
        ";
        $stmt1 = $con->prepare($sqlUp);

        /**
         * ✅ Types (12 variables) :
         * nom(s)
         * desc(s)
         * prix(d)
         * statut(s)
         * categorie(i)
         * image(s)
         * promo_active(i)
         * promo_type(s)
         * promo_value(d)
         * promo_start(s)  // null OK
         * promo_end(s)    // null OK
         * id_produit(i)
         *
         * => "ssdsisisdssi"
         */
        $stmt1->bind_param(
            "ssdsisisdssi",
            $nom,
            $desc,
            $prix,
            $statut,
            $categorie,
            $image_str_final,
            $promo_active,
            $promo_type,
            $promo_value,
            $promo_start,
            $promo_end,
            $id_produit
        );
        $stmt1->execute();

        // ✅ Update/Insert details_produit
        $stmtCheck = $con->prepare("SELECT id FROM details_produit WHERE id_produit=? LIMIT 1");
        $stmtCheck->bind_param("i", $id_produit);
        $stmtCheck->execute();
        $exists = $stmtCheck->get_result()->num_rows > 0;

        if ($exists) {
            $stmt2 = $con->prepare("UPDATE details_produit SET details=?, date_ajout=? WHERE id_produit=?");
            $stmt2->bind_param("ssi", $details_encoded, $today, $id_produit);
            $stmt2->execute();
        } else {
            $stmt2 = $con->prepare("INSERT INTO details_produit (id_produit, details, date_ajout) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $id_produit, $details_encoded, $today);
            $stmt2->execute();
        }

        $con->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Produit modifié avec succès ✅',
            'id_produit' => $id_produit
        ]);
        exit;

    } catch (Throwable $e) {
        try { $con->rollback(); } catch (Throwable $ex) {}
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ]);
        exit;
    }
}




/**
 * =========================
 *  DELETE PRODUCT
 * =========================
 */
if (isset($_POST['supprimerProduit'])) {
    header('Content-Type: application/json; charset=utf-8');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID invalide']);
        exit;
    }

    // delete details then product
    $stmt1 = $con->prepare("DELETE FROM details_produit WHERE id_produit=?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();

    $stmt2 = $con->prepare("DELETE FROM produit WHERE id=?");
    $stmt2->bind_param("i", $id);
    $ok = $stmt2->execute();

    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Produit supprimé ✅' : 'Erreur suppression']);
    exit;
}

/**
 * =========================
 *  DATATABLE: getAllProduct
 * =========================
 */
if (isset($_POST['getAllProduct'])) {
    header('Content-Type: application/json; charset=utf-8');

    $draw   = (int)($_POST['draw'] ?? 1);
    $start  = (int)($_POST['start'] ?? 0);
    $length = (int)($_POST['length'] ?? 10);
    if ($length <= 0) $length = 10;

    $searchValue = $_POST['search']['value'] ?? '';
    $searchValue = trim($searchValue);

    $where = "";
    $params = [];
    $types = "";

    if ($searchValue !== "") {
        $where = "WHERE p.nom LIKE ? OR p.description LIKE ?";
        $like = "%{$searchValue}%";
        $params[] = $like;
        $params[] = $like;
        $types .= "ss";
    }

    // total records
    $totalRecords = 0;
    $r1 = mysqli_query($con, "SELECT COUNT(*) AS total FROM produit");
    if ($r1) $totalRecords = (int)mysqli_fetch_assoc($r1)['total'];

    // filtered records
    if ($where !== "") {
        $stmtCount = $con->prepare("SELECT COUNT(*) AS total FROM produit p $where");
        $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalFiltered = (int)$stmtCount->get_result()->fetch_assoc()['total'];
    } else {
        $totalFiltered = $totalRecords;
    }

    $sql = "
    SELECT p.*, dp.details, c.nom AS categorie
    FROM produit p
    LEFT JOIN details_produit dp ON dp.id_produit = p.id
    LEFT JOIN categories c ON c.id = p.id_categorie
    $where
    ORDER BY p.date_ajout DESC
    LIMIT ? OFFSET ?
  ";

    $stmt = $con->prepare($sql);

    if ($where !== "") {
        $types2 = $types . "ii";
        $params2 = array_merge($params, [$length, $start]);
        $stmt->bind_param($types2, ...$params2);
    } else {
        $stmt->bind_param("ii", $length, $start);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {

        // images
        $imagesHtml = '<div class="d-flex flex-wrap gap-2 align-items-center">';
        $images = explode(',', (string)$row['image_principale']);
        foreach ($images as $image) {
            $imagePath = trim($image);
            $imagePath = str_replace('../', '', $imagePath);
            if ($imagePath === '') continue;
            $imagesHtml .= '<img src="' . e($imagePath) . '"
        class="rounded border" style="width:50px;height:50px;object-fit:cover;" alt="Produit">';
        }
        $imagesHtml .= '</div>';

        // details string
        $detailsString = '';
        if (!empty($row['details'])) {
            $details = json_decode($row['details'], true);
            if (is_array($details)) {
                foreach ($details as $d) {
                    $couleur = e($d['couleur'] ?? '');
                    $taille  = e($d['taille'] ?? '');
                    $qte     = (int)($d['quantite'] ?? 0);

                    $detailsString .= "
            <div class='mb-1'>
              <span>Couleur:
                <input disabled type='color' value='{$couleur}' style='border:none;width:22px;height:22px;vertical-align:middle;'>
              </span>
              <span class='mx-1'>|</span>
              <span>Taille: {$taille}</span>
              <span class='mx-1'>|</span>
              <span>Qté: {$qte}</span>
            </div>";
                }
            }
        }

        // statut
        $statutBadge = ($row['statut'] === 'actif')
            ? '<span class="badge bg-success">Actif</span>'
            : '<span class="badge bg-danger">Inactif</span>';

        // actions
        $actions = '
      <div class="d-flex gap-2 justify-content-center">
        <a href="javascript:void(0)" onclick="modifierProduit(' . (int)$row['id'] . ')" title="Modifier">
          <i class=" bi-pencil-square text-primary fs-5"></i>
        </a>
        <a href="javascript:void(0)" onclick="supprimerProduit(' . (int)$row['id'] . ')" title="Supprimer">
          <i class="bi-trash3 text-danger fs-5"></i>
        </a>
      </div>
    ';

        $data[] = [
            $imagesHtml,
            e($row['nom']),
            number_format((float)$row['prix'], 3) . " DT",
            $detailsString,
            e($row['description']),
            $statutBadge,
            e($row['categorie'] ?? ''),
            e($row['date_ajout'] ?? ''),
            $actions
        ];
    }

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalFiltered,
        "data" => $data
    ]);
    exit;
}

/**
 * =========================
 *  CART COUNT
 * =========================
 */
if (isset($_GET['getCartCount'])) {
    header('Content-Type: application/json; charset=utf-8');

    $count = 0;
    if (!empty($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
        $stmt = $con->prepare("SELECT COALESCE(SUM(quantite),0) AS total FROM panier WHERE id_user=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }
    echo json_encode(['status' => 'success', 'cart_count' => $count]);
    exit;
}

// fallback
http_response_code(400);
echo "Bad request";
