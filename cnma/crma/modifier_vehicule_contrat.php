<?php
include('../includes/auth.php');
include('../includes/config.php');

if ($_SESSION['role'] != 'CRMA') {
    exit();
}

$id = intval($_GET['id'] ?? 0);

// récupérer véhicule
$v = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM vehicule WHERE id_vehicule = $id"
));

if (!$v) {
    die("Véhicule introuvable");
}

$error = '';

if (isset($_POST['modifier'])) {

    $marque      = mysqli_real_escape_string($conn, $_POST['marque']);
    $modele      = mysqli_real_escape_string($conn, $_POST['modele']);
    $couleur     = mysqli_real_escape_string($conn, $_POST['couleur']);
    $nb_places   = intval($_POST['nombre_places']);
    $matricule   = mysqli_real_escape_string($conn, strtoupper(trim($_POST['matricule'])));
    $chassis     = mysqli_real_escape_string($conn, trim($_POST['numero_chassis']));
    $serie       = mysqli_real_escape_string($conn, trim($_POST['numero_serie']));
    $annee       = intval($_POST['annee']);
    $type        = mysqli_real_escape_string($conn, $_POST['type']);
    $carrosserie = mysqli_real_escape_string($conn, $_POST['carrosserie']);

    // CHECK matricule
    $check_mat = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_vehicule FROM vehicule 
         WHERE matricule='$matricule' AND id_vehicule != $id"
    ));

    if ($check_mat) {
        $error = "Matricule déjà utilisée.";
    }

    // CHECK chassis
    if (empty($error) && $chassis != '') {
        $check_ch = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id_vehicule FROM vehicule 
             WHERE numero_chassis='$chassis' AND id_vehicule != $id"
        ));

        if ($check_ch) {
            $error = "Numéro de châssis déjà utilisé.";
        }
    }

    // UPDATE
    if (empty($error)) {
        mysqli_query($conn, "UPDATE vehicule SET
            marque='$marque',
            modele='$modele',
            couleur='$couleur',
            nombre_places=$nb_places,
            matricule='$matricule',
            numero_chassis='$chassis',
            numero_serie='$serie',
            annee=$annee,
            type='$type',
            carrosserie='$carrosserie'
            WHERE id_vehicule=$id
        ");

    echo "<script>
    window.parent.closeEditVehicule();
    window.parent.location.reload();
</script>";
        exit();
    }
}
?>
<style>
body {
    font-family: 'Segoe UI';
    padding: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

input, select {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

button {
    margin-top: 20px;
    padding: 12px;
    border: none;
    background: #16a34a;
    color: white;
    border-radius: 8px;
    cursor: pointer;
}
</style>

<form method="POST">

<div class="form-grid">

<div>
<label>Marque</label>
<input type="text" name="marque" value="<?= $v['marque'] ?>" required>
</div>

<div>
<label>Modèle</label>
<input type="text" name="modele" value="<?= $v['modele'] ?>" required>
</div>

<div>
<label>Couleur</label>
<input type="text" name="couleur" value="<?= $v['couleur'] ?>">
</div>

<div>
<label>Matricule</label>
<input type="text" name="matricule" value="<?= $v['matricule'] ?>" required>
</div>

<div>
<label>Année</label>
<input type="number" name="annee" value="<?= $v['annee'] ?>">
</div>

<div>
<label>Nombre de places</label>
<input type="number" name="nombre_places" value="<?= $v['nombre_places'] ?>">
</div>

<div>
<label>Type</label>
<select name="type">
    <option value="Tourisme" <?= $v['type']=='Tourisme'?'selected':'' ?>>Tourisme</option>
    <option value="Utilitaire" <?= $v['type']=='Utilitaire'?'selected':'' ?>>Utilitaire</option>
    <option value="Camion" <?= $v['type']=='Camion'?'selected':'' ?>>Camion</option>
    <option value="Bus" <?= $v['type']=='Bus'?'selected':'' ?>>Bus</option>
    <option value="Moto" <?= $v['type']=='Moto'?'selected':'' ?>>Moto</option>
    <option value="Agricole" <?= $v['type']=='Agricole'?'selected':'' ?>>Agricole</option>
</select>
</div>

<div>
<label>Carrosserie</label>
<select name="carrosserie">
    <option value="Berline" <?= $v['carrosserie']=='Berline'?'selected':'' ?>>Berline</option>
    <option value="Hatchback" <?= $v['carrosserie']=='Hatchback'?'selected':'' ?>>Hatchback</option>
    <option value="SUV" <?= $v['carrosserie']=='SUV'?'selected':'' ?>>SUV</option>
    <option value="Pick-up" <?= $v['carrosserie']=='Pick-up'?'selected':'' ?>>Pick-up</option>
    <option value="Fourgon" <?= $v['carrosserie']=='Fourgon'?'selected':'' ?>>Fourgon</option>
    <option value="Camion" <?= $v['carrosserie']=='Camion'?'selected':'' ?>>Camion</option>
</select>
</div>

<div>
<label>Numéro de châssis</label>
<input type="text" name="numero_chassis" value="<?= $v['numero_chassis'] ?>">
</div>

<div>
<label>Numéro de série</label>
<input type="text" name="numero_serie" value="<?= $v['numero_serie'] ?>">
</div>

</div>

<button type="submit" name="modifier">Enregistrer</button>

<?php if (!empty($error)): ?>
<div style="color:red; margin-top:10px;">
    <?= $error ?>
</div>
<?php endif; ?>

</form>