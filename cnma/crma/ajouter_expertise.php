<?php
// ============================================================
// ajouter_expertise.php — Simplifié
// ❌ Suppression de la création automatique de réserve
// ❌ Suppression de la logique de seuil automatique
// L'expertise enregistre uniquement les données de l'expert
// L'état du dossier est géré manuellement par l'utilisateur
// ============================================================
session_start();
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: mes_dossiers.php"); exit();
}

$id_dossier     = intval($_POST['id_dossier']);
$id_expert      = intval($_POST['id_expert']);
$date_expertise = mysqli_real_escape_string($conn, $_POST['date_expertise']);
$montant        = floatval($_POST['montant_indemnite']);
$commentaire    = mysqli_real_escape_string($conn, $_POST['commentaire'] ?? '');
$user_id        = $_SESSION['id_user'];

// Upload fichier
$rapport = '';
if (isset($_FILES['rapport']) && $_FILES['rapport']['name'] != '') {
    $rapport = $_FILES['rapport']['name'];
    move_uploaded_file($_FILES['rapport']['tmp_name'], "../uploads/" . $rapport);
}

// 1. Insérer l'expertise (sans créer de réserve)
mysqli_query($conn, "
    INSERT INTO expertise (id_dossier, id_expert, date_expertise, rapport_pdf, montant_indemnite, commentaire)
    VALUES ($id_dossier, $id_expert, '$date_expertise', '$rapport', $montant, '$commentaire')
");

// 2. Mettre à jour l'expert du dossier
mysqli_query($conn, "UPDATE dossier SET id_expert = $id_expert WHERE id_dossier = $id_dossier");

// 3. Récupérer l'état actuel pour l'historique
$ancien_etat = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier"))['id_etat'];

// 4. Historique
mysqli_query($conn, "
    INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
    VALUES ($id_dossier, 'Ajout expertise', NOW(), $user_id, $ancien_etat, $ancien_etat)
");

header("Location: voir_dossier.php?id=$id_dossier&tab=expertise&added=1");
exit();