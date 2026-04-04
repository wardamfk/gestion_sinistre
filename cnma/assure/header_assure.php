<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<div class="assure-header">
    <div class="page-title">
        <?= isset($page_title) ? $page_title : 'Mon Espace Assuré'; ?>
    </div>
    <div class="user-info">
        <div class="av"><i class="fa fa-user" style="font-size:13px;"></i></div>
        <?= htmlspecialchars($_SESSION['nom'] ?? 'Assuré'); ?>
        <span style="color:#90a4ae;font-weight:400;">| CNMA</span>
    </div>
</div>