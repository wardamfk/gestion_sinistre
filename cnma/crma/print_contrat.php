<?php
session_start();
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

    p.nom, p.prenom, p.telephone, p.adresse,

    v.marque, v.modele, v.matricule,
    v.numero_chassis, v.numero_serie,
    v.couleur, v.nombre_places,
    v.annee, v.type, v.carrosserie

FROM contrat c
JOIN assure a ON c.id_assure = a.id_assure
JOIN personne p ON a.id_personne = p.id_personne
JOIN vehicule v ON c.id_vehicule = v.id_vehicule
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
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #2e7d32;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #2e7d32;
        }

        .section {
            margin-bottom: 20px;
        }

        .section h3 {
            margin-bottom: 10px;
            color: #333;
            border-left: 5px solid #2e7d32;
            padding-left: 10px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .label {
            font-weight: bold;
            color: #555;
        }

        .value {
            color: #000;
        }

        .footer {
            margin-top: 40px;
            text-align: right;
        }

        .btn-print {
            margin-top: 20px;
            padding: 10px 20px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-print:hover {
            background: #1b5e20;
        }

        @media print {
            .btn-print {
                display: none;
            }
            body {
                background: white;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <h1>CONTRAT D'ASSURANCE AUTOMOBILE</h1>
        <p> CRMA</p>
    </div>

    <!-- INFOS CONTRAT -->
    <div class="section">
        <h3>Informations du contrat</h3>

        <div class="row">
            <div class="label">Numéro de police</div>
            <div class="value"><?= $data['numero_police'] ?></div>
        </div>

        <div class="row">
            <div class="label">Montant</div>
            <div class="value"><?= number_format($data['net_a_payer'],2) ?> DA</div>
        </div>
        <div class="row">
    <div class="label">Date d'effet</div>
    <div class="value"><?= $data['date_effet'] ?></div>
</div>

<div class="row">
    <div class="label">Date expiration</div>
    <div class="value"><?= $data['date_expiration'] ?></div>
</div>
<div class="row">
    <div class="label">Durée</div>
    <div class="value">
        <?= floor((strtotime($data['date_expiration']) - strtotime($data['date_effet'])) / (60*60*24*30)) ?> mois
    </div>



    </div>

    <!-- ASSURE -->
    <div class="section">
        <h3>Informations de l'assuré</h3>

        <div class="row">
            <div class="label">Nom complet</div>
            <div class="value"><?= $data['nom'] ?> <?= $data['prenom'] ?></div>
        </div>
        <div class="row">
    <div class="label">Téléphone</div>
    <div class="value"><?= $data['telephone'] ?></div>
</div>

<div class="row">
    <div class="label">Adresse</div>
    <div class="value"><?= $data['adresse'] ?></div>
</div>
    </div>

    <!-- VEHICULE -->
    <div class="section">
        <h3>Informations du véhicule</h3>

        <div class="row">
            <div class="label">Marque</div>
            <div class="value"><?= $data['marque'] ?></div>
        </div>

        <div class="row">
            <div class="label">Modèle</div>
            <div class="value"><?= $data['modele'] ?></div>
        </div>
        <div class="row">
    <div class="label">Année</div>
    <div class="value"><?= $data['annee'] ?></div>
</div>

<div class="row">
    <div class="label">Type</div>
    <div class="value"><?= $data['type'] ?></div>
</div>
<div class="row">
    <div class="label">Couleur</div>
    <div class="value"><?= $data['couleur'] ?></div>
</div>
<div class="row">
    <div class="label">Châssis (VIN)</div>
    <div class="value"><?= $data['numero_chassis'] ?></div>
</div>

<div class="row">
    <div class="label">Numéro série</div>
    <div class="value"><?= $data['numero_serie'] ?></div>
</div>



<div class="row">
    <div class="label">Places</div>
    <div class="value"><?= $data['nombre_places'] ?></div>
</div>

        <div class="row">
            <div class="label">Matricule</div>
            <div class="value"><?= $data['matricule'] ?></div>
        </div>
    </div>
    <div class="section">
 
<h3>Garanties</h3>

<?php while($g = mysqli_fetch_assoc($garanties)) { ?>
    <div class="row">
        <div class="value">✔ <?= $g['nom_garantie'] ?></div>
    </div>
<?php } ?>

</div>

<div class="section">
<h3>Détail financier</h3>

<div class="row">
    <div class="label">Prime de base</div>
    <div class="value"><?= $data['prime_base'] ?> DA</div>
</div>

<div class="row">
    <div class="label">Réduction</div>
    <div class="value">- <?= $data['reduction'] ?> DA</div>
</div>

<div class="row">
    <div class="label">Majoration</div>
    <div class="value">+ <?= $data['majoration'] ?> DA</div>
</div>

<div class="row">
    <div class="label">Prime nette</div>
    <div class="value"><?= $data['prime_nette'] ?> DA</div>
</div>
<div class="row">
    <div class="label">Capital assuré</div>
    <div class="value"><?= number_format($data['capital'],2) ?> DA</div>
</div>
<div class="row">
    <div class="label"><b>Total à payer</b></div>
    <div class="value"><b><?= $data['net_a_payer'] ?> DA</b></div>
</div>


</div>
    <!-- SIGNATURE -->
    <div class="footer">
       <p>Fait à : ____________</p>
<p>Date : <?= date('Y-m-d') ?></p>
<br>
<p>Signature & Cachet</p>
    </div>

    <!-- PRINT BUTTON -->
    <button class="btn-print" onclick="window.print()">🖨️ Imprimer</button>

</div>

</body>
</html>