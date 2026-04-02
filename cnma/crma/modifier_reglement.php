<?php
session_start();
include('../includes/config.php');

if(!isset($_GET['id'])){
    echo "Règlement introuvable";
    exit();
}

$id = $_GET['id'];

$reglement = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT * FROM reglement WHERE id_reglement = $id
"));
if(isset($_POST['modifier'])){
    $montant = $_POST['montant'];
    $mode = $_POST['mode'];
    $commentaire = $_POST['commentaire'];
    $id_dossier = $reglement['id_dossier'];
    $user_id = $_SESSION['id_user'];

    // 1. Ancien état
    $sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $id_dossier);
    $stmt_old->execute();
    $ancien_etat = $stmt_old->get_result()->fetch_assoc()['id_etat'];

    // 2. Modifier règlement
    $sql_update = "UPDATE reglement 
                   SET montant = ?, mode_paiement = ?, commentaire = ?
                   WHERE id_reglement = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dssi", $montant, $mode, $commentaire, $id);
    $stmt_update->execute();

    // 3. Nouvel état = même état
    $nouvel_etat = $ancien_etat;
    $action = "Modification règlement";

    // 4. Historique
    $sql_hist = "INSERT INTO historique 
                (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
                VALUES (?, ?, NOW(), ?, ?, ?)";
    $stmt_hist = $conn->prepare($sql_hist);
    $stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_hist->execute();

    header("Location: voir_dossier.php?id=".$id_dossier."&tab=reglements&updated=1");
    exit();
}
?>

<!DOCTYPE html>

<html>
<head>
    <meta charset="UTF-8">
    <title>Modifier règlement</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="main">
    <h2>Modifier règlement</h2>

```
<form method="POST" class="form">
    <label>Montant</label>
    <input type="number" name="montant" value="<?php echo $reglement['montant']; ?>" required>

    <label>Mode de paiement</label>
    <select name="mode">
        <option <?php if($reglement['mode_paiement']=="Chèque") echo "selected"; ?>>Chèque</option>
        <option <?php if($reglement['mode_paiement']=="Virement") echo "selected"; ?>>Virement</option>
        <option <?php if($reglement['mode_paiement']=="Espèces") echo "selected"; ?>>Espèces</option>
    </select>

    <label>Commentaire</label>
    <input type="text" name="commentaire" value="<?php echo $reglement['commentaire']; ?>">

    <button type="submit" name="modifier" class="btn">Enregistrer</button>
    <a href="voir_dossier.php?id=<?php echo $reglement['id_dossier']; ?>&tab=reglements" class="btn-retour">
    Retour au dossier
</a>
</form>
```

</div>

</body>
</html>
