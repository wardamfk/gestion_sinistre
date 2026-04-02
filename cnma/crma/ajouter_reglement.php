<?php
session_start();
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_dossier = $_POST['id_dossier'];
    $montant = $_POST['montant'];
    $mode = $_POST['mode'];
    $commentaire = $_POST['commentaire'];
    $user_id = $_SESSION['id_user'];

    try {
        $conn->begin_transaction();

        // 1. Ajouter règlement
        $sql = "INSERT INTO reglement 
                (id_dossier, montant, mode_paiement, date_reglement, saisi_par, commentaire)
                VALUES (?, ?, ?, CURDATE(), ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if(!$stmt){
            die("Erreur SQL: " . $conn->error);
        }

        $stmt->bind_param("idsss", $id_dossier, $montant, $mode, $user_id, $commentaire);
        $stmt->execute();

        // 2. Récupérer ancien état AVANT modification
        $sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
        $stmt_old = $conn->prepare($sql_old);
        $stmt_old->bind_param("i", $id_dossier);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        $ancien_etat = $result_old->fetch_assoc()['id_etat'];

        // 3. Calcul total réglé
        $sql_regle = "SELECT SUM(montant) as total FROM reglement WHERE id_dossier = ?";
        $stmt_regle = $conn->prepare($sql_regle);
        $stmt_regle->bind_param("i", $id_dossier);
        $stmt_regle->execute();
        $result_regle = $stmt_regle->get_result();
        $total_regle = $result_regle->fetch_assoc()['total'] ?? 0;

        // 4. Calcul total réserve
        $sql_reserve = "SELECT SUM(montant) as total FROM reserve 
                        WHERE id_dossier = ? AND statut = 'actif'";
        $stmt_reserve = $conn->prepare($sql_reserve);
        $stmt_reserve->bind_param("i", $id_dossier);
        $stmt_reserve->execute();
        $result_reserve = $stmt_reserve->get_result();
        $total_reserve = $result_reserve->fetch_assoc()['total'] ?? 0;

        // 5. Déterminer état dossier
        if ($total_regle < $total_reserve) {
            $id_etat = 7;
            $action = "Règlement partiel";
        } else {
            $id_etat = 8;
            $action = "Règlement total";

            // dossier validé automatiquement
            $sql_valide = "UPDATE dossier 
                           SET statut_validation = 'valide',
                               date_validation = CURDATE()
                           WHERE id_dossier = ?";
            $stmt_valide = $conn->prepare($sql_valide);
            $stmt_valide->bind_param("i", $id_dossier);
            $stmt_valide->execute();
        }

        // 6. Update état dossier
        $sql_update = "UPDATE dossier SET id_etat = ? WHERE id_dossier = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $id_etat, $id_dossier);
        $stmt_update->execute();

        // 7. Historique
        $sql_hist = "INSERT INTO historique 
                    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
                    VALUES (?, ?, NOW(), ?, ?, ?)";
        $stmt_hist = $conn->prepare($sql_hist);
        $stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $id_etat);
        $stmt_hist->execute();

        $conn->commit();

        header("Location: voir_dossier.php?id=" . $id_dossier . "&tab=reglements&added=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Erreur : " . $e->getMessage();
    }
}
?>