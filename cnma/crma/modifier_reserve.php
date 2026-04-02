<?php
session_start();
include('../includes/config.php');

if(!isset($_GET['id'])){
    echo "Réserve introuvable";
    exit();
}

$id = $_GET['id'];

$reserve = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT * FROM reserve WHERE id_reserve = $id
"));
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

    header("Location: voir_dossier.php?id=".$id_dossier."&tab=reserves&updated=1");
    exit();
}
?>

<!DOCTYPE html>

<html>
<head>
    <meta charset="UTF-8">
    <title>Modifier réserve</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="main">
    <h2>Modifier réserve</h2>

```
<form method="POST" class="form">
    <label>Montant</label>
    <input type="number" name="montant" value="<?php echo $reserve['montant']; ?>" required>

    <label>Commentaire</label>
    <input type="text" name="commentaire" value="<?php echo $reserve['commentaire']; ?>">

    <button type="submit" name="modifier" class="btn">Enregistrer</button>
</form>
```

</div>

</body>
</html>
