<?php
include('../includes/config.php');

$num = mysqli_real_escape_string($conn, $_GET['num'] ?? '');

$res = mysqli_query($conn, "SELECT id_assure FROM assure WHERE num_permis='$num'");
$exists = mysqli_num_rows($res) > 0;

echo json_encode(['exists' => $exists]);