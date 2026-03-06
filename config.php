<?php




declare(strict_types=1);

session_start();

$DB_HOST = "localhost";
$DB_NAME = "aiglkkjf_depenses_db";
$DB_USER = "aiglkkjf_aigleroyal";
$DB_PASS = "Bonheur2025@";

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die("Erreur connexion base de données.");
}

// ✅ Fuseau horaire (change si besoin)
date_default_timezone_set('Africa/Abidjan'); // ou 'Europe/Paris'

// (optionnel) tente locale FR si dispo (mais on ne dépend pas de ça)
@setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'French_France.1252');

