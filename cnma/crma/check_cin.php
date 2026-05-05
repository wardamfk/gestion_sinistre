<?php
include('../includes/config.php');

header('Content-Type: application/json');

$cin = trim($_GET['cin'] ?? '');

if (empty($cin)) {
    echo json_encode(['exists' => false]);
    exit;
}

// normalisation (optionnel mais recommandé)
$cin = strtoupper($cin);

$cin = mysqli_real_escape_string($conn, $cin);

$res = mysqli_query($conn, "SELECT id_personne FROM personne WHERE num_identite='$cin'");

echo json_encode([
    'exists' => mysqli_num_rows($res) > 0
]);