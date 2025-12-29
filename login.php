<?php
session_start();
include 'connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['email'] ?? '');     // ici c'est login/email
    $password = trim($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = "Veuillez remplir tous les champs.";
    } else {

        // ✅ chercher par email OU login
        $stmt = $con->prepare("SELECT id, nom, prenom, email, login, password, statut 
                               FROM utilisateur 
                               WHERE email = ? OR login = ?
                               LIMIT 1");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['nom'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_statut'] = $user['statut'];

                // ✅ Synchroniser le panier localStorage -> DB
                if (!empty($_POST['cart'])) {
                    $cartItems = json_decode($_POST['cart'], true);

                    if (is_array($cartItems)) {
                        foreach ($cartItems as $item) {
                            $productId = (int)($item['id'] ?? 0);
                            $color = trim((string)($item['couleur'] ?? ''));
                            $size  = trim((string)($item['taille'] ?? ''));
                            $qty   = (int)($item['quantite'] ?? 1);

                            if ($productId <= 0 || $color === '' || $size === '' || $qty <= 0) continue;

                            // ✅ nécessite UNIQUE(id_user,id_produit,couleur,taille)
                            $stmt2 = $con->prepare("
                                INSERT INTO panier (id_produit, quantite, id_user, couleur, taille)
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)
                            ");
                            $uid = (int)$user['id'];
                            $stmt2->bind_param("iiiss", $productId, $qty, $uid, $color, $size);
                            $stmt2->execute();
                        }
                    }
                }

                header('Location: index.php');
                exit;

            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            $error = "Login introuvable.";
        }
    }
}
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lagos — Connexion</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
:root{--bg:#f7f8fa;--card:#fff;--muted:#6c757d;--accent:#111;}
html,body{height:100%;}
body{background:linear-gradient(180deg,var(--bg),#fff);font-family:system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial;display:flex;align-items:center;justify-content:center;padding:2rem;}
.login-card{width:100%;max-width:420px;background:var(--card);border-radius:12px;box-shadow:0 6px 30px rgba(15,15,15,0.06);padding:2rem;}
.brand{font-weight:700;letter-spacing:1px;color:var(--accent);font-size:1.75rem;}
.small-muted{color:var(--muted);font-size:0.9rem}
.form-control:focus{box-shadow:none;border-color:rgba(17,17,17,0.12);}
.btn-lagos{background:var(--accent);color:#fff;border-radius:10px;padding:0.6rem 1rem;font-weight:600;border:0;}
.divider{display:flex;align-items:center;gap:0.75rem;color:var(--muted);font-size:0.85rem;margin:1rem 0.5rem;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(0,0,0,0.06);}
a.link-muted{color:var(--accent);text-decoration:none;font-weight:600}
.alert{margin-bottom:1rem;}
@media (max-width:420px){body{padding:1rem}.login-card{padding:1.25rem}}
</style>
</head>
<body>
<main class="login-card">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <div class="brand">lagos</div>
      <div class="small-muted">Boutique en ligne — connexion</div>
    </div>
    <div class="text-end small-muted" style="font-size:0.85rem">Bienvenue 👋</div>
  </div>

  <?php if(!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form id="loginForm" action="" method="post" novalidate>
      <div class="mb-3">
        <label for="email" class="form-label">Login</label>
        <input type="text" class="form-control" id="email" name="email" placeholder="Login" required>
        <div class="invalid-feedback">Veuillez entrer votre login.</div>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" minlength="6" required>
        <div class="invalid-feedback">Le mot de passe doit contenir au moins 6 caractères.</div>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
          <label class="form-check-label" for="remember">Se souvenir de moi</label>
        </div>
        <a href="#" class="small-muted">Mot de passe oublié ?</a>
      </div>
      <div class="d-grid mb-3">
        <button type="submit" class="btn btn-lagos">Se connecter</button>
      </div>
      <div class="divider">ou</div>
      <div class="text-center mb-2">
        <button type="button" class="btn btn-outline-secondary w-100 mb-2">Se connecter avec Google</button>
        <button type="button" class="btn btn-outline-secondary w-100">Se connecter avec Facebook</button>
      </div>
      <div class="text-center mt-3 small-muted">Pas encore de compte ? <a href="register.php" class="link-muted">Créer un compte</a></div>
  </form>
  <footer class="text-center mt-4 small-muted">© <span id="year"></span> Lagos — Tous droits réservés</footer>
</main>

<script>
document.getElementById('year').textContent = new Date().getFullYear();

(function(){
  'use strict'
  var form = document.getElementById('loginForm');
  form.addEventListener('submit', function(event){
    if(!form.checkValidity()){
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
})();

// Envoyer le panier localStorage si existant
document.getElementById('loginForm').addEventListener('submit', function(e){
    const cart = localStorage.getItem('cart');
    if(cart){
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cart';
        input.value = cart;
        this.appendChild(input);
    }
});
</script>
</body>
</html>
