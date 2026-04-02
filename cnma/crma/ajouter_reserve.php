<?php
session_start();
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_dossier = $_POST['id_dossier'];
    $id_garantie = $_POST['id_garantie'];
    $montant = $_POST['montant'];
    $commentaire = $_POST['commentaire'];
    $user_id = $_SESSION['id_user'];

    try {
        $conn->begin_transaction();

        // 1. Ajouter réserve
        $sql = "INSERT INTO reserve 
                (id_dossier, id_garantie, montant, date_reserve, type_reserve, cree_par, date_creation, commentaire)
                VALUES (?, ?, ?, CURDATE(), 'ajustement', ?, CURDATE(), ?)";

        $stmt = $conn->prepare($sql);
        if(!$stmt){
            die("Erreur SQL: " . $conn->error);
        }

        $stmt->bind_param("iidis", $id_dossier, $id_garantie, $montant, $user_id, $commentaire);
        $stmt->execute();

        // 2. Calcul total réserve
        $sql_total = "SELECT SUM(montant) as total FROM reserve WHERE id_dossier = ?";
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->bind_param("i", $id_dossier);
        $stmt_total->execute();
        $result = $stmt_total->get_result();
        $row = $result->fetch_assoc();
        $total_reserve = $row['total'];

        // 3. Update total_reserve dossier
        $sql_update_total = "UPDATE dossier SET total_reserve = ? WHERE id_dossier = ?";
        $stmt_update_total = $conn->prepare($sql_update_total);
        $stmt_update_total->bind_param("di", $total_reserve, $id_dossier);
        $stmt_update_total->execute();

        // 4. Récupérer ancien état
        $sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
        $stmt_old = $conn->prepare($sql_old);
        $stmt_old->bind_param("i", $id_dossier);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        $ancien_etat = $result_old->fetch_assoc()['id_etat'];

        // 5. Récupérer seuil
        $sql_seuil = "SELECT montant_max FROM seuil_validation WHERE niveau_validation = 'Gestionnaire'";
        $result_seuil = $conn->query($sql_seuil);
        $seuil_max = $result_seuil->fetch_assoc()['montant_max'];

        // 6. Vérifier seuil
        if ($total_reserve >= $seuil_max && $ancien_etat < 3) {

            $nouvel_etat = 3;
            $statut_validation = 'en_attente';
            $action = "Transmission CNMA - Dépassement seuil";

            $sql_dossier = "UPDATE dossier 
                            SET statut_validation = ?, 
                                id_etat = ?, 
                                date_transmission = CURDATE(), 
                                transmis_par = ?
                            WHERE id_dossier = ?";

            $stmt_dossier = $conn->prepare($sql_dossier);
            $stmt_dossier->bind_param("siii", $statut_validation, $nouvel_etat, $user_id, $id_dossier);
            $stmt_dossier->execute();

        } else {
    $nouvel_etat = $ancien_etat;
    $action = "Ajout réserve";

    // AJOUTER CE BLOC
    $sql_statut = "UPDATE dossier 
                   SET statut_validation = 'valide'
                   WHERE id_dossier = ?";
    $stmt_statut = $conn->prepare($sql_statut);
    $stmt_statut->bind_param("i", $id_dossier);
    $stmt_statut->execute();
}

        // 7. Historique
        $sql_hist = "INSERT INTO historique 
                    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
                     VALUES (?, ?, NOW(), ?, ?, ?)";

        $stmt_hist = $conn->prepare($sql_hist);
        $stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
        $stmt_hist->execute();

        $conn->commit();

     header("Location: voir_dossier.php?id=" . $id_dossier . "&tab=reserves&added=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Erreur : " . $e->getMessage();
    }
}
?>