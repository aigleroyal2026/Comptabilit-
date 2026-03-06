<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/helpers.php";

$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$stmt = $pdo->prepare("
  SELECT depense_date, designation, montant
  FROM depenses
  WHERE depense_date = ?
  ORDER BY id ASC
");
$stmt->execute([$date]);
$rows = $stmt->fetchAll();

$total = 0;
foreach ($rows as $r) $total += (int)$r['montant'];

function fcfa(int $n): string { return number_format($n, 0, ',', '.') . "F"; }

$titreMois = "DEPENSE DU MOIS DE " . strtoupper(fr_month_name((int)date('m', strtotime($date)))) . " " . date('Y', strtotime($date));
$sousTitre = date_longue_fr($date);
$dateTable = date_fr($date);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Rapport dépenses - <?= htmlspecialchars($dateTable) ?></title>
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

    .subtitle{
      text-align: center;
      margin: 18px 0 12px;
      font-weight: 700;
      font-size: 18px;
      text-decoration: underline;
    }

    table{ 
      width: 100%; 
      border-collapse: collapse; 
      margin-top: 10px; 
      font-size: 15px;
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
    
    .total{ 
      color: #c00000; 
      font-weight: 800; 
      text-align: right; 
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

      /* ✅ Footer en tant qu'élément de flux normal */
      .footer-print {
        display: block;
        margin-top: 30mm;
        padding-top: 8px;
        border-top: 1.5px solid #333;
        page-break-inside: avoid;
        page-break-before: avoid;
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
  <div class="subtitle"><?= htmlspecialchars($sousTitre) ?></div>

  <table>
    <thead>
      <tr>
        <th>DATE</th>
        <th>DESIGNATION</th>
        <th>MONTANT</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="3" style="text-align:center;">Aucune dépense enregistrée pour cette date.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="date"><?= htmlspecialchars($dateTable) ?></td>
            <td class="designation"><?= htmlspecialchars($r['designation']) ?></td>
            <td class="montant"><?= htmlspecialchars(fcfa((int)$r['montant'])) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2" class="tfoot-label">Fin journalier</td>
        <td class="total"><?= htmlspecialchars(fcfa($total)) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="muted">
    Établi par : <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Utilisateur') ?></strong>
    — Date d'impression : <?= date('d/m/Y H:i') ?>
  </div>
</div>

<!-- ✅ Footer dans le flux normal du document -->
<div class="footer-print">
  SAMATA-CI SARL au Capital social de 1 000 000 FCFA, RCCM N°CI-TDI-2020-B-938, NCC : 2103740M<br>
  Siège social Yamoussoukro quartier Habitat, Tel.: +225 0708289898 / 0708368141<br>
  Compte Bancaire 079763302001 NSIA Agence Yamoussoukro — Email: samataci777@gmail.com
</div>

</body>
</html>