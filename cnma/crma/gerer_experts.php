<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des experts';
$success = $error = '';

/* ======= AJOUTER ======= */
if (isset($_POST['ajouter'])) {
    $id_personne = intval($_POST['id_personne']);
    $activite    = mysqli_real_escape_string($conn, trim($_POST['activite']));

    // Vérif doublon
    $chk = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_expert FROM expert WHERE id_personne=$id_personne"))['id_expert'] ?? 0;
    if ($chk) {
        $error = "Cette personne est déjà enregistrée comme expert.";
    } else {
        // Récupérer infos depuis personne
        $pers = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM personne WHERE id_personne=$id_personne"));
        $nom    = mysqli_real_escape_string($conn, $pers['nom']);
        $prenom = mysqli_real_escape_string($conn, $pers['prenom']);
        $tel    = mysqli_real_escape_string($conn, $pers['telephone'] ?? '');
        $email  = mysqli_real_escape_string($conn, $pers['email'] ?? '');

        mysqli_query($conn, "INSERT INTO expert (nom,prenom,telephone,email,activite,id_personne)
            VALUES ('$nom','$prenom','$tel','$email','$activite',$id_personne)");
        $success = "Expert ajouté avec succès.";
    }
}

/* ======= MODIFIER ======= */
if (isset($_POST['modifier'])) {
    $id       = intval($_POST['id_expert']);
    $activite = mysqli_real_escape_string($conn, trim($_POST['activite']));
    $tel      = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    mysqli_query($conn, "UPDATE expert SET telephone='$tel',email='$email',activite='$activite'
        WHERE id_expert=$id");
    $success = "Expert modifié.";
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM expertise WHERE id_expert=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : cet expert a des expertises enregistrées.";
    } else {
        mysqli_query($conn, "DELETE FROM expert WHERE id_expert=$id");
        $success = "Expert supprimé.";
    }
}

$experts = mysqli_query($conn, "
    SELECT e.*,
           (SELECT COUNT(*) FROM expertise ex WHERE ex.id_expert=e.id_expert) as nb_expertises,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_expert=e.id_expert) as nb_dossiers
    FROM expert e ORDER BY e.nom");
$total = mysqli_num_rows($experts);

/* Personnes disponibles avec statut expert non encore liées */
$personnes_expert = mysqli_query($conn,
    "SELECT id_personne, nom, prenom, telephone, email
     FROM personne
     WHERE statut_personne='expert'
       AND id_personne NOT IN (SELECT id_personne FROM expert WHERE id_personne IS NOT NULL)
     ORDER BY nom");

$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM expert WHERE id_expert=".intval($_GET['edit'])));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Experts — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== MODAL ===== */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 900;
    align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: #fff; border-radius: 18px;
    padding: 36px 38px;
    width: 560px; max-width: 96vw; max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 24px 70px rgba(0,0,0,.22);
}
.modal-box h3 {
    font-size: 17px; font-weight: 700; margin-bottom: 26px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--gray-100);
    display: flex; align-items: center; gap: 10px;
    color: var(--gray-800);
}

/* ===== FORM AMELIORÉ ===== */
.modal-box .form-group { margin-bottom: 20px; }
.modal-box .form-group label {
    display: block; font-size: 11.5px; font-weight: 700;
    color: var(--gray-500); text-transform: uppercase;
    letter-spacing: .5px; margin-bottom: 7px;
}
.modal-box .form-group input,
.modal-box .form-group select,
.modal-box .form-group textarea {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid var(--gray-200);
    border-radius: 10px; font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    color: var(--gray-800); background: var(--gray-50);
    transition: all .18s;
}
.modal-box .form-group input:focus,
.modal-box .form-group select:focus {
    border-color: var(--green-600); outline: none;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(22,163,74,.12);
}
.modal-box .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.modal-box .btn-row { display: flex; gap: 12px; margin-top: 28px; }
.modal-box .btn-row .btn { flex: 1; justify-content: center; padding: 13px; font-size: 14px; }

/* Person card inside select */
.person-hint {
    margin-top: 8px; padding: 10px 14px;
    background: var(--green-50); border: 1px solid var(--green-200);
    border-radius: 8px; font-size: 12.5px; color: var(--green-800);
    display: none;
}
.person-hint.visible { display: block; }
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">
<div class="page-heading">
    <div>
        <h1><i class="fa fa-user-tie"></i> Experts</h1>
        <p class="sub">Gestion des experts automobile · <?= $total ?> expert(s)</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouvel expert
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> $error</div>"; ?>

<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> expert(s)</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr><th>Expert</th><th>Contact</th><th>Activité</th><th>Expertises</th><th>Dossiers</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($e = mysqli_fetch_assoc($experts)): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:38px;height:38px;border-radius:50%;background:var(--teal-50);color:var(--teal-700);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0">
                        <?= strtoupper(substr($e['nom'],0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:600"><?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?></div>
                        <div style="font-size:11px;color:var(--gray-400)">#<?= $e['id_expert'] ?></div>
                    </div>
                </div>
            </td>
            <td>
                <div class="num-cell" style="font-size:13px"><?= htmlspecialchars($e['telephone']) ?></div>
                <div style="font-size:12px;color:var(--blue-700)"><?= htmlspecialchars($e['email']) ?></div>
            </td>
            <td><?= htmlspecialchars($e['activite']) ?></td>
            <td style="text-align:center"><span class="badge badge-teal"><?= $e['nb_expertises'] ?></span></td>
            <td style="text-align:center"><span class="badge badge-blue"><?= $e['nb_dossiers'] ?></span></td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="?edit=<?= $e['id_expert'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
                    <?php if ($e['nb_expertises'] == 0 && $e['nb_dossiers'] == 0): ?>
                    <a href="?del=<?= $e['id_expert'] ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Supprimer cet expert ?')"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="fa fa-user-tie"></i><p>Aucun expert</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL AJOUTER ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--teal-700)"></i> Nouvel expert</h3>

    <p style="font-size:13px;color:var(--gray-500);margin:-10px 0 20px;padding:12px 14px;background:var(--teal-50);border-radius:9px;border-left:3px solid var(--teal-600);">
        <i class="fa fa-info-circle" style="color:var(--teal-700)"></i>
        Sélectionnez une personne avec le statut <b>Expert</b> déjà enregistrée.
        <a href="gerer_personnes.php" style="color:var(--teal-700);font-weight:600;">Ajouter une personne</a>
    </p>

    <form method="POST">
        <div class="form-group">
            <label>Personne (statut Expert) <span style="color:red">*</span></label>
            <select name="id_personne" required id="sel_personne_expert" onchange="showPersonHint(this)">
                <option value="">— Sélectionner —</option>
                <?php
                mysqli_data_seek($personnes_expert, 0);
                while ($p = mysqli_fetch_assoc($personnes_expert)): ?>
                <option value="<?= $p['id_personne'] ?>"
                        data-tel="<?= htmlspecialchars($p['telephone'] ?? '') ?>"
                        data-email="<?= htmlspecialchars($p['email'] ?? '') ?>">
                    <?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <div class="person-hint" id="hint_expert">
                <i class="fa fa-phone"></i> <span id="hint_tel"></span> &nbsp;
                <i class="fa fa-envelope" style="margin-left:10px"></i> <span id="hint_email"></span>
            </div>
        </div>

        <div class="form-group">
            <label>Activité / Spécialité</label>
            <input type="text" name="activite" value="Expert automobile" placeholder="Ex: Expert automobile">
        </div>

        <div class="btn-row">
            <button type="submit" name="ajouter" class="btn btn-primary">
                <i class="fa fa-save"></i> Ajouter l'expert
            </button>
            <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Annuler</button>
        </div>
    </form>
</div>
</div>

<!-- ====== MODAL MODIFIER ====== -->
<?php if ($edit): ?>
<div class="modal-overlay open" id="modal-edit">
<div class="modal-box">
    <h3><i class="fa fa-pen" style="color:var(--teal-700)"></i>
        Modifier — <?= htmlspecialchars($edit['nom'].' '.$edit['prenom']) ?>
    </h3>
    <form method="POST">
        <input type="hidden" name="id_expert" value="<?= $edit['id_expert'] ?>">

        <div class="form-grid-2">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" value="<?= htmlspecialchars($edit['nom']) ?>" readonly
                       style="background:var(--gray-100);color:var(--gray-500);cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" value="<?= htmlspecialchars($edit['prenom']) ?>" readonly
                       style="background:var(--gray-100);color:var(--gray-500);cursor:not-allowed;">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" value="<?= htmlspecialchars($edit['telephone']) ?>" placeholder="0550 00 00 00">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($edit['email']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Activité / Spécialité</label>
            <input type="text" name="activite" value="<?= htmlspecialchars($edit['activite']) ?>">
        </div>

        <div class="btn-row">
            <button type="submit" name="modifier" class="btn btn-primary"><i class="fa fa-save"></i> Modifier</button>
            <a href="gerer_experts.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

</div>
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});

function showPersonHint(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const hint  = document.getElementById('hint_expert');
    const telEl = document.getElementById('hint_tel');
    const emEl  = document.getElementById('hint_email');
    if (!opt.value) { hint.classList.remove('visible'); return; }
    telEl.textContent = opt.getAttribute('data-tel') || '—';
    emEl.textContent  = opt.getAttribute('data-email') || '—';
    hint.classList.add('visible');
}
</script>
</body>
</html>