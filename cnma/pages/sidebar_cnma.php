<?php
// Compter notifications non lues (pour admin CNMA — en tant qu'expéditeur on n'en reçoit pas)
// Récupérer dossiers en attente
$nb_attente = 0;
if(isset($conn)) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE id_etat = 3"));
    $nb_attente = $r['n'];
}

// Page courante pour active
$current = basename($_SERVER['PHP_SELF']);?>

<div class="sidebar cnma-sidebar" id="cnma-sidebar">
 
<div class="sidebar-brand centered">
    <img src="/PfeCnma/cnma/images/logo.webp">
  
    <h2>CNMA</h2>
    <div class="badge-role">Administration</div>

    <button class="sidebar-toggle">
        <i class="fa fa-bars"></i>
    </button>
</div>

<!-- Dashboard -->
<div class="sidebar-section">
    <nav class="sidebar-nav">
        <a href="/PfeCnma/cnma/pages/dashboard_cnma.php"
           class="sidebar-link <?= $current=='dashboard_cnma.php'?'active':'' ?>">
           
            <span class="sidebar-link-icon"><i class="fa fa-chart-pie"></i></span>
            <span class="sidebar-link-label">Tableau de bord</span>
        </a>
    </nav>
</div>

<!-- Gestion -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span><i class="fa fa-folder"></i> Gestion</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">
        <a href="/PfeCnma/cnma/pages/dossiers_attente.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-clock"></i></span>
            <span class="sidebar-link-label">Dossiers attente</span>
        </a>

        <a href="/PfeCnma/cnma/pages/tous_dossiers_cnma.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-folder-open"></i></span>
            <span class="sidebar-link-label">Tous dossiers</span>
        </a>
    </nav>
</div>

<!-- Stat -->
<div class="sidebar-section">
    <nav class="sidebar-nav">
        <a href="/PfeCnma/cnma/pages/statistiques_cnma.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-chart-bar"></i></span>
            <span class="sidebar-link-label">Statistiques</span>
        </a>
    </nav>
</div>

<!-- Compte -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span><i class="fa fa-user"></i> Compte</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">

        <a href="/PfeCnma/cnma/pages/gestion_utilisateurs.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-users"></i></span>
            <span class="sidebar-link-label">Utilisateurs</span>
        </a>

        <a href="/PfeCnma/cnma/pages/profil_cnma.php" class="sidebar-link">
            <span class="sidebar-link-icon"><i class="fa fa-user"></i></span>
            <span class="sidebar-link-label">Profil</span>
        </a>

    </nav>
</div>

<div class="sidebar-footer">
    <a href="/PfeCnma/cnma/pages/logout.php" class="sidebar-link">
        <span class="sidebar-link-icon"><i class="fa fa-right-from-bracket"></i></span>
        <span class="sidebar-link-label">Déconnexion</span>
    </a>
</div>

</div>
<div class="cnma-sidebar-overlay" data-cnma-sidebar-overlay></div>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const desktopToggle = document.querySelector('.cnma-sidebar .sidebar-toggle');
    const sidebar = document.querySelector('.cnma-sidebar');
    const overlay = document.querySelector('[data-cnma-sidebar-overlay]');

    const isMobile = () => window.matchMedia('(max-width: 768px)').matches;
    const getMobileToggle = () => document.querySelector('.cnma-sidebar-toggle');

    const setOpen = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        document.body.classList.toggle('cnma-sidebar-open', open);
        const mt = getMobileToggle();
        if (mt) mt.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const handleToggleClick = () => {
        if (!sidebar) return;
        if (isMobile()) {
            setOpen(!sidebar.classList.contains('open'));
            return;
        }
        document.documentElement.classList.toggle('sidebar-collapsed');
    };

    if (desktopToggle) desktopToggle.addEventListener('click', handleToggleClick);

    document.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.cnma-sidebar-toggle') : null;
        if (!btn || !isMobile()) return;
        handleToggleClick();
    });

    if (overlay) overlay.addEventListener('click', () => setOpen(false));

    if (sidebar) {
        sidebar.addEventListener('click', (e) => {
            const a = e.target && e.target.closest ? e.target.closest('a') : null;
            if (a && isMobile()) setOpen(false);
        });
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });

    window.addEventListener('resize', () => {
        if (!isMobile()) setOpen(false);
    });

    // ===== TOGGLE SECTIONS (Gestion / Compte) =====
    document.querySelectorAll('.toggle-section').forEach(section => {

        section.addEventListener('click', function () {

            const submenu = this.nextElementSibling;

            // toggle affichage propre
            submenu.classList.toggle('show');

            // rotation flèche
            this.classList.toggle('open');

        });

    });

});
</script>
