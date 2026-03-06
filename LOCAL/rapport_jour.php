<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/helpers.php";

// Date demandée (YYYY-MM-DD)
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// ✅ Tout le bureau : récupérer toutes les dépenses du jour (sans user_id)
$stmt = $pdo->prepare("
  SELECT depense_date, designation, montant
  FROM depenses
  WHERE depense_date = ?
  ORDER BY id ASC
");
$stmt->execute([$date]);
$rows = $stmt->fetchAll();

// Total
$total = 0;
foreach ($rows as $r) $total += (int)$r['montant'];

function fcfa(int $n): string {
    return number_format($n, 0, ',', '.') . "F";
}

// Titres FR
$titreMois = "DEPENSE DU MOIS DE " . strtoupper(fr_month_name((int)date('m', strtotime($date)))) . " " . date('Y', strtotime($date));
$sousTitre = date_longue_fr($date);

// Date format tableau
$dateTable = date_fr($date);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Rapport dépenses - <?= htmlspecialchars($dateTable) ?></title>
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

    /* ✅ Boutons invisibles à l'impression */
    .no-print{ margin:12px 0; font-family: Arial, sans-serif; }
    @media print{ .no-print{ display:none; } }

    /* ✅ Contenu avec marges internes + réserve pour le footer */
    .page{
      padding: 12mm 14mm;
      padding-bottom: 32mm; /* réserve pied de page */
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
      margin: 0 auto;
    }

    .subtitle{
      text-align:center;
      margin: 18px 0 12px;
      font-weight:700;
      font-size:18px;
      text-decoration: underline;
    }

    table{
      width:100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 15px;
    }

    th, td{
      border: 1px solid #333;
      padding: 8px 10px;
      vertical-align: top;
    }

    th{
      text-align:center;
      font-weight:700;
    }

    td.date{ width:20%; white-space:nowrap; }
    td.designation{ width:60%; }
    td.montant{ width:20%; text-align:right; white-space:nowrap; }

    .tfoot-label{ text-align:center; font-weight:700; }
    .total{ color:#c00000; font-weight:800; text-align:right; }

    .muted{
      color:#333;
      font-family: Arial, sans-serif;
      font-size: 12px;
      margin-top: 10px;
    }

    tr, td, th{ page-break-inside: avoid; }

    /* ✅ Footer: caché à l’écran */
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
            <tr>
              <td colspan="3" style="text-align:center;">Aucune dépense enregistrée pour cette date.</td>
            </tr>
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
        — Date d’impression : <?= date('d/m/Y H:i') ?>
      </div>

    </div>
  </div>

  <div class="footer-print">
    SARL au Capital social de 1 000 000 FCFA, RCCM N°CI-TDI-2020-B-938, NCC : 2103740M<br>
    Siège social Yamoussoukro quartier Habitat, Tel.: +225 0708289898 / 0708368141<br>
    Compte Bancaire 079763302001 NSIA Agence Yamoussoukro – Email: samataci777@gmail.com
  </div>

</body>
</html>
