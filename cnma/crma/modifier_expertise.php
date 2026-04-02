<?php
session_start();
include('../includes/config.php');

if(!isset($_GET['id'])){
    echo "Expertise introuvable";
    exit();
}

$id = $_GET['id'];

$expertise = mysqli_fetch_assoc(mysqli_query($conn, "
SELECT * FROM expertise WHERE id_expertise = $id
"));
if(isset($_POST['modifier'])){
    $montant = $_POST['montant'];
    $commentaire = $_POST['commentaire'];
    $id_dossier = $expertise['id_dossier'];
    $user_id = $_SESSION['id_user'];

    // 1. Ancien état
    $sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $id_dossier);
    $stmt_old->execute();
    $ancien_etat = $stmt_old->get_result()->fetch_assoc()['id_etat'];

    // 2. Modifier expertise
    $sql_update = "UPDATE expertise 
                   SET montant_indemnite = ?, commentaire = ?
                   WHERE id_expertise = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dsi", $montant, $commentaire, $id);
    $stmt_update->execute();

    // 3. Nouvel état = même état
    $nouvel_etat = $ancien_etat;
    $action = "Modification expertise";

    // 4. Historique
    $sql_hist = "INSERT INTO historique 
                (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
                VALUES (?, ?, NOW(), ?, ?, ?)";
    $stmt_hist = $conn->prepare($sql_hist);
    $stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_hist->execute();

    header("Location: voir_dossier.php?id=".$id_dossier."&tab=expertise&updated=1");
    exit();
}
?>

<!DOCTYPE html>

<html>
<head>
    <meta charset="UTF-8">
    <title>Modifier expertise</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="main">
    <h2>Modifier expertise</h2>

```
<form method="POST" class="form">
    <label>Montant indemnité</label>
    <input type="number" name="montant" value="<?php echo $expertise['montant_indemnite']; ?>" required>

    <label>Commentaire</label>
    <input type="text" name="commentaire" value="<?php echo $expertise['commentaire']; ?>">

    <button type="submit" name="modifier" class="btn">Enregistrer</button>
</form>
```

</div>

</body>
</html>
