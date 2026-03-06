<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/helpers.php";

// Param mois: YYYY-MM (ex: 2026-01)
$mois = $_GET['mois'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mois)) {
    $mois = date('Y-m');
}

[$year, $month] = array_map('intval', explode('-', $mois));

$start = sprintf('%04d-%02d-01', $year, $month);
$endDt = DateTime::createFromFormat('Y-m-d', $start);
$endDt->modify('first day of next month');
$end = $endDt->format('Y-m-d');

// ✅ Tout le bureau : récupérer toutes les dépenses du mois (sans user_id)
$stmt = $pdo->prepare("
  SELECT depense_date, designation, montant
  FROM depenses
  WHERE depense_date >= ? AND depense_date < ?
  ORDER BY depense_date ASC, id ASC
");
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll();

function fcfa(int $n): string {
    return number_format($n, 0, ',', '.') . "F";
}

// Grouper par date
$byDate = [];
foreach ($rows as $r) {
    $d = $r['depense_date'];
    if (!isset($byDate[$d])) $byDate[$d] = [];
    $byDate[$d][] = $r;
}

// Titres
$moisLettre = fr_month_name($month);
$titreMois = "DEPENSE DU MOIS DE " . strtoupper($moisLettre) . " " . $year;

// Total mois
$totalMois = 0;
foreach ($rows as $r) $totalMois += (int)$r['montant'];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Rapport mensuel - <?= htmlspecialchars($mois) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    /* ✅ Marges imprimante */
    @page { size: A4; margin: 18mm; }

    body{
      font-family: "Times New Roman", Times, serif;
      color:#111;
      background:#fff;
      margin:0;
      padding:0;
    }

    .no-print{ margin:12px 0; font-family: Arial, sans-serif; }
    @media print{ .no-print{ display:none; } }

    /* ✅ Contenu + réserve pied de page */
    .page{
      padding: 12mm 14mm;
      padding-bottom: 32mm;
    }

    .wrap{ width:100%; }

    .title-box{
      border: 1.5px solid #333;
      padding: 10px 14px;
      text-align:center;
      font-weight:700;
      font-size:18px;
      letter-spacing:.3px;
      text-transform: uppercase;
      width: 78%;
      margin: 0 auto 10px;
    }

    .day-block{
      margin-top: 18px;
      page-break-inside: avoid;
    }

    .subtitle{
      text-align:center;
      margin: 10px 0 8px;
      font-weight:700;
      font-size:17px;
      text-decoration: underline;
    }

    table{
      width:100%;
      border-collapse: collapse;
      font-size: 15px;
      margin-top: 8px;
    }

    th, td{
      border: 1px solid #333;
      padding: 8px 10px;
      vertical-align: top;
    }

    th{ text-align:center; font-weight:700; }

    td.date{ width:20%; white-space:nowrap; }
    td.designation{ width:60%; }
    td.montant{ width:20%; text-align:right; white-space:nowrap; }

    .tfoot-label{ text-align:center; font-weight:700; }
    .total-day{ color:#c00000; font-weight:800; text-align:right; }

    .total-month-box{
      margin-top: 22px;
      border: 1.5px solid #333;
      padding: 12px 14px;
      font-family: Arial, sans-serif;
      display:flex;
      justify-content: space-between;
      align-items:center;
      gap: 10px;
      page-break-inside: avoid;
    }
    .total-month-box .label{ font-weight:800; text-transform: uppercase; }
    .total-month-box .value{ font-weight:900; color:#c00000; font-size:16px; }

    .muted{
      color:#333;
      font-family: Arial, sans-serif;
      font-size: 12px;
      margin-top: 10px;
    }

    tr, td, th{ page-break-inside: avoid; }

    /* Footer caché à l’écran */
    .footer-print{ display:none; }

    /* ✅ Impression : footer + bordures forcées */
    @media print{
      *{
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      table{
        border: 2px solid #000 !important;
        border-collapse: collapse !important;
      }
      th, td{
        border: 2px solid #000 !important;
      }

      .footer-print{
        display:block;
        position: fixed;
        bottom: 10mm;
        left: 18mm;
        right: 18mm;
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 10.5px;
        line-height: 1.35;
        color:#000;
        border-top: 1px solid #333;
        padding-top: 6px;
        background:#fff;
      }
    }
  </style>
</head>
<body>

  <div class="page">
    <div class="wrap">

      <div class="no-print">
        <button onclick="window.print()">Imprimer</button>
        <a href="index.php" style="margin-left:10px;">Retour</a>
      </div>

      <div class="title-box"><?= htmlspecialchars($titreMois) ?></div>

      <?php if (!$byDate): ?>
        <div style="text-align:center; margin-top: 20px;">
          Aucune dépense enregistrée pour ce mois.
        </div>
      <?php else: ?>

        <?php foreach ($byDate as $date => $items): ?>
          <?php
            $subtitle = date_longue_fr($date);
            $dateTable = date_fr($date);

            $totalJour = 0;
            foreach ($items as $it) $totalJour += (int)$it['montant'];
          ?>

          <div class="day-block">
            <div class="subtitle"><?= htmlspecialchars($subtitle) ?></div>

            <table>
              <thead>
                <tr>
                  <th>DATE</th>
                  <th>DESIGNATION</th>
                  <th>MONTANT</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td class="date"><?= htmlspecialchars($dateTable) ?></td>
                    <td class="designation"><?= htmlspecialchars($it['designation']) ?></td>
                    <td class="montant"><?= htmlspecialchars(fcfa((int)$it['montant'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="2" class="tfoot-label">Fin journalier</td>
                  <td class="total-day"><?= htmlspecialchars(fcfa($totalJour)) ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endforeach; ?>

        <div class="total-month-box">
          <div class="label">TOTAL GÉNÉRAL DU MOIS</div>
          <div class="value"><?= htmlspecialchars(fcfa($totalMois)) ?></div>
        </div>

        <div class="muted">
          Établi par : <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Utilisateur') ?></strong>
          — Date d’impression : <?= date('d/m/Y H:i') ?>
        </div>

      <?php endif; ?>

    </div>
  </div>

  <div class="footer-print">
    SARL au Capital social de 1 000 000 FCFA, RCCM N°CI-TDI-2020-B-938, NCC : 2103740M<br>
    Siège social Yamoussoukro quartier Habitat, Tel.: +225 0708289898 / 0708368141<br>
    Compte Bancaire 079763302001 NSIA Agence Yamoussoukro – Email: samataci777@gmail.com
  </div>

</body>
</html>
