<?php
// gerer_assures.php — CRUD assurés (filtrés depuis personne statut='assure')
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des assurés';
$cin = isset($_POST['num_identite']) 
    ? mysqli_real_escape_string($conn, trim($_POST['num_identite'])) 
    : '';
$success = $error = '';

/* ======= AJOUTER ASSURÉ ======= */
if (isset($_POST['ajouter'])) {
    $id_personne    = intval($_POST['id_personne']);
    $date_creation  = $_POST['date_creation'];
    $actif          = intval($_POST['actif']);
    $num_permis     = mysqli_real_escape_string($conn, $_POST['num_permis']);
    $date_deliv     = $_POST['date_delivrance_permis'];
    $lieu_deliv     = mysqli_real_escape_string($conn, $_POST['lieu_delivrance_permis']);
    $type_permis    = $_POST['type_permis'];
$checkPermis = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT id_assure FROM assure WHERE num_permis='$num_permis'"))['id_assure'] ?? 0;
    // Vérif doublon
    $check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_assure FROM assure WHERE id_personne=$id_personne"))['id_assure'] ?? 0;
  if ($check) {
    $error = "Cette personne est déjà enregistrée comme assurée.";
} elseif ($checkPermis) {
    $error = "❌ Ce numéro de permis existe déjà !";
} else {
   
        mysqli_query($conn, "INSERT INTO assure
            (id_personne,date_creation,actif,num_permis,date_delivrance_permis,lieu_delivrance_permis,type_permis)
            VALUES ($id_personne,'$date_creation',$actif,'$num_permis','$date_deliv','$lieu_deliv','$type_permis')");
        $success = "Assuré ajouté avec succès.";
    }
}

/* ======= MODIFIER ASSURÉ ======= */
if (isset($_POST['modifier'])) {
    $id         = intval($_POST['id_assure']);
    $actif      = intval($_POST['actif']);
    $num_permis = mysqli_real_escape_string($conn, $_POST['num_permis']);
    $date_deliv = $_POST['date_delivrance_permis'];
    $lieu_deliv = mysqli_real_escape_string($conn, $_POST['lieu_delivrance_permis']);
    $type_permis= $_POST['type_permis'];
    mysqli_query($conn, "UPDATE assure SET
        actif=$actif, num_permis='$num_permis',
        date_delivrance_permis='$date_deliv',
        lieu_delivrance_permis='$lieu_deliv',
        type_permis='$type_permis'
        WHERE id_assure=$id");
    $success = "Assuré modifié.";
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM contrat WHERE id_assure=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : cet assuré a des contrats associés.";
    } else {
        mysqli_query($conn, "DELETE FROM assure WHERE id_assure=$id");
        $success = "Assuré supprimé.";
    }
}

/* ======= CRÉER COMPTE ======= */
if (isset($_POST['creer_compte'])) {
    $id_personne = intval($_POST['id_personne_compte']);
    $email       = mysqli_real_escape_string($conn, trim($_POST['email_compte']));
    $pwd         = password_hash($_POST['pwd_compte'], PASSWORD_DEFAULT);
    $chk = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM utilisateur WHERE email='$email'"));
    if ($chk > 0) {
        $error = "Cet email est déjà utilisé.";
    } else {
        mysqli_query($conn, "INSERT INTO utilisateur (id_personne,email,mot_de_passe,role,actif)
            VALUES ($id_personne,'$email','$pwd','ASSURE',1)");
        $success = "Compte créé avec succès.";
    }
}

/* ======= DONNÉES ======= */
$filtre_q    = $_GET['q'] ?? '';
$filtre_actif= $_GET['actif'] ?? '';
$where = "WHERE 1=1";
if ($filtre_q)     $where .= " AND (p.nom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                                 OR p.prenom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%')";
if ($filtre_actif !== '') $where .= " AND a.actif=".intval($filtre_actif);

$assures = mysqli_query($conn, "
    SELECT a.*,p.nom,p.prenom,p.telephone,p.email,p.adresse,p.num_identite,
           (SELECT COUNT(*) FROM contrat c WHERE c.id_assure=a.id_assure) as nb_contrats,
           (SELECT COUNT(*) FROM utilisateur u WHERE u.id_personne=a.id_personne) as a_compte
    FROM assure a
    JOIN personne p ON a.id_personne=p.id_personne
    $where
    ORDER BY a.id_assure DESC");
$total = mysqli_num_rows($assures);

/* Personnes assurées sans compte assure */
$personnes_dispo = mysqli_query($conn,
    "SELECT id_personne,nom,prenom,email FROM personne
     WHERE statut_personne='assure'
     AND id_personne NOT IN (SELECT id_personne FROM assure WHERE id_personne IS NOT NULL)
     ORDER BY nom");

/* Édition */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.*,p.nom,p.prenom FROM assure a JOIN personne p ON a.id_personne=p.id_personne
         WHERE a.id_assure=".intval($_GET['edit'])));
}
/* Personne pour créer compte */
$compte_personne = null;
if (isset($_GET['compte'])) {
    $compte_personne = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT p.*,a.id_assure FROM assure a JOIN personne p ON a.id_personne=p.id_personne
         WHERE a.id_assure=".intval($_GET['compte'])));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Assurés — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:30px;width:700px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:8px}
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">
<div class="page-heading">
    <div>
        <h1><i class="fa fa-id-card"></i> Assurés</h1>
        <p class="sub">Gestion complète des assurés CRMA</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouvel assuré
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> $error</div>"; ?>

<!-- FILTRES -->
<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="Rechercher assuré…" value="<?= htmlspecialchars($filtre_q) ?>">
    <select name="actif">
        <option value="">Tous</option>
        <option value="1" <?= $filtre_actif==='1'?'selected':'' ?>>Actifs</option>
        <option value="0" <?= $filtre_actif==='0'?'selected':'' ?>>Suspendus</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fa fa-search"></i> Filtrer</button>
    <a href="gerer_assures.php" class="btn btn-ghost btn-sm"><i class="fa fa-times"></i></a>
</form>

<!-- TABLE -->
<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> assuré(s)</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr>
                <th>Assuré</th><th>Contact</th><th>Permis</th>
                <th>Contrats</th><th>Statut</th><th>Compte</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($a = mysqli_fetch_assoc($assures)): ?>
        <tr>
            <td>
                <div style="font-weight:500"><?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)">
    CIN: <?= htmlspecialchars($a['num_identite']) ?>
</div>
                <div style="font-size:11px;color:var(--gray-400)">#<?= $a['id_assure'] ?> · <?= $a['date_creation'] ?></div>
            </td>
            <td>
                <div><?= htmlspecialchars($a['telephone'] ?? '') ?>
</div>
                <div style="font-size:12px;color:var(--blue-700)"><?= htmlspecialchars($a['email']) ?></div>
            </td>
            <td>
                <?php if ($a['num_permis']): ?>
                <div class="num-cell" style="font-size:12px"><?= htmlspecialchars($a['num_permis'] ?? '') ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= $a['type_permis'] ?></div>
                <?php else: echo '<span style="color:var(--gray-300)">—</span>'; endif; ?>
            </td>
            <td style="text-align:center">
                <?php if ($a['nb_contrats'] > 0): ?>
                <span class="badge badge-blue"><?= $a['nb_contrats'] ?></span>
                <?php else: echo '<span style="color:var(--gray-300)">0</span>'; endif; ?>
            </td>
            <td>
                <span class="badge <?= $a['actif'] ? 'badge-green' : 'badge-red' ?>">
                    <?= $a['actif'] ? 'Actif' : 'Suspendu' ?>
                </span>
            </td>
            <td>
                <?php if ($a['a_compte']): ?>
                <span class="badge badge-teal"><i class="fa fa-check"></i> Oui</span>
                <?php else: ?>
                <span class="badge badge-gray">Non</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <a href="?edit=<?= $a['id_assure'] ?>" class="btn btn-outline btn-xs" title="Modifier">
                        <i class="fa fa-pen"></i>
                    </a>
                    <?php if (!$a['a_compte']): ?>
                    <a href="?compte=<?= $a['id_assure'] ?>" class="btn btn-xs btn-info" title="Créer compte">
                        <i class="fa fa-user-lock"></i>
                    </a>
                    <?php endif; ?>
                    <a href="gerer_contrats.php?assure=<?= $a['id_assure'] ?>" class="btn btn-xs btn-teal" title="Voir contrats">
                        <i class="fa fa-file-contract"></i>
                    </a>
                    <?php if ($a['nb_contrats'] == 0): ?>
                    <a href="#"
   class="btn btn-xs btn-danger"
   onclick="confirmDeleteAssure(event, <?= $a['id_assure'] ?>)">
                        <i class="fa fa-trash"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fa fa-id-card"></i><p>Aucun assuré trouvé</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL AJOUTER ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--green-700)"></i> Nouvel assuré</h3>
    <p style="font-size:13px;color:var(--gray-500);margin-bottom:18px">
        Choisissez une personne existante avec le statut <b>Assuré</b>.
        <a href="gerer_personnes.php" style="color:var(--green-700)">Ajouter une personne</a>
    </p>
    <form method="POST">
        <div class="form-group">
            <label>Personne <span style="color:red">*</span></label>
            <select name="id_personne" required>
                <option value="">— Sélectionner —</option>
                <?php while ($prs = mysqli_fetch_assoc($personnes_dispo)): ?>
                <option value="<?= $prs['id_personne'] ?>" data-email="<?= htmlspecialchars($prs['email']) ?>">
                    <?= htmlspecialchars($prs['nom'].' '.$prs['prenom']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Date de création</label>
                <input type="date" name="date_creation" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select name="actif">
                    <option value="1">Actif</option>
                    <option value="0">Suspendu</option>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
    <label>N° Permis</label>
    <input type="text" name="num_permis" id="num_permis" placeholder="Ex: AB123456" required>
    <small id="permis-error" class="error-text"></small>
</div>
            <div class="form-group">
                <label>Type permis</label>
                <select name="type_permis">
                    <option value="B">B</option><option value="A">A</option>
                    <option value="C">C</option><option value="D">D</option>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Date délivrance</label><input type="date" name="date_delivrance_permis" placeholder="Ex: 2023-01-01" required></div>
            <div class="form-group"><label>Lieu délivrance</label><input type="text" name="lieu_delivrance_permis" placeholder="Ex: alger" required></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="ajouter" class="btn btn-primary" style="flex:1">
                <i class="fa fa-save"></i> Ajouter
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
    <h3><i class="fa fa-pen" style="color:var(--green-700)"></i>
        Modifier — <?= htmlspecialchars($edit['nom'].' '.$edit['prenom']) ?>
    </h3>
    <form method="POST">
        <input type="hidden" name="id_assure" value="<?= $edit['id_assure'] ?>">
        <div class="form-group">
            <label>Statut</label>
            <select name="actif">
                <option value="1" <?= $edit['actif']?'selected':'' ?>>Actif</option>
                <option value="0" <?= !$edit['actif']?'selected':'' ?>>Suspendu</option>
            </select>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>N° Permis</label><input type="text" name="num_permis" value="<?= htmlspecialchars($edit['num_permis'] ?? '') ?>"></div>
            <div class="form-group">
                <label>Type permis</label>
                <select name="type_permis">
                    <?php foreach(['A','B','C','D'] as $t): ?>
                    <option <?= $edit['type_permis']==$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Date délivrance</label><input type="date" name="date_delivrance_permis"value="<?= $edit['date_delivrance_permis'] ?? '' ?>"></div>
            <div class="form-group"><label>Lieu délivrance</label><input type="text" name="lieu_delivrance_permis" 
            value="<?= htmlspecialchars($edit['lieu_delivrance_permis'] ?? '') ?>"></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="modifier" class="btn btn-primary" style="flex:1"><i class="fa fa-save"></i> Modifier</button>
            <a href="gerer_assures.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

<!-- ====== MODAL CRÉER COMPTE ====== -->
<?php if ($compte_personne): ?>
<div class="modal-overlay open" id="modal-compte">
<div class="modal-box">
    <h3><i class="fa fa-user-lock" style="color:var(--blue-700)"></i>
        Créer un compte — <?= htmlspecialchars($compte_personne['nom'].' '.$compte_personne['prenom']) ?>
    </h3>
    <form method="POST">
        <input type="hidden" name="id_personne_compte" value="<?= $compte_personne['id_personne'] ?>">
        <div class="form-group">
            <label>Email <span style="color:red">*</span></label>
            <input type="email" name="email_compte" value="<?= htmlspecialchars($compte_personne['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>Mot de passe <span style="color:red">*</span></label>
            <input type="password" name="pwd_compte" required minlength="6" placeholder="Minimum 6 caractères">
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="creer_compte" class="btn btn-info" style="flex:1"><i class="fa fa-save"></i> Créer le compte</button>
            <a href="gerer_assures.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>      
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});
</script>
<script>
const input = document.getElementById('num_permis');
const errorText = document.getElementById('permis-error');

let timeout = null;

input.addEventListener('input', () => {
    clearTimeout(timeout);

    const val = input.value.trim();

    if(val.length < 3){
        input.classList.remove('input-error');
        errorText.textContent = '';
        return;
    }

    document.querySelector('#modal-add form').addEventListener('submit', function(e){
    if(input.classList.contains('input-error')){
        e.preventDefault();
    }
});
    timeout = setTimeout(() => {
        fetch('check_permis.php?num=' + encodeURIComponent(val))
        .then(res => res.json())
        .then(data => {
            if(data.exists){
                input.classList.add('input-error');
                errorText.textContent = "❌ Ce numéro existe déjà";
            } else {
                input.classList.remove('input-error');
                errorText.textContent = "";
            }
        });
    }, 400);
    input.dispatchEvent(new Event('input')); // anti spam requêtes
    
});
function confirmDeleteAssure(e, id){
    e.preventDefault();

    Swal.fire({
        title: 'Supprimer cet assuré ?',
        text: "Cette action est irréversible",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?del=" + id;
        }
    });
}
</script>
</body>
</html>
