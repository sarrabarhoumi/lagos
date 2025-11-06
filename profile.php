<?php
session_start();

// --- Vérification de connexion ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "connection.php";

// --- Récupération des infos utilisateur ---
$user_id = $_SESSION['user_id'];
$sql = "SELECT nom, prenom, email, tel, login, date_ajout FROM utilisateur WHERE id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Exemple : points fidélité calculés à partir des commandes
$points = 120; // À calculer dynamiquement
$achats = 5;   // Exemple de nombre d’achats
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lagos — Mon Profil</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --bg: #f7f8fa;
      --card: #ffffff;
      --muted: #6c757d;
      --accent: #111111;
      --border: rgba(0,0,0,0.1);
    }
    body {
      background: linear-gradient(180deg, var(--bg), #ffffff);
      
      min-height: 100vh;
      display: flex;
    }

    /* --- SIDEBAR --- */
    .sidebar {
      width: 240px;
      background: var(--card);
      border-right: 1px solid var(--border);
      padding: 2rem 1.2rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      box-shadow: 4px 0 10px rgba(0,0,0,0.03);
    }
    .sidebar .brand {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--accent);
      margin-bottom: 2rem;
    }
    .menu a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
      color: var(--accent);
      padding: 0.5rem 0.75rem;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    .menu a:hover, .menu a.active {
      background: var(--accent);
      color: #fff;
    }

    /* --- MAIN CONTENT --- */
    .profile-content {
      flex: 1;
      padding: 3rem;
    }
    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    .profile-header .title {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--accent);
    }
    .info-card {
      background: var(--card);
      border-radius: 12px;
      box-shadow: 0 6px 30px rgba(15,15,15,0.05);
      padding: 2rem;
    }
    .info-label {
      font-weight: 600;
      color: var(--accent);
    }
    .info-value {
      color: var(--muted);
    }

    /* --- STATS --- */
    .stats {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }
    .stat-box {
      flex: 1;
      min-width: 150px;
      background: var(--card);
      border-radius: 10px;
      box-shadow: 0 3px 15px rgba(0,0,0,0.04);
      padding: 1rem;
      text-align: center;
    }
    .stat-box h3 {
      font-weight: 700;
      color: var(--accent);
    }
    .stat-box span {
      color: var(--muted);
      font-size: 0.9rem;
    }

    .btn-lagos {
      background: var(--accent);
      color: #fff;
      border-radius: 10px;
      padding: 0.6rem 1rem;
      font-weight: 600;
      border: 0;
    }
    .btn-lagos-outline {
      border: 1px solid var(--accent);
      color: var(--accent);
      border-radius: 10px;
      padding: 0.6rem 1rem;
      font-weight: 600;
      background: transparent;
    }
    .btn-lagos-outline:hover {
      background: var(--accent);
      color: #fff;
    }

    @media (max-width: 768px) {
      body { flex-direction: column; }
      .sidebar {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        border-right: none;
        border-bottom: 1px solid var(--border);
        box-shadow: none;
      }
      .profile-content { padding: 2rem 1rem; }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div>
      <div class="brand">Lagos</div>
      <nav class="menu">
        <a href="profile.php" class="active"><i class="bi bi-person-circle"></i> Mon profil</a>
        <a href="orders.php"><i class="bi bi-bag-check"></i> Historique d’achats</a>
        <a href="points.php"><i class="bi bi-stars"></i> Mes points</a>
        <a href="settings.php"><i class="bi bi-gear"></i> Paramètres du compte</a>
      </nav>
    </div>
    <a href="logout.php" class="btn btn-lagos-outline w-100 mt-4"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
  </aside>

  <!-- CONTENU PRINCIPAL -->
  <section class="profile-content">
    <div class="profile-header">
      <div>
        <div class="title">Bonjour, <?= htmlspecialchars($user['prenom']); ?> 👋</div>
        <div class="text-muted">Bienvenue dans votre espace personnel</div>
      </div>
    </div>

    <!-- STATS -->
    <div class="stats">
      <div class="stat-box">
        <h3><?= $achats; ?></h3>
        <span>Achats effectués</span>
      </div>
      <div class="stat-box">
        <h3><?= $points; ?></h3>
        <span>Points fidélité</span>
      </div>
      <div class="stat-box">
        <h3><?= date("d/m/Y", strtotime($user['date_ajout'])); ?></h3>
        <span>Membre depuis</span>
      </div>
    </div>

    <!-- INFOS PERSONNELLES -->
    <div class="info-card">
      <h5 class="mb-4">Informations personnelles</h5>
      <div class="row mb-2">
        <div class="col-md-6">
          <div class="info-label">Nom complet</div>
          <div class="info-value"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
        </div>
        <div class="col-md-6">
          <div class="info-label">Email</div>
          <div class="info-value"><?= htmlspecialchars($user['email']); ?></div>
        </div>
      </div>
      <div class="row mb-2">
        <div class="col-md-6">
          <div class="info-label">Téléphone</div>
          <div class="info-value"><?= htmlspecialchars($user['tel']); ?></div>
        </div>
        <div class="col-md-6">
          <div class="info-label">Identifiant</div>
          <div class="info-value"><?= htmlspecialchars($user['login']); ?></div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <a href="edit_profile.php" class="btn btn-lagos">Modifier mes infos</a>
        <a href="settings.php" class="btn btn-lagos-outline">Paramètres</a>
      </div>
    </div>
  </section>

</body>
</html>
