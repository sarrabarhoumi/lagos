<?php
// Charger automatiquement les variables d'environnement
$envFile = __DIR__ . '/.env.local';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // ignorer les commentaires
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
}



$host = getenv('DB_HOST') ?: 'db';
$user = getenv('DB_USER') ?: 'lagos';
$password = getenv('DB_PASS') ?: 'W3a9ClHrgC[krsrd';
$dbname = getenv('DB_NAME') ?: 'lagos';

$con = mysqli_connect($host, $user, $password, $dbname);

// Vérifier la connexion

if (!$con) {
    die("❌ Erreur MySQL : " . mysqli_connect_error());
}

mysqli_set_charset($con, "utf8mb4");
?>
