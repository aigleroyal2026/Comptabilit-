<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/helpers.php";

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
if (!$isAdmin) {
    http_response_code(403);
    die("Accès refusé.");
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$err = "";

// Charger la dépense (admin => pas de filtre user_id)
$stmt = $pdo->prepare("SELECT id, depense_date, designation, montant FROM depenses WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$dep = $stmt->fetch();

if (!$dep) {
    die("Dépense introuvable.");
}

// Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date = $_POST["depense_date"] ?? $dep["depense_date"];
    $designation = trim($_POST["designation"] ?? "");
    $montant = (int)($_POST["montant"] ?? 0);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $err = "Date invalide.";
    } elseif ($designation === "" || $montant <= 0) {
        $err = "Veuillez renseigner une désignation et un montant valide.";
    } else {
        $up = $pdo->prepare("UPDATE depenses SET depense_date = ?, designation = ?, montant = ? WHERE id = ? LIMIT 1");
        $up->execute([$date, $designation, $montant, $id]);

        $back = $_POST['back'] ?? 'index.php';
        header("Location: " . $back);
        exit;
    }
}

// page retour
$back = $_GET['back'] ?? 'index.php';

function fcfa(int $n): string {
    return number_format($n, 0, ',', '.') . " F";
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Modifier dépense</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .box{max-width:520px;margin:0 auto}
    .mini{font-size:12px;color:#666}
  </style>
</head>
<body>
<div class="container">
  <div class="topbar">
    <div>
      <div class="hello"><strong>Modifier une dépense</strong></div>
      <div class="mini">Date actuelle : <?= htmlspecialchars(date_fr($dep['depense_date'])) ?> — ID : #<?= (int)$dep['id'] ?></div>
    </div>
    <div class="top-actions">
      <a class="btn outline" href="<?= htmlspecialchars($back) ?>">Retour</a>
      <a class="btn danger" href="logout.php">Déconnexion</a>
    </div>
  </div>

  <div class="card box">
    <?php if ($err): ?>
      <div class="alert"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <input type="hidden" name="back" value="<?= htmlspecialchars($back) ?>">

      <label>Date</label>
      <input type="date" name="depense_date" value="<?= htmlspecialchars($dep['depense_date']) ?>" required>

      <label>Désignation</label>
      <input type="text" name="designation" value="<?= htmlspecialchars($dep['designation']) ?>" required>

      <label>Montant (FCFA)</label>
      <input type="number" name="montant" min="1" step="1" value="<?= (int)$dep['montant'] ?>" required>

      <button class="btn" type="submit">Enregistrer</button>
    </form>

    <div class="mini" style="margin-top:10px;">
      Montant actuel : <strong><?= htmlspecialchars(fcfa((int)$dep['montant'])) ?></strong>
    </div>
  </div>
</div>
</body>
</html>
