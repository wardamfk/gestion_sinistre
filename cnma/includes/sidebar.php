<?php
// sidebar.php — CRMA
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn)) { include __DIR__ . '/config.php'; }

$nb_notifs = 0;
$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as n FROM notification WHERE id_destinataire={$_SESSION['id_user']} AND lu=0"));
$nb_notifs = $r ? (int)$r['n'] : 0;

$nb_attente = 0; // Non utilisé côté CRMA mais gardé pour cohérence
$current = basename($_SERVER['PHP_SELF']);
$base = '/PfeCnma/cnma';

function nav_link(string $href, string $icon, string $label, string $current, int $badge = 0): void {
    $active = (basename($current) === basename($href)) ? 'active' : '';
    echo "<a href=\"$href\" class=\"$active\">";
    echo "<i class=\"fa $icon\"></i> $label";
    if ($badge > 0) echo "<span class=\"nb\">$badge</span>";
    echo "</a>";
}
?>
<link rel="stylesheet" href="<?= $base ?>/css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= $base ?>/images/logo.webp" alt="CNMA">
        <div class="sidebar-brand-text">
            <h2>CNMA</h2>
            <span>Espace CRMA</span>
        </div>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Principal</div>
        <nav class="sidebar-nav">
            <?php nav_link("$base/crma/dashboard_crma.php",   'fa-chart-pie',    'Tableau de bord', $current); ?>
        </nav>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Référentiel</div>
        <nav class="sidebar-nav">
            <?php nav_link("$base/crma/gerer_personnes.php",  'fa-users',        'Personnes',       $current); ?>
            <?php nav_link("$base/crma/gerer_assures.php",    'fa-id-card',      'Assurés',         $current); ?>
            <?php nav_link("$base/crma/gerer_experts.php",    'fa-user-tie',     'Experts',         $current); ?>
            <?php nav_link("$base/crma/gerer_tiers.php",      'fa-user-shield',  'Tiers adversaires',$current); ?>
            <?php nav_link("$base/crma/gerer_vehicules.php",  'fa-car',          'Véhicules',       $current); ?>
            <?php nav_link("$base/crma/gerer_contrats.php",   'fa-file-contract','Contrats',        $current); ?>
        </nav>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Sinistres</div>
        <nav class="sidebar-nav">
            <?php nav_link("$base/crma/mes_dossiers.php",     'fa-folder-open',  'Mes dossiers',    $current); ?>
            <?php nav_link("$base/crma/creer_dossier.php",    'fa-folder-plus',  'Nouveau dossier', $current); ?>
        </nav>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Alertes</div>
        <nav class="sidebar-nav">
            <?php nav_link("$base/crma/notifications.php",    'fa-bell',         'Notifications',   $current, $nb_notifs); ?>
        </nav>
    </div>

    <div class="sidebar-footer">
        <a href="<?= $base ?>/pages/logout.php">
            <i class="fa fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>