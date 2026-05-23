<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start($_GET['app'] ?? null);
include '../includes/config.php';

// ⚠️ vérifier id
if(!isset($_GET['id'])){
    die("ID manquant");
}
$id = intval($_GET['id']);

if ($_SESSION['role'] == 'ASSURE') {

    $id_user = $_SESSION['id_user'];

    $assure = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT a.id_assure 
        FROM assure a 
        JOIN utilisateur u ON a.id_personne=u.id_personne 
        WHERE u.id_user=$id_user 
        LIMIT 1
    "));

    $id_assure = $assure['id_assure'];

    $check = mysqli_query($conn,"
        SELECT id_contrat 
        FROM contrat 
        WHERE id_contrat = $id
        AND id_assure = $id_assure
    ");

    if (mysqli_num_rows($check) == 0) {
        die("Accès interdit");
    }
}
$id = intval($_GET['id']);

// 🔹 récupérer données
$res = mysqli_query($conn, "
SELECT 
    c.numero_police,
    c.date_effet,
    c.date_expiration,
    c.duree,
    c.net_a_payer,
    c.prime_base,
    c.reduction,
    c.majoration,
    c.prime_nette,
    c.complement,
    c.capital,
    c.statut,
    c.taxe,
c.timbre,
    c.date_creation,
  ag.nom_agence,
ag.code,
ag.wilaya,

    p.nom, p.prenom, p.telephone, p.adresse,
    a.num_permis,
a.date_delivrance_permis,
a.lieu_delivrance_permis,

    v.marque, v.modele, v.matricule,
    v.numero_chassis, v.numero_serie,
    v.couleur, v.nombre_places,
    v.annee, v.type, v.carrosserie

FROM contrat c
JOIN assure a ON c.id_assure = a.id_assure
JOIN personne p ON a.id_personne = p.id_personne
JOIN vehicule v ON c.id_vehicule = v.id_vehicule
JOIN agence ag ON c.id_agence = ag.id_agence
WHERE c.id_contrat = $id
");
$garanties = mysqli_query($conn, "
SELECT g.nom_garantie
FROM contrat_garantie cg
JOIN garantie g ON cg.id_garantie = g.id_garantie
WHERE cg.id_contrat = $id
");
$data = mysqli_fetch_assoc($res);

if(!$data){
    die("Contrat introuvable");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Contrat Assurance</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 15px;
            color: #333;
            font-size: 13px;
        }

        .container {
           width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    padding: 20px 24px;
        }

        .header-top {
            margin-bottom: 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
         
            font-size: 12px;
        }

        .logo {
         
            height: 60px;
            object-fit: contain;
             width: 85px;
        }

     .header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.header-left {
    display: flex;
    align-items: center;
}

.header-right {
    text-align: right;
}

.header-right h2 {

     color:#1f4d6d;
       font-size:15px;
   
    font-weight:500;
   
}

.logo {
    width: 95px;
    height: auto;
    object-fit: contain;
}

        .title-section {
         text-align: center;
    margin-bottom: 22px;
    border-bottom: 2px solid #333;
    padding-bottom: 14px;

    padding-top: 0;
margin-top: -10px;
        }

        .title-section h1 {
            font-size: 20px;
            font-weight: bold;
            color: #1f4d6d;
           padding-bottom: 14px;
            margin: 0 0 3px;
        }

        .title-section p {
            font-size: 20px;
            margin: 0;
            color: #555;
        }

        .section-header {
            font-size: 13px;
            font-weight: bold;
            color: #1f4d6d;
            border-bottom: 1px solid #333;
            padding: 8px 0;
            margin: 12px 0 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #999;
            padding: 6px 8px;
            text-align: left;
            font-size: 13px;
        }

        th {
            background: #e8e8e8;
            font-weight: bold;
            color: #1f4d6d;
        }

        td {
            background: white;
        }

        .contract-info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 12px;
        }

        .info-block {
            flex: 1;
        }

        .info-block h3 {
            font-size: 12px;
            font-weight: bold;
            color: #1f4d6d;
            margin: 10px 0 8px;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 8px;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .info-row-label {
            font-weight: bold;
            color: #555;
        }

        .info-row-value {
            color: #000;
        }

        .guarantee-table th {
            background: #e8e8e8;
        }

        .guarantee-table td {
            padding: 6px 6px;
            text-align: center;
        }

        .guarantee-table td:first-child {
            text-align: left;
        }

        .financial-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 18px 0;
            align-items: start;
        }

        .financial-left table {
            width: 100%;
        }

        .financial-left td:first-child {
            width: 60%;
            font-weight: 600;
            color: #555;
        }

        .financial-left td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .financial-right {
            text-align: center;
            border: 2px solid #333;
            padding: 16px;
            background: #f9f9f9;
        }

        .net-label {
            font-size: 12px;
            font-weight: bold;
            color: #1f4d6d;
            margin-bottom: 8px;
        }

        .net-amount {
            font-size: 28px;
            font-weight: bold;
            color: #1f4d6d;
            margin-bottom: 8px;
        }

        .total-reduction {
            font-size: 11px;
            color: #555;
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 18px;
            font-size: 12px;
        }

        .signature-block {
            border-top: 1px solid #999;
            padding-top: 50px;
            text-align: center;
        }

        .signature-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 4px;
        }

        .footer {
            text-align: right;
            margin-top: 20px;
            font-size: 11px;
            color: #666;
        }

        .btn-print {
            margin-top: 15px;
            padding: 10px 20px;
            background: #1f4d6d;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-print:hover {
            background: #163b4d;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 4mm;
            }

            html, body {
                width: 100%;
                min-height: 100%;
                margin: 0;
                padding: 0;
                color: #333;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
                zoom: 0.92;
            }

            .container {
                width: 100%;
                max-width: 187mm;
                padding: 4mm 5mm;
                margin: 0 auto;
                box-shadow: none;
                box-sizing: border-box;
            }

            .title-section {
                margin-bottom: 8px;
                padding-bottom: 6px;
            }

            .title-section h1 {
                font-size: 20px;
            }

            .title-section p {
                font-size: 12px;
            }

            .section-header {
                font-size: 12px;
                margin: 8px 0 4px;
                padding: 3px 0;
            }

            .info-row,
            .footer,
            .signature-label,
            .net-label,
            .net-amount,
            .total-reduction {
                font-size: 12px;
                line-height: 1.2;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 3px 5px;
            }

            .contract-info-row {
                gap: 8px;
            }

            .info-row {
                gap: 6px;
                margin-bottom: 2px;
            }

            .financial-section {
                gap: 10px;
                margin: 8px 0;
            }

            .financial-right {
                padding: 8px;
            }

            .signature-section {
                margin-top: 14px;
                margin-bottom: 18px;
                gap: 18px;
            }

            .signature-block {
                padding-top: 18px;
            }

            .footer {
                margin-top: 24px;
                padding-top: 8px;
                font-size: 11px;
            }

            .signature-section,
            .footer {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .btn-print {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <!-- Header -->
 <div class="header-top">

    <div class="header-left">
        <img class="logo" src="../images/logo.webp" alt="CNMA">
    </div>

    <div class="header-right">
        <h2><?= htmlspecialchars($data['nom_agence']) ?></h2>
    </div>

</div>
    <!-- Titre -->
    <div class="title-section">
        <h1>POLICE D'ASSURANCE</h1>
        <p>Automobile</p>
    </div>

    <!-- Informations du contrat -->
    <div class="section-header">Informations du contrat</div>
    <table>
        <tr>
            <td style="font-weight: bold; width: 25%;">N° Police</td>
            <td style="width: 25%;"><?= htmlspecialchars($data['numero_police']) ?></td>
            <td style="font-weight: bold; width: 25%;">Type</td>
            <td style="width: 25%;">Automobile particulière</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">N° Assuré</td>
            <td><?= htmlspecialchars($data['code']) ?>0052168</td>
            <td style="font-weight: bold;">Durée</td>
            <td><?= htmlspecialchars($data['duree']) ?> Mois</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Date d'effet</td>
            <td><?= date('d/m/Y', strtotime($data['date_effet'])) ?></td>
            <td style="font-weight: bold;">Expiration</td>
            <td><?= date('d/m/Y', strtotime($data['date_expiration'])) ?></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tarif</td>
            <td>4 roues – <?= htmlspecialchars($data['wilaya']) ?></td>
            <td style="font-weight: bold;">Catégorie</td>
            <td>Véhicule de Tourisme</td>
        </tr>
    </table>

    <!-- Deux blocs côte à côte -->
    <div class="contract-info-row">
        <div class="info-block">
            <h3>Informations de l'assuré</h3>
            <div class="info-row">
                <div class="info-row-label">Nom / Société</div>
                <div class="info-row-value"><?= htmlspecialchars($data['nom']) ?> <?= htmlspecialchars($data['prenom']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">Adresse</div>
                <div class="info-row-value"><?= htmlspecialchars($data['adresse']) ?></div>
            </div>
          <div class="info-row">
    <div class="info-row-label">Permis N°</div>
    <div class="info-row-value">
        <?= htmlspecialchars($data['num_permis']) ?>
    </div>
</div>
            <div class="info-row">
                <div class="info-row-label">Délivré le</div>
                <div class="info-row-value"><?= htmlspecialchars($data['date_delivrance_permis']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">Lieu</div>
                <div class="info-row-value"><?= htmlspecialchars($data['lieu_delivrance_permis']) ?></div>
            </div>
        </div>

        <div class="info-block">
            <h3>Identification du risque</h3>
            <div class="info-row">
                <div class="info-row-label">Marque</div>
                <div class="info-row-value"><?= htmlspecialchars($data['marque']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">Nb de places</div>
                <div class="info-row-value"><?= htmlspecialchars($data['nombre_places']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">N° Série dans le type</div>
                <div class="info-row-value"><?= htmlspecialchars($data['numero_serie']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">Carrosserie</div>
                <div class="info-row-value"><?= htmlspecialchars($data['carrosserie']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">Matricule</div>
                <div class="info-row-value"><?= htmlspecialchars($data['matricule']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">Type</div>
                <div class="info-row-value"><?= htmlspecialchars($data['type']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-row-label">Année</div>
                <div class="info-row-value"><?= htmlspecialchars($data['annee']) ?></div>
            </div>
        </div>
    </div>

   
    <!-- Garanties couvertes -->
<div class="section-header">Garanties couvertes</div>

<table class="guarantee-table">
    <tr>
        <th>Garantie</th>
        <th>Capital assuré</th>
        <th>Réduction</th>
        <th>Majoration</th>
    </tr>

    <?php
    mysqli_data_seek($garanties, 0);

    while ($g = mysqli_fetch_assoc($garanties)) {
    ?>
        <tr>
            <td><?= htmlspecialchars($g['nom_garantie']) ?></td>

          <td>
   <?= number_format($data['capital'], 0, ',', ' ') ?> DA
</td>

            <td>
                <?= !empty($g['reduction'])
                    ? number_format($g['reduction'], 2, ',', ' ') . ' %'
                    : '0 DA'
                ?>
            </td>

            <td>
                <?= !empty($g['majoration'])
                    ? number_format($g['majoration'], 2, ',', ' ') . ' %'
                    : '0 DA'
                ?>
            </td>
        </tr>
    <?php } ?>
</table>

    <!-- Détail financier -->
    <div class="section-header">Détail financier</div>
    <div class="financial-section">
        <div class="financial-left">
            <table style="border: none;">
                <tr style="border: none;">
                    <td style="border: none; font-weight: bold; padding: 4px 0;">Désignation</td>
                    <td style="border: none; font-weight: bold; text-align: right; padding: 4px 0;">Montant</td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; padding: 2px 0;">Prime nette</td>
                    <td style="border: none; text-align: right; padding: 2px 0;"><?= number_format($data['prime_nette'], 2, ',', ' ') ?></td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; padding: 2px 0;">Complément</td>
                    <td style="border: none; text-align: right; padding: 2px 0;"><?= number_format($data['complement'], 2, ',', ' ') ?></td>
                </tr>
                <tr style="border: none;">
                    <td style="border: none; padding: 2px 0;">TVA</td>
                    <td style="border: none; text-align: right; padding: 2px 0;">  <?= number_format($data['prime_nette'] * $data['taxe'], 2, ',', ' ') ?></td>
                </tr>
                <tr>
    <td style="border: none; padding: 2px 0;">Timbre</td>
    <td style="border: none; text-align: right; padding: 2px 0;"><?= number_format($data['timbre'], 2, ',', ' ') ?></td>
</tr>
            
             
            </table>
        </div>

        <div class="financial-right">
            <div class="net-label">NET À PAYER</div>
            <div class="net-amount"><?= number_format($data['net_a_payer'], 2, ',', ' ') ?> DA</div>
            <div class="total-reduction">
                Total Réduction<br>
                <?= number_format($data['reduction'], 2, ',', ' ') ?> DA
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-block">
            <div class="signature-label">Signature de l'assuré (Lu et approuvé)</div>
        </div>
        <div class="signature-block">
            <div class="signature-label">Cachet & Signature de l'assureur</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Contrat établi le : <?= date('d/m/Y', strtotime($data['date_creation'])) ?>
    </div>

    <button class="btn-print" onclick="window.print()">Imprimer le contrat</button>
</div>

</body>
</html>
