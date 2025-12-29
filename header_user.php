<?php
// header_user.php
// Important: ce fichier dépend de $con et de la session.
// On sécurise au maximum.

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($con)) {
  // Si jamais header_user.php est inclus sans connection.php
  // on évite un crash
  // (optionnel) include 'connection.php';
}

// Principe PASSAGER / CONNECTÉ
$isLogged = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userId = $isLogged ? (int)$_SESSION['user_id'] : null;

if (empty($_SESSION['guest_token'])) {
  $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}
$guestToken = $_SESSION['guest_token'];

// Récupérer le count du panier (DB) selon user/guest
$cartCount = 0;
if (isset($con)) {
  if ($isLogged) {
    $stmt = mysqli_prepare($con, "SELECT COALESCE(SUM(quantite),0) AS c FROM panier WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
  } else {
    $stmt = mysqli_prepare($con, "SELECT COALESCE(SUM(quantite),0) AS c FROM panier WHERE guest_token=?");
    mysqli_stmt_bind_param($stmt, "s", $guestToken);
  }

  if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $cartCount = (int)(mysqli_fetch_assoc($res)['c'] ?? 0);
    mysqli_stmt_close($stmt);
  }
}

// User name
$userName = $isLogged ? ($_SESSION['user_name'] ?? 'Mon compte') : 'Invité';
?>

<!-- HEADER LAGOS -->
<header id="lagosHeader" class="header shadow-sm">

  <div class="top-bar">
    <marquee behavior="scroll" direction="left" scrollamount="13">
      <?php
      if (isset($con)) {
        $sql = "SELECT message FROM top_bar_info WHERE statut='actif'";
        $result = mysqli_query($con, $sql);
        if ($result) {
          while ($topBarMessage = mysqli_fetch_assoc($result)) {
            echo htmlspecialchars($topBarMessage['message']) . str_repeat('&nbsp;', 20);
          }
        }
      }
      ?>
    </marquee>
  </div>

  <nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container">

      <!-- LOGO -->
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="image/logo.png" alt="LAGOS" style="height: 40px;">
      </a>

      <!-- Burger -->
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Menu -->
      <div class="collapse navbar-collapse justify-content-center" id="mainNav">
        <ul class="navbar-nav gap-3">
          <li class="nav-item"><a href="index.php" class="nav-link">Accueil</a></li>
          <li class="nav-item"><a href="all_products.php" class="nav-link">Produits</a></li>
          <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
          <li class="nav-item"><a href="about.php" class="nav-link">À propos</a></li>
        </ul>
      </div>

      <!-- Droite -->
      <div class="d-flex align-items-center gap-3">

        <!-- Recherche (optionnel: à brancher plus tard) -->
        <form class="d-none d-lg-flex align-items-center position-relative" action="all_products.php" method="GET">
          <input name="q" type="text"
                 class="form-control form-control-sm rounded-pill ps-3 border-0 bg-light"
                 placeholder="Rechercher...">
          <button class="btn btn-link text-dark position-absolute end-0 me-2" type="submit" aria-label="Rechercher">
            <i class="bi bi-search"></i>
          </button>
        </form>

        <!-- USER MENU -->
        <style>
          .user-menu { position: relative; display: inline-block; }
          .user-menu .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 110%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            min-width: 200px;
            z-index: 1000;
            padding: .5rem 0;
          }
          .user-menu.open .dropdown-menu { display: block; }

          .user-menu .dropdown-menu .dropdown-title {
            padding: .6rem 1rem;
            font-weight: 700;
            color: #111;
            border-bottom: 1px solid #eee;
            margin-bottom: .25rem;
          }

          .user-menu .dropdown-menu a {
            display: block;
            padding: .6rem 1rem;
            color: #111;
            text-decoration: none;
            transition: background .2s;
            font-weight: 500;
          }
          .user-menu .dropdown-menu a:hover { background: #f3f3f3; }

          .user-menu .icon-btn {
            background: none;
            border: none;
            color: #111;
            font-size: 1.3rem;
            cursor: pointer;
            position: relative;
          }
        </style>

        <div class="user-menu" id="userMenu">
          <button class="icon-btn" id="userMenuBtn" aria-label="Compte">
            <i class="bi bi-person"></i>
          </button>

          <div class="dropdown-menu">
            <div class="dropdown-title">
              <?= htmlspecialchars($userName) ?>
              <?php if (!$isLogged): ?>
                <div class="small text-muted fw-normal">Client passager</div>
              <?php endif; ?>
            </div>

            <?php if ($isLogged): ?>
              <a href="profile.php"><i class="bi bi-person me-2"></i> Mon profil</a>
              <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a>
            <?php else: ?>
              <a href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i> Se connecter</a>
              <a href="register.php"><i class="bi bi-person-plus me-2"></i> Créer un compte</a>
            <?php endif; ?>
          </div>
        </div>

        <script>
          document.addEventListener("DOMContentLoaded", () => {
            const userMenu = document.getElementById("userMenu");
            const btn = document.getElementById("userMenuBtn");

            btn.addEventListener("click", (e) => {
              e.stopPropagation();
              userMenu.classList.toggle("open");
            });

            document.addEventListener("click", (e) => {
              if (!userMenu.contains(e.target)) userMenu.classList.remove("open");
            });
          });
        </script>

        <!-- PANIER -->
        <a href="panier.php" class="icon-btn position-relative" aria-label="Panier">
          <i class="bi bi-bag"></i>

          <span class="cart-count" id="cartCountBadge"
                style="<?= ($cartCount <= 0 ? 'display:none;' : '') ?>">
            <?= (int)$cartCount ?>
          </span>
        </a>

      </div>
    </div>
  </nav>
</header>

<!-- STYLES -->
<style>
  body {
    opacity: 0;
    transform: translateY(15px);
    transition: opacity 1.2s ease, transform 1.2s ease;
  }
  body.loaded { opacity: 1; transform: translateY(0); }

  .header {
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
  }

  .navbar-nav .nav-link {
    font-weight: 500;
    color: #222;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    position: relative;
    transition: all 0.3s;
  }

  .navbar-nav .nav-link::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 50%;
    width: 0;
    height: 1.8px;
    background: #000;
    transition: all 0.3s ease;
    transform: translateX(-50%);
  }

  .navbar-nav .nav-link:hover::after { width: 60%; }
  .navbar-nav .nav-link:hover { color: #000; }

  .icon-btn {
    font-size: 1.25rem;
    color: #333;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    width: 36px;
    height: 36px;
  }
  .icon-btn:hover {
    background: #f1f1f1;
    transform: scale(1.05);
    color: #000;
  }

  .cart-count {
    background: #d32f2f;
    color: white;
    font-size: 10px;
    border-radius: 50%;
    position: absolute;
    top: 0;
    right: -4px;
    padding: 2px 6px;
    min-width: 18px;
    text-align: center;
  }

  .header.scrolled {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    background-color: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(6px);
  }
</style>

<!-- SCRIPT -->
<script>
  window.addEventListener('load', () => {
    document.body.classList.add('loaded');
  });

  window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    if (!header) return;
    if (window.scrollY > 50) header.classList.add('scrolled');
    else header.classList.remove('scrolled');
  });

  // Optionnel: helper pour mettre à jour le badge après AJAX
  window.updateCartBadge = function(count) {
    const el = document.getElementById("cartCountBadge");
    if (!el) return;
    const c = parseInt(count || 0, 10);
    if (c <= 0) {
      el.style.display = "none";
      el.textContent = "0";
    } else {
      el.style.display = "inline-block";
      el.textContent = String(c);
    }
  }
</script>
