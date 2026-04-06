<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') { header("Location: login.php"); exit(); }

$page_title = "Gestion des utilisateurs";
$success = ''; $error = '';

// === CRÉER UTILISATEUR ===
if(isset($_POST['creer'])) {
    $nom      = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role     = $_POST['role'];
    $id_agence= !empty($_POST['id_agence']) ? intval($_POST['id_agence']) : null;
    $pwd      = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if(strlen($_POST['password']) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $check = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM utilisateur WHERE email='$email'"));
        if($check > 0) {
            $error = "Cet email est déjà utilisé par un autre compte.";
        } else {
            $id_ag_sql = $id_agence ? $id_agence : 'NULL';
            $sql = "INSERT INTO utilisateur (nom, email, mot_de_passe, role, id_agence, actif)
                    VALUES ('$nom', '$email', '$pwd', '$role', $id_ag_sql, 1)";
            if(mysqli_query($conn, $sql)) {
                $success = "Compte créé avec succès pour <b>$nom</b> ($role).";
            } else {
                $error = "Erreur SQL : " . mysqli_error($conn);
            }
        }
    }
}

// === ACTIVER / DÉSACTIVER ===
if(isset($_GET['toggle'], $_GET['uid'])) {
    $uid = intval($_GET['uid']);
    if($uid != $_SESSION['id_user']) {
        $actuel = mysqli_fetch_assoc(mysqli_query($conn, "SELECT actif FROM utilisateur WHERE id_user=$uid"))['actif'];
        mysqli_query($conn, "UPDATE utilisateur SET actif=".($actuel ? 0 : 1)." WHERE id_user=$uid");
    }
    header("Location: gestion_utilisateurs.php?ok=toggle"); exit();
}

// === SUPPRIMER ===
if(isset($_GET['del'], $_GET['uid'])) {
    $uid = intval($_GET['uid']);
    if($uid != $_SESSION['id_user']) {
        // Vérifier qu'il n'a pas de dossiers
        $nb_dossiers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM dossier WHERE cree_par=$uid"))['n'];
        if($nb_dossiers > 0) {
            header("Location: gestion_utilisateurs.php?err=has_dossiers"); exit();
        }
        mysqli_query($conn, "DELETE FROM utilisateur WHERE id_user=$uid");
    }
    header("Location: gestion_utilisateurs.php?ok=del"); exit();
}

// === MODIFIER MOT DE PASSE ===
if(isset($_POST['reset_pwd'])) {
    $uid  = intval($_POST['uid']);
    $pwd  = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    mysqli_query($conn, "UPDATE utilisateur SET mot_de_passe='$pwd' WHERE id_user=$uid");
    $success = "Mot de passe réinitialisé avec succès.";
}

// Lire utilisateurs
$filtre_role = isset($_GET['role']) ? $_GET['role'] : '';
$where_role  = $filtre_role ? "WHERE u.role='$filtre_role'" : '';

$utilisateurs = mysqli_query($conn, "
SELECT 
    u.*,
    p.nom AS nom_personne,
    p.prenom AS prenom_personne,

    -- Agence pour agent (CRMA)
    a.nom_agence AS agence_user,
    a.wilaya AS wilaya_user,

    -- Agence pour assuré (via contrat)
    a2.nom_agence AS agence_assure,
    a2.wilaya AS wilaya_assure,

    (SELECT COUNT(*) FROM dossier d WHERE d.cree_par = u.id_user) as nb_dossiers

FROM utilisateur u
LEFT JOIN personne p ON u.id_personne = p.id_personne
LEFT JOIN agence a ON u.id_agence = a.id_agence

LEFT JOIN assure ass ON ass.id_personne = p.id_personne
LEFT JOIN contrat c ON c.id_assure = ass.id_assure
LEFT JOIN agence a2 ON c.id_agence = a2.id_agence

$where_role
ORDER BY u.role, u.nom
     ");
$agences = mysqli_query($conn, "SELECT * FROM agence ORDER BY type_agence, nom_agence");
$agences_arr = [];
while($a = mysqli_fetch_assoc($agences)) $agences_arr[] = $a;

// Compteurs
$nb_cnma   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM utilisateur WHERE role='CNMA'"))['n'];
$nb_crma   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM utilisateur WHERE role='CRMA'"))['n'];
$nb_assure = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM utilisateur WHERE role='ASSURE'"))['n'];
$nb_actifs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM utilisateur WHERE actif=1"))['n'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Utilisateurs — CNMA</title>
    <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .main-layout { display: grid; grid-template-columns: 420px 1fr; gap: 24px; align-items: start; }
        .form-sticky { position: sticky; top: 85px; }
        .user-table-wrap { overflow-x: auto; }
        .pwd-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; }
        .pwd-modal.open { display:flex; }
        .pwd-box { background:white; border-radius:14px; padding:30px; width:420px; box-shadow:0 10px 40px rgba(0,0,0,0.2); }
        .pwd-box h3 { margin-bottom:20px; color:#1a237e; }
        .user-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
        .u-stat { background:white; border-radius:12px; padding:16px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .u-stat .n { font-size:26px; font-weight:700; color:#1a237e; }
        .u-stat .l { font-size:11px; color:#78909c; font-weight:600; text-transform:uppercase; }
        .role-filter { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
        .role-filter a { padding:7px 16px; border-radius:20px; text-decoration:none; font-size:12px; font-weight:700; border:1.5px solid #e0e0e0; color:#546e7a; transition:0.2s; }
        .role-filter a:hover, .role-filter a.active { border-color:#1a237e; background:#1a237e; color:white; }
    </style>
</head>
<body>
<?php include("sidebar_cnma.php"); ?>
<?php include("header_cnma.php"); ?>

<div class="cnma-main">
    <div class="page-heading">
        <h2><i class="fa fa-users"></i> Gestion des utilisateurs</h2>
    </div>

    <?php if(!empty($success)) echo "<div class='msg success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
    <?php if(!empty($error))   echo "<div class='msg error'><i class='fa fa-exclamation-circle'></i> $error</div>"; ?>
    <?php if(isset($_GET['ok']) && $_GET['ok']=='toggle') echo "<div class='msg info'><i class='fa fa-info-circle'></i> Statut modifié.</div>"; ?>
    <?php if(isset($_GET['ok']) && $_GET['ok']=='del')    echo "<div class='msg success'><i class='fa fa-trash'></i> Utilisateur supprimé.</div>"; ?>
    <?php if(isset($_GET['err']) && $_GET['err']=='has_dossiers') echo "<div class='msg warning'><i class='fa fa-warning'></i> Impossible de supprimer — cet utilisateur a des dossiers associés.</div>"; ?>

    <!-- MINI STATS -->
    <div class="user-stats">
        <div class="u-stat">
            <div class="n"><?php echo $nb_cnma + $nb_crma + $nb_assure; ?></div>
            <div class="l">Total comptes</div>
        </div>
        <div class="u-stat">
            <div class="n" style="color:#283593;"><?php echo $nb_cnma; ?></div>
            <div class="l">Admins CNMA</div>
        </div>
        <div class="u-stat">
            <div class="n" style="color:#1b5e20;"><?php echo $nb_crma; ?></div>
            <div class="l">Agents CRMA</div>
        </div>
        <div class="u-stat">
            <div class="n" style="color:#2e7d32;"><?php echo $nb_actifs; ?></div>
            <div class="l">Comptes actifs</div>
        </div>
    </div>

    <div class="main-layout">

        <!-- FORMULAIRE -->
        <div class="form-sticky">
            <div class="cnma-form-card">
                <h3><i class="fa fa-user-plus" style="background:#e8eaf6; color:#1a237e; padding:8px; border-radius:8px;"></i> Créer un nouveau compte</h3>

                <form method="POST" id="createForm">
                    <div class="form-group">
                        <label>Nom complet <span style="color:red;">*</span></label>
                        <input type="text" name="nom" required placeholder="Ex: Agent Alger" minlength="3">
                    </div>

                    <div class="form-group">
                        <label>Adresse email <span style="color:red;">*</span></label>
                        <input type="email" name="email" required placeholder="exemple@cnma.dz">
                    </div>

                    <div class="form-group">
                        <label>Mot de passe <span style="color:red;">*</span></label>
                        <input type="password" name="password" required placeholder="Minimum 6 caractères" minlength="6"
                               id="pwd_input" oninput="checkPwd()">
                        <div id="pwd_strength" style="height:4px; border-radius:2px; margin-top:6px; transition:0.3s; background:#e0e0e0;"></div>
                        <small id="pwd_msg" style="color:#78909c; font-size:11px;"></small>
                    </div>

                    <div class="form-group">
                        <label>Rôle <span style="color:red;">*</span></label>
                        <select name="role" required id="role_sel" onchange="toggleAgence()">
                            <option value="">— Sélectionner un rôle —</option>
                            <option value="CNMA">CNMA — Administrateur</option>
                            <option value="CRMA">CRMA — Agent de gestion</option>
                        </select>
                    </div>

                    <div class="form-group" id="agence_group" style="display:none;">
                        <label>Agence CRMA <span style="color:red;">*</span></label>
                        <select name="id_agence" id="agence_sel">
                            <option value="">— Choisir l'agence —</option>
                            <?php foreach($agences_arr as $ag): ?>
                            <?php if($ag['type_agence'] == 'CRMA'): ?>
                            <option value="<?php echo $ag['id_agence']; ?>">
                                <?php echo $ag['nom_agence'].' — '.$ag['wilaya']; ?>
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="background:#f5f7ff; border-radius:8px; padding:12px; margin-bottom:18px; font-size:12px; color:#546e7a;">
                        <b>CNMA</b> = Accès admin complet (tableau de bord, validation, statistiques, utilisateurs)<br>
                        <b>CRMA</b> = Accès agent (dossiers, contrats, assurés, règlements)
                    </div>

                    <button type="submit" name="creer" class="cnma-btn primary" style="width:100%; justify-content:center; padding:13px; font-size:15px;">
                        <i class="fa fa-save"></i> Créer le compte
                    </button>
                </form>
            </div>
        </div>

        <!-- LISTE UTILISATEURS -->
        <div>
            <!-- Filtres par rôle -->
            <div class="role-filter">
                <a href="gestion_utilisateurs.php" class="<?php echo !$filtre_role ? 'active' : ''; ?>">
                    <i class="fa fa-users"></i> Tous
                </a>
                <a href="?role=CNMA" class="<?php echo $filtre_role=='CNMA' ? 'active' : ''; ?>">
                    <i class="fa fa-user-shield"></i> CNMA (<?php echo $nb_cnma; ?>)
                </a>
                <a href="?role=CRMA" class="<?php echo $filtre_role=='CRMA' ? 'active' : ''; ?>">
                    <i class="fa fa-user-tie"></i> CRMA (<?php echo $nb_crma; ?>)
                </a>
                <a href="?role=ASSURE" class="<?php echo $filtre_role=='ASSURE' ? 'active' : ''; ?>">
                    <i class="fa fa-user"></i> Assurés (<?php echo $nb_assure; ?>)
                </a>
            </div>

            <div class="user-table-wrap">
                <table class="cnma-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Agence / Wilaya</th>
                            <th>Dossiers</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                  <tbody>
<?php
$count = 0;
while($u = mysqli_fetch_assoc($utilisateurs)):
    $count++;

    $nom = $u['nom'] ?? $u['nom_personne'] ?? '';
    $prenom = $u['prenom'] ?? $u['prenom_personne'] ?? '';

    // Couleur avatar selon rôle
    $av_colors = ['CNMA'=>['#283593','#e8eaf6'], 'CRMA'=>['#1b5e20','#e8f5e9'], 'ASSURE'=>['#0d47a1','#e3f2fd']];
    $av = $av_colors[$u['role']] ?? ['#546e7a','#eceff1'];
    $initial = strtoupper(substr($nom ?: 'U', 0, 1));
?>
                    <tr>
                        <td>
                            <div class="user-card-row">
                                <div class="user-avatar" style="background:<?php echo $av[1]; ?>; color:<?php echo $av[0]; ?>;">
                                    <?php echo $initial; ?>
                                </div>
                                <div>
                                    <div style="font-weight:700; color:#2c3e50;"><?php echo htmlspecialchars($nom); ?></div>
                                    <div style="font-size:11px; color:#90a4ae;">#<?php echo $u['id_user']; ?>
                                        <?php if($u['id_user'] == $_SESSION['id_user']) echo " <b style='color:#f57c00;'>(vous)</b>"; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:13px; color:#546e7a;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="badge-role <?php echo $u['role']; ?>">
                                <i class="fa <?php echo $u['role']=='CNMA'?'fa-user-shield':($u['role']=='CRMA'?'fa-user-tie':'fa-user'); ?>"></i>
                                <?php echo $u['role']; ?>
                            </span>
                        </td>
                        
<td>
<?php
$agence = $u['agence_user'] ?? $u['agence_assure'] ?? '';
$wilaya = $u['wilaya_user'] ?? $u['wilaya_assure'] ?? '';
?>

<?php if($agence): ?>
    <div style="font-weight:600; font-size:13px;"><?php echo htmlspecialchars($agence); ?></div>
    <div style="font-size:11px; color:#90a4ae;"><?php echo htmlspecialchars($wilaya); ?></div>
<?php else: ?>
    <span style="color:#b0bec5; font-size:12px;">—</span>
<?php endif; ?>
</td>
                        <td style="text-align:center;">
                            <?php if($u['nb_dossiers'] > 0): ?>
                            <span style="background:#e8eaf6; color:#1a237e; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px;">
                                <?php echo $u['nb_dossiers']; ?>
                            </span>
                            <?php else: ?>
                            <span style="color:#b0bec5; font-size:12px;">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-status <?php echo $u['actif'] ? 'actif' : 'inactif'; ?>">
                                <i class="fa <?php echo $u['actif'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                                <?php echo $u['actif'] ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($u['id_user'] != $_SESSION['id_user']): ?>
                            <div style="display:flex; gap:5px; flex-wrap:wrap;">
                                <!-- Activer/Désactiver -->
                                <a href="gestion_utilisateurs.php?toggle=1&uid=<?php echo $u['id_user']; ?>"
                                   class="cnma-btn sm <?php echo $u['actif'] ? 'danger' : 'success'; ?>"
                                   onclick="return confirm('<?php echo $u['actif'] ? 'Désactiver' : 'Activer'; ?> ce compte ?')"
                                   title="<?php echo $u['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                    <i class="fa <?php echo $u['actif'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    <?php echo $u['actif'] ? 'Désactiver' : 'Activer'; ?>
                                </a>

                                <!-- Réinitialiser MDP -->
                                <button type="button" class="cnma-btn sm warning"
                                     onclick="openPwd(<?php echo $u['id_user']; ?>, '<?php echo htmlspecialchars($nom); ?>')"
                                        title="Réinitialiser mot de passe">
                                    <i class="fa fa-key"></i>
                                </button>

                                <!-- Supprimer -->
                                <?php if($u['nb_dossiers'] == 0): ?>
                                <a href="gestion_utilisateurs.php?del=1&uid=<?php echo $u['id_user']; ?>"
                                   class="cnma-btn sm secondary"
                                   onclick="return confirm('Supprimer définitivement ce compte ?')"
                                   title="Supprimer">
                                    <i class="fa fa-trash"></i>
                                </a>
                                <?php else: ?>
                                <span class="cnma-btn sm secondary" style="opacity:0.4; cursor:not-allowed;" title="Impossible — a des dossiers">
                                    <i class="fa fa-trash"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span style="color:#90a4ae; font-size:12px; font-style:italic;">Votre compte</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($count == 0): ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="fa fa-users"></i><p>Aucun utilisateur trouvé</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p style="color:#90a4ae; font-size:12px; margin-top:10px;"><?php echo $count; ?> compte(s) affiché(s)</p>
        </div>
    </div>
</div>

<!-- MODAL RÉINITIALISATION MDP -->
<div class="pwd-modal" id="pwdModal">
    <div class="pwd-box">
        <h3><i class="fa fa-key"></i> Réinitialiser le mot de passe</h3>
        <p id="pwd_user_name" style="color:#546e7a; margin-bottom:20px; font-size:14px;"></p>
        <form method="POST">
            <input type="hidden" name="uid" id="pwd_uid">
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 caractères" style="font-size:15px; padding:12px;">
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="reset_pwd" class="cnma-btn success" style="flex:1; justify-content:center; padding:12px;">
                    <i class="fa fa-save"></i> Enregistrer
                </button>
                <button type="button" class="cnma-btn secondary" onclick="closePwd()" style="padding:12px 18px;">
                    Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAgence() {
    const role = document.getElementById('role_sel').value;
    const grp  = document.getElementById('agence_group');
    const sel  = document.getElementById('agence_sel');
    grp.style.display = (role === 'CRMA') ? 'block' : 'none';
    sel.required = (role === 'CRMA');
}

function checkPwd() {
    const v = document.getElementById('pwd_input').value;
    const bar = document.getElementById('pwd_strength');
    const msg = document.getElementById('pwd_msg');
    let strength = 0;
    if(v.length >= 6) strength++;
    if(v.length >= 10) strength++;
    if(/[A-Z]/.test(v)) strength++;
    if(/[0-9]/.test(v)) strength++;
    if(/[^A-Za-z0-9]/.test(v)) strength++;
    const colors = ['#e0e0e0','#ef5350','#f57c00','#fdd835','#66bb6a','#2e7d32'];
    const msgs   = ['','Très faible','Faible','Moyen','Fort','Très fort'];
    bar.style.background = colors[strength];
    bar.style.width = (strength * 20) + '%';
    msg.textContent = msgs[strength] || '';
    msg.style.color = colors[strength];
}

function openPwd(uid, nom) {
    document.getElementById('pwd_uid').value = uid;
    document.getElementById('pwd_user_name').textContent = 'Compte : ' + nom;
    document.getElementById('pwdModal').classList.add('open');
}

function closePwd() {
    document.getElementById('pwdModal').classList.remove('open');
}

document.getElementById('pwdModal').addEventListener('click', function(e) {
    if(e.target === this) closePwd();
});

toggleAgence();
</script>
</body>
</html>