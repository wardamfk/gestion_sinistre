<?php
// Badge notifications
$nb_notifs = 0;
if(isset($conn) && isset($_SESSION['id_user'])) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM notification WHERE id_destinataire={$_SESSION['id_user']} AND lu=0"));
    $nb_notifs = $r ? $r['n'] : 0;
}
$current = basename($_SERVER['PHP_SELF']);
?>
<style>
.sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 240px;
    height: 100vh;
    background: linear-gradient(180deg, #0d7b1c 0%, #1b5e20 60%, #145217 100%);
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow-y: auto;
    z-index: 100;
    box-shadow: 3px 0 15px rgba(0,0,0,0.2);
}
.sidebar-brand {
    padding: 22px 18px 15px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.sidebar-brand h2 {
    color: white;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 2px;
    margin: 6px 0 4px;
}
.sidebar-brand .role-badge {
    display: inline-block;
    background: rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.9);
    font-size: 10px;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.sidebar-nav { flex: 1; padding: 14px 10px; }
.sidebar-nav .nav-section {
    font-size: 9px;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 12px 12px 5px;
    font-weight: 700;
}
.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,0.82);
    text-decoration: none;
    padding: 10px 13px;
    border-radius: 8px;
    margin-bottom: 2px;
    font-size: 13.5px;
    transition: all 0.2s;
    position: relative;
    white-space: nowrap;
}
.sidebar-nav a i { width: 18px; text-align: center; font-size: 14px; }
.sidebar-nav a:hover,
.sidebar-nav a.active {
    background: rgba(255,255,255,0.15);
    color: white;
    transform: translateX(3px);
}
.sidebar-nav a .notif-badge {
    position: absolute;
    right: 10px;
    background: #ef5350;
    color: white;
    border-radius: 10px;
    padding: 1px 7px;
    font-size: 10px;
    font-weight: bold;
}
.sidebar-footer {
    padding: 12px 10px;
    border-top: 1px solid rgba(255,255,255,0.1);
}
.sidebar-footer a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    padding: 10px 13px;
    border-radius: 8px;
    font-size: 13px;
    transition: 0.2s;
}
.sidebar-footer a:hover {
    background: rgba(239,83,80,0.3);
    color: white;
}
</style>

<div class="sidebar">
    <div class="sidebar-brand">
        <img src="/PfeCnma/cnma/images/logo.webp" alt="CNMA" style="width:48px;">
        <h2>CNMA</h2>
        <span class="role-badge">Espace CRMA</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>

        <a href="/PfeCnma/cnma/crma/dashboard_crma.php" class="<?= ($current=='dashboard_crma.php')?'active':'' ?>">
            <i class="fa fa-chart-pie"></i> Tableau de bord
        </a>

        <div class="nav-section">Gestion</div>

        <a href="/PfeCnma/cnma/crma/ajouter_personne.php" class="<?= ($current=='ajouter_personne.php')?'active':'' ?>">
            <i class="fa fa-user-plus"></i> Ajouter personne
        </a>
        <a href="/PfeCnma/cnma/crma/ajouter_assure.php" class="<?= ($current=='ajouter_assure.php')?'active':'' ?>">
            <i class="fa fa-id-card"></i> Ajouter assuré
        </a>
        <a href="/PfeCnma/cnma/crma/creer_compte_assure.php" class="<?= ($current=='creer_compte_assure.php')?'active':'' ?>">
            <i class="fa fa-user-lock"></i> Créer compte assuré
        </a>
        <a href="/PfeCnma/cnma/crma/ajouter_vehicule.php" class="<?= ($current=='ajouter_vehicule.php')?'active':'' ?>">
            <i class="fa fa-car"></i> Ajouter véhicule
        </a>
        <a href="/PfeCnma/cnma/crma/ajouter_contrat.php" class="<?= ($current=='ajouter_contrat.php')?'active':'' ?>">
            <i class="fa fa-file-contract"></i> Ajouter contrat
        </a>
        <a href="/PfeCnma/cnma/crma/ajouter_tiers.php" class="<?= ($current=='ajouter_tiers.php')?'active':'' ?>">
            <i class="fa fa-users"></i> Ajouter tiers
        </a>

        <div class="nav-section">Sinistres</div>

        <a href="/PfeCnma/cnma/crma/creer_dossier.php" class="<?= ($current=='creer_dossier.php')?'active':'' ?>">
            <i class="fa fa-folder-plus"></i> Créer dossier
        </a>
        <a href="/PfeCnma/cnma/crma/mes_dossiers.php" class="<?= ($current=='mes_dossiers.php')?'active':'' ?>">
            <i class="fa fa-folder-open"></i> Mes dossiers
        </a>

        <div class="nav-section">Alertes</div>

        <a href="/PfeCnma/cnma/crma/notifications.php" class="<?= ($current=='notifications.php')?'active':'' ?>" style="position:relative;">
            <i class="fa fa-bell"></i> Notifications
            <?php if($nb_notifs > 0): ?>
            <span class="notif-badge"><?= $nb_notifs; ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/PfeCnma/cnma/pages/logout.php">
            <i class="fa fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>