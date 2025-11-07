<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
  //var_dump($_SESSION);
}
?>
<!-- HEADER LAGOS -->
 
<header id="lagosHeader" class="header shadow-sm">

<div class="top-bar">
    <marquee behavior="scroll" direction="left" scrollamount="13">
        <?php 
        $sql = "SELECT message FROM top_bar_info WHERE statut='actif'";
        $result = mysqli_query($con, $sql);
        while($topBarMessage = mysqli_fetch_assoc($result)){
            echo htmlspecialchars($topBarMessage['message']) . str_repeat('&nbsp;', 20); 
        }
        ?>
    </marquee>
</div>



  <!-- Navigation principale -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white ">
    <div class="container">

      <!-- LOGO -->
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="image/logo.png" alt="LAGOS" style="height: 40px;">
      </a>

      <!-- Bouton burger (mobile) -->
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Menu principal -->
      <div class="collapse navbar-collapse justify-content-center" id="mainNav">
        <ul class="navbar-nav gap-3">
          <li class="nav-item"><a href="index.php" class="nav-link">Accueil</a></li>
          <li class="nav-item"><a href="all_products.php" class="nav-link">Produits</a></li>
          <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
          <li class="nav-item"><a href="about.php" class="nav-link">À propos</a></li>
        </ul>
      </div>

      <!-- Zone droite -->
      <div class="d-flex align-items-center gap-3">

        <!-- Recherche -->
        <form class="d-none d-lg-flex align-items-center position-relative">
          <input type="text" class="form-control form-control-sm rounded-pill ps-3 border-0 bg-light"
            placeholder="Rechercher...">
          <button class="btn btn-link text-dark position-absolute end-0 me-2" type="submit">
            <i class="bi bi-search"></i>
          </button>
        </form>

        <?php
        $isLogged = isset($_SESSION['user_id']);
        $userName = $isLogged ? ($_SESSION['user_name'] ?? 'Mon compte') : 'Invité';
        ?>
        <style>
          .user-menu {
            position: relative;
            display: inline-block;
          }

          .user-menu .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 110%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            min-width: 180px;
            z-index: 1000;
            padding: .5rem 0;
          }

          .user-menu .dropdown-menu a {
            display: block;
            padding: .6rem 1rem;
            color: #111;
            text-decoration: none;
            transition: background .2s;
            font-weight: 500;
          }

          .user-menu .dropdown-menu a:hover {
            background: #f3f3f3;
          }

          .user-menu .icon-btn {
            background: none;
            border: none;
            color: #111;
            font-size: 1.3rem;
            cursor: pointer;
            position: relative;
          }

          .user-menu.open .dropdown-menu {
            display: block;
          }
        </style>

        <div class="user-menu" id="userMenu">
          <button class="icon-btn" id="userMenuBtn">
            <i class="bi bi-person"></i>
          </button>
          <div class="dropdown-menu">
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
          // --- Toggle menu utilisateur ---
          document.addEventListener("DOMContentLoaded", () => {
            const userMenu = document.getElementById("userMenu");
            const btn = document.getElementById("userMenuBtn");

            btn.addEventListener("click", (e) => {
              e.stopPropagation();
              userMenu.classList.toggle("open");
            });

            document.addEventListener("click", (e) => {
              if (!userMenu.contains(e.target)) {
                userMenu.classList.remove("open");
              }
            });
          });
        </script>



        <!-- Panier -->
        <a href="panier.php" class="icon-btn position-relative">
          <i class="bi bi-bag"></i>
          <span class="cart-count">0</span>
        </a>

      </div>
    </div>
  </nav>
</header>

<!-- STYLES -->
<style>
  /* Animation globale */
  body {
    opacity: 0;
    transform: translateY(15px);
    transition: opacity 1.2s ease, transform 1.2s ease;
  }

  body.loaded {
    opacity: 1;
    transform: translateY(0);
  }

  /* Header */
  .header {
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
  }




  /* Navigation principale */
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

  .navbar-nav .nav-link:hover::after {
    width: 60%;
  }

  .navbar-nav .nav-link:hover {
    color: #000;
  }

  /* Icônes */
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

  /* Panier badge */
  .cart-count {
    background: #d32f2f;
    color: white;
    font-size: 10px;
    border-radius: 50%;
    position: absolute;
    top: 0;
    right: -4px;
    padding: 2px 5px;
  }

  /* Effet sticky lors du scroll */
  .header.scrolled {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    background-color: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(6px);
  }
</style>

<!-- SCRIPT -->
<script>
  // Apparition fluide
  window.addEventListener('load', () => {
    document.body.classList.add('loaded');
  });

  // Effet sticky élégant
  window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    if (window.scrollY > 50) header.classList.add('scrolled');
    else header.classList.remove('scrolled');
  });
</script>