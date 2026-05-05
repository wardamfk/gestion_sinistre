<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
include('../includes/config.php');

$res = mysqli_query($conn,"
SELECT r.id_reglement, r.montant, r.statut, d.numero_dossier, d.id_dossier
FROM reglement r
JOIN dossier d ON r.id_dossier = d.id_dossier
WHERE r.statut='disponible'
ORDER BY r.id_reglement DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Chèques à remettre</title>
<link rel="stylesheet" href="../css/style_crma.css">
</head>
<body>

<h2 style="margin:20px;">Chèques disponibles à remettre</h2>

<table class="crma-table">
<thead>
<tr>
<th>Dossier</th>
<th>Montant</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php while($row = mysqli_fetch_assoc($res)): ?>
<tr>
    <td><?= $row['numero_dossier'] ?></td>
    <td><?= number_format($row['montant'],2,',',' ') ?> DA</td>
    <td>
        <a href="voir_dossier.php?id=<?= $row['id_dossier'] ?>&tab=reglements"
           class="btn btn-primary btn-xs">
           Voir
        </a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>

</table>

</body>
</html>
