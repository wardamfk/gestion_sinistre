<?php if(session_status() == PHP_SESSION_NONE) session_start(); ?>
<div class="cnma-header">
    <div class="page-title">
        <?php echo isset($page_title) ? $page_title : 'Gestion des Sinistres'; ?>
    </div>
    <div class="header-right">
        <div class="user-info">
            <div style="background:#1a237e; color:white; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <i class="fa fa-user-shield" style="font-size:13px;"></i>
            </div>
            <?php echo $_SESSION['nom'] ?? 'Admin'; ?>
            <span style="color:#90a4ae; font-weight:400;">| CNMA</span>
        </div>
    </div>
</div>
