<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


?>
<div class="header">
    <div>
        <h3>Gestion des sinistres</h3>
    </div>

    <div class="header-right">
        <i class="fa fa-user"></i> <?php echo $_SESSION['nom']; ?>
        | <?php echo $_SESSION['nom_agence']; ?>
        | <?php echo $_SESSION['wilaya']; ?>
    </div>
</div>