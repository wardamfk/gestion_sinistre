<?php
// ============================================================
// voir_dossier.php — Interface simplifiée
// État géré par sélection manuelle (dropdown)
// Motifs affichés dynamiquement selon l'état sélectionné
// ============================================================
include('../includes/config.php');
session_start();

if (!isset($_GET['id'])) { echo "Dossier introuvable"; exit(); }
$id_dossier = intval($_GET['id']);
$user_id    = $_SESSION['id_user'];
$role       = $_SESSION['role'];

// Charger le dossier complet
$dossier = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT d.*, e.nom_etat, e.motif_obligatoire,
           p.nom AS nom_assure, p.prenom AS prenom_assure, p.telephone,
           pt.nom AS nom_tiers, pt.prenom AS prenom_tiers,
           t.compagnie_assurance, t.responsable,
           c.numero_police,
           v.marque, v.modele, v.matricule,
           ex.nom AS nom_expert, ex.prenom AS prenom_expert,
           ag.nom_agence, ag.wilaya
    FROM dossier d
    LEFT JOIN etat_dossier e ON d.id_etat = e.id_etat
    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure ass ON c.id_assure = ass.id_assure
    LEFT JOIN personne p ON ass.id_personne = p.id_personne
    LEFT JOIN tiers t ON d.id_tiers = t.id_tiers
    LEFT JOIN personne pt ON t.id_personne = pt.id_personne
    LEFT JOIN vehicule v ON c.id_vehicule = v.id_vehicule
    LEFT JOIN expert ex ON d.id_expert = ex.id_expert
    LEFT JOIN agence ag ON ag.id_agence = (SELECT id_agence FROM utilisateur WHERE id_user = d.cree_par LIMIT 1)
    WHERE d.id_dossier = $id_dossier
"));

if (!$dossier) { die("Dossier introuvable"); }

// Totaux financiers
$total_reserve = floatval(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(montant),0) as total 
     FROM reserve 
     WHERE id_dossier=$id_dossier AND statut='actif'"
))['total']);
$total_regle   = floatval(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(montant),0) as t FROM reglement WHERE id_dossier=$id_dossier"))['t']);
$total_enc     = floatval(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(montant),0) as t FROM encaissement WHERE id_dossier=$id_dossier"))['t']);
$reste = max(0, $total_reserve - $total_regle);

// Tous les états disponibles (pour le sélecteur)
if($role == 'CRMA'){
    $tous_etats = mysqli_query($conn, "
        SELECT * FROM etat_dossier 
        WHERE id_etat NOT IN (1,3,4,5,6) 
        ORDER BY id_etat
    ");
} else {
    // CNMA
    $tous_etats = mysqli_query($conn, "
        SELECT * FROM etat_dossier 
        WHERE id_etat IN (4,5,6)
        ORDER BY id_etat
    ");
}

// Règlement autorisé si état validé ou en règlement partiel
$reglement_ok = in_array($dossier['id_etat'], [2, 4, 7,9,15, 18]);
// Encaissement autorisé si tiers responsable ET dossier en règlement
$enc_ok = in_array($dossier['responsable'], ['oui', 'partiel'])
       && in_array($dossier['id_etat'], [7, 8, 13, 14, 19, 20]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dossier <?= htmlspecialchars($dossier['numero_dossier']); ?></title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── Hero ── */

.dossier-hero .dh-num { font-size:18px;font-weight:700;color:#fff;font-family:'DM Mono',monospace; }
.dossier-hero .dh-meta { display:flex;gap:14px;flex-wrap:wrap; }
.dossier-hero .dh-meta span { font-size:12px;color:rgba(255,255,255,.7);display:flex;align-items:center;gap:5px; }
.badge-hero { display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:999px;
    font-size:11.5px;font-weight:600;background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.2); }

/* ── KPI ── */
.kpi-bar { display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px; }
.kpi-item { background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);
    padding:12px 16px;display:flex;align-items:center;gap:12px; }
.kpi-icon { width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0; }
.kpi-label { font-size:10px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px; }
.kpi-value { font-size:20px;font-weight:700;font-family:'DM Mono',monospace;line-height:1; }
.kpi-value small { font-size:11px;font-weight:400;color:var(--gray-400);margin-left:2px; }
.kpi-reserve .kpi-icon{background:var(--blue-50);color:var(--blue-700)} .kpi-reserve .kpi-value{color:var(--blue-800)}
.kpi-regle   .kpi-icon{background:var(--green-100);color:var(--green-700)} .kpi-regle .kpi-value{color:var(--green-800)}
.kpi-reste   .kpi-icon{background:var(--red-50);color:var(--red-600)} .kpi-reste .kpi-value{color:var(--red-600)}
.kpi-reste.ok .kpi-icon{background:var(--green-100);color:var(--green-700)} .kpi-reste.ok .kpi-value{color:var(--green-700)}
.kpi-enc     .kpi-icon{background:var(--teal-50);color:var(--teal-700)} .kpi-enc .kpi-value{color:var(--teal-700)}

/* ── État selector box ── */
.etat-box {
    background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius-lg);
    padding:18px 22px;margin-bottom:16px;
}
.etat-box-title {
    font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.7px;
    margin-bottom:14px;display:flex;align-items:center;gap:8px;
}
.etat-box-title i { color:var(--green-700); }
.etat-form-row { display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap; }
.etat-form-row .fg { display:flex;flex-direction:column;gap:5px;min-width:200px; }
.etat-form-row label { font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px; }
.etat-form-row select, .etat-form-row textarea, .etat-form-row input {
    padding:9px 12px;border:1.5px solid var(--gray-200);border-radius:var(--radius);
    font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:var(--gray-50);
    transition:border-color .18s;
}
.etat-form-row select:focus, .etat-form-row textarea:focus {
    border-color:var(--green-600);outline:none;background:#fff;
}
.etat-current {
    display:inline-flex;align-items:center;gap:8px;padding:8px 16px;
    border-radius:var(--radius);background:var(--green-50);border:1px solid var(--green-200);
    font-size:13px;font-weight:600;color:var(--green-800);margin-bottom:14px;
}

/* ── Motif section (cachée par défaut) ── */
#motif-section { display:none; }
#motif-section.show { display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-top:12px; }

/* ── Info rows ── */
.info-row-crma{display:flex;justify-content:space-between;align-items:flex-start;
    padding:8px 0;border-bottom:1px solid #f0f4f8;font-size:13.5px}
.info-row-crma:last-child{border-bottom:none}
.info-row-crma span:first-child{color:var(--gray-500)}
.info-row-crma span:last-child{font-weight:500;color:var(--gray-800);text-align:right;max-width:60%}

/* Messages */
.msg-ok{background:var(--green-100);color:var(--green-800);border-left:3px solid var(--green-600);
    padding:12px 16px;border-radius:var(--radius);margin-bottom:14px;font-size:13.5px;display:flex;align-items:center;gap:8px}
.msg-err{background:var(--red-50);color:var(--red-700);border-left:3px solid var(--red-600);
    padding:12px 16px;border-radius:var(--radius);margin-bottom:14px;font-size:13.5px;display:flex;align-items:center;gap:8px}
.msg-info{background:var(--blue-50);color:var(--blue-800);border-left:3px solid var(--blue-600);
    padding:12px 16px;border-radius:var(--radius);margin-bottom:14px;font-size:13.5px;display:flex;align-items:center;gap:8px}

/* Encaissement form */
.enc-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:flex-end}
.enc-form-grid .fg{display:flex;flex-direction:column;gap:5px}
.enc-form-grid label{font-size:10.5px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px}
.enc-form-grid input,.enc-form-grid select{
    padding:8px 10px;border:1px solid var(--gray-300);border-radius:var(--radius);
    font-size:13px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:#fff}
.enc-form-grid input:focus,.enc-form-grid select:focus{border-color:var(--green-600);outline:none}
</style>
</head>
<body>
<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="main" style="padding-top:16px;">

<!-- HERO -->
<div class="dossier-hero">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div class="dh-num"><?= htmlspecialchars($dossier['numero_dossier']); ?></div>
        <span class="badge-hero">
            <i class="fa fa-circle" style="font-size:7px;opacity:.7;"></i>
            <?= htmlspecialchars($dossier['nom_etat']); ?>
        </span>
        <div class="dh-meta">
            <span><i class="fa fa-calendar"></i> <?= $dossier['date_sinistre']; ?></span>
            <span><i class="fa fa-map-marker-alt"></i> <?= htmlspecialchars($dossier['lieu_sinistre']); ?></span>
        </div>
    </div>
    <div style="font-size:12px;color:rgba(255,255,255,.6);">
        <i class="fa fa-building" style="margin-right:4px;"></i>
        <?= htmlspecialchars($dossier['nom_agence'] ?? ''); ?> · <?= htmlspecialchars($dossier['wilaya'] ?? ''); ?>
    </div>
</div>

<!-- KPI -->
<div class="kpi-bar">
    <div class="kpi-item kpi-reserve">
        <div class="kpi-icon"><i class="fa fa-shield-halved"></i></div>
        <div>
            <div class="kpi-label">Réserves</div>
            <div class="kpi-value"><?= number_format($total_reserve,0,',',' '); ?><small>DA</small></div>
        </div>
    </div>
    <div class="kpi-item kpi-regle">
        <div class="kpi-icon"><i class="fa fa-money-bill-wave"></i></div>
        <div>
            <div class="kpi-label">Réglé</div>
            <div class="kpi-value"><?= number_format($total_regle,0,',',' '); ?><small>DA</small></div>
        </div>
    </div>
    <div class="kpi-item kpi-reste<?= $reste <= 0 ? ' ok' : ''; ?>">
        <div class="kpi-icon"><i class="fa fa-scale-balanced"></i></div>
        <div>
            <div class="kpi-label">Reste a payé</div>
            <div class="kpi-value"><?= number_format($reste,0,',',' '); ?><small>DA</small></div>
        </div>
    </div>
    <div class="kpi-item kpi-enc">
        <div class="kpi-icon"><i class="fa fa-arrow-trend-down"></i></div>
        <div>
            <div class="kpi-label">Encaissements</div>
            <div class="kpi-value"><?= number_format($total_enc,0,',',' '); ?><small>DA</small></div>
        </div>
    </div>
</div>

<!-- MESSAGES -->
<?php if (isset($_GET['ok']) && $_GET['ok'] === 'etat_change'): ?>
<div class="msg-ok"><i class="fa fa-check-circle"></i> État mis à jour avec succès.</div>
<?php endif; ?>
<?php if (isset($_GET['err']) && $_GET['err'] === 'motif_required'): ?>
<div class="msg-err"><i class="fa fa-exclamation-triangle"></i> Un motif est obligatoire pour cet état.</div>
<?php endif; ?>
<?php if (isset($_GET['added'])): ?>
<div class="msg-ok"><i class="fa fa-check-circle"></i> Élément ajouté avec succès.</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     SÉLECTEUR D'ÉTAT SIMPLIFIÉ
     ══════════════════════════════════════════════════════ -->
<div class="etat-box">
    <div class="etat-box-title">
        <i class="fa fa-route"></i> Changer l'état du dossier
    </div>

    <!-- État actuel -->
    <div class="etat-current">
        <i class="fa fa-circle" style="font-size:8px;color:var(--green-600);"></i>
        État actuel : <strong><?= htmlspecialchars($dossier['nom_etat']); ?></strong>
    </div>

    <form method="POST" action="changer_etat_dossier.php" id="form-etat">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">

        <div class="etat-form-row">
            <div class="fg" style="flex:1;min-width:220px;">
                <label>Nouvel état</label>
                <select name="nouvel_etat" id="select-etat" onchange="onEtatChange(this.value)" required>
                    <option value="">— Sélectionner un état —</option>
                    <?php
                    mysqli_data_seek($tous_etats, 0);
                    while ($e = mysqli_fetch_assoc($tous_etats)):
                        $selected = ($e['id_etat'] == $dossier['id_etat']) ? 'selected' : '';
                    ?>
                    <option value="<?= $e['id_etat']; ?>" <?= $selected; ?>>
                        <?= htmlspecialchars($e['nom_etat']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="fg" style="flex:2;min-width:250px;">
                <label>Commentaire <span style="color:var(--gray-400);font-weight:400;">(optionnel)</span></label>
                <input type="text" name="commentaire" placeholder="Précisions sur ce changement…">
            </div>

            <button type="submit" class="btn btn-primary" style="padding:9px 18px;white-space:nowrap;" id="btn-valider-etat">
                <i class="fa fa-save"></i> Valider
            </button>
        </div>

        <!-- Motifs (affichés dynamiquement) -->
        <div id="motif-section">
            <div class="fg" style="min-width:300px;">
                <label>Motif <span id="motif-star" style="color:red;display:none;">*</span></label>
                <div id="motif-obligatoire-badge" style="display:none;background:var(--red-50);color:var(--red-700);border:1px solid var(--red-100);border-radius:6px;padding:5px 10px;font-size:12px;font-weight:600;margin-bottom:6px;">
                    <i class="fa fa-exclamation-triangle"></i> Motif obligatoire pour cet état
                </div>
                <select name="id_motif" id="select-motif">
                    <option value="">— Choisir un motif —</option>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- ONGLETS -->
<div class="crma-tabs">
    <button class="crma-tab-btn active" onclick="showTab('info',this)"><i class="fa fa-info-circle"></i> Informations</button>
    <button class="crma-tab-btn" onclick="showTab('documents',this)"><i class="fa fa-file-alt"></i> Documents</button>
    <button class="crma-tab-btn" onclick="showTab('expertise',this)"><i class="fa fa-search"></i> Expertise</button>
    <button class="crma-tab-btn" onclick="showTab('reserves',this)"><i class="fa fa-shield-halved"></i> Réserves</button>
    <button class="crma-tab-btn" onclick="showTab('reglements',this)"><i class="fa fa-money-bill"></i> Règlements</button>
    <button class="crma-tab-btn" onclick="showTab('encaissements',this)"><i class="fa fa-arrow-down"></i> Encaissements</button>
    <button class="crma-tab-btn" onclick="showTab('historique',this)"><i class="fa fa-history"></i> Historique</button>
</div>

<!-- ── TAB: INFORMATIONS ── -->
<div id="info" class="crma-tab-content" style="display:block;">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div class="crma-card">
        <h4><i class="fa fa-car-burst"></i> Sinistre</h4>
        <div class="info-row-crma"><span>Date sinistre</span><span><?= $dossier['date_sinistre']; ?></span></div>
        <div class="info-row-crma"><span>Lieu</span><span><?= htmlspecialchars($dossier['lieu_sinistre']); ?></span></div>
        <div class="info-row-crma"><span>Délai déclaration</span><span><?= $dossier['delai_declaration']; ?> jours</span></div>
        <div class="info-row-crma"><span>Description</span><span><?= htmlspecialchars($dossier['description']); ?></span></div>
        <div class="info-row-crma"><span>Statut validation</span>
            <span><?php
                $sv_map = ['non_soumis'=>['badge-gray','Non soumis'],'en_attente'=>['badge-amber','En attente'],'valide'=>['badge-green','Validé'],'refuse'=>['badge-red','Refusé']];
                $sv = $sv_map[$dossier['statut_validation']] ?? ['badge-gray', $dossier['statut_validation']];
                echo "<span class='badge {$sv[0]}'>{$sv[1]}</span>";
            ?></span>
        </div>
    </div>
    <div class="crma-card">
        <h4><i class="fa fa-user"></i> Assuré & Contrat</h4>
        <div class="info-row-crma"><span>Assuré</span><span><?= htmlspecialchars($dossier['nom_assure'].' '.$dossier['prenom_assure']); ?></span></div>
        <div class="info-row-crma"><span>Téléphone</span><span><?= htmlspecialchars($dossier['telephone']); ?></span></div>
        <div class="info-row-crma"><span>N° Police</span><span><?= htmlspecialchars($dossier['numero_police']); ?></span></div>
        <div class="info-row-crma"><span>Véhicule</span><span><?= htmlspecialchars($dossier['marque'].' '.$dossier['modele'].' — '.$dossier['matricule']); ?></span></div>
    </div>
    <div class="crma-card">
        <h4><i class="fa fa-users"></i> Tiers</h4>
        <div class="info-row-crma"><span>Nom</span><span><?= htmlspecialchars($dossier['nom_tiers'].' '.$dossier['prenom_tiers']); ?></span></div>
        <div class="info-row-crma"><span>Compagnie</span><span><?= htmlspecialchars($dossier['compagnie_assurance']); ?></span></div>
        <div class="info-row-crma"><span>Responsabilité</span>
            <span><?php
                $r = $dossier['responsable'];
                $rc = ['oui'=>'badge-red','non'=>'badge-green','partiel'=>'badge-amber'];
                echo "<span class='badge ".($rc[$r] ?? 'badge-gray')."'>".ucfirst($r ?? '—')."</span>";
            ?></span>
        </div>
    </div>
    <div class="crma-card">
        <h4><i class="fa fa-chart-bar"></i> Bilan financier</h4>
        <div class="info-row-crma"><span>Réserve totale</span><span style="font-family:monospace;font-weight:700;color:var(--blue-800);"><?= number_format($total_reserve,2,',',' '); ?> DA</span></div>
        <div class="info-row-crma"><span>Total réglé</span><span style="font-family:monospace;font-weight:700;color:var(--green-800);"><?= number_format($total_regle,2,',',' '); ?> DA</span></div>
        <div class="info-row-crma"><span>Reste</span><span style="font-family:monospace;font-weight:700;color:<?= $reste>0?'var(--red-600)':'var(--green-700)'; ?>;"><?= number_format($reste,2,',',' '); ?> DA</span></div>
        <div class="info-row-crma"><span>Encaissements</span><span style="font-family:monospace;font-weight:700;color:var(--teal-700);"><?= number_format($total_enc,2,',',' '); ?> DA</span></div>
        <?php if ($total_regle > 0): ?>
        <div class="info-row-crma"><span>Taux encaissement</span>
            <span style="font-weight:700;color:var(--amber-600);"><?= round($total_enc/$total_regle*100,1); ?>%</span>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- ── TAB: DOCUMENTS ── -->
<div id="documents" class="crma-tab-content">
<div class="crma-card">
    <h4><i class="fa fa-upload"></i> Ajouter document</h4>
    <form action="upload_document.php" method="POST" enctype="multipart/form-data"
          style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div style="flex:1;min-width:160px;">
            <label style="font-size:10px;font-weight:600;color:var(--gray-500);text-transform:uppercase;display:block;margin-bottom:5px;">Type</label>
            <select name="type" style="width:100%;padding:9px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:13px;">
                <?php $types = mysqli_query($conn, "SELECT * FROM type_document");
                while ($t = mysqli_fetch_assoc($types))
                    echo "<option value='{$t['id_type_document']}'>{$t['nom_type']}</option>"; ?>
            </select>
        </div>
        <div style="flex:2;min-width:200px;">
            <label style="font-size:10px;font-weight:600;color:var(--gray-500);text-transform:uppercase;display:block;margin-bottom:5px;">Fichier</label>
            <input type="file" name="fichier" required style="width:100%;padding:8px;border:1px solid var(--gray-300);border-radius:var(--radius);">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-upload"></i> Uploader</button>
    </form>
</div>
<div class="crma-table-wrapper">
<table class="crma-table">
    <thead><tr><th>Type</th><th>Fichier</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
    <?php
    $docs = mysqli_query($conn, "SELECT d.*,t.nom_type FROM document d LEFT JOIN type_document t ON d.id_type_document=t.id_type_document WHERE d.id_dossier=$id_dossier");
    if (mysqli_num_rows($docs) == 0)
        echo "<tr><td colspan='4'><div class='empty-state'><i class='fa fa-file-alt'></i><p>Aucun document</p></div></td></tr>";
    while ($d = mysqli_fetch_assoc($docs)):
    ?>
    <tr>
        <td><?= htmlspecialchars($d['nom_type']); ?></td>
        <td><a href="../uploads/<?= htmlspecialchars($d['nom_fichier']); ?>" target="_blank" class="btn btn-primary btn-xs"><i class="fa fa-eye"></i> Voir</a></td>
        <td style="font-size:12px;"><?= $d['date_upload']; ?></td>
        <td><a href="supprimer_documents.php?id=<?= $d['id_document']; ?>&dossier=<?= $id_dossier; ?>" onclick="return confirm('Supprimer ?')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>

<!-- ── TAB: EXPERTISE ── -->
<div id="expertise" class="crma-tab-content">
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Ajouter expertise</h4>
    <form action="ajouter_expertise.php" method="POST" enctype="multipart/form-data"
          style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div class="form-group" style="margin:0"><label>Expert</label>
            <select name="id_expert" required>
                <?php $experts = mysqli_query($conn, "SELECT * FROM expert");
                while ($e = mysqli_fetch_assoc($experts))
                    echo "<option value='{$e['id_expert']}'>{$e['nom']} {$e['prenom']}</option>"; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0"><label>Date expertise</label><input type="date" name="date_expertise" required></div>
        <div class="form-group" style="margin:0"><label>Montant indemnité (DA)</label><input type="number" step="1" onwheel="this.blur()" name="montant_indemnite" required></div>
        <div class="form-group" style="margin:0"><label>Rapport (fichier)</label><input type="file" name="rapport"></div>
        <div class="form-group" style="margin:0"><label>Commentaire</label><input type="text" name="commentaire"></div>
        <div style="display:flex;align-items:flex-end;">
            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa fa-plus"></i> Ajouter</button>
        </div>
    </form>
   
</div>
<div class="crma-table-wrapper">
<table class="crma-table">
    <thead><tr><th>Date</th><th>Expert</th><th>Montant indicatif</th><th>Rapport</th><th>Commentaire</th><th>Actions</th></tr></thead>
    <tbody>
    <?php
    $expertises = mysqli_query($conn, "SELECT ex.*,e.nom,e.prenom FROM expertise ex LEFT JOIN expert e ON ex.id_expert=e.id_expert WHERE ex.id_dossier=$id_dossier ORDER BY ex.id_expertise DESC");
    if (mysqli_num_rows($expertises) == 0)
        echo "<tr><td colspan='6'><div class='empty-state'><i class='fa fa-search'></i><p>Aucune expertise</p></div></td></tr>";
    while ($ex = mysqli_fetch_assoc($expertises)):
    ?>
    <tr>
        <td style="font-size:12px;"><?= $ex['date_expertise']; ?></td>
        <td><?= htmlspecialchars($ex['nom'].' '.$ex['prenom']); ?></td>
        <td class="num-cell" style="font-weight:600;"><?= number_format($ex['montant_indemnite'] ?? 0,2,',',' '); ?> DA</td>
        <td><?= $ex['rapport_pdf'] ? "<a href='../uploads/{$ex['rapport_pdf']}' target='_blank' class='btn btn-primary btn-xs'><i class='fa fa-file-pdf'></i> Voir</a>" : '—'; ?></td>
        <td style="font-size:12px;"><?= htmlspecialchars($ex['commentaire']); ?></td>
        <td style="display:flex;gap:4px;">
            <a href="modifier_expertise.php?id=<?= $ex['id_expertise']; ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
            <a href="supprimer_expertise.php?id=<?= $ex['id_expertise']; ?>&dossier=<?= $id_dossier; ?>" onclick="return confirm('Supprimer ?')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>

<!-- ── TAB: RÉSERVES ── -->
<div id="reserves" class="crma-tab-content">
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Ajouter réserve</h4>
    <form action="ajouter_reserve.php" method="POST"
          style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:flex-end;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div class="form-group" style="margin:0"><label>Montant (DA)</label><input type="number" step="1" onwheel="this.blur()" name="montant" required></div>
        <div class="form-group" style="margin:0"><label>Garantie</label>
            <select name="id_garantie">
                <?php $gar = mysqli_query($conn, "
    SELECT g.*
    FROM garantie g
    JOIN contrat_garantie cg ON g.id_garantie = cg.id_garantie
    JOIN dossier d ON d.id_contrat = cg.id_contrat
    WHERE d.id_dossier = $id_dossier
");
                while ($g = mysqli_fetch_assoc($gar))
                    echo "<option value='{$g['id_garantie']}'>{$g['nom_garantie']}</option>"; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0"><label>Commentaire</label><input type="text" name="commentaire"></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Ajouter</button>
    </form>
</div>
<div class="crma-table-wrapper">
<table class="crma-table">
    <thead><tr><th>Date</th><th>Garantie</th><th>Montant</th><th>Type</th><th>Commentaire</th><th>Actions</th></tr></thead>
    <tbody>
    <?php
    $reserves = mysqli_query($conn, "SELECT r.*,g.nom_garantie FROM reserve r LEFT JOIN garantie g ON r.id_garantie=g.id_garantie WHERE r.id_dossier=$id_dossier ORDER BY r.id_reserve DESC");
    if (mysqli_num_rows($reserves) == 0)
        echo "<tr><td colspan='6'><div class='empty-state'><i class='fa fa-shield-halved'></i><p>Aucune réserve</p></div></td></tr>";
    $type_badges = ['initiale'=>'badge-blue','expertise'=>'badge-teal','ajustement'=>'badge-amber'];
    while ($r = mysqli_fetch_assoc($reserves)):
        $tb = $type_badges[$r['type_reserve']] ?? 'badge-gray';
    ?>
    <tr>
        <td style="font-size:12px;"><?= $r['date_reserve']; ?></td>
        <td><?= htmlspecialchars($r['nom_garantie']); ?></td>
        <td class="num-cell" style="font-weight:600;"><?= number_format($r['montant'],2,',',' '); ?> DA</td>
        <td><span class="badge <?= $tb; ?>" style="font-size:11px;"><?= $r['type_reserve']; ?></span></td>
        <td style="font-size:12px;"><?= htmlspecialchars($r['commentaire'] ?? ''); ?></td>
        <td style="display:flex;gap:4px;">
            <a href="modifier_reserve.php?id=<?= $r['id_reserve']; ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
            <a href="supprimer_reserve.php?id=<?= $r['id_reserve']; ?>&dossier=<?= $id_dossier; ?>" onclick="return confirm('Supprimer ?')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>

<!-- ── TAB: RÈGLEMENTS ── -->
<div id="reglements" class="crma-tab-content">
<?php if ($reglement_ok): ?>
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Ajouter règlement</h4>
    <div class="msg-info" style="margin-bottom:14px;">
        <i class="fa fa-info-circle"></i>
        <strong>Règle automatique :</strong>
        Si règlement ≤ réserve → état passe en <em>Règlement partiel</em>.
        Si règlement > réserve → une réserve complémentaire est créée et l'état passe en <em>Règlement définitif amiable</em>.
    </div>
    <form action="ajouter_reglement.php" method="POST"
          style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:flex-end;">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div class="form-group" style="margin:0"><label>Montant (DA)</label><input type="number" step="1" onwheel="this.blur()" name="montant" required></div>
        <div class="form-group" style="margin:0"><label>Mode</label>
            <select name="mode"><option>Chèque</option><option>Virement</option></select>
        </div>
        <div class="form-group" style="margin:0"><label>Commentaire</label><input type="text" name="commentaire"></div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Ajouter</button>
    </form>
</div>
<?php else: ?>
<div class="msg-err" style="margin-bottom:16px;">
    <i class="fa fa-lock"></i>
    Règlement non disponible — état actuel : <strong><?= htmlspecialchars($dossier['nom_etat']); ?></strong>.
    Le dossier doit être dans un état validé ou en règlement partiel.
</div>
<?php endif; ?>
<div class="crma-table-wrapper">
<table class="crma-table">
    <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Statut</th><th>Commentaire</th><th>Actions</th></tr></thead>
    <tbody>
    <?php
    $reglements = mysqli_query($conn, "SELECT * FROM reglement WHERE id_dossier=$id_dossier ORDER BY id_reglement DESC");
    if (mysqli_num_rows($reglements) == 0)
        echo "<tr><td colspan='6'><div class='empty-state'><i class='fa fa-money-bill'></i><p>Aucun règlement</p></div></td></tr>";
    $statuts = ['en_attente'=>['badge-amber','En attente'],'disponible'=>['badge-green','Disponible'],'remis'=>['badge-teal','Remis']];
    while ($reg = mysqli_fetch_assoc($reglements)):
        $s = $statuts[$reg['statut']] ?? ['badge-gray', $reg['statut']];
        $actions = "<a href='modifier_reglement.php?id={$reg['id_reglement']}' class='btn btn-outline btn-xs'><i class='fa fa-pen'></i></a>";
        if ($reg['statut'] == 'en_attente') $actions .= "<a href='gerer_reglement_statut.php?id={$reg['id_reglement']}&dossier=$id_dossier&statut=disponible' onclick=\"return confirm('Marquer disponible ?')\" class='btn btn-primary btn-xs'><i class='fa fa-check'></i></a>";
        if ($reg['statut'] == 'disponible') $actions .= "<a href='gerer_reglement_statut.php?id={$reg['id_reglement']}&dossier=$id_dossier&statut=remis' onclick=\"return confirm('Marquer remis ?')\" class='btn btn-outline btn-xs'><i class='fa fa-handshake'></i></a>";
        $actions .= "<a href='supprimer_reglement.php?id={$reg['id_reglement']}&dossier=$id_dossier' onclick=\"return confirm('Supprimer ?')\" class='btn btn-danger btn-xs'><i class='fa fa-trash'></i></a>";
    ?>
    <tr>
        <td style="font-size:12px;"><?= $reg['date_reglement']; ?></td>
        <td class="num-cell" style="font-weight:600;"><?= number_format($reg['montant'],2,',',' '); ?> DA</td>
        <td><?= htmlspecialchars($reg['mode_paiement']); ?></td>
        <td><span class="badge <?= $s[0]; ?>" style="font-size:11px;"><?= $s[1]; ?></span></td>
        <td style="font-size:12px;"><?= htmlspecialchars($reg['commentaire']); ?></td>
        <td style="display:flex;gap:4px;flex-wrap:wrap;"><?= $actions; ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>

<!-- ── TAB: ENCAISSEMENTS ── -->
<div id="encaissements" class="crma-tab-content">
<?php if ($enc_ok): ?>
<div class="crma-card">
    <h4><i class="fa fa-plus"></i> Enregistrer un encaissement</h4>
    <form action="ajouter_encaissement.php" method="POST">
        <input type="hidden" name="id_dossier" value="<?= $id_dossier; ?>">
        <div class="enc-form-grid">
            <div class="fg"><label>Montant (DA) *</label><input type="number" step="1" onwheel="this.blur()" name="montant" required></div>
            <div class="fg"><label>Date *</label><input type="date" name="date_encaissement" value="<?= date('Y-m-d'); ?>" required></div>
            <div class="fg"><label>Tiers *</label>
                <select name="id_tiers" required>
                    <?php $trs = mysqli_query($conn, "SELECT t.id_tiers,p.nom,p.prenom,t.compagnie_assurance FROM tiers t JOIN personne p ON t.id_personne=p.id_personne");
                    while ($tr = mysqli_fetch_assoc($trs))
                        echo "<option value='{$tr['id_tiers']}'>{$tr['nom']} {$tr['prenom']} — {$tr['compagnie_assurance']}</option>"; ?>
                </select>
            </div>
            <div class="fg"><label>Type</label>
                <select name="type"><option value="recours">Recours</option><option value="franchise">Franchise</option><option value="epave">Épave</option><option value="autre">Autre</option></select>
            </div>
            <button type="submit" class="btn btn-primary" style="white-space:nowrap;"><i class="fa fa-save"></i> Enregistrer</button>
        </div>
        <div style="margin-top:10px;">
            <input type="text" name="commentaire" placeholder="Commentaire optionnel…"
                   style="width:100%;padding:8px 10px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:13px;">
        </div>
    </form>
</div>
<?php else: ?>
<div class="msg-err">
    <i class="fa fa-ban"></i>
    <?php if (!in_array($dossier['responsable'], ['oui', 'partiel'])): ?>
        Encaissement non autorisé — tiers non responsable.
    <?php else: ?>
        Encaissement non disponible dans l'état actuel : <strong><?= htmlspecialchars($dossier['nom_etat']); ?></strong>.
    <?php endif; ?>
</div>
<?php endif; ?>
<div class="crma-table-wrapper" style="margin-top:18px;">
<table class="crma-table">
    <thead><tr><th>Date</th><th>Tiers</th><th>Type</th><th>Montant</th><th>Commentaire</th></tr></thead>
    <tbody>
    <?php
    $encs = mysqli_query($conn, "SELECT enc.*,p.nom,p.prenom,t.compagnie_assurance FROM encaissement enc JOIN tiers t ON enc.id_tiers=t.id_tiers JOIN personne p ON t.id_personne=p.id_personne WHERE enc.id_dossier=$id_dossier ORDER BY enc.id_encaissement DESC");
    if (mysqli_num_rows($encs) == 0)
        echo "<tr><td colspan='5'><div class='empty-state'><i class='fa fa-inbox'></i><p>Aucun encaissement</p></div></td></tr>";
    $type_badges_enc = ['recours'=>'badge-blue','franchise'=>'badge-amber','epave'=>'badge-gray','autre'=>'badge-teal'];
    while ($enc = mysqli_fetch_assoc($encs)):
        $tb = $type_badges_enc[$enc['type']] ?? 'badge-gray';
    ?>
    <tr>
        <td style="font-size:12px;"><?= $enc['date_encaissement']; ?></td>
        <td><?= htmlspecialchars($enc['nom'].' '.$enc['prenom']); ?><br><small style="color:var(--gray-400);"><?= htmlspecialchars($enc['compagnie_assurance']); ?></small></td>
        <td><span class="badge <?= $tb; ?>" style="font-size:11px;"><?= $enc['type']; ?></span></td>
        <td class="num-cell" style="font-weight:700;color:var(--green-800);"><?= number_format($enc['montant'],2,',',' '); ?> DA</td>
        <td style="font-size:12px;color:var(--gray-500);"><?= htmlspecialchars($enc['commentaire']); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>

<!-- ── TAB: HISTORIQUE ── -->
<div id="historique" class="crma-tab-content">
<div class="crma-table-wrapper">
<table class="crma-table">
    <thead><tr><th>Date / Heure</th><th>Action</th><th>Ancien état</th><th>Nouvel état</th><th>Motif</th><th>Commentaire</th></tr></thead>
    <tbody>
    <?php
    $hist = mysqli_query($conn, "
        SELECT h.*, ea.nom_etat AS ancien, en.nom_etat AS nouveau, m.nom_motif
        FROM historique h
        LEFT JOIN etat_dossier ea ON h.ancien_etat=ea.id_etat
        LEFT JOIN etat_dossier en ON h.nouvel_etat=en.id_etat
        LEFT JOIN motif m ON h.id_motif=m.id_motif
        WHERE h.id_dossier=$id_dossier ORDER BY h.date_action DESC");
    while ($h = mysqli_fetch_assoc($hist)):
        $a = strtolower($h['action']);
        if     (str_contains($a,'valid'))                          $as="background:var(--green-100);color:var(--green-800);";
        elseif (str_contains($a,'refus'))                         $as="background:var(--red-50);color:var(--red-700);";
        elseif (str_contains($a,'clôture')||str_contains($a,'cloture')) $as="background:#f3e5f5;color:#4a148c;";
        elseif (str_contains($a,'règlement')||str_contains($a,'reglement')) $as="background:var(--teal-50);color:var(--teal-700);";
        elseif (str_contains($a,'réserve')||str_contains($a,'reserve')) $as="background:var(--blue-50);color:var(--blue-800);";
        elseif (str_contains($a,'créat')||str_contains($a,'creat')) $as="background:#e8eaf6;color:#283593;";
        else   $as="background:var(--gray-100);color:var(--gray-600);";
        $motif_cell = $h['nom_motif']
            ? "<span style='background:var(--amber-50);color:var(--amber-600);border:1px solid var(--amber-100);border-radius:6px;padding:2px 8px;font-size:11px;font-weight:600;'>"
              .htmlspecialchars($h['nom_motif'])."</span>"
            : '<span style="color:var(--gray-300);">—</span>';
    ?>
    <tr>
        <td style="font-size:12px;"><b><?= date('d/m/Y', strtotime($h['date_action'])); ?></b><br><small style="color:var(--gray-400);"><?= date('H:i:s', strtotime($h['date_action'])); ?></small></td>
        <td><span style="<?= $as; ?> padding:3px 10px;border-radius:12px;font-size:11.5px;font-weight:600;"><?= htmlspecialchars($h['action']); ?></span></td>
        <td style="font-size:12px;color:var(--gray-500);"><?= htmlspecialchars($h['ancien'] ?? '—'); ?></td>
        <td style="font-size:12px;color:var(--gray-500);"><?= htmlspecialchars($h['nouveau'] ?? '—'); ?></td>
        <td><?= $motif_cell; ?></td>
        <td style="font-size:12px;color:var(--gray-500);max-width:180px;"><?= htmlspecialchars($h['commentaire'] ?? ''); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>

</div><!-- fin .main -->

<script>
// ── Onglets
function showTab(tab, btn) {
    document.querySelectorAll('.crma-tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.crma-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tab).style.display = 'block';
    if (btn) btn.classList.add('active');
}
const urlTab = new URLSearchParams(window.location.search).get('tab');
if (urlTab) {
    const btn = document.querySelector(`.crma-tab-btn[onclick*="${urlTab}"]`);
    showTab(urlTab, btn);
}

// ── Changement d'état : charger motifs via AJAX
async function onEtatChange(idEtat) {
    const motifSection = document.getElementById('motif-section');
    const selectMotif  = document.getElementById('select-motif');
    const badge        = document.getElementById('motif-obligatoire-badge');
    const star         = document.getElementById('motif-star');

    selectMotif.innerHTML = '<option value="">— Choisir un motif —</option>';
    motifSection.className = 'motif-section';

    if (!idEtat) return;

    try {
        const resp = await fetch(`get_motifs.php?id_etat=${idEtat}`);
        const data = await resp.json();

        if (data.motifs && data.motifs.length > 0) {
            data.motifs.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id_motif;
                opt.textContent = m.nom_motif;
                selectMotif.appendChild(opt);
            });
            motifSection.className = 'motif-section show';
            badge.style.display = data.obligatoire ? 'block' : 'none';
            star.style.display  = data.obligatoire ? 'inline' : 'none';
            selectMotif.required = data.obligatoire;
        } else {
            motifSection.className = 'motif-section';
            selectMotif.required = false;
        }
    } catch (e) {
        console.error('Erreur chargement motifs', e);
    }
}

// Validation formulaire état
document.getElementById('form-etat').addEventListener('submit', function(e) {
    const etat = document.getElementById('select-etat').value;
    if (!etat) {
        e.preventDefault();
        alert('Veuillez sélectionner un état.');
        return false;
    }
    const motif   = document.getElementById('select-motif');
    const star    = document.getElementById('motif-star');
    if (star.style.display !== 'none' && motif.required && !motif.value) {
        e.preventDefault();
        motif.style.border = '2px solid var(--red-600)';
        alert('⚠ Un motif est obligatoire pour cet état.');
        return false;
    }
});
</script>
</body>
</html>