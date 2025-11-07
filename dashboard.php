<?php include 'connection.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | LAGOS</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f3f5;
        }

        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            background-color: #1a1a1a;
            color: #fff;
            /*position: fixed;
            width: 220px;*/
            transition: all 0.3s ease;
        }

        .sidebar .nav-link {
            color: #ccc;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background-color: #0d6efd;
            color: #fff;
            border-left: 4px solid #fff;
        }

        /* Main content */
        .content {
            /* margin-left: 220px; */
            padding: 30px 20px;
        }

        /* Cards Dashboard */
        .stat-card {
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            padding: 25px;
            margin-bottom: 20px;
            min-height: 150px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card h5 {
            font-weight: 500;
            color: #555;
        }

        .stat-card h4 {
            font-weight: 700;
            margin-top: 10px;
        }

        /* Tables */
        table.dataTable thead th {
            background-color: #e9ecef;
            font-weight: 600;
            text-align: center;
        }

        table.dataTable tbody td {
            text-align: center;
            vertical-align: middle;
        }

        .card-table {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .card-table h5 {
            font-weight: 600;
            margin-bottom: 15px;
        }

        @media(max-width:991px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <?php include 'header_dash.php'; ?>

    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-lg-2 sidebar p-3 bg-white shadow-sm">
            <div class="mb-4 text-center">
                <h4 class="fw-bold text-dark">LAGOS Admin</h4>
            </div>
            <ul class="nav flex-column w-100" id="menu" role="tablist">
                <li class="nav-item mb-2">
                    <a class="nav-link active d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#dashboard">
                        <i class="bi bi-speedometer2 me-2 fs-5"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#orders">
                        <i class="bi bi-cart-check me-2 fs-5"></i> <span>Commandes</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#customers">
                        <i class="bi bi-person-lines-fill me-2 fs-5"></i> <span>Clients</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#products">
                        <i class="bi bi-box-seam me-2 fs-5"></i> <span>Produits</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#categories">
                        <i class="bi bi-tags me-2 fs-5"></i> <span>Catégories</span>
                    </a>
                </li>
                <!-- Utilisateurs -->
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#users">
                        <i class="bi bi-people-fill me-2 fs-5"></i> <span>Utilisateurs</span>
                    </a>
                </li>

                <!-- Rôles -->
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#roles">
                        <i class="bi bi-shield-lock-fill me-2 fs-5"></i> <span>Rôles</span>
                    </a>
                </li>

                <!-- top-bar -->
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#top-bar">
                        <i class="bi bi-chat-dots-fill me-2 fs-5"></i>
                        <span>Messages</span>
                    </a>
                </li>
                <!-- top-bar -->
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center px-3 py-2 rounded" data-bs-toggle="pill" href="#contact-message">
                        <i class="bi bi-envelope-paper-fill me-2 fs-5"></i>
                        <span>Contact Messages</span>
                    </a>
                </li>



            </ul>
        </div>

        <style>
            .sidebar {
                min-height: 100vh;
                transition: all 0.3s ease;
            }

            .sidebar .nav-link {
                color: #333;
                /* texte gris foncé */
                font-weight: 500;
                transition: all 0.2s;
                border-left: 3px solid transparent;
            }

            .sidebar .nav-link:hover {
                background-color: #fff8f0;
                /* fond léger */
                color: #ff9800;
                /* texte orange doux */
                border-left: 3px solid #ff9800;
            }

            .sidebar .nav-link.active {
                background-color: #fff3e0;
                /* fond actif */
                color: #ff9800;
                border-left: 3px solid #ff9800;
                font-weight: 600;
            }

            .sidebar .nav-link i {
                min-width: 24px;
            }

            .sidebar .nav-link span {
                flex: 1;
            }

            @media(max-width:991px) {
                .sidebar {
                    position: relative;
                    width: 100%;
                    min-height: auto;
                }
            }
        </style>


        <!-- Main content -->
        <div class="col-lg-10 content">
            <div class="tab-content">
                <div id="update"></div>
                <!-- Dashboard -->
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h5>Commandes traitées</h5>
                                <h4>0</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h5>Utilisateurs actifs</h5>
                                <h4>0</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h5>Produits disponibles</h5>
                                <h4>0</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <h5>Chiffre d'affaires</h5>
                                <h4>0 DT</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-lg-8">
                            <div class="card-table">
                                <h5>Statistiques des ventes</h5>
                                <canvas id="salesChart" height="150"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card-table">
                                <h5>Meilleures ventes</h5>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Produit A <span>120</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Produit B <span>90</span>
                                    </li>
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
                            <h5>Liste des produits</h5>
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

                <!-- categories -->
                <div class="tab-pane fade" id="categories">
                    <div class="card-table">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Liste des categories</h5>
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


                <!-- users -->
                <div class="tab-pane fade" id="users">
                    <div class="card-table">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Liste des utilisateurs</h5>
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

                <!-- roles -->
                <div class="tab-pane fade" id="roles">
                    <div class="card-table">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Liste des roles</h5>
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


                <!-- top-bar -->
                <div class="tab-pane fade" id="top-bar">
                    <div class="card-table">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Message top bar</h5>
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

                <!-- contact-message -->
                <div class="tab-pane fade" id="contact-message">
                    <div class="card-table">

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
        </div>
    </div>

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/produit.js"></script>
    <script src="js/categorie.js"></script>
    <script src="js/user.js"></script>
    <script src="js/app.js"></script>

</body>

</html>