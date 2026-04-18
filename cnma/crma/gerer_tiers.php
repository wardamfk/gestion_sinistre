<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Tiers adversaires';
$success = $error = '';

/* ======= AJOUTER TIERS (personne + tiers en une seule étape) ======= */
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

    // --- Données TIERS ---
    $compagnie     = mysqli_real_escape_string($conn, trim($_POST['compagnie_assurance']));
    $numero_police = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $responsable   = $_POST['responsable'];

    // Vérification CIN doublon (CIN peut être vide pour tiers)
    $checkCIN = $cin ? mysqli_num_rows(mysqli_query($conn, "SELECT id_personne FROM personne WHERE num_identite='$cin'")) : 0;

    if ($checkCIN > 0) {
        $error = "❌ Ce numéro d'identité (CIN) est déjà utilisé.";
    } else {
        // 1. Créer la personne
        $dn_sql  = $date_naissance ? "'$date_naissance'" : "NULL";
        $cin_sql = $cin ? "'$cin'" : "NULL";
        mysqli_query($conn, "INSERT INTO personne
            (type_personne, nom, prenom, num_identite, date_naissance, lieu_naissance, telephone, adresse, email, statut_personne)
            VALUES ('physique','$nom','$prenom',$cin_sql,$dn_sql,'$lieu_naissance','$tel','$adresse','$email_p','adversaire')");
        $id_personne = mysqli_insert_id($conn);

        // 2. Créer le tiers lié
        mysqli_query($conn, "INSERT INTO tiers (id_personne, compagnie_assurance, numero_police, responsable)
            VALUES ($id_personne,'$compagnie','$numero_police','$responsable')");
        $success = "✅ Tiers <b>$nom $prenom</b> créé avec succès.";
    }
}

/* ======= MODIFIER TIERS ======= */
if (isset($_POST['modifier'])) {
    $id_tiers  = intval($_POST['id_tiers']);
    $compagnie = mysqli_real_escape_string($conn, trim($_POST['compagnie_assurance']));
    $police    = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $resp      = $_POST['responsable'];
    mysqli_query($conn, "UPDATE tiers SET compagnie_assurance='$compagnie',
        numero_police='$police',responsable='$resp' WHERE id_tiers=$id_tiers");
    $success = "✅ Tiers modifié.";
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM dossier WHERE id_tiers=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : ce tiers est lié à des dossiers.";
    } else {
        mysqli_query($conn, "DELETE FROM tiers WHERE id_tiers=$id");
        $success = "Tiers supprimé.";
    }
}

$tiers_list = mysqli_query($conn, "
    SELECT t.*,p.nom,p.prenom,p.telephone,p.adresse,p.num_identite,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_tiers=t.id_tiers) as nb_dossiers
    FROM tiers t JOIN personne p ON t.id_personne=p.id_personne
    ORDER BY t.id_tiers DESC");
$total = mysqli_num_rows($tiers_list);

$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT t.*,p.nom,p.prenom,p.telephone,p.adresse
         FROM tiers t JOIN personne p ON t.id_personne=p.id_personne
         WHERE t.id_tiers=".intval($_GET['edit'])));
}

$resp_badge = [
    'oui'     => ['badge-red',   'Responsable'],
    'non'     => ['badge-green', 'Non responsable'],
    'partiel' => ['badge-amber', 'Partiel'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Tiers adversaires — CRMA</title>
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
.modal-box .form-group input:focus,.modal-box .form-group select:focus{border-color:var(--amber-600);outline:none;background:#fff;box-shadow:0 0 0 3px rgba(217,119,6,.12)}
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
        <h1><i class="fa fa-user-shield"></i> Tiers adversaires</h1>
        <p class="sub">Gestion des tiers impliqués dans les sinistres</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouveau tiers
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> $error</div>"; ?>

<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> tiers</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr><th>Nom</th><th>CIN</th><th>Téléphone</th><th>Compagnie</th><th>N° Police</th><th>Responsabilité</th><th>Dossiers</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($t = mysqli_fetch_assoc($tiers_list)):
            $rb = $resp_badge[$t['responsable']] ?? ['badge-gray','—'];
        ?>
        <tr>
            <td><div style="font-weight:500"><?= htmlspecialchars($t['nom'].' '.$t['prenom']) ?></div></td>
            <td style="font-size:12px;color:var(--gray-500)"><?= htmlspecialchars($t['num_identite'] ?? '—') ?></td>
            <td class="num-cell"><?= htmlspecialchars($t['telephone']) ?></td>
            <td><?= htmlspecialchars($t['compagnie_assurance']) ?></td>
            <td class="num-cell" style="font-size:12px"><?= htmlspecialchars($t['numero_police']) ?></td>
            <td><span class="badge <?= $rb[0] ?>"><?= $rb[1] ?></span></td>
            <td style="text-align:center"><span class="badge badge-blue"><?= $t['nb_dossiers'] ?></span></td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="?edit=<?= $t['id_tiers'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
                    <?php if ($t['nb_dossiers'] == 0): ?>
                    <a href="?del=<?= $t['id_tiers'] ?>" class="btn btn-xs btn-danger"
                       onclick="return confirm('Supprimer ce tiers ?')"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="8"><div class="empty-state"><i class="fa fa-user-shield"></i><p>Aucun tiers</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL AJOUTER (personne + tiers) ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--amber-600)"></i> Nouveau tiers adversaire</h3>

    <form method="POST">

        <!-- ===== PARTIE 1 : PERSONNE ===== -->
        <div class="section-divider" style="background:var(--green-50);border:1px solid var(--green-200);color:var(--green-800);">
            <i class="fa fa-user" style="color:var(--green-700)"></i>
            Informations personnelles
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Nom <span style="color:red">*</span></label>
                <input type="text" name="nom" required placeholder="Ex: Kaci">
            </div>
            <div class="form-group">
                <label>Prénom <span style="color:red">*</span></label>
                <input type="text" name="prenom" required placeholder="Ex: Nadia">
            </div>
        </div>

        <div class="form-group">
            <label>N° identité (CIN)</label>
            <input type="text" name="num_identite" id="cin_tiers" placeholder="Ex: 026737600 (optionnel)">
            <small id="cin-error-tiers" class="error-text"></small>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Date de naissance</label>
                <input type="date" name="date_naissance">
            </div>
            <div class="form-group">
                <label>Lieu de naissance</label>
                <input type="text" name="lieu_naissance" placeholder="Ex: Oran">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Téléphone <span style="color:red">*</span></label>
                <input type="text" name="telephone" required placeholder="Ex: 0553333333">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="tiers@mail.com (optionnel)">
            </div>
        </div>

        <div class="form-group">
            <label>Adresse</label>
            <input type="text" name="adresse" placeholder="Ex: Oran">
        </div>

        <!-- ===== PARTIE 2 : TIERS ===== -->
        <div class="section-divider" style="background:var(--amber-50);border:1px solid var(--amber-100);color:#78350f;">
            <i class="fa fa-user-shield" style="color:var(--amber-600)"></i>
            Informations adversaire
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Compagnie d'assurance</label>
                <input type="text" name="compagnie_assurance" placeholder="Ex: SAA, CAAR, GAM…">
            </div>
            <div class="form-group">
                <label>N° police adverse</label>
                <input type="text" name="numero_police" placeholder="Ex: SAA123456">
            </div>
        </div>

        <div class="form-group">
            <label>Responsabilité du tiers <span style="color:red">*</span></label>
            <select name="responsable" required>
                <option value="">— Choisir —</option>
                <option value="oui">Le tiers est responsable</option>
                <option value="non">Le tiers n'est pas responsable</option>
                <option value="partiel">Responsabilité partagée</option>
            </select>
        </div>

        <div class="btn-row">
            <button type="submit" name="ajouter" class="btn btn-primary">
                <i class="fa fa-save"></i> Créer le tiers
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
    <h3><i class="fa fa-pen" style="color:var(--amber-600)"></i> Modifier le tiers</h3>
    <form method="POST">
        <input type="hidden" name="id_tiers" value="<?= $edit['id_tiers'] ?>">

        <div class="section-divider" style="background:var(--gray-100);border:1px solid var(--gray-200);">
            <i class="fa fa-lock"></i> Identité (non modifiable)
        </div>
        <div class="form-group">
            <label>Nom complet</label>
            <input type="text" value="<?= htmlspecialchars($edit['nom'].' '.$edit['prenom']) ?>"
                   readonly style="background:var(--gray-100);color:var(--gray-500);cursor:not-allowed;">
        </div>

        <div class="section-divider" style="background:var(--amber-50);border:1px solid var(--amber-100);color:#78350f;">
            <i class="fa fa-user-shield" style="color:var(--amber-600)"></i> Informations adversaire
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Compagnie</label>
                <input type="text" name="compagnie_assurance" value="<?= htmlspecialchars($edit['compagnie_assurance']) ?>">
            </div>
            <div class="form-group">
                <label>N° police</label>
                <input type="text" name="numero_police" value="<?= htmlspecialchars($edit['numero_police']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Responsabilité <span style="color:red">*</span></label>
            <select name="responsable" required>
                <option value="oui"     <?= $edit['responsable']=='oui'    ?'selected':'' ?>>Le tiers est responsable</option>
                <option value="non"     <?= $edit['responsable']=='non'    ?'selected':'' ?>>Le tiers n'est pas responsable</option>
                <option value="partiel" <?= $edit['responsable']=='partiel'?'selected':'' ?>>Responsabilité partagée</option>
            </select>
        </div>

        <div class="btn-row">
            <button type="submit" name="modifier" class="btn btn-primary"><i class="fa fa-save"></i> Modifier</button>
            <a href="gerer_tiers.php" class="btn btn-outline">Annuler</a>
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

// ── Vérification CIN (optionnel pour tiers) ──
const cinInput = document.getElementById('cin_tiers');
const cinError = document.getElementById('cin-error-tiers');
let cinInvalid = false, cinTimeout = null;
cinInput.addEventListener('input', () => {
    clearTimeout(cinTimeout);
    const val = cinInput.value.trim();
    if (!val) { cinInput.classList.remove('input-error'); cinError.textContent = ''; cinInvalid = false; return; }
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