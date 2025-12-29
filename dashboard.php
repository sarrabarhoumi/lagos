<?php
include 'connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Optionnel : sécuriser l'accès admin
 * if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
 *   header("Location: login.php"); exit;
 * }
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin | LAGOS</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <link rel="stylesheet" href="css/style.css">

  <style>
    :root{
      --bg:#f4f6f9;
      --card:#ffffff;
      --text:#111827;
      --muted:#6b7280;
      --accent:#ff9800;

      --sidebar:#0f172a;
      --sidebar2:#111827;
      --sidebarText:#cbd5e1;
      --sidebarHover:#1f2937;

      --shadow: 0 10px 30px rgba(15,23,42,.08);
      --radius: 16px;
    }
    *{ box-sizing:border-box; }
    body{ font-family:'Poppins',sans-serif; background:var(--bg); color:var(--text); }

    .app-wrap{ min-height:100vh; display:flex; }
    .sidebar{
      width:260px; background:linear-gradient(180deg,var(--sidebar),var(--sidebar2));
      color:var(--sidebarText); padding:18px 14px; position:sticky; top:0; height:100vh; overflow-y:auto;
      border-right:1px solid rgba(255,255,255,.06);
    }
    .brand-box{
      display:flex; align-items:center; justify-content:space-between;
      padding:10px 10px 16px; margin-bottom:10px;
      border-bottom:1px solid rgba(255,255,255,.08);
    }
    .brand-title{ font-weight:800; letter-spacing:.8px; color:#fff; margin:0; font-size:1.15rem; }
    .brand-sub{ font-size:.85rem; color:rgba(255,255,255,.65); }

    .sidebar .nav-link{
      display:flex; align-items:center; gap:10px; color:var(--sidebarText);
      padding:10px 12px; border-radius:12px; margin:6px 0;
      font-weight:600; transition:.2s ease; border:1px solid transparent;
    }
    .sidebar .nav-link i{ width:26px; font-size:1.1rem; opacity:.9; }
    .sidebar .nav-link:hover{ background:var(--sidebarHover); border-color:rgba(255,255,255,.06); color:#fff; }
    .sidebar .nav-link.active{
      background:rgba(255,255,255,.10); color:#fff; border-color:rgba(255,255,255,.14);
      box-shadow:0 6px 20px rgba(0,0,0,.18);
    }

    .content{ flex:1; padding:18px 18px 30px; }

    .topbar{
      background:rgba(255,255,255,.75); backdrop-filter:blur(8px);
      border:1px solid rgba(17,24,39,.08);
      box-shadow:var(--shadow);
      border-radius:var(--radius);
      padding:12px 14px;
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      margin-bottom:16px;
    }
    .topbar .title{ font-weight:800; margin:0; font-size:1.1rem; }
    .topbar .search{ width:min(520px,100%); position:relative; }
    .topbar .search input{
      width:100%; padding:10px 12px 10px 38px; border-radius:12px;
      border:1px solid rgba(17,24,39,.10); outline:none; background:#fff;
    }
    .topbar .search i{ position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); }
    .topbar .actions{ display:flex; align-items:center; gap:10px; }
    .icon-btn{
      border:1px solid rgba(17,24,39,.12); background:#fff;
      width:40px; height:40px; border-radius:12px; display:grid; place-items:center;
      cursor:pointer; transition:.2s ease;
    }
    .icon-btn:hover{ transform:translateY(-1px); box-shadow:var(--shadow); }

    .stat-card{
      background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow);
      padding:18px; border:1px solid rgba(17,24,39,.06); height:100%; transition:.2s ease;
    }
    .stat-card:hover{ transform:translateY(-2px); }
    .stat-head{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
    .stat-label{ color:var(--muted); font-weight:600; font-size:.9rem; margin:0; }
    .stat-value{ font-size:1.5rem; font-weight:900; margin:0; }
    .stat-icon{
      width:44px; height:44px; border-radius:14px;
      display:grid; place-items:center;
      background:rgba(255,152,0,.12); color:var(--accent); font-size:1.25rem;
    }
    .stat-foot{ margin-top:10px; color:var(--muted); font-size:.85rem; }

    .card-table{
      background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow);
      padding:16px; border:1px solid rgba(17,24,39,.06);
    }
    .card-table h5{ margin:0 0 12px; font-weight:800; }

    table.dataTable thead th{
      background:#f3f4f6; font-weight:800; text-align:center;
      border-bottom:1px solid rgba(17,24,39,.08) !important;
    }
    table.dataTable tbody td{ text-align:center; vertical-align:middle; }

    @media(max-width:991px){
      .app-wrap{ display:block; }
      .sidebar{ width:100%; height:auto; position:relative; border-right:none; border-bottom:1px solid rgba(255,255,255,.08); }
    }
  </style>
</head>

<body>

<?php include 'header_dash.php'; ?>

<div class="app-wrap">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand-box">
      <div>
        <p class="brand-title mb-0">LAGOS Admin</p>
        <div class="brand-sub">Gestion boutique</div>
      </div>
      <span class="badge text-bg-warning">PRO</span>
    </div>

    <ul class="nav flex-column" id="menu" role="tablist">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#orders"><i class="bi bi-cart-check"></i> Commandes</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#customers"><i class="bi bi-person-lines-fill"></i> Clients</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#products"><i class="bi bi-box-seam"></i> Produits</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#categories"><i class="bi bi-tags"></i> Catégories</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#users"><i class="bi bi-people-fill"></i> Utilisateurs</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#roles"><i class="bi bi-shield-lock-fill"></i> Rôles</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#top-bar"><i class="bi bi-chat-dots-fill"></i> Messages</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#contact-message"><i class="bi bi-envelope-paper-fill"></i> Contact</a></li>
    </ul>
  </aside>

  <!-- Content -->
  <main class="content">
    <div class="topbar">
      <div>
        <p class="title mb-0">Dashboard</p>
        <small class="text-muted">Vue d’ensemble & gestion</small>
      </div>

      <div class="search d-none d-md-block">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Recherche rapide (produits, commandes, clients...)" disabled>
      </div>

      <div class="actions">
        <button class="icon-btn" type="button" title="Notifications"><i class="bi bi-bell"></i></button>
        <button class="icon-btn" type="button" title="Paramètres"><i class="bi bi-gear"></i></button>
      </div>
    </div>

    <div class="tab-content">
      <div id="update"></div>

      <!-- DASHBOARD -->
      <div class="tab-pane fade show active" id="dashboard">

        <!-- KPI -->
        <div class="row g-3">
          <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-head">
                <div>
                  <p class="stat-label">Commandes traitées</p>
                  <p class="stat-value" id="kpiOrders">—</p>
                </div>
                <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
              </div>
              <div class="stat-foot">Total commandes validées</div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-head">
                <div>
                  <p class="stat-label">Utilisateurs</p>
                  <p class="stat-value" id="kpiUsers">—</p>
                </div>
                <div class="stat-icon"><i class="bi bi-people"></i></div>
              </div>
              <div class="stat-foot">Total utilisateurs</div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-head">
                <div>
                  <p class="stat-label">Produits</p>
                  <p class="stat-value" id="kpiProducts">—</p>
                </div>
                <div class="stat-icon"><i class="bi bi-box"></i></div>
              </div>
              <div class="stat-foot">Produits en base</div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card">
              <div class="stat-head">
                <div>
                  <p class="stat-label">Chiffre d’affaires</p>
                  <p class="stat-value" id="kpiRevenue">—</p>
                </div>
                <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
              </div>
              <div class="stat-foot">Somme des ventes (validées)</div>
            </div>
          </div>
        </div>

        <!-- CHART + TOP SALES -->
        <div class="row g-3 mt-1">
          <div class="col-lg-8">
            <div class="card-table">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0">Statistiques des ventes</h5>
                <span class="badge text-bg-light">7 derniers jours</span>
              </div>
              <canvas id="salesChart" height="140"></canvas>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card-table h-100">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0">Meilleures ventes</h5>
                <span class="small text-muted">Top 5</span>
              </div>

              <ul class="list-group list-group-flush" id="bestSalesList">
                <li class="list-group-item text-muted">Chargement...</li>
              </ul>
            </div>
          </div>
        </div>

      </div>

     <!-- Orders -->
      <div class="tab-pane fade" id="orders">
        <div class="card-table">
          <h5>Liste des commandes</h5>
          <div class="table-responsive mt-3">
            <table id="myTableCommandes" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>Details</th>
                  <th>Nom</th>
                  <th>Prénom</th>
                  <th>Adresse</th>
                  <th>Gouvernorat</th>
                  <th>Num Tel</th>
                  <th>Email</th>
                  <th>Statut</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Customers -->
      <div class="tab-pane fade" id="customers">
        <div class="card-table">
          <h5>Liste des clients</h5>
          <div class="table-responsive mt-3">
            <table id="myTableClients" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Nom</th>
                  <th>Prénom</th>
                  <th>Email</th>
                  <th>Num Tel</th>
                  <th>Adresse</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div class="tab-pane fade" id="products">
        <div class="card-table">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Liste des produits</h5>
            <button class="btn btn-dark" onclick="ajouter_produit()">+ Ajouter</button>
          </div>

          <div class="table-responsive">
            <table id="myTableProducts" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Nom</th>
                  <th>Prix</th>
                  <th>Détails</th>
                  <th>Description</th>
                  <th>Statut</th>
                  <th>Catégorie</th>
                  <th>Date d'ajout</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Categories -->
      <div class="tab-pane fade" id="categories">
        <div class="card-table">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Liste des catégories</h5>
            <button class="btn btn-dark" onclick="ajouter_categorie()">+ Ajouter</button>
          </div>

          <div class="table-responsive">
            <table id="myTableCategorie" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Nom</th>
                  <th>Statut</th>
                  <th>Date d'ajout</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Users -->
      <div class="tab-pane fade" id="users">
        <div class="card-table">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Liste des utilisateurs</h5>
            <button class="btn btn-dark" onclick="addUser()">+ Ajouter</button>
          </div>

          <div class="table-responsive">
            <table id="myTableUsers" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>nom</th>
                  <th>prenom</th>
                  <th>email</th>
                  <th>num tel</th>
                  <th>role</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Roles -->
      <div class="tab-pane fade" id="roles">
        <div class="card-table">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Liste des rôles</h5>
            <button class="btn btn-dark" onclick="addRole()">+ Ajouter</button>
          </div>

          <div class="table-responsive">
            <table id="myTableRole" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>role</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Top-bar -->
      <div class="tab-pane fade" id="top-bar">
        <div class="card-table">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Message top bar</h5>
            <button class="btn btn-dark" onclick="newTopBar()">+ Ajouter</button>
          </div>

          <div class="table-responsive">
            <table id="myTableTopBar" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>message</th>
                  <th>statut</th>
                  <th>action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- Contact -->
      <div class="tab-pane fade" id="contact-message">
        <div class="card-table">
          <h5>Contact Messages</h5>
          <div class="table-responsive">
            <table id="myTableContactMessage" class="table table-hover table-striped dataTable w-100">
              <thead>
                <tr>
                  <th>Message</th>
                  <th>Email</th>
                  <th>Nom</th>
                  <th>Numéro de téléphone</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="js/produit.js"></script>
<script src="js/categorie.js"></script>
<script src="js/user.js"></script>
<script src="js/app.js"></script>

<script>
  function setText(id, value){
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = value;
  }

  function moneyDT(n){
    const x = Number(n || 0);
    return x.toFixed(3) + " DT";
  }

  function escapeHtml(str){
    return String(str ?? "")
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }

  function renderTopSales(list){
    const ul = document.getElementById("bestSalesList");
    if (!ul) return;

    if (!Array.isArray(list) || list.length === 0){
      ul.innerHTML = `<li class="list-group-item text-muted">Aucune donnée.</li>`;
      return;
    }

    ul.innerHTML = list.map(it => `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span class="text-truncate" style="max-width:70%">
          ${escapeHtml(it.nom)}
          <small class="text-muted d-block">Qté: ${it.qte_total}</small>
        </span>
        <span class="badge text-bg-dark">${Number(it.ca_total||0).toFixed(3)} DT</span>
      </li>
    `).join("");
  }

  let salesChartInstance = null;

  function renderSalesChart(labels, values){
    const canvas = document.getElementById("salesChart");
    if (!canvas) return;

    if (salesChartInstance) salesChartInstance.destroy();

    salesChartInstance = new Chart(canvas, {
      type: "line",
      data: {
        labels,
        datasets: [{
          label: "Ventes (DT)",
          data: values,
          tension: 0.35
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  async function loadDashboardStats(){
    try{
      const res = await fetch("ajaxfiles/dashboard_stats.php", { cache: "no-store" });
      const data = await res.json();

      if (!res.ok || !data.success){
        console.error("dashboard_stats error:", data);
        return;
      }

      setText("kpiOrders", data.kpi.orders_processed ?? 0);
      setText("kpiUsers", data.kpi.users_total ?? 0);
      setText("kpiProducts", data.kpi.products_total ?? 0);
      setText("kpiRevenue", moneyDT(data.kpi.revenue_total ?? 0));

      renderSalesChart(data.sales.labels || [], data.sales.values || []);
      renderTopSales(data.best_sales || []);
    }catch(e){
      console.error(e);
    }
  }

  document.addEventListener("DOMContentLoaded", loadDashboardStats);
</script>

</body>
</html>
