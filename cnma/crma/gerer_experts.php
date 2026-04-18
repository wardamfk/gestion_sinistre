<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des experts';
$success = $error = '';

/* ======= AJOUTER EXPERT (personne + expert en une seule étape) ======= */
if (isset($_POST['ajouter'])) {
    // --- Données PERSONNE ---
    $nom            = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $prenom         = mysqli_real_escape_string($conn, trim($_POST['prenom']));
    $cin            = mysqli_real_escape_string($conn, trim($_POST['num_identite']));
    $tel            = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $email_p        = mysqli_real_escape_string($conn, trim($_POST['email']));
    $adresse        = mysqli_real_escape_string($conn, trim($_POST['adresse']));
    $date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
    $lieu_naissance = mysqli_real_escape_string($conn, trim($_POST['lieu_naissance'] ?? ''));

    // --- Données EXPERT ---
    $activite = mysqli_real_escape_string($conn, trim($_POST['activite']));

    // Vérification CIN doublon
    $checkCIN = mysqli_num_rows(mysqli_query($conn, "SELECT id_personne FROM personne WHERE num_identite='$cin'"));

    if ($checkCIN > 0) {
        $error = "❌ Ce numéro d'identité (CIN) est déjà utilisé.";
    } else {
        // 1. Créer la personne
        $dn_sql = $date_naissance ? "'$date_naissance'" : "NULL";
        mysqli_query($conn, "INSERT INTO personne
            (type_personne, nom, prenom, num_identite, date_naissance, lieu_naissance, telephone, adresse, email, statut_personne)
            VALUES ('physique','$nom','$prenom','$cin',$dn_sql,'$lieu_naissance','$tel','$adresse','$email_p','expert')");
        $id_personne = mysqli_insert_id($conn);

        // 2. Créer l'expert lié
        mysqli_query($conn, "INSERT INTO expert (nom, prenom, telephone, email, activite, id_personne)
            VALUES ('$nom','$prenom','$tel','$email_p','$activite',$id_personne)");
        $success = "✅ Expert <b>$nom $prenom</b> créé avec succès.";
    }
}

/* ======= MODIFIER EXPERT ======= */
if (isset($_POST['modifier'])) {
    $id       = intval($_POST['id_expert']);
    $activite = mysqli_real_escape_string($conn, trim($_POST['activite']));
    $tel      = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    mysqli_query($conn, "UPDATE expert SET telephone='$tel',email='$email',activite='$activite' WHERE id_expert=$id");
    $success = "✅ Expert modifié.";
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
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:900;align-items:center;justify-content:center;backdrop-filter:blur(2px)}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:18px;padding:36px 38px;width:640px;max-width:96vw;max-height:90vh;overflow-y:auto;box-shadow:0 24px 70px rgba(0,0,0,.22)}
.modal-box h3{font-size:17px;font-weight:700;margin-bottom:26px;padding-bottom:16px;border-bottom:2px solid var(--gray-100);display:flex;align-items:center;gap:10px;color:var(--gray-800)}
.modal-box .form-group{margin-bottom:20px}
.modal-box .form-group label{display:block;font-size:11.5px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px}
.modal-box .form-group input,.modal-box .form-group select{width:100%;padding:11px 14px;border:1.5px solid var(--gray-200);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:var(--gray-50);transition:all .18s}
.modal-box .form-group input:focus,.modal-box .form-group select:focus{border-color:var(--green-600);outline:none;background:#fff;box-shadow:0 0 0 3px rgba(22,163,74,.12)}
.modal-box .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.modal-box .btn-row{display:flex;gap:12px;margin-top:28px}
.modal-box .btn-row .btn{flex:1;justify-content:center;padding:13px;font-size:14px}
.section-divider{padding:9px 14px;border-radius:var(--radius);margin:16px 0 14px;display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px}
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
                    <a href="?del=<?= $e['id_expert'] ?>" class="btn btn-xs btn-danger"
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

<!-- ====== MODAL AJOUTER (personne + expert) ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--teal-700)"></i> Nouvel expert</h3>

    <form method="POST">

        <!-- ===== PARTIE 1 : PERSONNE ===== -->
        <div class="section-divider" style="background:var(--teal-50);border:1px solid var(--teal-100);color:var(--teal-800);">
            <i class="fa fa-user" style="color:var(--teal-700)"></i>
            Informations personnelles
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Nom <span style="color:red">*</span></label>
                <input type="text" name="nom" required placeholder="Ex: Brahimi">
            </div>
            <div class="form-group">
                <label>Prénom <span style="color:red">*</span></label>
                <input type="text" name="prenom" required placeholder="Ex: Ahmed">
            </div>
        </div>

        <div class="form-group">
            <label>N° identité (CIN) <span style="color:red">*</span></label>
            <input type="text" name="num_identite" id="cin_expert" required placeholder="Ex: 026737698">
            <small id="cin-error-expert" class="error-text"></small>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Date de naissance</label>
                <input type="date" name="date_naissance">
            </div>
            <div class="form-group">
                <label>Lieu de naissance</label>
                <input type="text" name="lieu_naissance" placeholder="Ex: Alger">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Téléphone <span style="color:red">*</span></label>
                <input type="text" name="telephone" required placeholder="Ex: 0550 00 00 01">
            </div>
            <div class="form-group">
                <label>Email <span style="color:red">*</span></label>
                <input type="email" name="email" required placeholder="expert@mail.dz">
            </div>
        </div>

        <div class="form-group">
            <label>Adresse</label>
            <input type="text" name="adresse" placeholder="Ex: 5 rue Didouche Mourad, Alger">
        </div>

        <!-- ===== PARTIE 2 : EXPERT ===== -->
        <div class="section-divider" style="background:var(--amber-50);border:1px solid var(--amber-100);color:#78350f;">
            <i class="fa fa-user-tie" style="color:var(--amber-600)"></i>
            Informations expert
        </div>

        <div class="form-group">
            <label>Activité / Spécialité <span style="color:red">*</span></label>
            <input type="text" name="activite" value="Expert automobile" required placeholder="Ex: Expert automobile">
        </div>

        <div class="btn-row">
            <button type="submit" name="ajouter" class="btn btn-primary">
                <i class="fa fa-save"></i> Créer l'expert
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

        <div class="section-divider" style="background:var(--gray-100);border:1px solid var(--gray-200);">
            <i class="fa fa-lock"></i> Identité (non modifiable)
        </div>
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

        <div class="section-divider" style="background:var(--amber-50);border:1px solid var(--amber-100);color:#78350f;">
            <i class="fa fa-user-tie" style="color:var(--amber-600)"></i> Informations expert
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" value="<?= htmlspecialchars($edit['telephone']) ?>">
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

// ── Vérification CIN ──
const cinInput = document.getElementById('cin_expert');
const cinError = document.getElementById('cin-error-expert');
let cinInvalid = false, cinTimeout = null;
cinInput.addEventListener('input', () => {
    clearTimeout(cinTimeout);
    const val = cinInput.value.trim();
    if (!val) { cinInput.classList.remove('input-error'); cinError.textContent = ''; cinInvalid = true; return; }
    cinTimeout = setTimeout(() => {
        fetch('check_cin.php?cin=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(d => {
            if (d.exists) {
                cinInput.classList.add('input-error');
                cinError.innerHTML = '❌ Ce numéro d\'identité est déjà utilisé';
                cinInvalid = true;
            } else {
                cinInput.classList.remove('input-error');
                cinError.textContent = '';
                cinInvalid = false;
            }
        });
    }, 400);
});
document.querySelector('#modal-add form').addEventListener('submit', function(e) {
    if (cinInvalid) e.preventDefault();
});
</script>
</body>
</html>