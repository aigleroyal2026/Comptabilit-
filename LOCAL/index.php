<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/helpers.php";

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

$today = date('Y-m-d');

// Recherche (GET)
$q = trim($_GET['q'] ?? '');

// =====================
// Ajouter dépense
// =====================
$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date = $_POST["depense_date"] ?? $today;
    $designation = trim($_POST["designation"] ?? "");
    $montant = (int)($_POST["montant"] ?? 0);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = $today;

    if ($designation === "" || $montant <= 0) {
        $err = "Veuillez renseigner une désignation et un montant valide.";
    } else {
        // On garde user_id (traçabilité), mais tout le monde voit tout
        $stmt = $pdo->prepare("
            INSERT INTO depenses (user_id, depense_date, designation, montant)
            VALUES (?,?,?,?)
        ");
        $stmt->execute([$_SESSION["user_id"], $date, $designation, $montant]);
        header("Location: index.php");
        exit;
    }
}

// =====================
// Charger dépenses du jour (TOUT LE BUREAU)
// =====================
if ($q !== '') {
    $stmt = $pdo->prepare("
        SELECT id, depense_date, designation, montant
        FROM depenses
        WHERE depense_date = ? AND designation LIKE ?
        ORDER BY id DESC
    ");
    $stmt->execute([$today, "%".$q."%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT id, depense_date, designation, montant
        FROM depenses
        WHERE depense_date = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$today]);
}

$rows = $stmt->fetchAll();

// Total du jour (ou filtré)
$total = 0;
foreach ($rows as $r) $total += (int)$r["montant"];

function fcfa(int $n): string {
    return number_format($n, 0, ',', '.') . " F";
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Dépenses du jour</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .searchbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:10px 0 12px}
    .searchbar input{flex:1;min-width:220px;padding:10px;border:1px solid #cfd5e3;border-radius:10px}
    .searchbar .btn{padding:10px 12px}
    .muted{font-size:12px;color:#666}
  </style>
</head>
<body>

<div class="container">
  <div class="topbar">
    <div>
      <div class="hello">
        Bonjour, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
      </div>
      <div class="date">
        Aujourd’hui : <?= date_fr($today) ?>
      </div>
    </div>

    <div class="top-actions">
      <?php if ($isAdmin): ?>
        <a class="btn outline" href="admin.php">Admin</a>
      <?php endif; ?>
      <a class="btn outline" href="historique.php">Historique</a>
      <a class="btn outline" href="rapport_mois.php?mois=<?= date('Y-m') ?>" target="_blank">Rapport du mois</a>
      <a class="btn outline" href="rapport_jour.php?date=<?= urlencode($today) ?>" target="_blank">Imprimer (Word)</a>
      <a class="btn danger" href="logout.php">Déconnexion</a>
    </div>
  </div>

  <div class="grid">

    <!-- ===================== -->
    <!-- AJOUT DÉPENSE -->
    <!-- ===================== -->
    <div class="card">
      <h2>Nouvelle dépense</h2>

      <?php if ($err): ?>
        <div class="alert"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form method="post" class="form">
        <label>Date</label>
        <input type="date" name="depense_date" value="<?= htmlspecialchars($today) ?>" required>

        <label>Désignation</label>
        <input type="text" name="designation" placeholder="Ex: Carburant / Dépôt / Fournitures" required>

        <label>Montant (FCFA)</label>
        <input type="number" name="montant" min="1" step="1" placeholder="Ex: 150000" required>

        <button class="btn" type="submit">Ajouter</button>
      </form>
    </div>

    <!-- ===================== -->
    <!-- LISTE DU JOUR -->
    <!-- ===================== -->
    <div class="card">
      <div class="card-head">
        <h2>Dépenses du jour</h2>
        <div class="total-box">
          Fin journalier :
          <span class="total"><?= fcfa($total) ?></span>
        </div>
      </div>

      <!-- Recherche -->
      <form method="get" class="searchbar">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
               placeholder="Rechercher dans les dépenses d’aujourd’hui…">
        <button class="btn outline" type="submit">Rechercher</button>
        <?php if ($q !== ''): ?>
          <a class="btn outline" href="index.php">Réinitialiser</a>
        <?php endif; ?>
      </form>

      <?php if ($q !== ''): ?>
        <div class="muted">
          Filtre actif : <strong><?= htmlspecialchars($q) ?></strong>
          — <?= count($rows) ?> résultat(s)
        </div>
      <?php endif; ?>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:18%">DATE</th>
              <th>DÉSIGNATION</th>
              <th style="width:22%; text-align:right">MONTANT</th>
              <th style="width:14%">#</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="4">Aucune dépense trouvée.</td></tr>
            <?php endif; ?>

            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= date_fr($r['depense_date']) ?></td>
                <td><?= htmlspecialchars($r['designation']) ?></td>
                <td style="text-align:right"><?= fcfa((int)$r['montant']) ?></td>
                <td style="text-align:center">
                  <?php if ($isAdmin): ?>
                    <a class="link"
                       href="edit_depense.php?id=<?= (int)$r['id'] ?>&back=<?= urlencode('index.php'.($q!==''?('?q='.urlencode($q)):'') ) ?>">
                      Modif
                    </a>
                    &nbsp;|&nbsp;
                    <a class="link" href="delete_depense.php?id=<?= (int)$r['id'] ?>&back=<?= urlencode('index.php'.($q!==''?('?q='.urlencode($q)):'') ) ?>" onclick="return confirm('Supprimer cette ligne ?')">Suppr</a>
                  <?php else: ?>
                    <span style="color:#888;">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="2" style="text-align:right">Fin journalier</th>
              <th style="text-align:right;color:#c00000;font-weight:800">
                <?= fcfa($total) ?>
              </th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

    </div>
  </div>
</div>

</body>
</html>
