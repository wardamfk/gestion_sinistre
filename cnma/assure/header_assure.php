<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('assure');
?>
<div class="assure-header">
    <div class="assure-header-left">
        <button type="button" class="assure-sidebar-toggle" aria-label="Ouvrir le menu" aria-controls="assure-sidebar" aria-expanded="false">
            <i class="fa fa-bars" aria-hidden="true"></i>
        </button>
        <div class="page-title"></div>
    </div>
    <div class="user-info">
        <div class="av"><i class="fa fa-user" style="font-size:13px;"></i></div>
        <?= htmlspecialchars($_SESSION['nom'] ?? 'Assuré'); ?>
        <span style="color:#90a4ae;font-weight:400;">| CNMA</span>
    </div>
</div>
<script>
(() => {
    const body = document.body;
    const toggle = document.querySelector('.assure-sidebar-toggle');
    const sidebar = document.querySelector('.assure-sidebar');
    const overlay = document.querySelector('[data-assure-sidebar-overlay]');
    if (!toggle || !sidebar || !overlay) return;

    const setOpen = (open) => {
        body.classList.toggle('assure-sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

    toggle.addEventListener('click', () => {
        if (!isMobile()) return;
        setOpen(!body.classList.contains('assure-sidebar-open'));
    });

    overlay.addEventListener('click', () => setOpen(false));

    sidebar.addEventListener('click', (e) => {
        const a = e.target && e.target.closest ? e.target.closest('a') : null;
        if (a && isMobile()) setOpen(false);
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });

    window.addEventListener('resize', () => {
        if (!isMobile()) setOpen(false);
    });
})();
</script>
