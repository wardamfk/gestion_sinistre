<?php
session_start();
include '../includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_dossier = $_POST['id_dossier'];
    $id_expert = $_POST['id_expert'];
    $date_expertise = $_POST['date_expertise'];
    $montant = $_POST['montant_indemnite'];
    $commentaire = $_POST['commentaire'];
    $user_id = $_SESSION['id_user'];

    // Upload fichier
    $rapport = $_FILES['rapport']['name'];
    $tmp = $_FILES['rapport']['tmp_name'];
    move_uploaded_file($tmp, "../uploads/" . $rapport);

    /* ================= 1. Ajouter expertise ================= */
    mysqli_query($conn, "INSERT INTO expertise 
    (id_dossier, id_expert, date_expertise, rapport_pdf, montant_indemnite, commentaire)
    VALUES 
    ('$id_dossier', '$id_expert', '$date_expertise', '$rapport', '$montant', '$commentaire')");

    /* ================= 2. Mettre expert dans dossier ================= */
    mysqli_query($conn, "UPDATE dossier 
    SET id_expert = '$id_expert'
    WHERE id_dossier = $id_dossier");

// Récupérer garantie du dossier (exemple RC)
$result_garantie = mysqli_query($conn, "
SELECT id_garantie 
FROM reserve 
WHERE id_dossier = $id_dossier 
LIMIT 1
");

$row_garantie = mysqli_fetch_assoc($result_garantie);
$id_garantie = $row_garantie['id_garantie'];

/* ================= 3. Ajouter réserve expertise ================= */
mysqli_query($conn, "INSERT INTO reserve
(id_dossier, id_garantie, montant, date_reserve, type_reserve, cree_par, date_creation, commentaire)
VALUES
('$id_dossier', '$id_garantie', '$montant', CURDATE(), 'expertise', '$user_id', NOW(), 'Réserve après expertise')");

/* ================= 4. Recalcul total réserve ================= */
$result = mysqli_query($conn, "
SELECT SUM(montant) as total 
FROM reserve 
WHERE id_dossier = $id_dossier
");

$row = mysqli_fetch_assoc($result);
$total_reserve = $row['total'];

mysqli_query($conn, "UPDATE dossier 
SET total_reserve = '$total_reserve'
WHERE id_dossier = $id_dossier");

    /* ================= 5. Vérifier seuil ================= */
   /* ================= Vérifier seuil ================= */
// Récupérer seuil CRMA
$result_seuil = mysqli_query($conn, "
SELECT montant_max 
FROM seuil_validation 
WHERE niveau_validation = 'Gestionnaire'
");

$row_seuil = mysqli_fetch_assoc($result_seuil);
$seuil_max = $row_seuil['montant_max'];

// Vérifier seuil
if ($total_reserve > $seuil_max) {
    $id_etat = 3; // Transmis CNMA
    $statut = 'en_attente';
    $action = "Expertise + Transmission CNMA";
} else {
    $id_etat = 2; // En cours CRMA
    $statut = 'valide';
    $action = "Expertise validée CRMA";
}
// Récupérer ancien état
$result_old = mysqli_query($conn, "SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier");
$row_old = mysqli_fetch_assoc($result_old);
$ancien_etat = $row_old['id_etat'];
    /* ================= 6. Update dossier ================= */
    mysqli_query($conn, "UPDATE dossier 
    SET id_etat = '$id_etat', statut_validation = '$statut'
    WHERE id_dossier = $id_dossier");

    /* ================= 7. Historique ================= */
  mysqli_query($conn, "INSERT INTO historique 
(id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
VALUES 
('$id_dossier', '$action', NOW(), '$user_id', '$ancien_etat', '$id_etat')");

    header("Location: voir_dossier.php?id=".$id_dossier."#expertise");
    exit();
}
?>