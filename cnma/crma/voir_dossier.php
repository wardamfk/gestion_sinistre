<?php
include('../includes/config.php');
session_start();

if(!isset($_GET['id'])){
    echo "Dossier introuvable";
    exit();
}
if(isset($_GET['updated'])){
    echo "<div class='success'>Modification enregistrée</div>";
}
$id_dossier = $_GET['id'];
$id_user = $_SESSION['id_user'];

/* ================= RECUP DOSSIER ================= */
$dossier = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT d.*, e.nom_etat
FROM dossier d
LEFT JOIN etat_dossier e ON d.id_etat = e.id_etat
WHERE d.id_dossier = $id_dossier
"));

if(!$dossier){
    die("Dossier introuvable");
}

/* ================= EXPERT DU DOSSIER ================= */
$expert_dossier = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT e.nom, e.prenom, e.id_expert
FROM dossier d
LEFT JOIN expert e ON d.id_expert = e.id_expert
WHERE d.id_dossier = $id_dossier
"));

/* ================= TOTAL ================= */
$total_reserve = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT SUM(montant) as total FROM reserve WHERE id_dossier = $id_dossier
"))['total'];

$total_regle = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT SUM(montant) as total FROM reglement WHERE id_dossier = $id_dossier
"))['total'];

if($total_reserve == NULL) $total_reserve = 0;
if($total_regle == NULL) $total_regle = 0;

$reste = $total_reserve - $total_regle;

/* ================= ETAT FINANCIER ================= */
if($total_reserve == 0){
    $etat_financier = "Ouvert";
}
elseif($total_regle < $total_reserve){
    $etat_financier = "En cours";
}
elseif($total_regle == $total_reserve){
    $etat_financier = "Réglé";
}
else{
    $etat_financier = "Erreur";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dossier</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="main">

<h2>Dossier <?php echo $dossier['numero_dossier']; ?></h2>

<div class="tabs">
    <button class="tab-btn" onclick="showTab('info')">Informations</button>
    <button class="tab-btn" onclick="showTab('documents')">Documents</button>
    <button class="tab-btn" onclick="showTab('expertise')">Expertise</button>
    <button class="tab-btn" onclick="showTab('reserves')">Réserves</button>
    <button class="tab-btn" onclick="showTab('reglements')">Règlements</button>
    <button class="tab-btn" onclick="showTab('historique')">Historique</button>
</div>

<!-- INFORMATIONS -->
<div id="info" class="tab-content">
    <p><b>Expert :</b>
    <?php 
    if($expert_dossier && $expert_dossier['nom']){
        echo $expert_dossier['nom'].' '.$expert_dossier['prenom'];
    } else {
        echo "Non affecté";
    }
    ?>
    </p>

    <p><b>Date sinistre:</b> <?php echo $dossier['date_sinistre']; ?></p>
    <p><b>Lieu:</b> <?php echo $dossier['lieu_sinistre']; ?></p>
    <p><b>Description:</b> <?php echo $dossier['description']; ?></p>
    <p><b>Etat dossier:</b> <?php echo $dossier['nom_etat']; ?></p>

    <hr>

    <p><b>Total Réserves:</b> <?php echo number_format($total_reserve,2,',',' '); ?> DA</p>
    <p><b>Total Règlements:</b> <?php echo number_format($total_regle,2,',',' '); ?> DA</p>
    <p><b>Reste à régler:</b> <?php echo number_format($reste,2,',',' '); ?> DA</p>
    <p><b>État Financier:</b> <?php echo $etat_financier; ?></p>
    <p><b>Statut validation:</b> <?php echo $dossier['statut_validation']; ?></p>
    <?php if($dossier['id_etat'] == 3 && $_SESSION['role'] == 'CNMA'){ ?>

    <a href="valider_cnma.php?id=<?php echo $id_dossier; ?>" 
       class="btn"
       onclick="return confirm('Valider ce dossier ?')">
       Valider CNMA
    </a>

    <a href="refuser_cnma.php?id=<?php echo $id_dossier; ?>" 
       class="btn btn-danger"
       onclick="return confirm('Refuser ce dossier ?')">
       Refuser CNMA
    </a>

<?php } ?>

<?php if($dossier['id_etat'] == 8 && $_SESSION['role'] == 'CRMA'){ ?>

    <a href="cloturer_dossier.php?id=<?php echo $id_dossier; ?>" 
       class="btn btn-success"
       onclick="return confirm('Clôturer ce dossier ?')">
       Clôturer dossier
    </a>

<?php } ?>
</div>

<!-- DOCUMENTS -->
<div id="documents" class="tab-content">
    <h3>Ajouter document</h3>

    <form action="upload_document.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_dossier" value="<?php echo $id_dossier; ?>">

        Type :
        <select name="type">
            <?php
            $types = mysqli_query($conn, "SELECT * FROM type_document");
            while($t = mysqli_fetch_assoc($types)){
                echo "<option value='".$t['id_type_document']."'>".$t['nom_type']."</option>";
            }
            ?>
        </select>

        Fichier :
        <input type="file" name="fichier" required>

        <button type="submit" class="btn">Upload</button>
    </form>

    <table class="table">
        <tr>
            <th>Type</th>
            <th>Fichier</th>
            <th>Date</th>
            <th>Action</th>
        </tr>

       <?php
$docs = mysqli_query($conn, "
SELECT d.*, t.nom_type
FROM document d
LEFT JOIN type_document t ON d.id_type_document = t.id_type_document
WHERE d.id_dossier = $id_dossier
");

if(mysqli_num_rows($docs) == 0){
    echo "<tr><td colspan='4'>Aucun document</td></tr>";
}

while($d = mysqli_fetch_assoc($docs)){
    echo "<tr>";
    echo "<td>".$d['nom_type']."</td>";
    echo "<td><a href='../uploads/".$d['nom_fichier']."' target='_blank'>Voir</a></td>";
    echo "<td>".$d['date_upload']."</td>";
    echo "<td>
            <a href='supprimer_document.php?id=".$d['id_document']."&dossier=".$id_dossier."'
            onclick=\"return confirm('Supprimer document ?')\">
            <i class='fa fa-trash'></i>
            </a>
          </td>";
    echo "</tr>";
}
?>
    </table>
</div>

<!-- EXPERTISE -->
<div id="expertise" class="tab-content">

<div class="form">
    <h3>Ajouter expertise / contre-expertise</h3>

    <form action="ajouter_expertise.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_dossier" value="<?php echo $id_dossier; ?>">

        <label>Expert</label>
        <select name="id_expert" required>
            <?php
            $experts = mysqli_query($conn, "SELECT * FROM expert");
            while($e = mysqli_fetch_assoc($experts)){
                $selected = ($e['id_expert'] == $expert_dossier['id_expert']) ? "selected" : "";
                echo "<option value='".$e['id_expert']."' $selected>".$e['nom']." ".$e['prenom']."</option>";
            }
            ?>
        </select>

        <label>Date expertise</label>
        <input type="date" name="date_expertise" required>

        <label>Montant indemnité</label>
        <input type="number" name="montant_indemnite" required>

        <label>Rapport PDF</label>
        <input type="file" name="rapport" required>

        <label>Commentaire</label>
        <textarea name="commentaire"></textarea>

        <button type="submit" class="btn">Ajouter expertise</button>
    </form>
</div>

<table class="table">
    <tr>
        <th>Date</th>
        <th>Expert</th>
        <th>Montant</th>
        <th>Rapport</th>
        <th>Commentaire</th>
        <th>Action</th>
    </tr>

    <?php
$expertises = mysqli_query($conn, "
SELECT ex.id_expertise, ex.date_expertise, ex.montant_indemnite, ex.rapport_pdf, ex.commentaire,
       e.nom, e.prenom
FROM expertise ex
LEFT JOIN expert e ON ex.id_expert = e.id_expert
WHERE ex.id_dossier = $id_dossier
ORDER BY ex.id_expertise DESC
");

$highlight = isset($_GET['added']) ? true : false;
$first = true;

while($ex = mysqli_fetch_assoc($expertises)){
    if($highlight && $first){
        $class = "new-row";
        $first = false;
    } else {
        $class = "";
    }

    echo "<tr class='$class'>";
    echo "<td>".$ex['date_expertise']."</td>";
    echo "<td>".$ex['nom']." ".$ex['prenom']."</td>";
    echo "<td>".$ex['montant_indemnite']." DA</td>";
    echo "<td><a href='../uploads/".$ex['rapport_pdf']."' target='_blank'>Voir</a></td>";
    echo "<td>".$ex['commentaire']."</td>";
    echo "<td>

<a href='modifier_expertise.php?id=".$ex['id_expertise']."'>
<i class='fa fa-pen'></i>
</a>

<a href='supprimer_expertise.php?id=".$ex['id_expertise']."&dossier=".$id_dossier."'
onclick=\"return confirm('Supprimer expertise ?')\">
<i class='fa fa-trash'></i>
</a>

</td>";
    echo "</tr>";
}
?>
</table>
</div>

<!-- RESERVES -->
<!-- RESERVES -->
<div id="reserves" class="tab-content">

<h3>Total Réserves : <?php echo number_format($total_reserve,2,',',' '); ?> DA</h3>

<?php if($dossier['id_etat'] == 1 || $dossier['id_etat'] == 2 || $dossier['id_etat'] == 3 || $dossier['id_etat'] == 7){ ?>

<div class="form">
    <h3>Ajouter réserve</h3>

    <form action="ajouter_reserve.php" method="POST">
        <input type="hidden" name="id_dossier" value="<?php echo $id_dossier; ?>">

        <label>Montant</label>
        <input type="number" name="montant" required>

        <label>Garantie</label>
        <select name="id_garantie">
            <?php
            $gar = mysqli_query($conn, "SELECT * FROM garantie");
            while($g = mysqli_fetch_assoc($gar)){
                echo "<option value='".$g['id_garantie']."'>".$g['nom_garantie']."</option>";
            }
            ?>
        </select>

        <label>Commentaire</label>
        <input type="text" name="commentaire">

        <button type="submit" class="btn">Ajouter réserve</button>
    </form>
</div>

<?php } else { ?>

<p style="color:red; font-weight:bold;">
Impossible d'ajouter réserve — dossier réglé
</p>

<?php } ?>

<hr>

<h3>Liste des réserves</h3>

<table class="table">
<tr>
    <th>Date</th>
    <th>Garantie</th>
    <th>Montant</th>
    <th>Type</th>
    <th>Commentaire</th>
    <th>Action</th>
</tr>

<?php
$reserves = mysqli_query($conn, "
SELECT r.*, g.nom_garantie
FROM reserve r
LEFT JOIN garantie g ON r.id_garantie = g.id_garantie
WHERE r.id_dossier = $id_dossier
ORDER BY r.id_reserve DESC
");

$highlight = isset($_GET['added']) ? true : false;
$first = true;

while($r = mysqli_fetch_assoc($reserves)){
    if($highlight && $first){
        $class = "new-row";
        $first = false;
    } else {
        $class = "";
    }

    echo "<tr class='$class'>";
    echo "<td>".$r['date_reserve']."</td>";
    echo "<td>".$r['nom_garantie']."</td>";
    echo "<td>".$r['montant']." DA</td>";
    echo "<td>".$r['type_reserve']."</td>";
    echo "<td>".$r['commentaire']."</td>";
    echo "<td>

<a href='modifier_reserve.php?id=".$r['id_reserve']."'>
    <i class='fa fa-pen'></i>
</a>
            <a href='supprimer_reserve.php?id=".$r['id_reserve']."&dossier=".$id_dossier."' 
               onclick=\"return confirm('Supprimer cette réserve ?')\">
               <i class='fa fa-trash'></i>

            </a>

          </td>";
    echo "</tr>";
}
?>
</table>

</div>
<!-- REGLEMENTS -->
<div id="reglements" class="tab-content">

<h3>Total Réglé : <?php echo number_format($total_regle,2,',',' '); ?> DA</h3>

<?php
// Etats qui BLOQUENT le règlement
if($dossier['id_etat'] == 3){ ?>

    <p style="color:red; font-weight:bold;">
        Règlement impossible — dossier transmis à la CNMA
    </p>

<?php } elseif($dossier['id_etat'] == 5){ ?>

    <p style="color:red; font-weight:bold;">
        Règlement impossible — dossier refusé par la CNMA
    </p>

<?php } elseif($dossier['id_etat'] == 8){ ?>

    <p style="color:green; font-weight:bold;">
        Dossier réglé totalement
    </p>

<?php } else { ?>

    <!-- FORMULAIRE REGLEMENT -->
    <div class="form">
        <form action="ajouter_reglement.php" method="POST">
            <input type="hidden" name="id_dossier" value="<?php echo $id_dossier; ?>">

            Montant:
            <input type="number" name="montant" required>

            Mode:
            <select name="mode">
                <option>Chèque</option>
                <option>Virement</option>
                <option>Espèces</option>
            </select>

            Commentaire:
            <input type="text" name="commentaire">

            <button type="submit" class="btn">Ajouter règlement</button>
        </form>
    </div>

<?php } ?>

<hr>

<h3>Liste des règlements</h3>
<table class="table">
<tr>
    <th>Date</th>
    <th>Montant</th>
    <th>Mode</th>
    <th>Commentaire</th>
    <th>Action</th>
</tr>

<?php
$reglements = mysqli_query($conn, "
SELECT * FROM reglement
WHERE id_dossier = $id_dossier
ORDER BY id_reglement DESC
");

$highlight = isset($_GET['added']) ? true : false;
$first = true;

while($reg = mysqli_fetch_assoc($reglements)){
    if($highlight && $first){
        $class = "new-row";
        $first = false;
    } else {
        $class = "";
    }

    echo "<tr class='$class'>";
    echo "<td>".$reg['date_reglement']."</td>";
    echo "<td>".$reg['montant']." DA</td>";
    echo "<td>".$reg['mode_paiement']."</td>";
    echo "<td>".$reg['commentaire']."</td>";
    echo "<td>
<a href='modifier_reglement.php?id=".$reg['id_reglement']."'>
    <i class='fa fa-pen'></i>
</a>
            <a href='supprimer_reglement.php?id=".$reg['id_reglement']."&dossier=".$id_dossier."' 
               onclick=\"return confirm('Supprimer ce règlement ?')\">
               <i class='fa fa-trash'></i>
            </a>
          </td>";
    echo "</tr>";
}
?>
</table>

</div>
<!-- HISTORIQUE -->
<div id="historique" class="tab-content">
<table class="table">
<tr>
    <th>Date</th>
    <th>Action</th>
</tr>

<?php
$hist = mysqli_query($conn, "SELECT * FROM historique WHERE id_dossier = $id_dossier ORDER BY date_action DESC");

while($h = mysqli_fetch_assoc($hist)){
    echo "<tr>";
    echo "<td>".$h['date_action']."</td>";
    echo "<td>".$h['action']."</td>";
    echo "</tr>";
}
?>
</table>
</div>

</div>

<script>
function showTab(tab){
    var tabs = document.getElementsByClassName("tab-content");
    for(var i=0;i<tabs.length;i++){
        tabs[i].style.display = "none";
    }
    document.getElementById(tab).style.display = "block";
}

// Lire l'URL pour savoir quel onglet afficher
const params = new URLSearchParams(window.location.search);
const tab = params.get("tab");

if(tab){
    showTab(tab);
} else {
    showTab('info');
}

// Si on vient d'ajouter une réserve → scroll vers tableau
if(params.get("added") == "1"){
    setTimeout(function(){
        document.getElementById("reserves").scrollIntoView({behavior: "smooth"});
    }, 300);
}
</script>

</body>
</html>