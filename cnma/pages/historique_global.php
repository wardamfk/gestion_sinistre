<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') { header("Location: login.php"); exit(); }

$page_title = "Historique global";

// Filtres
$filtre_user   = isset($_GET['uid'])    ? intval($_GET['uid'])   : 0;
$filtre_action = isset($_GET['action']) ? $_GET['action']        : '';
$filtre_date   = isset($_GET['date'])   ? $_GET['date']          : '';

$where = "WHERE 1=1";
if($filtre_user   > 0)   $where .= " AND h.fait_par = $filtre_user";
if($filtre_action != '') $where .= " AND h.action LIKE '%".mysqli_real_escape_string($conn,$filtre_action)."%'";
if($filtre_date   != '') $where .= " AND DATE(h.date_action) = '".mysqli_real_escape_string($conn,$filtre_date)."'";

$historique = mysqli_query($conn, "
    SELECT h.*,
           d.numero_dossier,
           u.nom AS utilisateur_nom, u.role AS utilisateur_role,
           ea.nom_etat AS ancien_etat_nom,
           en.nom_etat AS nouvel_etat_nom
    FROM historique h
    LEFT JOIN dossier d ON h.id_dossier = d.id_dossier
    LEFT JOIN utilisateur u ON h.fait_par = u.id_user
    LEFT JOIN etat_dossier ea ON h.ancien_etat = ea.id_etat
    LEFT JOIN etat_dossier en ON h.nouvel_etat = en.id_etat
    $where
    ORDER BY h.date_action DESC
    LIMIT 300
");

$utilisateurs = mysqli_query($conn, "SELECT id_user, nom, role FROM utilisateur ORDER BY nom");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique global — CNMA</title>
    <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .action-chip {
            display: inline-block;
            padding: 3px 10px; border-radius: 12px;
            font-size: 11.5px; font-weight: 600;
        }
    </style>
</head>
<body>
<?php include("sidebar_cnma.php"); ?>
<?php include("header_cnma.php"); ?>

<div class="cnma-main">
    <div class="page-heading">
        <h2><i class="fa fa-history"></i> Historique global des actions</h2>
    </div>

    <!-- FILTRES -->
    <form method="GET" class="filter-bar">
        <select name="uid">
            <option value="0">— Tous les utilisateurs —</option>
            <?php while($u = mysqli_fetch_assoc($utilisateurs)): ?>
            <option value="<?php echo $u['id_user']; ?>" <?php echo $filtre_user==$u['id_user']?'selected':''; ?>>
                <?php echo $u['nom'].' ('.$u['role'].')'; ?>
            </option>
            <?php endwhile; ?>
        </select>

        <input type="text" name="action" placeholder="Rechercher action..." value="<?php echo htmlspecialchars($filtre_action); ?>">

        <input type="date" name="date" value="<?php echo $filtre_date; ?>">

        <button type="submit" class="cnma-btn primary sm"><i class="fa fa-search"></i> Filtrer</button>
        <a href="historique_global.php" class="cnma-btn secondary sm"><i class="fa fa-times"></i> Reset</a>
    </form>

    <table class="cnma-table">
        <thead>
            <tr>
                <th>Date / Heure</th>
                <th>Utilisateur</th>
                <th>Dossier</th>
                <th>Action</th>
                <th>Ancien état</th>
                <th>Nouvel état</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        while($h = mysqli_fetch_assoc($historique)):
            $count++;

            // Couleur de l'action
            $a = strtolower($h['action']);
            if(str_contains($a,'valid'))      $ac_style = "background:#e8f5e9; color:#1b5e20;";
            elseif(str_contains($a,'refus'))  $ac_style = "background:#ffebee; color:#b71c1c;";
            elseif(str_contains($a,'clôture')||str_contains($a,'cloture')) $ac_style = "background:#f3e5f5; color:#4a148c;";
            elseif(str_contains($a,'règlement')||str_contains($a,'reglement')) $ac_style = "background:#e0f2f1; color:#004d40;";
            elseif(str_contains($a,'créat')||str_contains($a,'creat')) $ac_style = "background:#e8eaf6; color:#283593;";
            elseif(str_contains($a,'expert')) $ac_style = "background:#fff3e0; color:#e65100;";
            elseif(str_contains($a,'réserve')||str_contains($a,'reserve')) $ac_style = "background:#e3f2fd; color:#0d47a1;";
            else $ac_style = "background:#eceff1; color:#37474f;";

            // Badge rôle utilisateur
            $role_colors = ['CNMA'=>'#283593', 'CRMA'=>'#1b5e20', 'ASSURE'=>'#0d47a1'];
            $rc = $role_colors[$h['utilisateur_role']] ?? '#546e7a';
        ?>
        <tr>
            <td style="font-size:12px; white-space:nowrap;">
                <div style="font-weight:600; color:#2c3e50;"><?php echo date('d/m/Y', strtotime($h['date_action'])); ?></div>
                <div style="color:#90a4ae;"><?php echo date('H:i:s', strtotime($h['date_action'])); ?></div>
            </td>
            <td>
                <div style="font-weight:600; font-size:13px;"><?php echo htmlspecialchars($h['utilisateur_nom'] ?? '—'); ?></div>
                <div style="font-size:11px; color:<?php echo $rc; ?>; font-weight:600;"><?php echo $h['utilisateur_role'] ?? ''; ?></div>
            </td>
            <td>
                <?php if($h['numero_dossier']): ?>
                <a href="voir_dossier_cnma.php?id=<?php echo $h['id_dossier']; ?>"
                   style="color:#1a237e; font-weight:700; text-decoration:none; font-size:13px;">
                    <?php echo $h['numero_dossier']; ?>
                </a>
                <?php else: echo '—'; endif; ?>
            </td>
            <td>
                <span class="action-chip" style="<?php echo $ac_style; ?>">
                    <?php echo htmlspecialchars($h['action']); ?>
                </span>
            </td>
            <td style="font-size:12px; color:#78909c;"><?php echo $h['ancien_etat_nom'] ?? '—'; ?></td>
            <td style="font-size:12px; color:#78909c;"><?php echo $h['nouvel_etat_nom'] ?? '—'; ?></td>
            <td style="font-size:12px; color:#546e7a; max-width:200px;">
                <?php echo htmlspecialchars($h['commentaire'] ?? '—'); ?>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if($count == 0): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fa fa-history"></i><p>Aucune action trouvée</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <p style="color:#90a4ae; font-size:12px; margin-top:10px;"><?php echo $count; ?> action(s) affichée(s) — Limité à 300 dernières</p>
</div>
</body>
</html>
