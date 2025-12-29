<?php
header('Content-Type: application/json; charset=utf-8');
include '../connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function out($success, $payload = []){
  echo json_encode(array_merge(['success'=>$success], $payload), JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // =========================
  // 1) KPI - commandes validées
  // =========================
  // NOTE: si ta table "commandes" n'a pas de champ "statut", adapte la condition.
  // Je mets une condition safe: si "statut" existe, on filtre, sinon on compte tout.

  // Vérifie si colonne "statut" existe
  $hasStatut = false;
  $q = $con->query("SHOW COLUMNS FROM commandes LIKE 'statut'");
  if ($q && $q->num_rows > 0) $hasStatut = true;

  if ($hasStatut) {
    // adapte les valeurs selon ton projet: 'valide', 'livré', 'payé', etc.
    $ordersSql = "SELECT COUNT(*) AS c FROM commandes WHERE statut IN ('valide','validé','livré','paye','payé','completed','terminee','terminée')";
  } else {
    $ordersSql = "SELECT COUNT(*) AS c FROM commandes";
  }

  $ordersProcessed = 0;
  $r = $con->query($ordersSql);
  if ($r) $ordersProcessed = (int)($r->fetch_assoc()['c'] ?? 0);

  // =========================
  // 2) KPI - utilisateurs total
  // =========================
  $usersTotal = 0;
  $r = $con->query("SELECT COUNT(*) AS c FROM utilisateur");
  if ($r) $usersTotal = (int)($r->fetch_assoc()['c'] ?? 0);

  // =========================
  // 3) KPI - produits total
  // =========================
  $productsTotal = 0;
  $r = $con->query("SELECT COUNT(*) AS c FROM produit");
  if ($r) $productsTotal = (int)($r->fetch_assoc()['c'] ?? 0);

  // =========================
  // 4) KPI - revenue total (validé)
  // =========================
  // On calcule CA via commande_items: SUM(qte * prix)
  // On joint avec commandes si statut existe.
  $revenueTotal = 0.0;

  $hasPrice = false;
  $q = $con->query("SHOW COLUMNS FROM commande_items LIKE 'prix'");
  if ($q && $q->num_rows > 0) $hasPrice = true;

  $hasQty = false;
  $q = $con->query("SHOW COLUMNS FROM commande_items LIKE 'quantite'");
  if ($q && $q->num_rows > 0) $hasQty = true;

  if ($hasPrice && $hasQty) {
    if ($hasStatut) {
      $sql = "
        SELECT COALESCE(SUM(ci.quantite * ci.prix),0) AS ca
        FROM commande_items ci
        INNER JOIN commandes c ON c.id = ci.id_commande
        WHERE c.statut IN ('valide','validé','livré','paye','payé','completed','terminee','terminée')
      ";
    } else {
      $sql = "SELECT COALESCE(SUM(quantite * prix),0) AS ca FROM commande_items";
    }

    $r = $con->query($sql);
    if ($r) $revenueTotal = (float)($r->fetch_assoc()['ca'] ?? 0);
  }

  // =========================
  // 5) Chart (7 derniers jours)
  // =========================
  // On construit labels = dates, values = CA du jour
  // On suppose commandes.date_ajout OU commandes.date_commande OU commandes.created_at
  $dateCol = null;
  $dateCandidates = ['date_ajout','date_commande','created_at','date'];
  foreach ($dateCandidates as $c) {
    $q = $con->query("SHOW COLUMNS FROM commandes LIKE '".$con->real_escape_string($c)."'");
    if ($q && $q->num_rows > 0) { $dateCol = $c; break; }
  }

  $labels = [];
  $values = [];

  // Génère les 7 jours (YYYY-MM-DD)
  $days = [];
  for ($i = 6; $i >= 0; $i--) {
    $d = new DateTime("today -$i day");
    $key = $d->format("Y-m-d");
    $days[$key] = 0.0;
  }

  if ($dateCol && $hasPrice && $hasQty) {
    $whereStatus = $hasStatut ? " AND c.statut IN ('valide','validé','livré','paye','payé','completed','terminee','terminée')" : "";

    $sql = "
      SELECT DATE(c.`$dateCol`) AS d,
             COALESCE(SUM(ci.quantite * ci.prix),0) AS ca
      FROM commandes c
      INNER JOIN commande_items ci ON ci.id_commande = c.id
      WHERE c.`$dateCol` >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      $whereStatus
      GROUP BY DATE(c.`$dateCol`)
      ORDER BY d ASC
    ";
    $r = $con->query($sql);
    if ($r) {
      while ($row = $r->fetch_assoc()) {
        $k = $row['d'];
        if (isset($days[$k])) $days[$k] = (float)$row['ca'];
      }
    }
  }

  foreach ($days as $d => $ca) {
    $labels[] = $d;          // tu peux formater en "J-6" si tu veux
    $values[] = (float)$ca;
  }

  // =========================
  // 6) Best Sales (Top 5)
  // =========================
  $bestSales = [];
  // colonnes supposées: commande_items.id_produit, id_commande, quantite, prix
  // produit: id, nom
  $whereStatus = $hasStatut ? " WHERE c.statut IN ('valide','validé','livré','paye','payé','completed','terminee','terminée')" : "";

  if ($hasPrice && $hasQty) {
    $sql = "
      SELECT p.nom AS nom,
             SUM(ci.quantite) AS qte_total,
             SUM(ci.quantite * ci.prix) AS ca_total
      FROM commande_items ci
      INNER JOIN produit p ON p.id = ci.id_produit
      INNER JOIN commandes c ON c.id = ci.id_commande
      $whereStatus
      GROUP BY p.id
      ORDER BY qte_total DESC
      LIMIT 5
    ";
    $r = $con->query($sql);
    if ($r) {
      while ($row = $r->fetch_assoc()) {
        $bestSales[] = [
          'nom' => $row['nom'] ?? '',
          'qte_total' => (int)($row['qte_total'] ?? 0),
          'ca_total' => (float)($row['ca_total'] ?? 0),
        ];
      }
    }
  }

  out(true, [
    'kpi' => [
      'orders_processed' => $ordersProcessed,
      'users_total' => $usersTotal,
      'products_total' => $productsTotal,
      'revenue_total' => $revenueTotal
    ],
    'sales' => [
      'labels' => $labels,
      'values' => $values
    ],
    'best_sales' => $bestSales
  ]);

} catch (Throwable $e) {
  out(false, ['message' => "Erreur serveur", 'debug' => $e->getMessage()]);
}
