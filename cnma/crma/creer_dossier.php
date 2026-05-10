<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
include('../includes/config.php');
// ===== AJAX GARANTIES =====
if(isset($_GET['ajax']) && $_GET['ajax'] == 'garanties'){

    $id_contrat = intval($_GET['id_contrat']);

    $res = mysqli_query($conn, "
        SELECT g.id_garantie, g.nom_garantie
        FROM contrat_garantie cg
        JOIN garantie g ON cg.id_garantie = g.id_garantie
        WHERE cg.id_contrat = $id_contrat
    ");

    $data = [];
    while($row = mysqli_fetch_assoc($res)){
        $data[] = $row;
    }

    echo json_encode($data);
    exit(); // IMPORTANT
}
if(isset($_GET['ajax']) && $_GET['ajax'] == 'contrats'){

    $id_assure = intval($_GET['id_assure']);

    $res = mysqli_query($conn, "
        SELECT c.id_contrat, c.numero_police, v.marque, v.modele, v.matricule
        FROM contrat c
        JOIN vehicule v ON c.id_vehicule = v.id_vehicule
        WHERE c.id_assure = $id_assure
    ");

    $data = [];

    while($row = mysqli_fetch_assoc($res)){
        $data[] = $row;
    }

    echo json_encode($data);
    exit();
}
// Récupérer contrat depuis GET
if(isset($_GET['id_contrat'])){
    $id_contrat = $_GET['id_contrat'];
} else {
    $id_contrat = "";
}

if(isset($_POST['creer'])){

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $id_contrat = $_POST['id_contrat'];
    $id_tiers = $_POST['id_tiers'];
    $date_sinistre = $_POST['date_sinistre'];
    $date_declaration = $_POST['date_declaration'];
    $lieu = $_POST['lieu'];
    $description = $_POST['description'];
    $info = $_POST['info'];
    $id_expert = $_POST['id_expert'];
    $responsable = $_POST['responsable'];

    $cree_par = $_SESSION['id_user'];
    $date_creation = date('Y-m-d');
  
$statut_validation = 'non_soumis';

    // Calcul délai déclaration
    $d1 = new DateTime($date_sinistre);
    $d2 = new DateTime($date_declaration);
    $delai = $d1->diff($d2)->days;
// 🔥 Refus automatique si délai > 5 jours
if($delai > 5){

    // Refus automatique : délai dépassé
    $id_etat = 21;

} else {

    // En cours CRMA
    $id_etat = 2;
}
    // Générer numéro dossier
  $annee = date('Y');

$cree_par = $_SESSION['id_user'];

/* récupérer code agence */

$res_agence = mysqli_query($conn, "
    SELECT a.code
    FROM utilisateur u
    INNER JOIN agence a
        ON u.id_agence = a.id_agence
    WHERE u.id_user = '$cree_par'
");

$data_agence = mysqli_fetch_assoc($res_agence);

$code_agence = strtoupper($data_agence['code']);

/* récupérer dernier numéro dossier agence */

$res_num = mysqli_query($conn, "
    SELECT MAX(
        CAST(SUBSTRING_INDEX(numero_dossier, '-', -1) AS UNSIGNED)
    ) AS maxnum

    FROM dossier

    WHERE numero_dossier LIKE 'DOS-$code_agence-$annee-%'
");

$row_num = mysqli_fetch_assoc($res_num);

$next = ($row_num['maxnum'] ?? 0) + 1;

/* génération numéro dossier */

$numero_dossier = 'DOS-'
                 . $code_agence
                 . '-'
                 . $annee
                 . '-'
                 . str_pad($next, 4, '0', STR_PAD_LEFT);

    // INSERT DOSSIER AVEC EXPERT
    $sql = "INSERT INTO dossier 
    (numero_dossier, date_creation, cree_par, id_etat, id_contrat, id_tiers, date_sinistre, lieu_sinistre, info_complementaire, description, delai_declaration, id_expert, statut_validation)
    VALUES 
    ('$numero_dossier', '$date_creation', '$cree_par', '$id_etat', '$id_contrat', '$id_tiers', '$date_sinistre', '$lieu', '$info', '$description', '$delai', '$id_expert', '$statut_validation')";

    mysqli_query($conn, $sql);

    $id_dossier = mysqli_insert_id($conn);
    $reserves = $_POST['reserve'] ?? [];

if($delai <= 5){

    foreach($reserves as $id_garantie => $montant){

        if($montant > 0){

            mysqli_query($conn, "
                INSERT INTO reserve
                (id_dossier, id_garantie, montant, date_reserve)
                VALUES
                ('$id_dossier', '$id_garantie', '$montant', NOW())
            ");
        }
    }

}
    // =======================
// UPLOAD DOCUMENTS
// =======================
$documents = [
    "constat" => 1,
    "pv" => 2,
    "photos" => 3,
    "carte_grise" => 4,
    "permis" => 5,
    "devis" => 6
];

foreach($documents as $input => $id_type){
    if(isset($_FILES[$input]) && $_FILES[$input]['name'] != ""){
        
        $nom_fichier = $_FILES[$input]['name'];
        $tmp = $_FILES[$input]['tmp_name'];
        $chemin = "../uploads/" . $nom_fichier;

        move_uploaded_file($tmp, $chemin);

        mysqli_query($conn, "INSERT INTO document
        (id_dossier, nom_fichier, date_upload, upload_par, id_type_document)
        VALUES
        ('$id_dossier', '$nom_fichier', NOW(), '$cree_par', '$id_type')");
    }
}

    // RESPONSABILITE TIERS
    mysqli_query($conn, "UPDATE tiers SET responsable='$responsable' WHERE id_tiers='$id_tiers'");

   // HISTORIQUE CREATION
mysqli_query($conn, "INSERT INTO historique
(id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
VALUES
('$id_dossier', 'Création dossier', NOW(), '$cree_par', NULL, 2)");
// 🔥 Refus automatique si hors délai
if($delai > 5){

    mysqli_query($conn, "
    INSERT INTO historique
    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
    VALUES
    (
        '$id_dossier',
        'Refus automatique : déclaration hors délai réglementaire',
        NOW(),
        '$cree_par',
        NULL,
        '21'
    )
    ");
    // 🔥 Refus automatique si hors délai
if($delai > 5){

    mysqli_query($conn, "
    INSERT INTO historique
    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
    VALUES
    (
        '$id_dossier',
        'Refus automatique : déclaration hors délai réglementaire',
        NOW(),
        '$cree_par',
        NULL,
        '21'
    )
    ");
}
// 🔥 Notification assuré hors délai

$assureInfo = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT u.id_user AS assure_user_id
    FROM contrat c
    JOIN assure a ON c.id_assure = a.id_assure
    JOIN utilisateur u 
        ON u.id_personne = a.id_personne
        AND u.role = 'ASSURE'
    WHERE c.id_contrat = '$id_contrat'
    LIMIT 1
"));

if($assureInfo && !empty($assureInfo['assure_user_id'])){

    $msg = mysqli_real_escape_string(
        $conn,
        "Votre dossier n’a pas pu être pris en charge car le délai de déclaration autorisé a été dépassé."
    );

    mysqli_query($conn, "
        INSERT INTO notification
        (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES
        (
            '$id_dossier',
            '$cree_par',
            '{$assureInfo['assure_user_id']}',
            'refus',
            '$msg'
        )
    ");
}
}

// SI EXPERT AFFECTÉ → PASSER EN EXPERTISE
if($id_expert != "" && $delai <= 5){
    
    // Ancien état = 2
    $ancien_etat = 2;
    $nouvel_etat = 9;

    // Update état dossier
    mysqli_query($conn, "UPDATE dossier 
                         SET id_etat = 9 
                         WHERE id_dossier = '$id_dossier'");

    // Historique affectation expert
    mysqli_query($conn, "INSERT INTO historique
    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
    VALUES
    ('$id_dossier', 'Affectation expert', NOW(), '$cree_par', '$ancien_etat', '$nouvel_etat')");
}

    header("Location: mes_dossiers.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Créer dossier</title>
   <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <link rel="stylesheet" href="../css/style_crma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="crma-main">
    <h2>Créer un dossier sinistre</h2>

<form method="POST" enctype="multipart/form-data">

<div class="form-grid-main">

    <!-- LEFT -->
    <div class="crma-card">

        <h3>Informations sinistre</h3>
<div class="form-group">
    <label>Assuré</label>
<select id="assure_select"
        name="id_assure"
        placeholder="Rechercher un assuré...">
        <?php
 $res = mysqli_query($conn, "
    SELECT 
        a.id_assure,
        p.nom,
        p.prenom,
        p.raison_sociale,
        p.type_personne
    FROM assure a
    JOIN personne p ON a.id_personne = p.id_personne
");

while($row = mysqli_fetch_assoc($res)){

    if($row['type_personne'] == 'morale'){

        $nom_affiche = $row['raison_sociale'];

    } else {

        $nom_affiche = $row['nom'].' '.$row['prenom'];
    }

    echo "<option value='".$row['id_assure']."'>"
    .htmlspecialchars($nom_affiche).
    "</option>";
}
        ?>
    </select>
</div>
        <div class="form-group">
            <label>Contrat</label>
            <select name="id_contrat" id="contrat_select" required>
                <option value="">-- Sélectionner contrat --</option>
                <?php
                
                while($row = mysqli_fetch_assoc($res)){
                    $selected = ($id_contrat == $row['id_contrat']) ? "selected" : "";
                    echo "<option value='".$row['id_contrat']."' $selected>".$row['numero_police']."</option>";
                }
                ?>
            </select>
        </div>

        <div id="garanties_box"></div>

        <div class="form-group">
            
            <label>Tiers</label>
            
            <div style="display:flex; gap:10px; align-items:center;">
    
    <select id="tiers_select"
        name="id_tiers"
        required
        style="flex:1;">

        <option value="">-- rechercher tiers --</option>
        <?php
        $res = mysqli_query($conn, "
            SELECT t.id_tiers, p.nom, p.prenom, t.compagnie_assurance
            FROM tiers t 
            JOIN personne p ON t.id_personne = p.id_personne
        ");

        while($row = mysqli_fetch_assoc($res)){
            echo "<option value='".$row['id_tiers']."'>"
            .$row['nom']." ".$row['prenom']." - ".$row['compagnie_assurance'].
            "</option>";
        }
        ?>
    </select>

    <a href="gerer_tiers.php" class="btn btn-primary" style="white-space:nowrap;">
        + Ajouter
    </a>

</div>

    </div>    

        <div class="form-grid-3">
            <div class="form-group">
                <label>Date sinistre</label>
                <input type="date" name="date_sinistre" required>
            </div>

            <div class="form-group">
                <label>Date déclaration</label>
                <input type="date" name="date_declaration" required>
            </div>

            <div class="form-group">
                <label>Lieu sinistre</label>
                <input type="text" name="lieu" required>
            </div>
        </div>
        <div id="alert-delai" 
     style="
        display:none;
        margin-top:15px;
        padding:14px;
        border-radius:10px;
        background:#fff3cd;
        border:1px solid #ffe69c;
        color:#856404;
        font-weight:600;
     ">
     
    <i class="fa fa-triangle-exclamation"></i>
    Déclaration hors délai réglementaire (> 5 jours).<br>
    Le dossier sera automatiquement refusé et aucune réserve ne sera créée.

</div>

        <div class="form-group">
            <label>Responsabilité</label>
            <select name="responsable">
                <option value="oui">Responsable</option>
                <option value="non">Non responsable</option>
                <option value="partiel">Responsabilité partielle</option>
            </select>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" required></textarea>
        </div>

        <div class="form-group">
            <label>Informations complémentaires</label>
            <textarea name="info"></textarea>
        </div>

        <!-- DOCUMENTS -->
        <h3>Documents</h3>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Constat</label>
                <input type="file" name="constat">
            </div>

            <div class="form-group">
                <label>Photos</label>
                <input type="file" name="photos">
            </div>


            <div class="form-group">
                <label>Carte grise</label>
                <input type="file" name="carte_grise">
            </div>

            <div class="form-group">
                <label>Permis</label>
                <input type="file" name="permis">
            </div>

        
            <div class="form-group">
                <label>PV Police</label>
                <input type="file" name="pv">
            </div>
        </div>

    </div>

    <!-- RIGHT -->
    <div class="crma-card">

        <h3>Expertise</h3>

        <div class="form-group">
            <label>Expert</label>
        <select id="expert_select"
        name="id_expert"
        required>

    <option value="">-- Rechercher expert --</option>
                <?php
                $res = mysqli_query($conn, "SELECT id_expert, nom, prenom FROM expert");
                while($row = mysqli_fetch_assoc($res)){
                    echo "<option value='".$row['id_expert']."'>".$row['nom']." ".$row['prenom']."</option>";
                }
                ?>
            </select>
        </div>

        <button type="submit" name="creer" class="btn btn-success" style="width:100%; margin-top:10px;">
            Créer dossier
        </button>

    </div>

</div>

</form>
</div>
<script>
    document.getElementById('assure_select').addEventListener('change', function(){

    let id = this.value;

    let select = document.getElementById('contrat_select');

    if(!id){
        select.innerHTML = '<option value="">-- Sélectionner contrat --</option>';
        return;
    }

    fetch('creer_dossier.php?ajax=contrats&id_assure=' + id)
    .then(res => res.json())
    .then(data => {

        select.innerHTML = '<option value="">-- Sélectionner contrat --</option>';

        data.forEach(c => {
            select.innerHTML += `
                <option value="${c.id_contrat}">
                    ${c.numero_police} — ${c.marque} ${c.modele} (${c.matricule})
                </option>
            `;
        });

    });

});
document.getElementById('contrat_select').addEventListener('change', function() {

    let id = this.value;

    if (!id) {
        document.getElementById('garanties_box').innerHTML = '';
        return;
    }

    fetch('creer_dossier.php?ajax=garanties&id_contrat=' + id)
    .then(res => res.json())
    .then(data => {

        let html = `<h4 style="margin:20px 0 10px; font-weight:600;">Garanties du contrat</h4>`;

        if (data.length === 0) {
            html += "<p style='color:red'>Aucune garantie trouvée</p>";
        }

        data.forEach(g => {
            html += `
            <div class="garantie-card">
                <div class="garantie-nom">${g.nom_garantie}</div>
                <div class="garantie-input">
                    <input type="number" name="reserve[${g.id_garantie}]" placeholder="0 DA">
                </div>
            </div>
            `;
        });

        document.getElementById('garanties_box').innerHTML = html;
    })
    .catch(err => {
        console.error(err);
        document.getElementById('garanties_box').innerHTML = "<p style='color:red'>Erreur chargement</p>";
    });

});
const dateSinistre = document.querySelector('[name="date_sinistre"]');
const dateDeclaration = document.querySelector('[name="date_declaration"]');
const alertDelai = document.getElementById('alert-delai');
new TomSelect("#tiers_select",{
    create:false,
    sortField:{
        field:"text",
        direction:"asc"
    }
});

function verifierDelai(){

    if(!dateSinistre.value || !dateDeclaration.value){
        alertDelai.style.display = 'none';
        return;
    }

    const d1 = new Date(dateSinistre.value);
    const d2 = new Date(dateDeclaration.value);

    const diff = Math.floor(
        (d2 - d1) / (1000 * 60 * 60 * 24)
    );

    if(diff > 5){

        alertDelai.style.display = 'block';

    } else {

        alertDelai.style.display = 'none';
    }
}

dateSinistre.addEventListener('change', verifierDelai);
dateDeclaration.addEventListener('change', verifierDelai);
new TomSelect("#assure_select",{
    create:false,
    sortField:{
        field:"text",
        direction:"asc"
    }
});
new TomSelect("#expert_select",{
    create:false,
    sortField:{
        field:"text",
        direction:"asc"
    }
});
</script>
</body>
</html>
