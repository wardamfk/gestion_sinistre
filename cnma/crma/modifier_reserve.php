<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
include('../includes/config.php');

if(!isset($_GET['id'])){
    echo "Réserve introuvable";
    exit();
}

$id = $_GET['id'];
$is_modal = isset($_GET['modal']) && $_GET['modal'] === '1';
if ($is_modal) {
    ini_set('display_errors', 0);
}
$reserve = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT * FROM reserve WHERE id_reserve = $id
"));
if (!$reserve) {
    echo "Réserve introuvable";
    exit();
}
if(isset($_POST['modifier'])){
    $montant = $_POST['montant'];
    $commentaire = $_POST['commentaire'];
    $id_dossier = $reserve['id_dossier'];
    $user_id = $_SESSION['id_user'];

    // 1. Ancien état
    $sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $id_dossier);
    $stmt_old->execute();
    $ancien_etat = $stmt_old->get_result()->fetch_assoc()['id_etat'];

    // 2. Modifier réserve
    $sql_update = "UPDATE reserve 
                   SET montant = ?, commentaire = ?
                   WHERE id_reserve = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dsi", $montant, $commentaire, $id);
    $stmt_update->execute();

    // 3. Nouvel état = même état
    $nouvel_etat = $ancien_etat;
    $action = "Modification réserve";

    // 4. Historique
    $sql_hist = "INSERT INTO historique 
                (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
                VALUES (?, ?, NOW(), ?, ?, ?)";
    $stmt_hist = $conn->prepare($sql_hist);
    $stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_hist->execute();

    if ($is_modal) {
        $refresh = "voir_dossier.php?id=".$id_dossier."&tab=reserves&updated=1";
        echo "<div data-modal-success data-refresh=\"".htmlspecialchars($refresh, ENT_QUOTES, 'UTF-8')."\"></div>";
        exit();
    }

    header("Location: voir_dossier.php?id=".$id_dossier."&tab=reserves&updated=1");
    exit();
}
?>

<?php if (!$is_modal): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier réserve</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/style_crma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="main" style="padding-top:16px;">
<?php endif; ?>

<div class="crma-card" style="margin:0;">
    <h4><i class="fa fa-pen"></i> Modifier réserve</h4>
   <form method="POST"
      action="modifier_reserve.php?id=<?php echo $id; ?>">
        <div class="form-group">
            <label>Montant (DA)</label>
            <input type="number" step="1" onwheel="this.blur()" name="montant" value="<?php echo htmlspecialchars($reserve['montant'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <div class="form-group">
            <label>Commentaire</label>
            <input type="text" name="commentaire" value="<?php echo htmlspecialchars($reserve['commentaire'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
            <?php if ($is_modal): ?>
                <button type="button" class="btn btn-outline" data-vd-cancel>Annuler</button>
            <?php else: ?>
                <a class="btn btn-outline" href="voir_dossier.php?id=<?php echo $reserve['id_dossier']; ?>&tab=reserves">Retour</a>
            <?php endif; ?>
            <button type="submit" name="modifier" class="btn btn-primary"><i class="fa fa-check"></i> Enregistrer</button>
        </div>
    </form>
</div>

<?php if (!$is_modal): ?>
</div>
</body>
</html>
<?php endif; ?>
