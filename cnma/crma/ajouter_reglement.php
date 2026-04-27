<?php
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

    // 1. Etat actuel
    $dossier = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier"));

    $ancien_etat = $dossier['id_etat'];

    // 2. INSERT règlement
    $stmt = $conn->prepare(
        "INSERT INTO reglement (id_dossier, montant, mode_paiement, date_reglement, saisi_par, commentaire)
         VALUES (?, ?, ?, CURDATE(), ?, ?)"
    );
    $stmt->bind_param("idsss", $id_dossier, $montant, $mode, $user_id, $commentaire);
    $stmt->execute();

    // 🔥 TOTAL REGLEMENT (cumul)
    $res_regle = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(montant) as total FROM reglement WHERE id_dossier = $id_dossier"));

    $total_regle = floatval($res_regle['total']);

    // 🔥 TOTAL RESERVE ACTUELLE
    $res_reserve = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(montant) as total FROM reserve WHERE id_dossier = $id_dossier AND statut = 'actif'"));

    $reserve_totale = floatval($res_reserve['total']);

    // 🔥 DEPASSEMENT → RESERVE COMPLEMENTAIRE
    if ($total_regle > $reserve_totale) {

        $complement = $total_regle - $reserve_totale;

        // récupérer une garantie existante
        $id_garantie = 1;
        $r = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id_garantie FROM reserve WHERE id_dossier = $id_dossier ORDER BY id_reserve LIMIT 1"));
        if ($r) $id_garantie = $r['id_garantie'];

        $comm_res = "Réserve complémentaire après dépassement règlement";

        mysqli_query($conn,
            "INSERT INTO reserve 
            (id_dossier, id_garantie, montant, date_reserve, type_reserve, cree_par, date_creation, commentaire)
            VALUES 
            ($id_dossier, $id_garantie, $complement, CURDATE(), 'complementaire', $user_id, CURDATE(), '$comm_res')");
    }

    // 🔥 RE-CALCUL RESERVE APRES AJOUT
    $res_reserve = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(montant) as total FROM reserve WHERE id_dossier = $id_dossier AND statut = 'actif'"));

    $reserve_totale = floatval($res_reserve['total']);

    // 🔥 ETAT DOSSIER
    if ($total_regle >= $reserve_totale) {
        $nouvel_etat = 8;
        $action = "Règlement total";
    } else {
        $nouvel_etat = 7;
        $action = "Règlement partiel";
    }

    mysqli_query($conn,
        "UPDATE dossier SET id_etat = $nouvel_etat WHERE id_dossier = $id_dossier");

    // 🔥 HISTORIQUE
    $stmt_h = $conn->prepare(
        "INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
         VALUES (?, ?, NOW(), ?, ?, ?)"
    );
    $stmt_h->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_h->execute();

    $conn->commit();

    header("Location: voir_dossier.php?id=$id_dossier&tab=reglements&added=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Erreur : " . htmlspecialchars($e->getMessage());
}