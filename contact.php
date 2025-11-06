<?php include 'connection.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | LAGOS</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Formulaire et cartes info */
        .contact-wrap, .info-wrap {
            border-radius: 12px;
            background-color: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 2rem;
        }

        .breadcrumb-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control:focus {
            border-color: #111;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        .btn-lagos {
            background-color: #111;
            color: #fff;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
        }

        .btn-lagos:hover {
            background-color: #333;
        }

        .info-wrap p, .info-wrap i {
            color: #222;
        }

        .info-wrap a {
            color: #111;
            text-decoration: none;
        }

        .info-wrap a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<?php include 'header_user.php'; ?>

<div class="container py-5">

    <!-- Breadcrumb -->
    <div class="text-center mb-5">
        <h2 class="breadcrumb-title">Contact</h2>
        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-dark">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact</li>
            </ol>
        </nav>
    </div>

    <!-- Formulaire et infos alignés -->
    <div class="row g-4 d-flex align-items-stretch">
        <!-- Formulaire -->
        <div class="col-md-6">
            <div class="contact-wrap h-100">
                <h3 class="mb-4">Écrivez-nous</h3>
                <div id="form-message"></div>
                <form id="contactForm" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom :</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email :</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Numéro de téléphone :</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="+216 ..." pattern="^\+?\d{1,4}?[0-9]{7,14}$" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message :</label>
                        <textarea class="form-control" id="message" name="message" rows="5" placeholder="Votre message" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-lagos">Envoyer le message</button>
                </form>
            </div>
        </div>

        <!-- Informations -->
        <div class="col-md-6">
            <div class="info-wrap h-100">
                <h3 class="mb-3">Informations de contact</h3>
                <p>Nous sommes ouverts à toutes suggestions ou pour discuter.</p>
                <p><i class="bi bi-telephone me-2"></i> Téléphone : <a href="tel:+21688888888">+216 88888888</a></p>
                <p><i class="bi bi-envelope me-2"></i> Email : <a href="mailto:lagos@gmail.com">lagos@gmail.com</a></p>
                <p><i class="bi bi-geo-alt me-2"></i> Adresse : Tunis, Tunisie</p>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Formulaire de contact
    $('#contactForm').on('submit', function(e){
        e.preventDefault();
        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const message = $('#message').val().trim();

        if(!name || !email || !phone || !message){
            Swal.fire('Erreur','Veuillez remplir tous les champs','error');
            return;
        }

        $.ajax({
            url: 'send_contact.php',
            method: 'POST',
            data: {name,email,phone,message},
            success: function(response){
                Swal.fire('Message envoyé','Merci pour votre message !','success');
                $('#contactForm')[0].reset();
            },
            error: function(){
                Swal.fire('Erreur','Une erreur est survenue.','error');
            }
        });
    });
</script>

</body>
</html>
