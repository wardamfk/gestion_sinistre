<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
include('../includes/config.php');

$id_agence = $_SESSION['id_agence'] ?? 0;

$res = mysqli_query($conn,"
SELECT
    d.id_dossier,
    d.numero_dossier,
    COUNT(*) AS nb_cheques,
    IFNULL(SUM(r.montant),0) AS montant_total,
    MAX(r.id_reglement) AS last_reg
FROM reglement r
JOIN dossier d ON r.id_dossier = d.id_dossier
JOIN utilisateur u ON d.cree_par = u.id_user
WHERE r.statut='disponible'
  AND u.id_agence = '$id_agence'
GROUP BY d.id_dossier
ORDER BY last_reg DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Chèques à remettre</title>
<link rel="stylesheet" href="../css/style_crma.css">
</head>
<body>

<h2 style="margin:20px;">Règlements disponibles à remettre (par dossier)</h2>

<table class="crma-table">
<thead>
<tr>
<th>Dossier</th>
<th>Nb</th>
<th>Total</th>
<th>Action</th>
</tr>
</thead>

<tbody>
<?php while($row = mysqli_fetch_assoc($res)): ?>
<tr>
    <td><?= $row['numero_dossier'] ?></td>
    <td style="width:80px;"><?= intval($row['nb_cheques']) ?></td>
    <td><?= number_format($row['montant_total'],2,',',' ') ?> DA</td>
    <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="voir_dossier.php?id=<?= $row['id_dossier'] ?>&tab=reglements"
               class="btn btn-primary btn-xs">
               Voir
            </a>
            <a href="confirmer_remise_totale.php?id=<?= $row['id_dossier'] ?>"
               onclick="return confirm('Confirmer la remise totale des règlements disponibles de ce dossier ? (tous les chèques disponibles passeront à REMIS)');"
               class="btn btn-success btn-xs">
               Remis
            </a>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</tbody>

</table>

</body>
</html>
