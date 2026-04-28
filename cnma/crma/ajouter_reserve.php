<?php
// ============================================================
// ajouter_reserve.php — Simplifié
// ❌ Suppression de la logique de seuil automatique (CNMA)
// ❌ Suppression du changement automatique d'état
// La réserve est ajoutée et le total est mis à jour
// L'état du dossier est géré manuellement
// ============================================================
session_start();
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: mes_dossiers.php"); exit();
}

$id_dossier  = intval($_POST['id_dossier']);
$id_garantie = intval($_POST['id_garantie']);
$montant     = floatval($_POST['montant']);
$commentaire = mysqli_real_escape_string($conn, $_POST['commentaire'] ?? '');
$user_id     = $_SESSION['id_user'];

try {
    $conn->begin_transaction();

    // 1. Insérer la réserve
    $stmt = $conn->prepare(
        "INSERT INTO reserve (id_dossier, id_garantie, montant, date_reserve, type_reserve, cree_par, date_creation, commentaire)
         VALUES (?, ?, ?, CURDATE(), 'ajustement', ?, CURDATE(), ?)");
    $stmt->bind_param("iidis", $id_dossier, $id_garantie, $montant, $user_id, $commentaire);
    $stmt->execute();

    // 2. Recalculer et mettre à jour le total_reserve
    $res = $conn->query("SELECT SUM(montant) as total FROM reserve WHERE id_dossier = $id_dossier AND statut = 'actif'");
    $total = $res->fetch_assoc()['total'] ?? 0;

   

    // 3. Historique (sans changement d'état)
    $ancien_etat = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier"))['id_etat'];

    $stmt_h = $conn->prepare(
        "INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
         VALUES (?, 'Ajout réserve', NOW(), ?, ?, ?)");
    $stmt_h->bind_param("iiii", $id_dossier, $user_id, $ancien_etat, $ancien_etat);
    $stmt_h->execute();

    $conn->commit();
    header("Location: voir_dossier.php?id=$id_dossier&tab=reserves&added=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Erreur : " . htmlspecialchars($e->getMessage());
}