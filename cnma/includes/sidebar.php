<?php
// sidebar.php - CRMA
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn)) { include __DIR__ . '/config.php'; }

$base = '/PfeCnma/cnma';
$current = basename($_SERVER['PHP_SELF']);
$nb_notifs = 0;

if (!empty($_SESSION['id_user'])) {
    $id_user_sidebar = intval($_SESSION['id_user']);
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as n FROM notification WHERE id_destinataire=$id_user_sidebar AND lu=0"));
    $nb_notifs = $r ? (int)$r['n'] : 0;
}

function nav_link(string $href, string $icon, string $label, string $current, int $badge = 0, array $also_active = []): void {
    $target = basename(parse_url($href, PHP_URL_PATH));
    $active_files = array_merge([$target], $also_active);
    $active = in_array($current, $active_files, true) ? 'active' : '';
    $safe_label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    echo "<a href=\"$href\" class=\"sidebar-link $active\" title=\"$safe_label\">";
    echo "<span class=\"sidebar-link-icon\"><i class=\"fa $icon\"></i></span>";
    echo "<span class=\"sidebar-link-label\">$safe_label</span>";
    if ($badge > 0) {
        echo "<span class=\"nb\" data-sidebar-notif-badge data-count=\"$badge\">$badge</span>";
    }
    echo "</a>";
}
?>
<link rel="stylesheet" href="<?= $base ?>/css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="sidebar">
 <div class="sidebar-brand centered">
    <img src="<?= $base ?>/images/logo.webp" alt="CNMA">

    <h2>CRMA</h2>

    <div class="badge-role">Gestion des sinistres</div>

    <button type="button" class="sidebar-toggle">
        <i class="fa fa-bars"></i>
    </button>
</div>

   <!-- Tableau de bord -->
<div class="sidebar-section">
    <div class="sidebar-section-label">Tableau de bord</div>
    <nav class="sidebar-nav">
        <?php nav_link("$base/crma/dashboard_crma.php", 'fa-chart-pie', 'Tableau de bord', $current); ?>
    </nav>
</div>

<!-- Production -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span>Production</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">
        <?php nav_link("$base/crma/gerer_contrats.php", 'fa-file-contract', 'Contrats', $current); ?>
        <?php nav_link("$base/crma/gerer_assures.php", 'fa-id-card', 'Assures', $current); ?>
        <?php nav_link("$base/crma/gerer_experts.php", 'fa-user-tie', 'Experts', $current); ?>
        <?php nav_link("$base/crma/gerer_tiers.php", 'fa-car-burst', 'Tiers adverses', $current); ?>
    </nav>
</div>

<!-- Sinistres -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span>Sinistres</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">
        <?php nav_link("$base/crma/mes_dossiers.php", 'fa-folder-open', 'Mes dossiers', $current); ?>
        <?php nav_link("$base/crma/creer_dossier.php", 'fa-folder-plus', 'Nouveau dossier', $current); ?>
    </nav>
</div>

<!-- Alertes -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span>Alertes</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">
        <?php nav_link("$base/crma/notifications.php", 'fa-bell', 'Notifications', $current, $nb_notifs); ?>
    </nav>
</div>

<!-- Compte -->
<div class="sidebar-section">
    <div class="sidebar-section-label toggle-section">
        <span>Compte</span>
        <i class="fa fa-chevron-down arrow"></i>
    </div>

    <nav class="sidebar-nav sub-menu">
        <?php nav_link("$base/crma/profil_crma.php", 'fa-user', 'Profil', $current); ?>
    </nav>
</div>

    <div class="sidebar-footer">
        <a href="<?= $base ?>/pages/logout.php" class="sidebar-link logout-link" title="Deconnexion">
            <span class="sidebar-link-icon"><i class="fa fa-right-from-bracket"></i></span>
            <span class="sidebar-link-label">Deconnexion</span>
        </a>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-section').forEach(section => {
    section.addEventListener('click', () => {

        const menu = section.nextElementSibling;
        const isOpen = menu.classList.contains('open');

        // reset total
        document.querySelectorAll('.sub-menu').forEach(m => m.classList.remove('open'));
        document.querySelectorAll('.toggle-section').forEach(s => s.classList.remove('active'));

        // ouvrir si fermé
        if (!isOpen) {
            menu.classList.add('open');
            section.classList.add('active');
        }
    });
});

// 🔥 IMPORTANT : garder ouvert selon page
document.querySelectorAll('.sidebar-link.active').forEach(link => {
    const menu = link.closest('.sub-menu');
    const section = menu?.previousElementSibling;

    if (menu) menu.classList.add('open');
    if (section) section.classList.add('active');
});
(function () {
    const toggle = document.querySelector('.sidebar-toggle');
    const badge = document.querySelector('[data-sidebar-notif-badge]');
    const storageKey = 'crma-sidebar-collapsed';

    if (localStorage.getItem(storageKey) === '1') {
        document.documentElement.classList.add('sidebar-collapsed');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            document.documentElement.classList.toggle('sidebar-collapsed');
            localStorage.setItem(storageKey, document.documentElement.classList.contains('sidebar-collapsed') ? '1' : '0');
        });
    }

    async function refreshNotifBadge() {
        if (!badge) return;
        try {
            const resp = await fetch('<?= $base ?>/crma/notification_count.php', { cache: 'no-store' });
            const data = await resp.json();
            const count = Number(data.count || 0);
            badge.textContent = count;
            badge.dataset.count = String(count);
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        } catch (e) {}
    }

    refreshNotifBadge();
    setInterval(refreshNotifBadge, 30000);
})();
</script>
   <i class="fa fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>