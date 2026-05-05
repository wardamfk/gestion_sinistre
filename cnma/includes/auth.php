<?php
require_once __DIR__ . '/session.php';
pfe_session_start();

if(!isset($_SESSION['id_user'])) {
    header("Location: ../pages/login.php");
    exit();
}
?>
