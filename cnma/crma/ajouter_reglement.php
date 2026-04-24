<?php
// ============================================================
// ajouter_reglement.php — Logique simplifiée
// Règle métier :
//   règlement ≤ réserve  → réserve = réserve - règlement (partiel)
//   règlement > réserve  → créer nouvelle réserve = règlement - réserve
//                          puis état = Règlement définitif amiable (8)
// ============================================================
session_start();
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: mes_dossiers.php"); exit();
}

$id_dossier  = intval($_POST['id_dossier']);
$montant     = floatval($_POST['montant']);
$mode        = mysqli_real_escape_string($conn, $_POST['mode']);
$commentaire = mysqli_real_escape_string($conn, $_POST['commentaire'] ?? '');
$user_id     = $_SESSION['id_user'];

try {
    $conn->begin_transaction();

    // 1. Récupérer état actuel et réserve totale
    $dossier = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_etat, total_reserve FROM dossier WHERE id_dossier = $id_dossier"));
    $ancien_etat  = $dossier['id_etat'];
    $reserve_actuelle = floatval($dossier['total_reserve']);

    // 2. Insérer le règlement
    $stmt = $conn->prepare(
        "INSERT INTO reglement (id_dossier, montant, mode_paiement, date_reglement, saisi_par, commentaire)
         VALUES (?, ?, ?, CURDATE(), ?, ?)");
    $stmt->bind_param("idsss", $id_dossier, $montant, $mode, $user_id, $commentaire);
    $stmt->execute();

    // 3. Appliquer la règle métier sur la réserve
    if ($montant <= $reserve_actuelle) {
        // Règlement partiel : déduire de la réserve
        $nouvelle_reserve = $reserve_actuelle - $montant;
        $nouvel_etat = 7; // Règlement partiel
        $action = "Règlement partiel";

        $conn->query(
            "UPDATE dossier SET total_reserve = $nouvelle_reserve, id_etat = $nouvel_etat
             WHERE id_dossier = $id_dossier");

    } else {
        // Règlement dépasse la réserve → créer une réserve complémentaire
        $complement = $montant - $reserve_actuelle;

        // Trouver la garantie RC par défaut (id_garantie = 1)
        $id_garantie = 1;
        $r = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id_garantie FROM reserve WHERE id_dossier = $id_dossier ORDER BY id_reserve LIMIT 1"));
        if ($r) $id_garantie = $r['id_garantie'];

        // Créer la réserve complémentaire
        $comm_res = mysqli_real_escape_string($conn, "Réserve complémentaire auto (règlement > réserve)");
        $conn->query(
            "INSERT INTO reserve (id_dossier, id_garantie, montant, date_reserve, type_reserve, cree_par, date_creation, commentaire)
             VALUES ($id_dossier, $id_garantie, $complement, CURDATE(), 'ajustement', $user_id, CURDATE(), '$comm_res')");

        // La nouvelle réserve totale = montant du règlement
        $nouvelle_reserve = $montant;
        $nouvel_etat = 8; // Règlement définitif amiable
        $action = "Règlement définitif amiable";

        $conn->query(
            "UPDATE dossier SET total_reserve = $nouvelle_reserve, id_etat = $nouvel_etat,
                                statut_validation = 'valide', date_validation = CURDATE()
             WHERE id_dossier = $id_dossier");
    }

    // 4. Historique
    $stmt_h = $conn->prepare(
        "INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
         VALUES (?, ?, NOW(), ?, ?, ?)");
    $stmt_h->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_h->execute();

    $conn->commit();
    header("Location: voir_dossier.php?id=$id_dossier&tab=reglements&added=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Erreur : " . htmlspecialchars($e->getMessage());
}