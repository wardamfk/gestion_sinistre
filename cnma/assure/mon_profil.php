<?php
session_start();
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') {
    header("Location: ../pages/login.php"); exit();
}
$id_user = $_SESSION['id_user'];
$page_title = "Mon profil";

$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT u.*, p.*, a.num_permis, a.type_permis, a.date_delivrance_permis, a.actif AS statut_assure
     FROM utilisateur u
     LEFT JOIN personne p ON u.id_personne=p.id_personne
     LEFT JOIN assure a ON a.id_personne=u.id_personne
     WHERE u.id_user=$id_user"));

$success = $error = $pwd_success = $pwd_error = '';

// ===== Modifier coordonnées =====
if(isset($_POST['modifier'])) {
    $tel     = mysqli_real_escape_string($conn, $_POST['telephone']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $adresse = mysqli_real_escape_string($conn, $_POST['adresse']);
    $id_pers = $user['id_personne'];

    // Vérifier si l'email est déjà pris par un autre compte
    $check_email = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_user FROM utilisateur WHERE email='$email' AND id_user != $id_user"));
    if($check_email) {
        $error = "Cet email est déjà utilisé par un autre compte.";
    } else {
        mysqli_query($conn, "UPDATE personne SET telephone='$tel', email='$email', adresse='$adresse' WHERE id_personne=$id_pers");
        mysqli_query($conn, "UPDATE utilisateur SET email='$email' WHERE id_user=$id_user");
        $_SESSION['nom'] = trim($user['nom'].' '.$user['prenom']);
        $success = "Coordonnées mises à jour avec succès.";
        // Recharger les données
        $user = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT u.*, p.*, a.num_permis, a.type_permis, a.date_delivrance_permis, a.actif AS statut_assure
             FROM utilisateur u
             LEFT JOIN personne p ON u.id_personne=p.id_personne
             LEFT JOIN assure a ON a.id_personne=u.id_personne
             WHERE u.id_user=$id_user"));
    }
}

// ===== Changer mot de passe =====
if(isset($_POST['changer_mdp'])) {
    $ancien   = $_POST['ancien_mdp'];
    $nouveau  = $_POST['nouveau_mdp'];
    $confirm  = $_POST['confirm_mdp'];

    // Vérifications
    if(empty($ancien) || empty($nouveau) || empty($confirm)) {
        $pwd_error = "Tous les champs sont obligatoires.";
    } elseif(!password_verify($ancien, $user['mot_de_passe'])) {
        $pwd_error = "L'ancien mot de passe est incorrect.";
    } elseif(strlen($nouveau) < 6) {
        $pwd_error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } elseif($nouveau !== $confirm) {
        $pwd_error = "Les deux nouveaux mots de passe ne correspondent pas.";
    } elseif(password_verify($nouveau, $user['mot_de_passe'])) {
        $pwd_error = "Le nouveau mot de passe doit être différent de l'ancien.";
    } else {
        $hash = password_hash($nouveau, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE utilisateur SET mot_de_passe='$hash' WHERE id_user=$id_user");
        $pwd_success = "Mot de passe modifié avec succès. Utilisez-le lors de votre prochaine connexion.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon profil</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== EXTRAS PROFIL ===== */
.profil-hero {
    background: linear-gradient(135deg, #0d47a1 0%, #1565c0 60%, #1976d2 100%);
    border-radius: 16px;
    padding: 28px 30px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 22px;
    box-shadow: 0 6px 24px rgba(13,71,161,0.25);
}
.profil-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: rgba(255,255,255,0.18);
    border: 3px solid rgba(255,255,255,0.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; color: white; font-weight: 700;
    flex-shrink: 0;
}
.profil-hero-info h3 {
    color: white; font-size: 20px; font-weight: 700; margin-bottom: 5px;
}
.profil-hero-meta {
    display: flex; gap: 14px; flex-wrap: wrap; margin-top: 8px;
}
.profil-hero-meta span {
    color: rgba(255,255,255,0.75); font-size: 12.5px;
    display: flex; align-items: center; gap: 5px;
}

/* Tabs */
.profil-tabs {
    display: flex; gap: 4px;
    background: white; padding: 6px;
    border-radius: 12px; margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.profil-tab {
    flex: 1; padding: 10px 16px;
    border: none; border-radius: 8px;
    background: transparent; color: #78909c;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: all 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 7px;
}
.profil-tab:hover { background: #f0f4f8; color: #0d47a1; }
.profil-tab.active { background: #0d47a1; color: white; }

.profil-section { display: none; }
.profil-section.active { display: block; }

/* Carte info lecture */
.info-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #e3f2fd; color: #0d47a1;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 700;
}

/* Indicateur force MDP */
.pwd-strength-bar {
    height: 5px; border-radius: 3px;
    background: #e0e0e0; margin-top: 6px;
    transition: all 0.3s;
    overflow: hidden;
}
.pwd-strength-bar .fill {
    height: 100%; border-radius: 3px;
    transition: all 0.3s;
    width: 0%;
}
.pwd-strength-label {
    font-size: 11px; margin-top: 3px;
    font-weight: 600;
}

/* Input password toggle */
.pwd-input-wrap {
    position: relative;
}
.pwd-input-wrap input {
    padding-right: 42px !important;
}
.pwd-toggle {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #78909c; font-size: 14px; padding: 4px;
    transition: color 0.2s;
}
.pwd-toggle:hover { color: #0d47a1; }

/* Checklist MDP */
.pwd-checklist {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 4px; margin-top: 8px;
}
.pwd-check-item {
    font-size: 11px; color: #90a4ae;
    display: flex; align-items: center; gap: 5px;
    transition: color 0.2s;
}
.pwd-check-item.ok { color: #2e7d32; }
.pwd-check-item i { font-size: 10px; }

/* Readonly style */
.field-readonly {
    background: #f5f7f9 !important;
    color: #546e7a !important;
    cursor: not-allowed;
    border-color: #e0e0e0 !important;
}
.field-readonly:focus {
    border-color: #e0e0e0 !important;
    box-shadow: none !important;
}
</style>
</head>
<body>
<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>

<div class="assure-main">

    <!-- HERO PROFIL -->
    <div class="profil-hero">
        <div class="profil-avatar">
            <?= strtoupper(substr($user['prenom'] ?? $user['nom'] ?? 'A', 0, 1)); ?>
        </div>
        <div class="profil-hero-info">
            <h3><?= htmlspecialchars(trim($user['nom'].' '.$user['prenom'])); ?></h3>
            <div style="margin-bottom:6px;">
                <span class="info-badge" style="background:rgba(255,255,255,0.18);color:rgba(255,255,255,0.9);">
                    <i class="fa fa-circle-dot" style="font-size:8px;color:<?= $user['statut_assure'] ? '#69f0ae' : '#ff5252'; ?>;"></i>
                    <?= $user['statut_assure'] ? 'Compte actif' : 'Compte suspendu'; ?>
                </span>
            </div>
            <div class="profil-hero-meta">
                <span><i class="fa fa-envelope"></i> <?= htmlspecialchars($user['email']); ?></span>
                <span><i class="fa fa-phone"></i> <?= htmlspecialchars($user['telephone'] ?: 'Non renseigné'); ?></span>
                <span><i class="fa fa-map-marker-alt"></i> <?= htmlspecialchars($user['adresse'] ?: 'Non renseignée'); ?></span>
            </div>
        </div>
    </div>

    <!-- ONGLETS -->
    <div class="profil-tabs">
        <button class="profil-tab active" onclick="switchTab('infos', this)">
            <i class="fa fa-id-card"></i> Mes informations
        </button>
        <button class="profil-tab" onclick="switchTab('modifier', this)">
            <i class="fa fa-pen"></i> Modifier coordonnées
        </button>
        <button class="profil-tab" onclick="switchTab('securite', this)">
            <i class="fa fa-lock"></i> Sécurité
        </button>
    </div>

    <!-- ===== ONGLET 1 : INFORMATIONS ===== -->
    <div id="tab-infos" class="profil-section active">

        <?php if($success) echo "<div class='msg success'><i class='fa fa-check-circle'></i> $success</div>"; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <div class="assure-card">
                <h3><i class="fa fa-user-circle"></i> Identité</h3>
                <div class="info-row">
                    <span class="lbl">Nom complet</span>
                    <span class="val"><?= htmlspecialchars(trim($user['nom'].' '.$user['prenom'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Type de personne</span>
                    <span class="val">
                        <span class="info-badge">
                            <i class="fa fa-<?= $user['type_personne']=='physique' ? 'user' : 'building'; ?>"></i>
                            <?= ucfirst($user['type_personne'] ?? '—'); ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="lbl">N° identité</span>
                    <span class="val"><?= htmlspecialchars($user['num_identite'] ?: '—'); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Date de naissance</span>
                    <span class="val"><?= $user['date_naissance'] ?: '—'; ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Lieu de naissance</span>
                    <span class="val"><?= htmlspecialchars($user['lieu_naissance'] ?: '—'); ?></span>
                </div>
            </div>

            <div class="assure-card">
                <h3><i class="fa fa-car"></i> Permis de conduire</h3>
                <div class="info-row">
                    <span class="lbl">N° permis</span>
                    <span class="val"><?= htmlspecialchars($user['num_permis'] ?: '—'); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Type permis</span>
                    <span class="val">
                        <?php if($user['type_permis']): ?>
                        <span class="info-badge"><i class="fa fa-id-badge"></i> Catégorie <?= $user['type_permis']; ?></span>
                        <?php else: echo '—'; endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="lbl">Date délivrance</span>
                    <span class="val"><?= $user['date_delivrance_permis'] ?: '—'; ?></span>
                </div>
            </div>

            <div class="assure-card">
                <h3><i class="fa fa-address-book"></i> Coordonnées</h3>
                <div class="info-row">
                    <span class="lbl">Email</span>
                    <span class="val"><?= htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Téléphone</span>
                    <span class="val"><?= htmlspecialchars($user['telephone'] ?: '—'); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Adresse</span>
                    <span class="val"><?= htmlspecialchars($user['adresse'] ?: '—'); ?></span>
                </div>
            </div>

            <div class="assure-card">
                <h3><i class="fa fa-shield-halved"></i> Compte</h3>
                <div class="info-row">
                    <span class="lbl">Statut compte</span>
                    <span class="val">
                        <span class="badge-etat <?= $user['statut_assure'] ? 'green' : 'red'; ?>">
                            <i class="fa fa-<?= $user['statut_assure'] ? 'check-circle' : 'ban'; ?>"></i>
                            <?= $user['statut_assure'] ? 'Actif' : 'Suspendu'; ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="lbl">Rôle</span>
                    <span class="val"><span class="info-badge"><i class="fa fa-user-check"></i> Assuré</span></span>
                </div>
                <div style="margin-top:16px;padding-top:12px;border-top:1px solid #f0f4f8;">
                    <button class="assure-btn primary" onclick="switchTab('modifier', document.querySelectorAll('.profil-tab')[1])" style="width:100%;justify-content:center;">
                        <i class="fa fa-pen"></i> Modifier mes coordonnées
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ===== ONGLET 2 : MODIFIER COORDONNÉES ===== -->
    <div id="tab-modifier" class="profil-section">

        <?php if($success) echo "<div class='msg success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
        <?php if($error)   echo "<div class='msg warning'><i class='fa fa-exclamation-triangle'></i> $error</div>"; ?>

        <div class="assure-card" style="max-width:620px;margin:0 auto;">
            <h3><i class="fa fa-pen-to-square"></i> Modifier mes coordonnées</h3>

            <form method="POST">
                <div class="form-group">
                    <label>Nom <span style="color:#90a4ae;font-weight:400;">(non modifiable)</span></label>
                    <input type="text" value="<?= htmlspecialchars(trim($user['nom'].' '.$user['prenom'])); ?>"
                           class="field-readonly" readonly>
                </div>

                <div class="form-group">
                    <label>N° identité <span style="color:#90a4ae;font-weight:400;">(non modifiable)</span></label>
                    <input type="text" value="<?= htmlspecialchars($user['num_identite'] ?: '—'); ?>"
                           class="field-readonly" readonly>
                </div>

                <div style="height:1px;background:#f0f4f8;margin:18px 0;"></div>
                <p style="font-size:12px;color:#78909c;margin-bottom:16px;">
                    <i class="fa fa-info-circle"></i>
                    Seules vos coordonnées de contact peuvent être modifiées.
                </p>

                <div class="form-group">
                    <label>Téléphone <span style="color:#ef5350;">*</span></label>
                    <input type="tel" name="telephone"
                           value="<?= htmlspecialchars($user['telephone']); ?>"
                           placeholder="0550 00 00 00" required>
                </div>

                <div class="form-group">
                    <label>Email <span style="color:#ef5350;">*</span></label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($user['email']); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Adresse</label>
                    <input type="text" name="adresse"
                           value="<?= htmlspecialchars($user['adresse']); ?>"
                           placeholder="Votre adresse complète">
                </div>

                <div style="display:flex;gap:10px;margin-top:22px;">
                    <button type="submit" name="modifier" class="assure-btn primary" style="flex:1;justify-content:center;padding:12px;">
                        <i class="fa fa-save"></i> Enregistrer les modifications
                    </button>
                    <button type="reset" class="assure-btn secondary" style="padding:12px 18px;">
                        <i class="fa fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== ONGLET 3 : SÉCURITÉ / CHANGER MDP ===== -->
    <div id="tab-securite" class="profil-section">

        <?php if($pwd_success) echo "<div class='msg success'><i class='fa fa-check-circle'></i> $pwd_success</div>"; ?>
        <?php if($pwd_error)   echo "<div class='msg warning'><i class='fa fa-exclamation-triangle'></i> $pwd_error</div>"; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

            <!-- FORMULAIRE CHANGEMENT MDP -->
            <div class="assure-card">
                <h3><i class="fa fa-lock"></i> Changer mon mot de passe</h3>

                <form method="POST" id="pwdForm">

                    <!-- Ancien MDP -->
                    <div class="form-group">
                        <label>Ancien mot de passe <span style="color:#ef5350;">*</span></label>
                        <div class="pwd-input-wrap">
                            <input type="password" name="ancien_mdp" id="ancien_mdp"
                                   placeholder="Votre mot de passe actuel" required
                                   autocomplete="current-password">
                            <button type="button" class="pwd-toggle" onclick="togglePwd('ancien_mdp', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div style="height:1px;background:#f0f4f8;margin:16px 0;"></div>

                    <!-- Nouveau MDP -->
                    <div class="form-group">
                        <label>Nouveau mot de passe <span style="color:#ef5350;">*</span></label>
                        <div class="pwd-input-wrap">
                            <input type="password" name="nouveau_mdp" id="nouveau_mdp"
                                   placeholder="Minimum 6 caractères" required
                                   autocomplete="new-password"
                                   oninput="evalPwd(this.value)">
                            <button type="button" class="pwd-toggle" onclick="togglePwd('nouveau_mdp', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <!-- Barre de force -->
                        <div class="pwd-strength-bar"><div class="fill" id="strengthFill"></div></div>
                        <div class="pwd-strength-label" id="strengthLabel"></div>
                        <!-- Checklist -->
                        <div class="pwd-checklist" id="pwdChecklist">
                            <div class="pwd-check-item" id="chk-len"><i class="fa fa-circle-xmark"></i> 6 caractères min.</div>
                            <div class="pwd-check-item" id="chk-maj"><i class="fa fa-circle-xmark"></i> Majuscule</div>
                            <div class="pwd-check-item" id="chk-num"><i class="fa fa-circle-xmark"></i> Chiffre</div>
                            <div class="pwd-check-item" id="chk-spe"><i class="fa fa-circle-xmark"></i> Caractère spécial</div>
                        </div>
                    </div>

                    <!-- Confirmer MDP -->
                    <div class="form-group">
                        <label>Confirmer le nouveau mot de passe <span style="color:#ef5350;">*</span></label>
                        <div class="pwd-input-wrap">
                            <input type="password" name="confirm_mdp" id="confirm_mdp"
                                   placeholder="Répétez le nouveau mot de passe" required
                                   autocomplete="new-password"
                                   oninput="checkMatch()">
                            <button type="button" class="pwd-toggle" onclick="togglePwd('confirm_mdp', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <div id="matchMsg" style="font-size:11px;margin-top:4px;font-weight:600;"></div>
                    </div>

                    <button type="submit" name="changer_mdp" class="assure-btn primary"
                            style="width:100%;justify-content:center;padding:13px;margin-top:8px;font-size:14px;">
                        <i class="fa fa-key"></i> Changer le mot de passe
                    </button>

                </form>
            </div>

            <!-- CONSEILS SÉCURITÉ -->
            <div>
                <div class="assure-card" style="border-left:4px solid #0d47a1;">
                    <h3><i class="fa fa-shield-halved"></i> Conseils de sécurité</h3>
                    <div style="display:flex;flex-direction:column;gap:12px;margin-top:4px;">
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#e3f2fd;color:#0d47a1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa fa-key" style="font-size:13px;"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;color:#1a2a3a;margin-bottom:2px;">Mot de passe fort</div>
                                <div style="font-size:12px;color:#78909c;">Utilisez au moins 8 caractères avec majuscules, chiffres et symboles.</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#e8f5e9;color:#2e7d32;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa fa-user-secret" style="font-size:13px;"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;color:#1a2a3a;margin-bottom:2px;">Confidentialité</div>
                                <div style="font-size:12px;color:#78909c;">Ne partagez jamais votre mot de passe avec quelqu'un, même un agent CRMA.</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#fff3e0;color:#e65100;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa fa-rotate" style="font-size:13px;"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;color:#1a2a3a;margin-bottom:2px;">Renouvellement régulier</div>
                                <div style="font-size:12px;color:#78909c;">Changez votre mot de passe tous les 3 à 6 mois pour plus de sécurité.</div>
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:32px;height:32px;border-radius:8px;background:#fce4ec;color:#c62828;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa fa-triangle-exclamation" style="font-size:13px;"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;color:#1a2a3a;margin-bottom:2px;">Mot de passe unique</div>
                                <div style="font-size:12px;color:#78909c;">N'utilisez pas le même mot de passe sur d'autres sites ou applications.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dernière activité (simulation) -->
                <div class="assure-card" style="margin-top:0;">
                    <h3><i class="fa fa-clock-rotate-left"></i> Sécurité du compte</h3>
                    <div class="info-row">
                        <span class="lbl">Statut</span>
                        <span class="val">
                            <span class="badge-etat green"><i class="fa fa-lock"></i> Protégé</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Méthode d'auth.</span>
                        <span class="val">Email + Mot de passe</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div><!-- fin assure-main -->

<script>
// ===== Gestion des onglets =====
function switchTab(id, btn) {
    document.querySelectorAll('.profil-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.profil-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    if(btn) btn.classList.add('active');
}

// Ouvrir automatiquement l'onglet en erreur
<?php if($error): ?>
switchTab('modifier', document.querySelectorAll('.profil-tab')[1]);
<?php elseif($pwd_error || $pwd_success): ?>
switchTab('securite', document.querySelectorAll('.profil-tab')[2]);
<?php endif; ?>

// ===== Toggle afficher/masquer mot de passe =====
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if(input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}

// ===== Évaluation force du mot de passe =====
function evalPwd(v) {
    const checks = {
        len: v.length >= 6,
        maj: /[A-Z]/.test(v),
        num: /[0-9]/.test(v),
        spe: /[^A-Za-z0-9]/.test(v)
    };

    // Mise à jour checklist
    Object.keys(checks).forEach(k => {
        const el = document.getElementById('chk-' + k);
        if(checks[k]) {
            el.classList.add('ok');
            el.querySelector('i').className = 'fa fa-circle-check';
        } else {
            el.classList.remove('ok');
            el.querySelector('i').className = 'fa fa-circle-xmark';
        }
    });

    // Calcul score
    const score = Object.values(checks).filter(Boolean).length;
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    const levels = [
        { w:'0%',   c:'#e0e0e0', t:'' },
        { w:'25%',  c:'#ef5350', t:'Très faible' },
        { w:'50%',  c:'#f57c00', t:'Moyen' },
        { w:'75%',  c:'#fdd835', t:'Bon' },
        { w:'90%',  c:'#66bb6a', t:'Fort' },
        { w:'100%', c:'#2e7d32', t:'Très fort' },
    ];
    const lv = v.length === 0 ? levels[0] : (v.length < 6 ? levels[1] : levels[Math.min(score+1, 5)]);
    fill.style.width  = lv.w;
    fill.style.background = lv.c;
    label.textContent = lv.t;
    label.style.color = lv.c;

    checkMatch();
}

// ===== Vérification correspondance =====
function checkMatch() {
    const n = document.getElementById('nouveau_mdp').value;
    const c = document.getElementById('confirm_mdp').value;
    const msg = document.getElementById('matchMsg');
    if(!c) { msg.textContent = ''; return; }
    if(n === c) {
        msg.textContent = '✓ Les mots de passe correspondent';
        msg.style.color = '#2e7d32';
    } else {
        msg.textContent = '✗ Les mots de passe ne correspondent pas';
        msg.style.color = '#ef5350';
    }
}
</script>

</body>
</html>