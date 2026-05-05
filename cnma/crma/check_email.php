<?php
include('../includes/config.php');

header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(["exists" => false]);
    exit;
}

$email = mysqli_real_escape_string($conn, $email);

$res = mysqli_query($conn, "SELECT id_user FROM utilisateur WHERE email='$email'");

echo json_encode([
    "exists" => mysqli_num_rows($res) > 0
]);