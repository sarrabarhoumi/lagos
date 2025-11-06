<?php 
session_start();
include '../connection.php';
$id_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] :'';

if (isset($_POST['ajouterPanier'])) {
    $id_produit = $_POST['id'];
    $today = date('Y-m-d');
    
    // Vérification si le produit existe déjà dans le panier
    $sql_verif = mysqli_query($con, "SELECT * FROM panier WHERE id_produit = '$id_produit' AND id_user='$id_user'");
    
    $sql_total = mysqli_query($con, "SELECT MAX(id) AS total FROM panier WHERE id_user=$id_user");
    $row_t = mysqli_fetch_assoc($sql_total);
    $total = $row_t['total'];

    if ($sql_verif && mysqli_num_rows($sql_verif) > 0) {
        echo json_encode(['status' => 'existe', 'message' => 'Produit déjà ajouté au panier', 'total' => $total]);
        exit;
    }

    $sql_insert = mysqli_query($con, "INSERT INTO panier (id_produit, quantite, date_ajout,id_user) VALUES ('$id_produit', '1', '$today','$id_user')");
    $sql_total_ = mysqli_query($con, "SELECT MAX(id) AS total FROM panier WHERE id_user='$id_user'");
    $row_t_ = mysqli_fetch_assoc($sql_total_);
    $total_ = $row_t_['total'];

    if ($sql_insert) {
        echo json_encode(['status' => 'success', 'message' => 'Produit ajouté avec succès', 'total' => $total_]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'ajout du produit au panier', 'total' => $total_]);
    }
}
if(isset($_POST['modifierQuantite'])){
   
    $id_produit = intval($_POST['id_produit']);
    $quantite = intval($_POST['quantite']);
    if ($quantite < 1) {
        echo json_encode(["status" => "error", "message" => "La quantité ne peut pas être inférieure à 1"]);
        exit;
    }

    $sql=mysqli_query($con,"UPDATE panier SET quantite = '".$quantite."' WHERE id_produit = '".$id_produit."' AND id_user = '$id_user'");
    if ($sql) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erreur lors de la mise à jour"]);
    }
}

if(isset($_POST['cart'])){
   
    $cartGuest = isset($_POST['cart']) ? $_POST['cart'] : [];
    $data = [];
    
    foreach ($cartGuest as $item) {
        $productId = intval($item['id']);
    
        // Récupérer les infos principales du produit
        $sqlProduct = "SELECT id, nom, prix, image_principale FROM produit WHERE id = $productId";
        $resultProduct = mysqli_query($con, $sqlProduct);
    
        if ($product = mysqli_fetch_assoc($resultProduct)) {
    
            // Récupérer les détails JSON
            $sqlDetails = "SELECT details FROM details_produit WHERE id_produit = $productId";
            $resultDetails = mysqli_query($con, $sqlDetails);
    
            $couleurs = [];
            $tailles = [];
            $quantiteMax = 0;
    
            while ($row = mysqli_fetch_assoc($resultDetails)) {
                $detailsArray = json_decode($row['details'], true);
    
                foreach ($detailsArray as $detail) {
                    if (!in_array($detail['couleur'], $couleurs)) $couleurs[] = $detail['couleur'];
                    if (!in_array($detail['taille'], $tailles)) $tailles[] = $detail['taille'];
                    if (intval($detail['quantite']) > $quantiteMax) $quantiteMax = intval($detail['quantite']);
                }
            }
    
            $data[] = [
                'id' => $product['id'],
                'nom' => $product['nom'],
                'prix' => $product['prix'],
                'image' => $product['image_principale'],
                'quantite' => $item['quantite'], // quantité choisie par le client
                'quantite_max' => $quantiteMax,
                'couleurs' => $couleurs,
                'tailles' => $tailles
            ];
        }
    }
    
    echo json_encode(['status' => 'success', 'cart' => $data]);
    }
?>
