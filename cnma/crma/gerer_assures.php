<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des assurés';
$success = $error = '';

/* ======= AJOUTER ASSURÉ (personne + assuré en une seule étape) ======= */
if (isset($_POST['ajouter'])) {

    $type = $_POST['type_personne'];

    // ===== PERSONNE =====
    $nom     = mysqli_real_escape_string($conn, trim($_POST['nom'] ?? ''));
    $prenom  = mysqli_real_escape_string($conn, trim($_POST['prenom'] ?? ''));
    $raison  = mysqli_real_escape_string($conn, trim($_POST['raison_sociale'] ?? ''));

    $cin = null;
    $nif = null;

    if ($type == 'physique') {
        $cin = mysqli_real_escape_string($conn, trim($_POST['num_identite']));
    } else {
        $nif = mysqli_real_escape_string($conn, trim($_POST['nif']));
    }

    $tel     = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $email_p = mysqli_real_escape_string($conn, trim($_POST['email']));
    $adresse = mysqli_real_escape_string($conn, trim($_POST['adresse']));
    $date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
    $lieu_naissance = mysqli_real_escape_string($conn, trim($_POST['lieu_naissance'] ?? ''));

    // ===== ASSURE =====
    $date_creation = $_POST['date_creation'];
    $actif = intval($_POST['actif']);

    // IMPORTANT
    $num_permis = null;
    $c_nom = $c_prenom = $c_permis = $c_type = null;

    if ($type == 'physique') {

        $num_permis = mysqli_real_escape_string($conn, trim($_POST['num_permis']));

        $date_deliv  = $_POST['date_delivrance_permis'];
        $lieu_deliv  = mysqli_real_escape_string($conn, trim($_POST['lieu_delivrance_permis']));
        $type_permis = $_POST['type_permis'];

    } else {

        $c_nom    = mysqli_real_escape_string($conn, $_POST['chauffeur_nom']);
        $c_prenom = mysqli_real_escape_string($conn, $_POST['chauffeur_prenom']);
        $c_permis = mysqli_real_escape_string($conn, $_POST['chauffeur_permis']);
        $c_type   = $_POST['chauffeur_type_permis'];

        $date_deliv = $lieu_deliv = $type_permis = null;
    }

    // ===== VALIDATION =====
    if ($type == 'physique' && empty($num_permis)) {
        $error .= "❌ Permis obligatoire.<br>";
    }

    if ($type == 'morale' && empty($c_permis)) {
       $error .= "❌ Permis chauffeur obligatoire.<br>";
    }

// ===== DOUBLONS =====

// CIN
if ($type == 'physique') {
    $checkCIN = mysqli_num_rows(mysqli_query($conn,
        "SELECT id_personne FROM personne WHERE num_identite='$cin'"));
} else {
    $checkCIN = 0;
}

// NIF
if ($type == 'morale') {
    $checkNIF = mysqli_num_rows(mysqli_query($conn,
        "SELECT id_personne FROM personne WHERE nif='$nif'"));

    if ($checkNIF > 0) {
        $error .= "❌ NIF déjà utilisé.<br>";
    }
}

// EMAIL
$checkEmail = mysqli_num_rows(mysqli_query($conn,
    "SELECT id_personne FROM personne WHERE email='$email_p'"));

if ($checkEmail > 0) {
    $error .= "❌ Email déjà utilisé.<br>";
}

// PERMIS
$checkPermis = ($type == 'physique' && $num_permis)
    ? mysqli_num_rows(mysqli_query($conn, "SELECT id_assure FROM assure 
        WHERE num_permis='$num_permis' 
        OR chauffeur_permis='$num_permis'"))
    : 0;

// CIN
if ($checkCIN > 0) {
    $error .= "❌ CIN déjà utilisé.<br>";
}

// PERMIS
if ($checkPermis > 0) {
    $error .= "❌ Permis déjà utilisé.<br>";
}
}


    // ===== INSERT =====
    if (!$error) {

        $dn_sql = $date_naissance ? "'$date_naissance'" : "NULL";

        if ($type == 'physique') {

            mysqli_query($conn, "INSERT INTO personne
            (type_personne, nom, prenom, num_identite, date_naissance, lieu_naissance, telephone, adresse, email, statut_personne)
            VALUES ('physique','$nom','$prenom','$cin',$dn_sql,'$lieu_naissance','$tel','$adresse','$email_p','assure')");

        } else {

            mysqli_query($conn, "INSERT INTO personne
            (type_personne, raison_sociale, nif, telephone, adresse, email, statut_personne)
            VALUES ('morale','$raison','$nif','$tel','$adresse','$email_p','assure')");
        }

        $id_personne = mysqli_insert_id($conn);

        // INSERT ASSURE UNIQUE
      mysqli_query($conn, "INSERT INTO assure
(id_personne, date_creation, actif,
 num_permis, date_delivrance_permis, lieu_delivrance_permis, type_permis,
 chauffeur_nom, chauffeur_prenom, chauffeur_permis, chauffeur_type_permis)
VALUES
($id_personne, '$date_creation', $actif,
 ".($num_permis ? "'$num_permis'" : "NULL").",
 ".($date_deliv ? "'$date_deliv'" : "NULL").",
 ".($lieu_deliv ? "'$lieu_deliv'" : "NULL").",
 ".($type_permis ? "'$type_permis'" : "NULL").",
 ".($c_nom ? "'$c_nom'" : "NULL").",
 ".($c_prenom ? "'$c_prenom'" : "NULL").",
 ".($c_permis ? "'$c_permis'" : "NULL").",
 ".($c_type ? "'$c_type'" : "NULL")."
)");
    

    
}
}

/* ======= MODIFIER ASSURÉ ======= */
if (isset($_POST['modifier'])) {
    $id          = intval($_POST['id_assure']);
    $actif       = intval($_POST['actif']);
    $num_permis  = mysqli_real_escape_string($conn, $_POST['num_permis']);
    $date_deliv  = $_POST['date_delivrance_permis'];
    $lieu_deliv  = mysqli_real_escape_string($conn, $_POST['lieu_delivrance_permis']);
    $type_permis = $_POST['type_permis'];
    mysqli_query($conn, "UPDATE assure SET
        actif=$actif, num_permis='$num_permis',
        date_delivrance_permis='$date_deliv',
        lieu_delivrance_permis='$lieu_deliv',
        type_permis='$type_permis'
        WHERE id_assure=$id");
    $success = "✅ Assuré modifié.";
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

/* ======= DONNÉES LISTE ======= */
$filtre_q     = $_GET['q'] ?? '';
$filtre_actif = $_GET['actif'] ?? '';
$where = "WHERE 1=1";
if ($filtre_q)             $where .= " AND (p.nom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%' OR p.prenom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%')";
if ($filtre_actif !== '')  $where .= " AND a.actif=".intval($filtre_actif);

$assures = mysqli_query($conn, "
    SELECT a.*,p.nom,p.prenom,p.telephone,p.email,p.adresse,p.num_identite,nif,type_personne,p.raison_sociale,
           (SELECT COUNT(*) FROM contrat c WHERE c.id_assure=a.id_assure) as nb_contrats,
           (SELECT COUNT(*) FROM utilisateur u WHERE u.id_personne=a.id_personne) as a_compte
    FROM assure a
    JOIN personne p ON a.id_personne=p.id_personne
    $where
    ORDER BY a.id_assure DESC");
$total = mysqli_num_rows($assures);

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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:30px;width:720px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:8px}
.section-divider{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius);padding:10px 14px;margin:18px 0 14px;display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.6px}
.section-divider i{font-size:13px}
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
                <div style="font-weight:500">

<?php if ($a['type_personne'] == 'physique'): ?>
    <?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?>
<?php else: ?>
    <?= htmlspecialchars($a['raison_sociale']) ?>
<?php endif; ?>

</div>
                <div style="font-size:11px;color:var(--gray-400)">CIN: <?= htmlspecialchars($a['num_identite']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)">#<?= $a['id_assure'] ?> · <?= $a['date_creation'] ?></div>
            </td>
            <td>
                <div><?= htmlspecialchars($a['telephone'] ?? '') ?></div>
                <div style="font-size:12px;color:var(--blue-700)"><?= htmlspecialchars($a['email']) ?></div>
            </td>
           <td>

<?php if ($a['type_personne'] == 'physique'): ?>

    <div class="num-cell"><?= htmlspecialchars($a['num_permis'] ?? '') ?></div>
    <div style="font-size:11px;color:var(--gray-400)">
        <?= $a['type_permis'] ?>
    </div>

<?php else: ?>

    <div><?= htmlspecialchars($a['chauffeur_nom'].' '.$a['chauffeur_prenom']) ?></div>
    <div style="font-size:11px;color:gray">
        Permis: <?= htmlspecialchars($a['chauffeur_permis']) ?>
    </div>

<?php endif; ?>

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
                    <a href="#" class="btn btn-xs btn-danger"
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

<!-- ====== MODAL AJOUTER (personne + assuré) ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--green-700)"></i> Nouvel assuré</h3>

    <form method="POST">

        <!-- ===== PARTIE 1 : PERSONNE ===== -->
        <div class="section-divider" style="background:var(--green-50);border-color:var(--green-200);color:var(--green-800);">
            <i class="fa fa-user" style="color:var(--green-700)"></i>
            Informations personnelles
        </div>
<div class="form-group">
    <label>Type de personne <span style="color:red">*</span></label>
    <select name="type_personne" id="type_personne" onchange="toggleTypeAssure()" required>
        <option value="physique">Physique</option>
        <option value="morale">Morale (Entreprise)</option>
    </select>
</div>
   <div id="physique_fields">

    <!-- Nom / Prénom -->
    <div class="form-grid-2">
        <div class="form-group">
            <label>Nom <span style="color:red">*</span></label>
            <input type="text" name="nom" id="nom">
        </div>
        <div class="form-group">
            <label>Prénom <span style="color:red">*</span></label>
            <input type="text" name="prenom" id="prenom">
        </div>
    </div>

    <!-- Date + lieu naissance -->
    <div class="form-grid-2">
        <div class="form-group">
            <label>Date de naissance <span style="color:red">*</span></label>
            <input type="date" name="date_naissance" id="date_naissance">
        </div>
        <div class="form-group">
            <label>Lieu de naissance <span style="color:red">*</span></label>
            <input type="text" name="lieu_naissance" id="lieu_naissance">
        </div>
    </div>

</div>

<div id="morale_fields" style="display:none">
    <div class="form-group">
        <label>Raison sociale <span style="color:red">*</span></label>
        <input type="text" name="raison_sociale" id="raison_sociale">
    </div>
</div>
<div class="form-group" id="cin_field">
    <label>N° identité (CIN) <span style="color:red">*</span></label>
    <input type="text" name="num_identite" id="cin_add" placeholder="Ex: 026737698">
    <small id="cin-error" style="color:red;font-size:12px;"></small>
</div>
<div class="form-group" id="nif_field" style="display:none">
    <label>NIF <span style="color:red">*</span></label>
    <input type="text" name="nif" id="nif_add" placeholder="Ex: 123456789">
</div>
<div class="section-divider">
    <i class="fa fa-phone"></i> Coordonnées
</div>

<div class="form-grid-2">

    <div class="form-group">
        <label>Téléphone <span style="color:red">*</span></label>
      <input type="text"
       name="telephone"
       pattern="[0-9]{10}"
       title="Entrer 10 chiffres"
         id="telephone_add"
         placeholder="Ex: 0550123456"
       required>
              
    </div>

    <div class="form-group">
        <label>Email <span style="color:red">*</span></label>
        <input type="email"
               name="email"
               id="email_add"
               required
               placeholder="exemple@mail.com">
    </div>

</div>

  

        <div class="form-group">
            <label>Adresse</label>
            <input type="text" name="adresse" placeholder="Ex: 12 rue des Fleurs, Alger">
        </div>

        <!-- ===== PARTIE 2 : ASSURÉ ===== -->
        <div class="section-divider" style="background:var(--blue-50);border-color:var(--blue-100);color:var(--blue-800);">
            <i class="fa fa-id-card" style="color:var(--blue-700)"></i>
            Informations assuré & permis
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

  <div id="permis_fields">

    <div class="form-grid-2">
        <div class="form-group">
            <label>N° Permis <span style="color:red">*</span></label>
            <input type="text" name="num_permis" id="permis_add" placeholder="Ex: AB123456">
            <small id="permis-error" style="color:red;font-size:12px;"></small>
        </div>

        <div class="form-group">
            <label>Type permis</label>
            <select name="type_permis">
                <option value="B">B</option>
                <option value="A">A</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
        </div>
    </div>

    <div class="form-grid-2">
        <div class="form-group">
            <label>Date délivrance permis</label>
            <input type="date" name="date_delivrance_permis">
        </div>

        <div class="form-group">
            <label>Lieu délivrance permis</label>
            <input type="text" name="lieu_delivrance_permis">
        </div>
    </div>

</div>
            <div id="conducteur_fields" style="display:none">

    <div class="section-divider">
        <i class="fa fa-user"></i> Chauffeur
    </div>

    <div class="form-grid-2">
        <div class="form-group">
            <label>Nom chauffeur</label>
            <input type="text" name="chauffeur_nom">
        </div>

        <div class="form-group">
            <label>Prénom chauffeur</label>
            <input type="text" name="chauffeur_prenom">
        </div>
    </div>

    <div class="form-grid-2">
        <div class="form-group">
            <label>N° permis chauffeur</label>
            <input type="text" name="chauffeur_permis">
            <small id="chauffeur-error" style="color:red;font-size:12px;"></small>
        </div>

        <div class="form-group">
            <label>Type permis</label>
            <select name="chauffeur_type_permis">
                <option>B</option>
                <option>C</option>
                <option>D</option>
            </select>
        </div>
    </div>

</div>
      

        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="ajouter" class="btn btn-primary" style="flex:1">
                <i class="fa fa-save"></i> Créer l'assuré
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

        <!-- Infos identité en lecture seule -->
        <div class="section-divider" style="background:var(--gray-100);border-color:var(--gray-200);">
            <i class="fa fa-lock"></i> Identité (non modifiable)
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" value="<?= htmlspecialchars($edit['nom'].' '.$edit['prenom']) ?>"
                       style="background:var(--gray-100);color:var(--gray-500);cursor:not-allowed" readonly>
            </div>
            <div class="form-group">
                <label>CIN</label>
                <input type="text" value="<?= htmlspecialchars($edit['num_identite'] ?? '—') ?>"
                       style="background:var(--gray-100);color:var(--gray-500);cursor:not-allowed" readonly>
            </div>
        </div>

        <div class="section-divider" style="background:var(--blue-50);border-color:var(--blue-100);color:var(--blue-800);">
            <i class="fa fa-id-card" style="color:var(--blue-700)"></i> Informations assuré & permis
        </div>

        <div class="form-group">
            <label>Statut</label>
            <select name="actif">
                <option value="1" <?= $edit['actif']?'selected':'' ?>>Actif</option>
                <option value="0" <?= !$edit['actif']?'selected':'' ?>>Suspendu</option>
            </select>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>N° Permis</label>
                <input type="text" name="num_permis" value="<?= htmlspecialchars($edit['num_permis'] ?? '') ?>">
            </div>
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
            <div class="form-group">
                <label>Date délivrance</label>
                <input type="date" name="date_delivrance_permis" value="<?= $edit['date_delivrance_permis'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Lieu délivrance</label>
                <input type="text" name="lieu_delivrance_permis"
                       value="<?= htmlspecialchars($edit['lieu_delivrance_permis'] ?? '') ?>">
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="modifier" class="btn btn-primary" style="flex:1">
                <i class="fa fa-save"></i> Modifier
            </button>
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
            <button type="submit" name="creer_compte" class="btn btn-info" style="flex:1">
                <i class="fa fa-save"></i> Créer le compte
            </button>
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

// ── Vérification CIN en temps réel ──
const cinInput = document.getElementById('cin_add');

const cinError = document.getElementById('cin-error');
let cinInvalid = false;
let cinTimeout = null;
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

const permisInput = document.getElementById('permis_add');
const chauffeurError = document.getElementById('chauffeur-error');
const chauffeurInput = document.querySelector('[name="chauffeur_permis"]');
if (chauffeurInput) {
    chauffeurInput.addEventListener('input', () => {

        const type = document.getElementById('type_personne').value;
        if (type !== 'morale') return;

        const val = chauffeurInput.value.trim();
        if (val.length < 3) {
            chauffeurError.textContent = '';
            return;
        }

        fetch('check_permis.php?num=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(d => {
            if (d.exists) {
                chauffeurInput.classList.add('input-error');
                chauffeurError.innerHTML = '❌ Permis déjà utilisé';
                permisInvalid = true;
            } else {
                chauffeurInput.classList.remove('input-error');
                chauffeurError.textContent = '';
                permisInvalid = false;
            }
        });
    });
}
const permisError = document.getElementById('permis-error');
let permisInvalid = false;
let permisTimeout = null;

permisInput.addEventListener('input', () => {

    const type = document.getElementById('type_personne').value;

    // Ne vérifier que pour physique
    if (type !== 'physique') return;

    clearTimeout(permisTimeout);
    const val = permisInput.value.trim();

    if (val.length < 3) {
        permisInput.classList.remove('input-error');
        permisError.textContent = '';
        return;
    }

    permisTimeout = setTimeout(() => {
        fetch('check_permis.php?num=' + encodeURIComponent(val))
        .then(r => r.json())
        .then(d => {
            if (d.exists) {
                permisInput.classList.add('input-error');
                permisError.innerHTML = '❌ Ce numéro de permis existe déjà';
                permisInvalid = true;
            } else {
                permisInput.classList.remove('input-error');
                permisError.textContent = '';
                permisInvalid = false;
            }
        });
    }, 400);
});

function toggleTypeAssure() {

    const type = document.getElementById('type_personne').value;

    const cinField = document.getElementById('cin_field');
    const nifField = document.getElementById('nif_field');
    const cin = document.getElementById('cin_add');
    const nif = document.getElementById('nif_add');

    const nom = document.getElementById('nom');
    const prenom = document.getElementById('prenom');
    const raison = document.getElementById('raison_sociale');

    const date = document.getElementById('date_naissance');
    const lieu = document.getElementById('lieu_naissance');

    const physique = document.getElementById('physique_fields');
    const morale = document.getElementById('morale_fields');

    const permis = document.getElementById('permis_add');
    const permisFields = document.getElementById('permis_fields'); // 🔥 AJOUT IMPORTANT

    const conducteur = document.getElementById('conducteur_fields');
    const chauffeurPermis = document.querySelector('[name="chauffeur_permis"]');

    // ===== CIN / NIF =====
    if (type === 'physique') {
        cinField.style.display = '';
        nifField.style.display = 'none';

        cin.required = true;
        nif.required = false;
        nif.value = '';
    } else {
        cinField.style.display = 'none';
        nifField.style.display = '';

        cin.required = false;
        cin.value = '';

        nif.required = true;
    }

    // ===== CHAMPS =====
    if (type === 'physique') {

        physique.style.display = '';
        morale.style.display = 'none';

        nom.required = true;
        prenom.required = true;
        date.required = true;
        lieu.required = true;

        raison.required = false;
        raison.value = '';

        // 🔥 PERMIS
        permisFields.style.display = '';
        permis.required = true;

        // 🔥 CHAUFFEUR
        conducteur.style.display = 'none';
        chauffeurPermis.required = false;
        chauffeurPermis.value = '';

    } else {

        physique.style.display = 'none';
        morale.style.display = '';

        nom.required = false;
        prenom.required = false;
        date.required = false;
        lieu.required = false;

        raison.required = true;

        nom.value = '';
        prenom.value = '';
        date.value = '';
        lieu.value = '';

        // 🔥 PERMIS
        permisFields.style.display = 'none';
        permis.required = false;
        permis.value = '';

        // 🔥 CHAUFFEUR
        conducteur.style.display = '';
        chauffeurPermis.required = true;
    }
}
window.onload = toggleTypeAssure;
function confirmDeleteAssure(e, id) {
    e.preventDefault();
    Swal.fire({
        title: 'Supprimer cet assuré ?',
        text: 'Cette action est irréversible',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then(r => { if (r.isConfirmed) window.location.href = '?del=' + id; });
   
}
</script>

</body>
</html> 