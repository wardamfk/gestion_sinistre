<?php
// get_motifs.php — AJAX: retourne les motifs d'un état en JSON
include '../includes/config.php';
header('Content-Type: application/json');

$id_etat = intval($_GET['id_etat'] ?? 0);
if (!$id_etat) { echo json_encode([]); exit(); }

$result = mysqli_query($conn,
    "SELECT id_motif, nom_motif FROM motif WHERE id_etat = $id_etat ORDER BY id_motif");

$motifs = [];
$obligatoire = false;

$etat_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT motif_obligatoire FROM etat_dossier WHERE id_etat = $id_etat"));
if ($etat_row) $obligatoire = (bool)$etat_row['motif_obligatoire'];

while ($row = mysqli_fetch_assoc($result)) {
    $motifs[] = $row;
}

echo json_encode(['motifs' => $motifs, 'obligatoire' => $obligatoire]);
