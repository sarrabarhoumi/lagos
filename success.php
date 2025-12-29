<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Sécurité minimale :
 * – si on arrive sans commande validée → retour accueil
 */
$orderId = $_GET['order'] ?? null;
if (!$orderId) {
  header("Location: index.php");
  exit;
}

// Nettoyage éventuel du panier local (client passager)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Commande confirmée | LAGOS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">

  <style>
    body{
      background:#f6f7f8;
    }
    .success-box{
      background:#fff;
      border-radius:22px;
      padding:42px 32px;
      box-shadow:0 18px 50px rgba(0,0,0,.08);
      max-width:560px;
      margin:auto;
      text-align:center;
    }
    .success-icon{
      width:96px;
      height:96px;
      border-radius:50%;
      background:#111;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:42px;
      margin:0 auto 20px;
    }
    .success-box h1{
      font-weight:900;
      margin-bottom:10px;
    }
    .success-box p{
      color:#666;
      margin-bottom:22px;
    }
    .order-ref{
      background:#f1f1f1;
      border-radius:999px;
      padding:10px 18px;
      display:inline-block;
      font-weight:700;
      margin-bottom:24px;
    }
    .btn-brand{
      background:#111;
      color:#fff;
      border-radius:999px;
      padding:12px 18px;
      font-weight:800;
      border:0;
    }
    .btn-brand:hover{
      background:#000;
      color:#fff;
    }
    .btn-outline-brand{
      border:2px solid #111;
      border-radius:999px;
      padding:12px 18px;
      font-weight:800;
    }
  </style>
</head>

<body>

<?php include 'header_user.php'; ?>

<div class="container d-flex align-items-center justify-content-center" style="min-height:80vh;">
  <div class="success-box">

    <div class="success-icon">
      <i class="bi bi-check-lg"></i>
    </div>

    <h1>Merci pour votre commande 🎉</h1>
    <p>
      Votre commande a été enregistrée avec succès.<br>
      Notre équipe va la traiter dans les plus brefs délais.
    </p>

    <div class="order-ref">
      Référence commande : <strong>#<?= htmlspecialchars($orderId) ?></strong>
    </div>

    <div class="d-grid gap-3 mt-3">
      <a href="index.php" class="btn btn-brand">
        <i class="bi bi-house"></i> Retour à l’accueil
      </a>

      <a href="all_products.php" class="btn btn-outline-brand">
        <i class="bi bi-bag"></i> Continuer mes achats
      </a>
    </div>

    <div class="mt-4 text-muted small">
      <i class="bi bi-info-circle"></i>
      Un SMS ou un email de confirmation vous sera envoyé.
    </div>

  </div>
</div>

<script>
  // Nettoyage panier local (client passager)
  try {
    localStorage.removeItem("cart");
  } catch(e){}

  // Mise à jour badge panier si présent
  if (typeof window.updateCartBadge === "function") {
    window.updateCartBadge(0);
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
