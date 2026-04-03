<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') {
    header("Location: login.php");
    exit();
}

// Filtre par état
$filtre_etat = isset($_GET['etat']) ? intval($_GET['etat']) : 0;
$filtre_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE 1=1";
if($filtre_etat > 0) $where .= " AND d.id_etat = $filtre_etat";
if($filtre_search != '') $where .= " AND (d.numero_dossier LIKE '%$filtre_search%' OR p.nom LIKE '%$filtre_search%' OR p.prenom LIKE '%$filtre_search%')";

$sql = "
SELECT
    d.id_dossier, d.numero_dossier, d.date_creation, d.total_reserve,
    d.statut_validation, d.id_etat,
    p.nom AS nom_assure, p.prenom AS prenom_assure,
    pt.nom AS nom_tiers, pt.prenom AS prenom_tiers,
    t.compagnie_assurance, t.responsable,
    e.nom_etat,
    (SELECT IFNULL(SUM(montant),0) FROM reglement r WHERE r.id_dossier = d.id_dossier) AS total_regle,
    u.nom AS agent_nom, ag.nom_agence
FROM dossier d
LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
LEFT JOIN assure ass ON c.id_assure = ass.id_assure
LEFT JOIN personne p ON ass.id_personne = p.id_personne
LEFT JOIN tiers t ON d.id_tiers = t.id_tiers
LEFT JOIN personne pt ON t.id_personne = pt.id_personne
LEFT JOIN etat_dossier e ON d.id_etat = e.id_etat
LEFT JOIN utilisateur u ON d.cree_par = u.id_user
LEFT JOIN agence ag ON u.id_agence = ag.id_agence
$where
ORDER BY d.id_dossier DESC
";

$result = mysqli_query($conn, $sql);

// Liste des états pour le filtre
$etats = mysqli_query($conn, "SELECT * FROM etat_dossier ORDER BY id_etat");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tous les dossiers — CNMA</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .filtres {
            display: flex; gap: 12px; align-items: center;
            background: white; padding: 15px 20px;
            border-radius: 10px; margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            flex-wrap: wrap;
        }
        .filtres input, .filtres select {
            padding: 9px 12px; border-radius: 7px;
            border: 1px solid #ddd; font-size: 14px;
        }
        .filtres input { flex: 1; min-width: 200px; }
        .filtres button {
            background: #0d7b1c; color: white;
            border: none; padding: 9px 18px;
            border-radius: 7px; cursor: pointer;
            font-weight: bold;
        }
        .filtres a.reset {
            color: #666; text-decoration: none;
            font-size: 13px; padding: 9px;
        }
    </style>
</head>
<body>

<?php include("sidebar_cnma.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <h2 style="color:#1f3a5f;">Tous les dossiers</h2>

    <!-- FILTRES -->
    <form method="GET" class="filtres">
        <input type="text" name="search" placeholder="Rechercher par numéro ou assuré..."
               value="<?php echo htmlspecialchars($filtre_search); ?>">

        <select name="etat">
            <option value="0">— Tous les états —</option>
            <?php while($e = mysqli_fetch_assoc($etats)): ?>
            <option value="<?php echo $e['id_etat']; ?>"
                    <?php echo $filtre_etat == $e['id_etat'] ? 'selected' : ''; ?>>
                <?php echo $e['nom_etat']; ?>
            </option>
            <?php endwhile; ?>
        </select>

        <button type="submit"><i class="fa fa-search"></i> Filtrer</button>
        <a href="tous_dossiers_cnma.php" class="reset"><i class="fa fa-times"></i> Réinitialiser</a>
    </form>

    <table class="table">
        <tr>
            <th>N° Dossier</th>
            <th>Date</th>
            <th>Agence</th>
            <th>Assuré</th>
            <th>Tiers</th>
            <th>État</th>
            <th>Réserve</th>
            <th>Réglé</th>
            <th>Actions</th>
        </tr>

        <?php
        $count = 0;
        while($row = mysqli_fetch_assoc($result)):
            $count++;
            $etat = $row['id_etat'];
            $class = "badge";
            if($etat == 2) $class .= " blue";
            elseif($etat == 3) $class .= " orange";
            elseif($etat == 4) $class .= " green";
            elseif($etat == 5) $class .= " red";
            elseif($etat == 7) $class .= " dark";
            elseif($etat == 8) $class .= " gray";
            elseif($etat == 14) $class .= " gray";
            else $class .= " gray";
        ?>
        <tr>
            <td class="col-dossier">
                <?php
                $parts = explode('-', $row['numero_dossier']);
                echo $parts[0]."-".$parts[1]."<br>".$parts[2];
                ?>
            </td>
            <td><?php echo $row['date_creation']; ?></td>
            <td><?php echo $row['nom_agence']; ?></td>
            <td><?php echo $row['nom_assure'].' '.$row['prenom_assure']; ?></td>
            <td>
                <?php echo $row['nom_tiers'].' '.$row['prenom_tiers']; ?><br>
                <small><?php echo $row['compagnie_assurance']; ?></small>
            </td>
            <td><span class="<?php echo $class; ?>"><?php echo $row['nom_etat']; ?></span></td>
            <td>
                <span class="money">
                    <?php echo number_format($row['total_reserve'], 2, ',', ' '); ?>
                    <small>DA</small>
                </span>
            </td>
            <td>
                <span class="money">
                    <?php echo number_format($row['total_regle'], 2, ',', ' '); ?>
                    <small>DA</small>
                </span>
            </td>
            <td>
                <a href="voir_dossier_cnma.php?id=<?php echo $row['id_dossier']; ?>" class="btn" style="padding:7px 14px; font-size:13px;">
                    <i class="fa fa-eye"></i> Voir
                </a>
            </td>
        </tr>
        <?php endwhile; ?>

        <?php if($count == 0): ?>
        <tr><td colspan="9" style="text-align:center; padding:30px; color:#999;">Aucun dossier trouvé</td></tr>
        <?php endif; ?>
    </table>

    <p style="color:#999; font-size:13px; margin-top:10px;"><?php echo $count; ?> dossier(s) affiché(s)</p>
</div>
</body>
</html>
