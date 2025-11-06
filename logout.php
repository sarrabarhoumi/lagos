<?php
// ---------------------------------------------------------
// 🌐 Fichier : logout.php
// Objectif : Déconnecter l’utilisateur proprement et le
// rediriger vers la page d’accueil en mode "visiteur".
// ---------------------------------------------------------

// Démarre la session uniquement si elle n’est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Supprimer toutes les variables de session
$_SESSION = [];

// Supprimer le cookie de session s’il existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax'
        ]
    );
}

// Détruire la session
session_destroy();

// Optionnel : Ajouter un flag de déconnexion pour un toast sur la page d’accueil
header("Location: index.php?logout=1");
exit;
