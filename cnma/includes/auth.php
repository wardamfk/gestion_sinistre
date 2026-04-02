<?php
session_start();

if(!isset($_SESSION['id_user'])) {
    header("Location: ../pages/login.php");
    exit();
}
?>