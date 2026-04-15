<?php
include('../includes/config.php');

$cin = mysqli_real_escape_string($conn, $_GET['cin'] ?? '');

$res = mysqli_query($conn, "SELECT id_personne FROM personne WHERE num_identite='$cin'");
$exists = mysqli_num_rows($res) > 0;

echo json_encode(['exists' => $exists]);