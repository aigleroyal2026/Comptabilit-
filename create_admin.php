<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";

$today = date('Y-m-d');

// Ajouter dépense
$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date = $_POST["depense_date"] ?? $today;
    $designation = trim($_POST["designation"] ?? "");
    $montant = (int)($_POST["montant"] ?? 0);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = $today;

    if ($designation === "" || $montant <= 0) {
        $err = "Veuillez renseigner une désignation et un montant valide.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO depenses (user_id, depense_date, designation, montant) VALUES (?,?,?,?)");
        $stmt->execute([$_SESSION["user_id"], $date, $designation, $montant]);
        header("Location: index.php");
        exit;
    }
}

// Charger dépenses du jour
$stmt = $pdo->prepare("SELECT id, depense_date, designation, montant FROM depenses WHERE user_id = ? AND depense_date = ? ORDER BY id DESC");
$stmt->execute([$_SESSION["user_id"], $today]);
$rows = $stmt->fetchAll();

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
</head>
<body>
  <div class="container">
    <div class="topbar">
      <div>
        <div class="hello">Bonjour, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
        <div class="date">Aujourd’hui : <?= date('d/m/Y') ?></div>
      </div>
      <div class="top-actions">
        <a class="btn outline" href="rapport_jour.php?date=<?= urlencode($today) ?>" target="_blank">Imprimer (Word)</a>
        <a class="btn danger" href="logout.php">Déconnexion</a>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Nouvelle dépense</h2>

        <?php if ($err): ?>
          <div class="alert"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="post" class="form">
          <label>Date</label>
          <input type="date" name="depense_date" value="<?= htmlspecialchars($today) ?>" required>

          <label>Désignation</label>
          <input type="text" name="designation" placeholder="Ex: Mr Fernand / Dépôt au Boss ..." required>

          <label>Montant (FCFA)</label>
          <input type="number" name="montant" min="1" step="1" placeholder="Ex: 150000" required>

          <button class="btn" type="submit">Ajouter</button>
        </form>
      </div>

      <div class="card">
        <div class="card-head">
          <h2>Dépenses du jour</h2>
          <div class="total-box">
            Fin journalier :
            <span class="total"><?= fcfa($total) ?></span>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th style="width:18%">DATE</th>
                <th>DÉSIGNATION</th>
                <th style="width:22%; text-align:right">MONTANT</th>
                <th style="width:10%">#</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="4">Aucune dépense enregistrée aujourd’hui.</td></tr>
              <?php endif; ?>

              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['depense_date']))) ?></td>
                  <td><?= htmlspecialchars($r['designation']) ?></td>
                  <td style="text-align:right"><?= fcfa((int)$r['montant']) ?></td>
                  <td style="text-align:center">
                    <a class="link" href="delete_depense.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Supprimer cette ligne ?')">Suppr</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="2" style="text-align:right">Fin journalier</th>
                <th style="text-align:right; color:#c00000; font-weight:800"><?= fcfa($total) ?></th>
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
