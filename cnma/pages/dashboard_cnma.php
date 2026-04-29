<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') { header("Location: login.php"); exit(); }

$page_title = "Tableau de bord";

// === STATS ===
$total    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier"))['n'];
$attente  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 3"))['n'];
$valides  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 4"))['n'];
$refuses  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 5"))['n'];
$total_decision = $valides + $refuses;
$taux = $total_decision > 0 ? round(($valides / $total_decision) * 100, 1) : null;

$total_reserve = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(montant),0) as n FROM reserve"))['n'];
$total_regle   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(montant),0) as n FROM reglement"))['n'];
$delais = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT 
        AVG(DATEDIFF(date_transmission, date_creation)) as crma,
        AVG(DATEDIFF(date_validation, date_transmission)) as cnma,
        AVG(DATEDIFF(date_validation, date_creation)) as total
    FROM dossier
    WHERE date_creation IS NOT NULL
      AND date_transmission IS NOT NULL
      AND date_validation IS NOT NULL
"));
$delai_crma  = $delais['crma'] ?? 0;
$delai_cnma  = $delais['cnma'] ?? 0;
$delai_total = $delais['total'] ?? 0;
// Dossiers en attente (5 derniers)
$derniers = mysqli_query($conn, "
    SELECT d.id_dossier, d.numero_dossier, d.date_creation, d.date_transmission, d.total_reserve,
           p.nom, p.prenom, ag.nom_agence
    FROM dossier d
    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure a ON c.id_assure = a.id_assure
    LEFT JOIN personne p ON a.id_personne = p.id_personne
    LEFT JOIN utilisateur u ON d.cree_par = u.id_user
    LEFT JOIN agence ag ON u.id_agence = ag.id_agence
    WHERE d.id_etat = 3
    ORDER BY d.date_creation ASC LIMIT 5
");
// === DOSSIERS LENTS CRMA ===
$dossiers_lents = mysqli_query($conn,"
    SELECT 
        id_dossier,
        numero_dossier,
        DATEDIFF(date_transmission, date_creation) as delai
    FROM dossier
    WHERE date_transmission IS NOT NULL
    ORDER BY delai DESC
    LIMIT 5
");
// Répartition par agence
$par_agence = mysqli_query($conn, "
    SELECT ag.nom_agence, COUNT(d.id_dossier) as total
    FROM dossier d
    LEFT JOIN utilisateur u ON d.cree_par = u.id_user
    LEFT JOIN agence ag ON u.id_agence = ag.id_agence
    GROUP BY ag.nom_agence
    ORDER BY total DESC
");
// === ACTIVITÉ RÉCENTE ===
$activite = mysqli_query($conn, "
    SELECT h.action, h.date_action, d.id_dossier, d.numero_dossier
    FROM historique h
    LEFT JOIN dossier d ON h.id_dossier = d.id_dossier
    ORDER BY h.date_action DESC
    LIMIT 5
");

// === DOSSIERS LES PLUS ANCIENS ===
$dossiers_anciens = mysqli_query($conn, "
    SELECT id_dossier, numero_dossier,
           DATEDIFF(NOW(), date_creation) as delai
    FROM dossier
    WHERE id_etat != 4 AND id_etat != 5
    ORDER BY delai DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard CNMA</title>
    <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include("sidebar_cnma.php"); ?>
<?php include("header_cnma.php"); ?>

<div class="cnma-main">
    <div class="page-heading">
        <h2><i class="fa fa-chart-pie"></i> Tableau de bord CNMA</h2>
        <a href="dossiers_attente.php" class="cnma-btn primary">
            <i class="fa fa-clock"></i> Traiter les dossiers
            <?php if($attente > 0) echo "<span style='background:#ef5350;border-radius:10px;padding:1px 8px;font-size:11px;margin-left:4px;'>$attente</span>"; ?>
        </a>
    </div>

  
    <div class="stats-grid">

        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa fa-clock"></i></div>
           <div class="stat-value text-orange"><?php echo $attente; ?></div>
            <div class="stat-label">En attente</div>
            <div class="stat-sub">À traiter</div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
            <div class="stat-value text-green"><?php echo $valides; ?></div>
            <div class="stat-label">Validés CNMA</div>
        </div>

        <div class="stat-card red">
            <div class="stat-icon"><i class="fa fa-times-circle"></i></div>
         <div class="stat-value text-red"><?php echo $refuses; ?></div>
            <div class="stat-label">Refusés</div>
        </div>

        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa fa-percent"></i></div>
           <div class="stat-value 
<?php 
if($taux !== null){
    if($taux >= 80) echo 'text-green';
    elseif($taux >= 50) echo 'text-orange';
    else echo 'text-red';
}
?>">
<?php echo $taux !== null ? $taux.'%' : '—'; ?>
</div>
            <div class="stat-label">Taux validation</div>
        </div>

    </div>

    <!-- 2. DOSSIERS URGENTS -->
    <?php if($attente > 0): ?>
    <div class="section-title"><i class="fa fa-exclamation-circle" style="color:#f57c00;"></i> Dossiers urgents — En attente de décision</div>
    <table class="cnma-table">
        <thead>
            <tr>
                <th>N° Dossier</th>
                <th>Agence</th>
                <th>Assuré</th>
                <th>Date création</th>
                <th>Transmis le</th>
                <th>Réserve</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while($d = mysqli_fetch_assoc($derniers)): 
            $urgent = false;
            if($d['date_transmission']) {
                $urgent = ((new DateTime())->diff(new DateTime($d['date_transmission']))->days >= 3);
            }
        ?>
        <tr class="<?php echo $urgent ? 'urgent' : ''; ?>">
            <td><b style="color:#1a237e;"><?php echo $d['numero_dossier']; ?></b></td>
            <td><small><?php echo $d['nom_agence']; ?></small></td>
            <td><?php echo $d['nom'].' '.$d['prenom']; ?></td>
            <td><?php echo $d['date_creation']; ?></td>
            <td>
                <?php if($d['date_transmission']): ?>
                <?php echo $d['date_transmission']; ?>
                <?php if($urgent) echo " <span style='color:#f57c00;font-size:11px;'>⚠ Urgent</span>"; ?>
                <?php else: echo '<span style="color:#90a4ae;">—</span>'; endif; ?>
            </td>
            <td class="money-cell money-reserve"><?php echo number_format($d['total_reserve'], 2, ',', ' '); ?> <span class="currency">DA</span></td>
            <td>
                <a href="voir_dossier_cnma.php?id=<?php echo $d['id_dossier']; ?>" class="cnma-btn success sm">
                    <i class="fa fa-gavel"></i> Traiter
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="msg success"><i class="fa fa-check-circle"></i> Aucun dossier en attente — Tout est traité !</div>
    <?php endif; ?>


<!-- ACTIONS RAPIDES -->
<div class="section-title">
    <i class="fa fa-bolt"></i> Actions rapides
</div>

<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px;">

    <!-- 🔥 PRINCIPAL -->
    <a href="dossiers_attente.php" class="cnma-btn success">
        <i class="fa fa-gavel"></i> Dossiers en attente
    </a>

    <!-- 📂 DOSSIERS -->
    <a href="tous_dossiers_cnma.php" class="cnma-btn neutral">
        <i class="fa fa-folder-open"></i> Tous les dossiers
    </a>



    <!-- 📊 ANALYSE -->
    <a href="statistiques_cnma.php" class="cnma-btn neutral">
        <i class="fa fa-chart-bar"></i> Statistiques
    </a>

    <!-- ⚙️ ADMIN -->
    <a href="gestion_utilisateurs.php" class="cnma-btn neutral">
        <i class="fa fa-users"></i> Utilisateurs
    </a>

    <a href="historique_global.php" class="cnma-btn neutral">
        <i class="fa fa-history"></i> Historique global
    </a>

    <!-- 👤 USER -->
    <a href="profil_cnma.php" class="cnma-btn neutral">
        <i class="fa fa-user"></i> Mon profil
    </a>

 
</div>



    <!-- 3. DOSSIERS LES PLUS LENTS -->
    <div class="section-title">
        <i class="fa fa-hourglass-half" style="color:#d32f2f;"></i>
        Dossiers les plus lents CRMA (inclus non validé)
    </div>

    <table class="cnma-table">
        <thead>
            <tr>
                <th>Dossier</th>
                <th>Délai</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>

        <?php while($d = mysqli_fetch_assoc($dossiers_lents)): ?>
        <tr>
            <td><b><?php echo $d['numero_dossier']; ?></b></td>
            <td style="color:#d32f2f;"><?php echo $d['delai']; ?> j</td>
            <td>
                <a href="voir_dossier_cnma.php?id=<?php echo $d['id_dossier']; ?>" class="cnma-btn primary sm">
                    Ouvrir
                </a>
            </td>
        </tr>
        <?php endwhile; ?>

        </tbody>
    </table>

    <!-- 4. DÉLAIS MOYENS -->
    <div class="section-title"><i class="fa fa-clock"></i> Délais moyens</div>
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-value"><?php echo round((float)$delai_crma,1); ?> j</div>
            <div class="stat-label">Délai moyen CRMA</div>
        </div>

        <div class="stat-card">
            <div class="stat-value"><?php echo round((float)$delai_cnma,1); ?> j</div>
            <div class="stat-label">Délai moyen CNMA</div>
        </div>

        <div class="stat-card">
            <div class="stat-value"><?php echo round((float)$delai_total,1); ?> j</div>
            <div class="stat-label">Délai moyen total</div>
        </div>

    </div>

    <!-- 5. FINANCES -->
    <div class="section-title"><i class="fa fa-money-bill-wave"></i> Finances</div>
    <div class="finance-bar">
        <div class="finance-card reserve">
            <div class="finance-icon"><i class="fa fa-shield-halved"></i></div>
            <div class="finance-body">
                <div class="finance-label">Total Réserves</div>
                <div class="finance-amount"><?php echo number_format($total_reserve, 2, ',', ' '); ?><span class="finance-da">DA</span></div>
                <div class="finance-sub">Toutes réserves actives</div>
            </div>
        </div>
        <div class="finance-card regle">
            <div class="finance-icon"><i class="fa fa-money-bill-wave"></i></div>
            <div class="finance-body">
                <div class="finance-label">Total Réglé</div>
                <div class="finance-amount"><?php echo number_format($total_regle, 2, ',', ' '); ?><span class="finance-da">DA</span></div>
                <div class="finance-sub">Tous règlements effectués</div>
            </div>
        </div>
        <div class="finance-card reste">
            <div class="finance-icon"><i class="fa fa-scale-balanced"></i></div>
            <div class="finance-body">
                <div class="finance-label">Reste à régler</div>
                <div class="finance-amount"><?php echo number_format($total_reserve - $total_regle, 2, ',', ' '); ?><span class="finance-da">DA</span></div>
                <div class="finance-sub">Solde global</div>
            </div>
        </div>
    </div>
    <!-- DOSSIERS LES PLUS ANCIENS -->
<div class="section-title">
    <i class="fa fa-exclamation-triangle" style="color:#d32f2f;"></i>
    Dossiers les plus anciens — À surveiller
</div>

<table class="cnma-table">
    <thead>
        <tr>
            <th>Dossier</th>
            <th>Délai</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

    <?php while($d = mysqli_fetch_assoc($dossiers_anciens)): ?>
    <tr>
        <td>
            <a href="voir_dossier_cnma.php?id=<?php echo $d['id_dossier']; ?>" style="color:#1a237e;font-weight:bold;">
                <?php echo $d['numero_dossier']; ?>
            </a>
        </td>

        <td style="
            color: <?php echo ($d['delai'] >= 25) ? '#c62828' : '#f57c00'; ?>;
            font-weight:bold;
        ">
            <?php echo $d['delai']; ?> j
        </td>

        <td>
            <a href="voir_dossier_cnma.php?id=<?php echo $d['id_dossier']; ?>" class="cnma-btn primary sm">
                Ouvrir
            </a>
        </td>
    </tr>
    <?php endwhile; ?>

    </tbody>
</table>
<!-- ACTIVITÉ RÉCENTE -->
<div class="section-title">
    <i class="fa fa-clock"></i> Dernières actions sur les dossiers
</div>
<div style="color:#6b7280; font-size:13px; margin-bottom:10px;">
    Actions réalisées par CRMA et CNMA
</div>

<div style="background:white; padding:15px; border-radius:10px; margin-bottom:25px;">

<?php while($a = mysqli_fetch_assoc($activite)): ?>
    <div style="margin-bottom:12px; display:flex; align-items:center; gap:10px;">

        <div style="
            width:8px;
            height:8px;
            border-radius:50%;
            background:#1b5e20;
        "></div>

        <div>
            <div style="font-weight:500;">
                <?php echo $a['action']; ?>
            </div>

            <small style="color:#607d8b;">
                <?php echo $a['date_action']; ?> 

                <?php if($a['id_dossier']): ?>
                    — 
                    <a href="voir_dossier_cnma.php?id=<?php echo $a['id_dossier']; ?>" style="color:#1a237e;">
                        <?php echo $a['numero_dossier']; ?>
                    </a>
                <?php endif; ?>
            </small>
        </div>

    </div>
<?php endwhile; ?>

</div>

</div>
</body>
</html>
