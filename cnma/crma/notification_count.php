<?php
session_start();
include '../includes/config.php';

header('Content-Type: application/json');

if (empty($_SESSION['id_user']) || !in_array($_SESSION['role'] ?? '', ['CRMA', 'CNMA'], true)) {
    echo json_encode(['count' => 0]);
    exit();
}

$id_user = intval($_SESSION['id_user']);
$row = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS n
    FROM notification
    WHERE id_destinataire = $id_user AND lu = 0
"));

echo json_encode(['count' => $row ? intval($row['n']) : 0]);
?>
