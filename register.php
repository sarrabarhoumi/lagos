<?php
include 'connection.php';
session_start();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription | LAGOS</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .register-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .btn-lagos {
            background-color: #111;
            color: #fff;
            font-weight: 500;
        }
        .btn-lagos:hover {
            background-color: #333;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>

<body>

<?php include 'header_user.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card register-card p-4 bg-white">
                <h3 class="text-center mb-4">Créer un compte</h3>
                <form method="POST" id="registerForm" novalidate>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Votre prénom" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Votre email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmez le mot de passe" required>
                    </div>
                    <button type="submit" class="btn btn-lagos w-100">S'inscrire</button>
                </form>
                <p class="text-center mt-3">Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('#registerForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'registerProc.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    title: 'Succès!',
                    text: response,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = 'login.php';
                });
            },
            error: function(xhr) {
                Swal.fire('Erreur', 'Une erreur est survenue. Veuillez réessayer.', 'error');
            }
        });
    });
});
</script>

</body>
</html>
