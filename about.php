<?php include 'connection.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos | LAGOS</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        .breadcrumb-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .section-title {
            text-align: center;
            margin: 3rem 0 2rem;
            font-weight: 600;
        }

        .about-hero {
            background: linear-gradient(to bottom right, #ffffff, #f8f9fa);
            text-align: center;
            padding: 5rem 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 3rem;
        }

        .about-hero h1 {
            font-weight: 700;
            letter-spacing: 1px;
        }

        .about-hero p {
            color: #666;
            max-width: 600px;
            margin: 1.5rem auto 0;
        }

        .card-about {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 2rem;
            transition: transform 0.3s;
            text-align: center;
        }

        .card-about:hover {
            transform: translateY(-5px);
        }

        .card-about i {
            font-size: 2rem;
            color: #111;
            margin-bottom: 1rem;
        }

        .card-about h5 {
            margin-top: 1rem;
            font-weight: 600;
        }

        .card-about p {
            color: #555;
        }
    </style>
</head>

<body>

<?php include 'header_user.php'; ?>

<div class="container py-5">

    <!-- Breadcrumb -->
    <div class="text-center mb-5">
        <h2 class="breadcrumb-title">À propos</h2>
        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-dark">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">À propos</li>
            </ol>
        </nav>
    </div>

    <!-- Section Hero -->
    <section class="about-hero">
        <h1>Qui sommes-nous ?</h1>
        <p>LAGOS est une boutique en ligne dédiée à la mode et aux accessoires de qualité. Nous mettons tout en œuvre pour offrir à nos clients une expérience shopping agréable, simple et moderne.</p>
    </section>

    <!-- Mission & Valeurs -->
    <section>
        <h2 class="section-title">Notre Mission</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="card-about">
                    <i class="bi bi-award"></i>
                    <h5>Qualité</h5>
                    <p>Nous proposons des produits sélectionnés avec soin pour garantir la meilleure qualité à nos clients.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-about">
                    <i class="bi bi-people"></i>
                    <h5>Service Client</h5>
                    <p>Notre équipe est à votre écoute pour vous accompagner à chaque étape de votre achat.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-about">
                    <i class="bi bi-truck"></i>
                    <h5>Livraison Rapide</h5>
                    <p>Nous assurons une livraison fiable et rapide partout en Tunisie pour tous vos achats.</p>
                </div>
            </div>
        </div>
    </section>

    

</div>

<!-- Footer -->
<footer class="text-center py-4 border-top">
    © <?php echo date('Y'); ?> Lagos — Votre boutique de confiance.
</footer>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
