<?php
include '../includes/config.php';

header('Content-Type: application/json');

$type  = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');

if ($value === '') {
    echo json_encode(["exists" => false]);
    exit;
}

$exists = false;

switch ($type) {

    case 'matricule':
        $stmt = $conn->prepare("SELECT id_vehicule FROM vehicule WHERE matricule = ?");
        break;

    case 'chassis':
        $stmt = $conn->prepare("SELECT id_vehicule FROM vehicule WHERE numero_chassis = ?");
        break;

    case 'police':
        $stmt = $conn->prepare("SELECT id_contrat FROM contrat WHERE numero_police = ?");
        break;

    case 'paiement':
        $stmt = $conn->prepare("SELECT id_reglement FROM reglement WHERE reference_paiement = ?");
        break;

    case 'identite':
        $stmt = $conn->prepare("SELECT id_personne FROM personne WHERE num_identite = ?");
        break;

    case 'email':
        $stmt = $conn->prepare("SELECT id_user FROM utilisateur WHERE email = ?");
        break;

    default:
        echo json_encode(["exists" => false]);
        exit;
        case 'serie':
    $stmt = $conn->prepare("SELECT id_vehicule FROM vehicule WHERE numero_serie = ?");
    break;
}

$stmt->bind_param("s", $value);
$stmt->execute();
$result = $stmt->get_result();

$exists = $result->num_rows > 0;

echo json_encode(["exists" => $exists]);