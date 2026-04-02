<?php
$conn = mysqli_connect("127.0.0.1", "root", "", "gestion_sinistre", 3306);

if (!$conn) {
    die("Connexion échouée");
}
?>