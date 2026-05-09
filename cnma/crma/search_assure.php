<?php

include '../includes/config.php';

$search = trim($_GET['q'] ?? '');

if(strlen($search) < 2){
    echo json_encode([]);
    exit;
}

$search = mysqli_real_escape_string($conn, $search);

$sql = "
SELECT
    a.id_assure,
    p.nom,
    p.prenom,
    p.raison_sociale,
  p.num_identite
FROM assure a
JOIN personne p ON p.id_personne = a.id_personne
WHERE (
    p.nom LIKE '%$search%'
    OR p.prenom LIKE '%$search%'
    OR p.raison_sociale LIKE '%$search%'
    OR p.num_identite LIKE '%$search%'
)
ORDER BY
    p.nom ASC,
    p.prenom ASC
LIMIT 20
";

$res = mysqli_query($conn, $sql);

$data = [];

while($row = mysqli_fetch_assoc($res)){

    if(!empty($row['raison_sociale'])){
        $label = $row['raison_sociale'];
    } else {

        $label = trim($row['nom'].' '.$row['prenom']);

      if(!empty($row['num_identite'])){
           $label .= ' — ID: '.$row['num_identite'];
        }
    }

    $data[] = [
        'value' => $row['id_assure'],
        'text' => $label
    ];
}

header('Content-Type: application/json; charset=utf-8');

echo json_encode($data);