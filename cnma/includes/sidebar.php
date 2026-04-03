<div class="sidebar">
    <?php if(!isset($nb_notifs)) { $nb_notifs = 0; } ?>
    <h2>CNMA</h2>
<p class="role">Espace CRMA</p>

    <a href="/PfeCnma/cnma/crma/dashboard_crma.php">
        <i class="fa fa-home"></i> Dashboard
    </a>

    <a href="/PfeCnma/cnma/crma/ajouter_personne.php">
        <i class="fa fa-user"></i> Ajouter personne
    </a>

    <a href="/PfeCnma/cnma/crma/ajouter_assure.php">
        <i class="fa fa-id-card"></i> Ajouter assuré
    </a>

    <a href="/PfeCnma/cnma/crma/creer_compte_assure.php">
        <i class="fa fa-user-plus"></i> Créer compte assuré
    </a>

    <a href="/PfeCnma/cnma/crma/ajouter_vehicule.php">
        <i class="fa fa-car"></i> Ajouter véhicule
    </a>

    <a href="/PfeCnma/cnma/crma/ajouter_contrat.php">
        <i class="fa fa-file"></i> Ajouter contrat
    </a>

    <a href="/PfeCnma/cnma/crma/ajouter_tiers.php">
        <i class="fa fa-users"></i> Ajouter tiers
    </a>

    <a href="/PfeCnma/cnma/crma/creer_dossier.php">
        <i class="fa fa-folder"></i> Créer dossier
    </a>

    <a href="/PfeCnma/cnma/crma/mes_dossiers.php">
        <i class="fa fa-folder-open"></i> Mes dossiers
    </a>

    <!-- NOTIFICATIONS avec badge -->
    <a href="/PfeCnma/cnma/crma/notifications.php" style="position:relative;">
        <i class="fa fa-bell"></i> Notifications
        <?php if($nb_notifs > 0): ?>
        <span style="
            position:absolute; right:12px; top:50%; transform:translateY(-50%);
            background:#ef5350; color:white; border-radius:10px;
            padding:1px 7px; font-size:11px; font-weight:bold;">
            <?php echo $nb_notifs; ?>
        </span>
        <?php endif; ?>
    </a>
    <a href="/PfeCnma/cnma/pages/logout.php" class="logout">
        Déconnexion
    </a>
</div>