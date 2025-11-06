<?php
session_start();
include 'connection.php';

// Si utilisateur connecté
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Panier | LAGOS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="css/style.css">
<style>
/* Styles comme avant */

.breadcrumb-title { font-weight: 600; margin-bottom: 0.5rem; }
.table thead th { background-color: #fff; font-weight: 600; }
.table tbody tr:hover { background-color: #f1f1f1; }
.card-summary { border-radius: 12px; padding: 20px; background-color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.card-summary h2 { font-weight: 600; margin-bottom: 1rem; }
.list-group-item { background-color: #f8f9fa; border: 1px solid #e0e0e0; display:flex; justify-content:space-between; }
.btn-lagos { background-color: #111; color: #fff; font-weight:500; border-radius:8px; width:100%; margin-top:1rem; transition:0.3s; }
.btn-lagos:hover { background-color: #333; }
</style>
</head>
<body>

<?php include 'header_user.php'; ?>

<div class="container py-5">
    <div id="update"></div>

    <!-- Breadcrumb -->
    <div class="text-center mb-4">
        <h2 class="breadcrumb-title">Mon Panier</h2>
        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-dark">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Panier</li>
            </ol>
        </nav>
    </div>

    <div class="row g-4">
        <!-- Tableau des produits -->
        <div class="col-lg-8">
            <div class="table-responsive bg-white rounded shadow-sm p-3">
                <table id="myTablePanier" class="table table-hover text-center mb-0">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix</th>
                            <th>Quantité</th>
                            <th>Total</th>
                            <th>Supprimer</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Résumé de la commande -->
        <div class="col-lg-4">
            <div class="card-summary h-100 d-flex flex-column justify-content-between">
                <div>
                    <h2 class="text-center">Résumé de la Commande</h2>
                    <ul class="list-group mt-3 mb-3" id="cart-summary">
                        <li class="list-group-item">
                            <span>Sous-total</span>
                            <span id="subtotal">0 DT</span>
                        </li>
                        <li class="list-group-item">
                            <span>Frais de livraison</span>
                            <span>7.000 DT</span>
                        </li>
                        <li class="list-group-item fw-bold">
                            <span>Total Général</span>
                            <span id="total-general">0 DT</span>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-lagos" onclick="ValiderCommande()">Confirmer l'achat</button>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/produit.js"></script>
<script src="js/commande.js"></script>



</body>
</html>
