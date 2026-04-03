<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') { header("Location: login.php"); exit(); }

$page_title = "Statistiques";

// === STATS GÉNÉRALES ===
$total    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier"))['n'];
$attente  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 3"))['n'];
$valides  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 4"))['n'];
$refuses  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 5"))['n'];
$en_cours = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 2"))['n'];
$regles   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat IN (7,8)"))['n'];
$clotures = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 14"))['n'];

$total_reserve = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(montant),0) as n FROM reserve"))['n'];
$total_regle   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(montant),0) as n FROM reglement"))['n'];
$nb_expertises = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM expertise WHERE montant_indemnite IS NOT NULL"))['n'];

// Délai moyen traitement (transmission → validation)
$delai_moy = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT ROUND(AVG(DATEDIFF(date_validation, date_transmission)),1) as moy
    FROM dossier
    WHERE date_transmission IS NOT NULL AND date_validation IS NOT NULL
"))['moy'];

// Montant moyen des réserves
$moy_reserve = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT ROUND(AVG(total_reserve),2) as moy FROM dossier WHERE total_reserve > 0
"))['moy'];

// === DONNÉES GRAPHIQUES ===

// 1. Répartition par état
$etats_data = mysqli_query($conn, "
    SELECT e.nom_etat, COUNT(d.id_dossier) as total
    FROM etat_dossier e
    LEFT JOIN dossier d ON e.id_etat = d.id_etat
    GROUP BY e.id_etat, e.nom_etat
    HAVING total > 0 ORDER BY total DESC
");
$etats_labels = []; $etats_vals = [];
while($r = mysqli_fetch_assoc($etats_data)) {
    $etats_labels[] = $r['nom_etat'];
    $etats_vals[]   = (int)$r['total'];
}

// 2. Dossiers par agence
$agence_data = mysqli_query($conn, "
    SELECT IFNULL(ag.nom_agence,'Non défini') as agence, COUNT(d.id_dossier) as total
    FROM dossier d
    LEFT JOIN utilisateur u ON d.cree_par = u.id_user
    LEFT JOIN agence ag ON u.id_agence = ag.id_agence
    GROUP BY ag.nom_agence ORDER BY total DESC
");
$ag_labels = []; $ag_vals = [];
while($r = mysqli_fetch_assoc($agence_data)) {
    $ag_labels[] = $r['agence'];
    $ag_vals[]   = (int)$r['total'];
}

// 3. Dossiers par mois (12 derniers mois)
$mois_data = mysqli_query($conn, "
    SELECT DATE_FORMAT(date_creation,'%b %Y') as mois,
           YEAR(date_creation) as annee,
           MONTH(date_creation) as num_mois,
           COUNT(*) as total
    FROM dossier
    WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY YEAR(date_creation), MONTH(date_creation)
    ORDER BY annee, num_mois
");
$mois_labels = []; $mois_vals = [];
while($r = mysqli_fetch_assoc($mois_data)) {
    $mois_labels[] = $r['mois'];
    $mois_vals[]   = (int)$r['total'];
}

// 4. Réserves vs Réglé par agence
$fin_agence = mysqli_query($conn, "
    SELECT IFNULL(ag.nom_agence,'Non défini') as agence,
           IFNULL(SUM(d.total_reserve),0) as reserve,
           IFNULL((SELECT SUM(r.montant) FROM reglement r WHERE r.id_dossier IN
               (SELECT id_dossier FROM dossier WHERE cree_par = u.id_user)),0) as regle
    FROM utilisateur u
    LEFT JOIN agence ag ON u.id_agence = ag.id_agence
    LEFT JOIN dossier d ON d.cree_par = u.id_user
    WHERE u.role = 'CRMA'
    GROUP BY ag.nom_agence
    ORDER BY reserve DESC
");
$fin_labels = []; $fin_reserve = []; $fin_regle = [];
while($r = mysqli_fetch_assoc($fin_agence)) {
    $fin_labels[]  = $r['agence'];
    $fin_reserve[] = round($r['reserve']);
    $fin_regle[]   = round($r['regle']);
}

// 5. Top experts
$experts_data = mysqli_query($conn, "
    SELECT CONCAT(e.nom,' ',e.prenom) as expert, COUNT(ex.id_expertise) as total,
           IFNULL(AVG(ex.montant_indemnite),0) as moy
    FROM expertise ex
    JOIN expert e ON ex.id_expert = e.id_expert
    GROUP BY e.id_expert ORDER BY total DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques — CNMA</title>
    <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<?php include("sidebar_cnma.php"); ?>
<?php include("header_cnma.php"); ?>

<div class="cnma-main">
    <div class="page-heading">
        <h2><i class="fa fa-chart-bar"></i> Statistiques CNMA</h2>
        <span style="color:#78909c; font-size:13px;"><i class="fa fa-calendar"></i> Données au <?php echo date('d/m/Y'); ?></span>
    </div>

    <!-- KPIs -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa fa-folder"></i></div>
            <div class="stat-value"><?php echo $total; ?></div>
            <div class="stat-label">Total dossiers</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa fa-clock"></i></div>
            <div class="stat-value"><?php echo $attente; ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
            <div class="stat-value"><?php echo $valides; ?></div>
            <div class="stat-label">Validés</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon"><i class="fa fa-times-circle"></i></div>
            <div class="stat-value"><?php echo $refuses; ?></div>
            <div class="stat-label">Refusés</div>
        </div>
        <div class="stat-card gray">
            <div class="stat-icon"><i class="fa fa-archive"></i></div>
            <div class="stat-value"><?php echo $clotures; ?></div>
            <div class="stat-label">Clôturés</div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon"><i class="fa fa-search"></i></div>
            <div class="stat-value"><?php echo $nb_expertises; ?></div>
            <div class="stat-label">Expertises</div>
        </div>
    </div>

    <!-- INDICATEURS FINANCIERS -->
    <div class="finance-bar">
        <div class="finance-card reserve">
            <div class="finance-icon"><i class="fa fa-shield-halved"></i></div>
            <div class="finance-body">
                <div class="finance-label">Total Réserves</div>
                <div class="finance-amount"><?php echo number_format($total_reserve,2,',',' '); ?><span class="finance-da">DA</span></div>
                <div class="finance-sub">Montant moyen : <?php echo number_format($moy_reserve,2,',',' '); ?> DA</div>
            </div>
        </div>
        <div class="finance-card regle">
            <div class="finance-icon"><i class="fa fa-money-bill-wave"></i></div>
            <div class="finance-body">
                <div class="finance-label">Total Réglé</div>
                <div class="finance-amount"><?php echo number_format($total_regle,2,',',' '); ?><span class="finance-da">DA</span></div>
                <div class="finance-sub">Taux règlement : <?php echo $total_reserve > 0 ? round($total_regle/$total_reserve*100,1) : 0; ?>%</div>
            </div>
        </div>
        <div class="finance-card reste">
            <div class="finance-icon"><i class="fa fa-hourglass-half"></i></div>
            <div class="finance-body">
                <div class="finance-label">Délai moyen traitement</div>
                <div class="finance-amount"><?php echo $delai_moy ?? '—'; ?><span class="finance-da" style="font-size:16px;"> jours</span></div>
                <div class="finance-sub">Transmission → Validation</div>
            </div>
        </div>
    </div>

    <!-- GRAPHIQUES ROW 1 -->
    <div class="charts-grid">
        <!-- Camembert états -->
        <div class="chart-card">
            <h4><i class="fa fa-chart-pie"></i> Répartition par état</h4>
            <canvas id="chartEtats"></canvas>
        </div>

        <!-- Barres par agence -->
        <div class="chart-card">
            <h4><i class="fa fa-building"></i> Dossiers par agence</h4>
            <canvas id="chartAgence"></canvas>
        </div>

        <!-- Courbe par mois (pleine largeur) -->
        <div class="chart-card wide">
            <h4><i class="fa fa-chart-line"></i> Évolution mensuelle des dossiers (12 derniers mois)</h4>
            <canvas id="chartMois" style="max-height:220px;"></canvas>
        </div>

        <!-- Réserves vs Réglé par agence -->
        <div class="chart-card wide">
            <h4><i class="fa fa-scale-balanced"></i> Réserves vs Réglé par agence (DA)</h4>
            <canvas id="chartFinance" style="max-height:230px;"></canvas>
        </div>
    </div>

    <!-- TOP EXPERTS -->
    <div class="section-title"><i class="fa fa-star"></i> Top 5 Experts</div>
    <table class="cnma-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Expert</th>
                <th>Nombre d'expertises</th>
                <th>Montant moyen indemnité</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $rank = 1;
        while($ex = mysqli_fetch_assoc($experts_data)):
        ?>
        <tr>
            <td>
                <span style="background:<?php echo $rank==1?'#f39c12':($rank==2?'#bdc3c7':($rank==3?'#cd7f32':'#e8eaf6')); ?>;
                      color:<?php echo $rank<=3?'white':'#546e7a'; ?>;
                      width:28px; height:28px; border-radius:50%; display:inline-flex;
                      align-items:center; justify-content:center; font-weight:bold; font-size:12px;">
                    <?php echo $rank; ?>
                </span>
            </td>
            <td><b><?php echo $ex['expert']; ?></b></td>
            <td>
                <span style="background:#e8eaf6; color:#1a237e; padding:4px 12px; border-radius:12px; font-weight:700;">
                    <?php echo $ex['total']; ?> expertise(s)
                </span>
            </td>
            <td class="money-cell money-reserve">
                <?php echo number_format($ex['moy'],2,',',' '); ?> <span class="currency">DA</span>
            </td>
        </tr>
        <?php $rank++; endwhile; ?>
        </tbody>
    </table>

</div>

<script>
// Couleurs palette CNMA
const palette = ['#1a237e','#f57c00','#2e7d32','#c62828','#6a1b9a','#00695c','#37474f','#0277bd'];
const palette_light = ['#e8eaf6','#fff3e0','#e8f5e9','#ffebee','#f3e5f5','#e0f2f1','#eceff1','#e1f5fe'];

// 1. Camembert répartition par état
new Chart(document.getElementById('chartEtats'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($etats_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($etats_vals); ?>,
            backgroundColor: palette,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right', labels: { font: { size: 12 }, padding: 12 } }
        },
        cutout: '60%'
    }
});

// 2. Barres par agence
new Chart(document.getElementById('chartAgence'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($ag_labels); ?>,
        datasets: [{
            label: 'Dossiers',
            data: <?php echo json_encode($ag_vals); ?>,
            backgroundColor: '#1a237e',
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// 3. Courbe mensuelle
new Chart(document.getElementById('chartMois'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($mois_labels); ?>,
        datasets: [{
            label: 'Dossiers créés',
            data: <?php echo json_encode($mois_vals); ?>,
            borderColor: '#1a237e',
            backgroundColor: 'rgba(26,35,126,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#1a237e',
            pointRadius: 5,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});

// 4. Réserves vs Réglé
new Chart(document.getElementById('chartFinance'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($fin_labels); ?>,
        datasets: [
            {
                label: 'Réserves (DA)',
                data: <?php echo json_encode($fin_reserve); ?>,
                backgroundColor: 'rgba(26,35,126,0.8)',
                borderRadius: 6,
                borderSkipped: false
            },
            {
                label: 'Réglé (DA)',
                data: <?php echo json_encode($fin_regle); ?>,
                backgroundColor: 'rgba(46,125,50,0.8)',
                borderRadius: 6,
                borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { font: { size: 12 }, padding: 15 } }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f0f0f0' },
                ticks: {
                    font: { size: 10 },
                    callback: v => v.toLocaleString('fr-DZ') + ' DA'
                }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>
