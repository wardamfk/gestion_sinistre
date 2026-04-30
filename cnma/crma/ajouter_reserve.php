<?php
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

    // 1. INSERT RESERVE
    $stmt = $conn->prepare("
        INSERT INTO reserve (id_dossier, id_garantie, montant, date_reserve, type_reserve, cree_par, date_creation, commentaire)
        VALUES (?, ?, ?, CURDATE(), 'ajustement', ?, CURDATE(), ?)
    ");
    $stmt->bind_param("iidis", $id_dossier, $id_garantie, $montant, $user_id, $commentaire);
    $stmt->execute();

    // 2. CALCUL TOTAL
    $res = $conn->query("
        SELECT SUM(montant) as total 
        FROM reserve 
        WHERE id_dossier = $id_dossier AND statut = 'actif'
    ");
    $total = $res->fetch_assoc()['total'] ?? 0;

    // 3. UPDATE TOTAL DOSSIER
    $stmt_upd = $conn->prepare("UPDATE dossier SET total_reserve = ? WHERE id_dossier = ?");
    $stmt_upd->bind_param("di", $total, $id_dossier);
    $stmt_upd->execute();

    // 4. RECUP ETAT ACTUEL
    $res_etat = $conn->query("SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier");
    $ancien_etat = $res_etat->fetch_assoc()['id_etat'];

    $nouvel_etat = $ancien_etat;

    // 5. RECUP SEUIL
    $seuil_res = $conn->query("
        SELECT montant_min 
        FROM seuil_validation 
        WHERE niveau_validation = 'CNMA' 
        LIMIT 1
    ");
    $seuil = $seuil_res->fetch_assoc()['montant_min'] ?? 500000;

    // 6. LOGIQUE SEUIL
    if ($total > $seuil && $ancien_etat != 3) {
        $nouvel_etat = 3;

        $stmt_etat = $conn->prepare("UPDATE dossier SET id_etat = 3 WHERE id_dossier = ?");
        $stmt_etat->bind_param("i", $id_dossier);
        $stmt_etat->execute();

        // historique transmission CNMA
        $stmt_hist = $conn->prepare("
            INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
            VALUES (?, 'Transmission automatique CNMA (seuil dépassé)', NOW(), ?, ?, 3)
        ");
        $stmt_hist->bind_param("iii", $id_dossier, $user_id, $ancien_etat);
        $stmt_hist->execute();
    }

    // 7. HISTORIQUE AJOUT RESERVE
    $stmt_h = $conn->prepare("
        INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
        VALUES (?, 'Ajout réserve', NOW(), ?, ?, ?)
    ");
    $stmt_h->bind_param("iiii", $id_dossier, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_h->execute();

    $conn->commit();

    header("Location: voir_dossier.php?id=$id_dossier&tab=reserves&added=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Erreur : " . htmlspecialchars($e->getMessage());
}