<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/helpers.php";

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

function fcfa(int $n): string {
    return number_format($n, 0, ',', '.') . " F";
}

$today = date('Y-m-d');

// Filtres
$mode = $_GET['mode'] ?? 'range'; // range | month
$q    = trim($_GET['q'] ?? '');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? $today;

$mois = $_GET['mois'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to = $today;
if (!preg_match('/^\d{4}-\d{2}$/', $mois))       $mois = date('Y-m');

// Base query : tout le bureau
$params = [];
$where  = " WHERE 1=1 ";
$title  = "";

if ($mode === 'month') {
    // Mois => [start, end)
    [$y, $m] = array_map('intval', explode('-', $mois));
    $start = sprintf('%04d-%02d-01', $y, $m);
    $endDt = DateTime::createFromFormat('Y-m-d', $start);
    $endDt->modify('first day of next month');
    $end = $endDt->format('Y-m-d');

    $where .= " AND depense_date >= ? AND depense_date < ? ";
    $params[] = $start;
    $params[] = $end;

    $title = "Historique — Mois " . $mois;
} else {
    // Période inclusive: from..to
    $where .= " AND depense_date >= ? AND depense_date <= ? ";
    $params[] = $from;
    $params[] = $to;

    $title = "Historique — Du " . date_fr($from) . " au " . date_fr($to);
}

if ($q !== '') {
    $where .= " AND designation LIKE ? ";
    $params[] = "%" . $q . "%";
}

$sql = "SELECT id, depense_date, designation, montant
        FROM depenses
        $where
        ORDER BY depense_date DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$total = 0;
foreach ($rows as $r) $total += (int)$r['montant'];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Historique dépenses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .filters{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
    .filters .field{min-width:220px}
    .filters label{display:block;font-size:12px;color:#555;margin:0 0 6px}
    .filters input,.filters select{width:100%;padding:10px;border:1px solid #cfd5e3;border-radius:10px}
    .pill{display:inline-block;padding:6px 10px;border:1px solid #ddd;border-radius:999px;font-size:12px;color:#333;background:#fff}
    .right{text-align:right}
    .small{font-size:12px;color:#666}
  </style>
</head>
<body>
<div class="container">

  <div class="topbar">
    <div>
      <div class="hello"><strong><?= htmlspecialchars($title) ?></strong></div>
      <div class="small">
        Total période : <span style="color:#c00000;font-weight:900"><?= htmlspecialchars(fcfa($total)) ?></span>
      </div>
    </div>
    <div class="top-actions">
      <?php if ($isAdmin): ?>
        <a class="btn outline" href="admin.php">Admin</a>
      <?php endif; ?>
      <a class="btn outline" href="index.php">Retour</a>
      <a class="btn danger" href="logout.php">Déconnexion</a>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px;">
    <form method="get" class="filters">

      <div class="field">
        <label>Type de filtre</label>
        <select name="mode" onchange="this.form.submit()">
          <option value="range" <?= $mode==='range'?'selected':'' ?>>Période (du / au)</option>
          <option value="month" <?= $mode==='month'?'selected':'' ?>>Par mois</option>
        </select>
      </div>

      <?php if ($mode === 'month'): ?>
        <div class="field">
          <label>Mois</label>
          <input type="month" name="mois" value="<?= htmlspecialchars($mois) ?>">
        </div>
        <div class="field" style="min-width:260px">
          <label>Impression</label>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn outline" target="_blank" href="rapport_mois.php?mois=<?= urlencode($mois) ?>">Imprimer rapport mois</a>
          </div>
        </div>
      <?php else: ?>
        <div class="field">
          <label>Du</label>
          <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="field">
          <label>Au</label>
          <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>
        <div class="field" style="min-width:260px">
          <label>Impression</label>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn outline" target="_blank" href="rapport_jour.php?date=<?= urlencode($from) ?>">Imprimer jour (date du “Du”)</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="field" style="flex:1;min-width:260px">
        <label>Recherche (désignation)</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Ex: carburant, dépôt, papier...">
      </div>

      <div class="field" style="min-width:160px">
        <button class="btn" type="submit">Afficher</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
      <span class="pill"><?= count($rows) ?> ligne(s)</span>
      <span class="pill">Total : <strong style="color:#c00000"><?= htmlspecialchars(fcfa($total)) ?></strong></span>
    </div>

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
            <tr><td colspan="4">Aucune dépense trouvée pour ces filtres.</td></tr>
          <?php endif; ?>

          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars(date_fr($r['depense_date'])) ?></td>
              <td><?= htmlspecialchars($r['designation']) ?></td>
              <td class="right"><?= htmlspecialchars(fcfa((int)$r['montant'])) ?></td>
              <td style="text-align:center">
                <?php if ($isAdmin): ?>
                  <a class="link" href="edit_depense.php?id=<?= (int)$r['id'] ?>&back=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Modif</a>
                  &nbsp;|&nbsp;
                  <a class="link" href="delete_depense.php?id=<?= (int)$r['id'] ?>&back=<?= urlencode($_SERVER['REQUEST_URI']) ?>" onclick="return confirm('Supprimer cette ligne ?')">Suppr</a>
                <?php else: ?>
                  <span style="color:#888;">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2" style="text-align:right">TOTAL</th>
            <th class="right" style="color:#c00000;font-weight:900"><?= htmlspecialchars(fcfa($total)) ?></th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

</div>
</body>
</html>
