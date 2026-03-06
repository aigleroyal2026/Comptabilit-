<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/helpers.php";

$mois = $_GET['mois'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mois)) $mois = date('Y-m');

[$year, $month] = array_map('intval', explode('-', $mois));

$start = sprintf('%04d-%02d-01', $year, $month);
$endDt = DateTime::createFromFormat('Y-m-d', $start);
$endDt->modify('first day of next month');
$end = $endDt->format('Y-m-d');

$stmt = $pdo->prepare("
  SELECT depense_date, designation, montant
  FROM depenses
  WHERE depense_date >= ? AND depense_date < ?
  ORDER BY depense_date ASC, id ASC
");
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll();

function fcfa(int $n): string { return number_format($n, 0, ',', '.') . "F"; }

$byDate = [];
foreach ($rows as $r) {
    $d = $r['depense_date'];
    if (!isset($byDate[$d])) $byDate[$d] = [];
    $byDate[$d][] = $r;
}

$titreMois = "DEPENSE DU MOIS DE " . strtoupper(fr_month_name($month)) . " " . $year;

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
    @page { 
      size: A4; 
      margin: 15mm 18mm 15mm 18mm; /* ✅ Marges normales */
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body{ 
      font-family: "Times New Roman", Times, serif; 
      color: #111; 
      background: #fff;
    }

    .no-print{ 
      margin: 12px; 
      font-family: Arial, sans-serif; 
    }
    
    @media print{ 
      .no-print{ 
        display: none; 
      } 
    }

    .content-wrapper {
      padding: 0;
      margin-bottom: 60mm; /* ✅ CRUCIAL: Marge en bas pour le footer */
    }

    .title-box{
      border: 1.5px solid #333;
      padding: 10px 14px;
      text-align: center;
      font-weight: 700;
      font-size: 18px;
      letter-spacing: .3px;
      text-transform: uppercase;
      width: 78%;
      margin: 10px auto 15px;
    }

    .day-block{ 
      margin-top: 20px; 
      page-break-inside: avoid; 
    }

    .day-block:first-of-type {
      margin-top: 10px;
    }

    .subtitle{
      text-align: center;
      margin: 10px 0 8px;
      font-weight: 700;
      font-size: 17px;
      text-decoration: underline;
    }

    table{ 
      width: 100%; 
      border-collapse: collapse; 
      font-size: 15px; 
      margin-top: 8px;
      page-break-inside: auto;
    }
    
    thead {
      display: table-header-group;
    }

    tbody {
      display: table-row-group;
    }
    
    th, td{ 
      border: 0.5px solid #333; 
      padding: 8px 10px; 
      vertical-align: top; 
    }
    
    th{ 
      text-align: center; 
      font-weight: 700;
      background: #f5f5f5;
    }
    
    td.date{ 
      width: 20%; 
      white-space: nowrap; 
    }
    
    td.designation{ 
      width: 60%; 
    }
    
    td.montant{ 
      width: 20%; 
      text-align: right; 
      white-space: nowrap; 
    }

    .tfoot-label{ 
      text-align: center; 
      font-weight: 700; 
    }
    
    .total-day{ 
      color: #c00000; 
      font-weight: 800; 
      text-align: right; 
    }

    .total-month-box{
      margin: 25px 0;
      border: 1.5px solid #333;
      padding: 12px 14px;
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      page-break-inside: avoid;
    }
    
    .total-month-box .label{ 
      font-weight: 800; 
      text-transform: uppercase; 
    }
    
    .total-month-box .value{ 
      font-weight: 900; 
      color: #c00000; 
      font-size: 16px; 
    }

    .muted{ 
      color: #333; 
      font-family: Arial, sans-serif; 
      font-size: 12px; 
      margin: 15px 0 30px 0;
    }

    tr { 
      page-break-inside: avoid; 
    }

    /* ✅ Footer - Approche STATIC en fin de document */
    .footer-print{ 
      margin-top: 40mm;
      padding-top: 8px;
      border-top: 1.5px solid #333;
      text-align: center;
      font-family: Arial, sans-serif;
      font-size: 9px;
      line-height: 1.4;
      color: #000;
      page-break-inside: avoid;
    }

    @media print {
      * { 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact; 
      }

      body {
        margin: 0;
        padding: 0;
      }

      .content-wrapper {
        margin-bottom: 0; /* Reset pour l'impression */
      }

      table{ 
        border: 1px solid #000 !important; 
      }
      
      th, td{ 
        border: 0.5px solid #000 !important; 
      }

      th {
        background: #f5f5f5 !important;
      }

      /* ✅ Footer en tant qu'élément de flux normal, pas fixed */
      .footer-print {
        display: block;
        margin-top: 30mm; /* Espace avant le footer */
        padding-top: 8px;
        border-top: 1.5px solid #333;
        page-break-inside: avoid;
        page-break-before: avoid; /* Évite qu'il soit seul sur une page */
      }
    }

    @media screen {
      .content-wrapper {
        min-height: calc(100vh - 150px);
        margin-bottom: 100px;
      }

      .footer-print {
        margin-top: 40px;
      }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()" style="padding:10px 20px; background:#111; color:#fff; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Imprimer</button>
  <a href="index.php" style="margin-left:10px; padding:10px 20px; background:#fff; color:#111; border:1px solid #333; border-radius:5px; text-decoration:none; display:inline-block;">Retour</a>
</div>

<div class="content-wrapper">
  <div class="title-box"><?= htmlspecialchars($titreMois) ?></div>

  <?php if (!$byDate): ?>
    <div style="text-align:center; margin-top: 40px; font-size: 16px;">
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
      — Date d'impression : <?= date('d/m/Y H:i') ?>
    </div>

  <?php endif; ?>
</div>

<!-- ✅ Footer dans le flux normal du document -->
<div class="footer-print">
  SAMATA-CI SARL au Capital social de 1 000 000 FCFA, RCCM N°CI-TDI-2020-B-938, NCC : 2103740M<br>
  Siège social Yamoussoukro quartier Habitat, Tel.: +225 0708289898 / 0708368141<br>
  Compte Bancaire 079763302001 NSIA Agence Yamoussoukro — Email: samataci777@gmail.com
</div>

</body>
</html>