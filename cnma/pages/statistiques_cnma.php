<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') { header("Location: login.php"); exit(); }

$page_title = "Analyse & Statistiques";

// ===================== FILTRES =====================
$filtre_agence = isset($_GET['agence'])   ? intval($_GET['agence'])         : 0;
$filtre_expert = isset($_GET['expert'])   ? intval($_GET['expert'])         : 0;
$filtre_annee  = isset($_GET['annee']) ? intval($_GET['annee']) : 0;
$filtre_mois   = isset($_GET['mois'])     ? intval($_GET['mois'])           : 0;

// Clauses de filtrage
$where_dossier = "WHERE 1=1";
$join_agence   = "LEFT JOIN utilisateur u_f ON d.cree_par = u_f.id_user LEFT JOIN agence ag_f ON u_f.id_agence = ag_f.id_agence";
if($filtre_agence > 0) $where_dossier .= " AND u_f.id_agence = $filtre_agence";
if($filtre_expert > 0) $where_dossier .= " AND d.id_expert = $filtre_expert";
if($filtre_annee  > 0) $where_dossier .= " AND YEAR(d.date_creation) = $filtre_annee";
if($filtre_mois   > 0) $where_dossier .= " AND MONTH(d.date_creation) = $filtre_mois";

// ===================== DONNÉES DE FILTRES =====================
$agences_list = mysqli_query($conn, "SELECT id_agence, nom_agence FROM agence ORDER BY nom_agence");
$experts_list = mysqli_query($conn, "SELECT id_expert, nom, prenom FROM expert ORDER BY nom");
$annees_list  = mysqli_query($conn, "SELECT DISTINCT YEAR(date_creation) as y FROM dossier ORDER BY y DESC");

// ===================== KPIs DÉCISIONNELS =====================
$q_kpi = mysqli_query($conn, "
SELECT
COUNT(*) as total,

SUM(CASE WHEN id_etat NOT IN (1,6) 
         AND id_etat IN (2,3,7,9,13,15,16,18,20)
    THEN 1 ELSE 0 END) as en_traitement,

SUM(CASE WHEN id_etat = 4 THEN 1 ELSE 0 END) as valides,
SUM(CASE WHEN id_etat = 5 THEN 1 ELSE 0 END) as refuses,
SUM(CASE WHEN id_etat IN (8,17) THEN 1 ELSE 0 END) as regles,
SUM(CASE WHEN id_etat = 14 THEN 1 ELSE 0 END) as clotures

FROM dossier d
$join_agence
$where_dossier
AND d.id_etat NOT IN (1,6) 
");

$kpi = mysqli_fetch_assoc($q_kpi);

$retard = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as n
FROM dossier d
$join_agence
$where_dossier
AND d.id_etat NOT IN (1,6,5,8,14) 
AND DATEDIFF(NOW(), d.date_creation) > 15
"))['n'];

$total_decide = ($kpi['valides'] + $kpi['refuses']);
$taux_validation = $total_decide > 0 ? round($kpi['valides'] / $total_decide * 100, 1) : null;
$taux_refus      = $total_decide > 0 ? round($kpi['refuses'] / $total_decide * 100, 1) : null;

// ===================== FINANCE =====================
$sql_fin = "
SELECT

(
    SELECT IFNULL(SUM(r.montant),0)
    FROM reserve r
    JOIN dossier d1 ON r.id_dossier = d1.id_dossier
    JOIN utilisateur u1 ON d1.cree_par = u1.id_user
    WHERE 1=1
    ".($filtre_agence > 0 ? " AND u1.id_agence = $filtre_agence" : "")."
    ".($filtre_annee > 0 
    ? " AND YEAR(r.date_reserve) = $filtre_annee"
    : ""
)."
) as total_reserve,

(
    SELECT IFNULL(SUM(rg.montant),0)
    FROM reglement rg
    JOIN dossier d2 ON rg.id_dossier = d2.id_dossier
    JOIN utilisateur u2 ON d2.cree_par = u2.id_user
    WHERE 1=1
    ".($filtre_agence > 0 ? " AND u2.id_agence = $filtre_agence" : "")."
    ".($filtre_annee > 0 ? " AND YEAR(rg.date_reglement) = $filtre_annee" : "")."
) as total_regle,

(
    SELECT IFNULL(SUM(en.montant),0)
    FROM encaissement en
    JOIN dossier d3 ON en.id_dossier = d3.id_dossier
    JOIN utilisateur u3 ON d3.cree_par = u3.id_user
    WHERE 1=1
    ".($filtre_agence > 0 ? " AND u3.id_agence = $filtre_agence" : "")."
    ".($filtre_annee > 0 ? " AND YEAR(en.date_encaissement) = $filtre_annee" : "")."
) as total_enc
";



$q_fin = mysqli_query($conn, $sql_fin);
$fin = mysqli_fetch_assoc($q_fin);

$taux_conso = $fin['total_reserve'] > 0 ? round($fin['total_regle'] / $fin['total_reserve'] * 100, 1) : 0;

// ===================== ÉVOLUTION MENSUELLE =====================
$mois_data = [];
for($m = 1; $m <= 12; $m++) {
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT
            COUNT(*) as crees,
            SUM(CASE WHEN id_etat=4 THEN 1 ELSE 0 END) as valides,
            SUM(CASE WHEN id_etat=5 THEN 1 ELSE 0 END) as refuses
         FROM dossier d $join_agence
        WHERE 1=1
".($filtre_annee > 0 ? " AND YEAR(d.date_creation) = $filtre_annee" : "")."AND MONTH(d.date_creation)=$m
         " . ($filtre_agence > 0 ? "AND u_f.id_agence=$filtre_agence" : "")
         . ($filtre_expert  > 0 ? "AND d.id_expert=$filtre_expert"   : "")
    ));
    $mois_data[] = $r;
}
$mois_labels  = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
$m_crees  = array_column($mois_data, 'crees');
$m_valides = array_column($mois_data, 'valides');
$m_refuses = array_column($mois_data, 'refuses');

// ===================== PIPELINE CRMA =====================

$colors_map = [

    2  => '#2563eb',
    6  => '#60a5fa',

    9  => '#d97706',
    16 => '#92400e',

    3  => '#7c3aed',

    4  => '#059669',
    7  => '#0891b2',
    8  => '#16a34a',
    17 => '#15803d',

    5  => '#dc2626',

    14 => '#374151',

    11 => '#6b7280',
    12 => '#6b7280',
    13 => '#6b7280',
    19 => '#4b5563',

    15 => '#ca8a04',
    18 => '#a16207',
    20 => '#854d0e',
];

$q_pipeline = mysqli_query($conn, "
SELECT e.id_etat, e.nom_etat, COUNT(*) as n
FROM dossier d
JOIN etat_dossier e ON d.id_etat = e.id_etat
$join_agence
$where_dossier
AND d.id_etat NOT IN (1,6)
GROUP BY e.id_etat, e.nom_etat
ORDER BY e.id_etat
");

$pipeline_states = [];
$pipeline_vals = [];

while($row = mysqli_fetch_assoc($q_pipeline)) {
$pipeline_states[] = [
    'label' => $row['nom_etat'],
    'color' => $colors_map[$row['id_etat']] ?? '#9ca3af'
];
    $pipeline_vals[] = intval($row['n']);
}
// ===================== PERFORMANCE PAR AGENCE =====================
$perf_agence = mysqli_query($conn, "
SELECT
    ag.nom_agence,
    COUNT(DISTINCT d.id_dossier) as total,

    SUM(CASE WHEN d.id_etat=4 THEN 1 ELSE 0 END) as valides,
    SUM(CASE WHEN d.id_etat=5 THEN 1 ELSE 0 END) as refuses,

    ROUND(AVG(
        CASE 
            WHEN d.date_validation IS NOT NULL 
            THEN DATEDIFF(d.date_validation, d.date_creation)
        END
    ),1) as delai_moy,

    IFNULL(SUM(r.montant),0) as total_reserve,
    IFNULL(SUM(rg.montant),0) as total_regle

FROM dossier d

LEFT JOIN utilisateur u ON d.cree_par = u.id_user
LEFT JOIN agence ag ON u.id_agence = ag.id_agence

LEFT JOIN reserve r ON r.id_dossier = d.id_dossier
LEFT JOIN reglement rg ON rg.id_dossier = d.id_dossier

$join_agence
$where_dossier

GROUP BY ag.id_agence, ag.nom_agence
ORDER BY total DESC
");

// ===================== PERFORMANCE EXPERTS =====================
$perf_experts = mysqli_query($conn, "

    SELECT
        CONCAT(e.nom,' ',e.prenom) as expert_nom,
        COUNT(DISTINCT ex.id_dossier) as nb_dossiers,
        COUNT(ex.id_expertise) as nb_expertises,
        ROUND(AVG(ex.montant_indemnite),0) as moy_indemnite,
        MAX(ex.montant_indemnite) as max_indemnite

    FROM expertise ex
    JOIN expert e ON ex.id_expert = e.id_expert
    JOIN dossier d ON ex.id_dossier = d.id_dossier
    LEFT JOIN utilisateur u ON d.cree_par = u.id_user

    WHERE 1=1
    " . ($filtre_agence > 0 ? "AND u.id_agence = $filtre_agence" : "") . "
    " . ($filtre_annee > 0 ? "AND YEAR(d.date_creation) = $filtre_annee" : "") . "

    GROUP BY e.id_expert, e.nom, e.prenom
    ORDER BY nb_dossiers DESC
    LIMIT 8
");

// ===================== BLOCAGES =====================
$blocages = mysqli_query($conn, "
    SELECT d.id_dossier, d.numero_dossier, d.date_creation,
           DATEDIFF(NOW(), d.date_creation) as age_jours,
           e.nom_etat, ag.nom_agence,
           p.nom AS nom_assure, p.prenom AS prenom_assure
    FROM dossier d
    LEFT JOIN etat_dossier e ON d.id_etat = e.id_etat
    LEFT JOIN utilisateur u ON d.cree_par = u.id_user
    LEFT JOIN agence ag ON u.id_agence = ag.id_agence

    -- 👇 ICI TU AJOUTES TES JOIN FILTRE
    LEFT JOIN utilisateur u_f ON d.cree_par = u_f.id_user
    LEFT JOIN agence ag_f ON u_f.id_agence = ag_f.id_agence

    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure ass ON c.id_assure = ass.id_assure
    LEFT JOIN personne p ON ass.id_personne = p.id_personne

    WHERE d.id_etat NOT IN (5,8,14,19)
    AND DATEDIFF(NOW(), d.date_creation) > 15
    " . ($filtre_agence > 0 ? "AND u_f.id_agence=$filtre_agence " : "") . "

    ORDER BY age_jours DESC
    LIMIT 10
");

// ===================== EVOLUTION FINANCE PAR MOIS =====================
$fin_mois_data = [];

for($m = 1; $m <= 12; $m++) {

 $rf = mysqli_fetch_assoc(mysqli_query($conn, "

SELECT 
    (
        SELECT IFNULL(SUM(r.montant),0)
        FROM reserve r
        JOIN dossier d2 ON r.id_dossier = d2.id_dossier
        JOIN utilisateur u2 ON d2.cree_par = u2.id_user
        WHERE MONTH(r.date_reserve) = $m
        ".($filtre_annee > 0 ? " AND YEAR(r.date_reserve) = $filtre_annee" : "")."
        ".($filtre_agence > 0 ? " AND u2.id_agence = $filtre_agence" : "")."
    ) as reserve,

    (
        SELECT IFNULL(SUM(rg.montant),0)
        FROM reglement rg
        JOIN dossier d3 ON rg.id_dossier = d3.id_dossier
        JOIN utilisateur u3 ON d3.cree_par = u3.id_user
        WHERE MONTH(rg.date_reglement) = $m
        ".($filtre_annee > 0 ? " AND YEAR(rg.date_reglement) = $filtre_annee" : "")."
        ".($filtre_agence > 0 ? " AND u3.id_agence = $filtre_agence" : "")."
    ) as regle

"));

    $fin_mois_data[] = $rf;
}
$fm_reserve = array_map(fn($x) => round(floatval($x['reserve'])), $fin_mois_data);
$fm_regle   = array_map(fn($x) => round(floatval($x['regle'])),   $fin_mois_data);

// Goulots d'étranglement : durée moyenne par étape
$goulots = mysqli_query($conn, "
    SELECT e.nom_etat, d.id_etat,
           COUNT(d.id_dossier) as nb,
           ROUND(AVG(DATEDIFF(NOW(), d.date_creation)),0) as age_moy
    FROM dossier d
    JOIN etat_dossier e ON d.id_etat = e.id_etat
    WHERE d.id_etat NOT IN (5,8,14)
    GROUP BY d.id_etat ORDER BY age_moy DESC LIMIT 6
");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Analyse & Statistiques — CNMA</title>
<link rel="stylesheet" href="../css/style_cnma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<style>
/* ====== BASE OVERRIDE ====== */
body { font-family:'IBM Plex Sans',sans-serif; background:#f8f9fb; }
.cnma-main { padding:24px 28px; }

/* ====== FILTER BAR ====== */
.stat-filter-bar {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:14px 20px;
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:24px;
}
.stat-filter-bar label {
    font-size:11px; font-weight:600; color:#6b7280;
    text-transform:uppercase; letter-spacing:.6px;
    margin-right:4px;
}
.stat-filter-bar select {
    padding:7px 12px;
    border:1px solid #e5e7eb;
    border-radius:7px;
    font-size:13px;
    font-family:'IBM Plex Sans',sans-serif;
    color:#374151;
    background:#f9fafb;
    cursor:pointer;
}
.stat-filter-bar select:focus { border-color:#374151; outline:none; }
.btn-filter {
    padding:7px 16px;
    background:#1f2937;
    color:#fff;
    border:none;
    border-radius:7px;
    font-size:13px;
    font-family:'IBM Plex Sans',sans-serif;
    font-weight:500;
    cursor:pointer;
    display:flex; align-items:center; gap:6px;
}
.btn-filter:hover { background:#374151; }
.btn-reset {
    padding:7px 14px;
    background:transparent;
    color:#6b7280;
    border:1px solid #e5e7eb;
    border-radius:7px;
    font-size:13px;
    font-family:'IBM Plex Sans',sans-serif;
    text-decoration:none;
    display:inline-flex; align-items:center; gap:5px;
}
.filter-active-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px;
    background:#fef3c7;
    color:#92400e;
    border-radius:20px;
    font-size:11px; font-weight:600;
    margin-left:auto;
}

/* ====== PAGE HEADER ====== */
.stat-page-header {
    display:flex; justify-content:space-between; align-items:flex-start;
    margin-bottom:20px;
}
.stat-page-header h1 {
    font-family:'IBM Plex Mono',monospace;
    font-size:18px; font-weight:600; color:#111827;
    letter-spacing:-.3px;
}
.stat-page-header .subtitle {
    font-size:12px; color:#6b7280; margin-top:3px;
}
.last-update {
    font-size:11px; color:#9ca3af; font-family:'IBM Plex Mono',monospace;
}

/* ====== SECTION TITLES ====== */
.section-header {
    display:flex; align-items:center; gap:10px;
    margin:28px 0 14px;
    padding-bottom:10px;
    border-bottom:1px solid #e5e7eb;
}
.section-header h2 {
    font-size:13px; font-weight:600; color:#374151;
    text-transform:uppercase; letter-spacing:.8px;
}
.section-header .sect-icon {
    width:28px; height:28px;
    display:flex; align-items:center; justify-content:center;
    border-radius:6px;
    font-size:13px;
}
.section-header .sect-tag {
    margin-left:auto;
    font-size:11px; color:#9ca3af;
    font-family:'IBM Plex Mono',monospace;
}

/* ====== KPI GRID ====== */
.kpi-grid-top {
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:12px;
    margin-bottom:16px;
}
.kpi-card {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:16px 18px;
    position:relative;
    overflow:hidden;
}
.kpi-card::before {
    content:'';
    position:absolute;
    top:0; left:0; right:0;
    height:3px;
    background:var(--accent, #e5e7eb);
}
.kpi-card .kpi-label {
    font-size:10.5px; font-weight:600; color:#6b7280;
    text-transform:uppercase; letter-spacing:.7px;
    margin-bottom:10px;
    display:flex; align-items:center; gap:6px;
}
.kpi-card .kpi-label i { font-size:11px; }
.kpi-card .kpi-value {
    font-family:'IBM Plex Mono',monospace;
    font-size:28px; font-weight:600; line-height:1;
    color:#111827;
}
.kpi-card .kpi-value.good { color:#059669; }
.kpi-card .kpi-value.warn { color:#d97706; }
.kpi-card .kpi-value.bad  { color:#dc2626; }
.kpi-card .kpi-sub {
    font-size:11px; color:#9ca3af; margin-top:6px;
    font-family:'IBM Plex Mono',monospace;
}
.kpi-card .kpi-mini-bar {
    height:4px; background:#f3f4f6; border-radius:2px; margin-top:10px;
}
.kpi-card .kpi-mini-bar .fill {
    height:100%; border-radius:2px;
    background:var(--accent, #9ca3af);
    transition:width .6s ease;
}

/* ====== TWO-COL LAYOUT ====== */
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.three-col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
.wide-narrow { display:grid; grid-template-columns:2fr 1fr; gap:16px; }
.narrow-wide { display:grid; grid-template-columns:1fr 2fr; gap:16px; }

/* ====== CHART CARDS ====== */
.chart-card {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:20px;
}
.chart-card .cc-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    margin-bottom:16px;
}
.chart-card .cc-title {
    font-size:13px; font-weight:600; color:#1f2937;
}
.chart-card .cc-question {
    font-size:11px; color:#6b7280; margin-top:2px;
    font-style:italic;
}
.chart-card .cc-badge {
    font-size:10px; padding:3px 8px; border-radius:12px;
    font-weight:600; white-space:nowrap;
}
.chart-card canvas { max-height:220px; }

/* ====== PIPELINE BAR ====== */
.pipeline-container {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:20px;
}
.pipeline-bar {
    display:flex; height:36px; border-radius:6px; overflow:hidden;
    margin:16px 0;
    gap:2px;
}
.pipeline-segment {
    height:100%;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:600; color:#fff;
    font-family:'IBM Plex Mono',monospace;
    transition:width .8s ease;
    cursor:pointer;
    position:relative;
    min-width:2px;
}
.pipeline-segment:hover { filter:brightness(1.1); }
.pipeline-legend {
    display:flex; flex-wrap:wrap; gap:10px; margin-top:12px;
}
.pipeline-legend-item {
    display:flex; align-items:center; gap:6px;
    font-size:11.5px; color:#374151;
}
.pipeline-legend-dot {
    width:10px; height:10px; border-radius:2px;
}

/* ====== PERF TABLE ====== */
.perf-table {
    width:100%; border-collapse:collapse;
    font-size:12.5px;
}
.perf-table th {
    text-align:left;
    padding:8px 12px;
    font-size:10px; font-weight:700; color:#6b7280;
    text-transform:uppercase; letter-spacing:.7px;
    border-bottom:1px solid #e5e7eb;
    background:#f9fafb;
    white-space:nowrap;
}
.perf-table td {
    padding:10px 12px;
    border-bottom:1px solid #f3f4f6;
    color:#374151;
    vertical-align:middle;
}
.perf-table tr:hover td { background:#fafafa; }
.perf-table .mono { font-family:'IBM Plex Mono',monospace; font-weight:500; }

/* ====== BLOCAGE TABLE ====== */
.blocage-row-critical td { background:#fef2f2 !important; }
.blocage-row-warn td     { background:#fffbeb !important; }
.age-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 8px; border-radius:12px;
    font-size:11px; font-weight:600;
    font-family:'IBM Plex Mono',monospace;
}
.age-badge.crit { background:#fee2e2; color:#991b1b; }
.age-badge.warn { background:#fef3c7; color:#92400e; }
.age-badge.ok   { background:#f0fdf4; color:#166534; }

/* ====== GOULOT BARS ====== */
.goulot-item {
    display:flex; align-items:center; gap:12px;
    padding:8px 0;
    border-bottom:1px solid #f3f4f6;
}
.goulot-item:last-child { border-bottom:none; }
.goulot-label { flex:1; font-size:12.5px; color:#374151; }
.goulot-bar-wrap { width:140px; }
.goulot-bar-bg { height:6px; background:#f3f4f6; border-radius:3px; overflow:hidden; }
.goulot-bar-fill { height:100%; border-radius:3px; }
.goulot-val {
    font-family:'IBM Plex Mono',monospace;
    font-size:12px; font-weight:500; color:#374151;
    width:60px; text-align:right;
}
.goulot-count { font-size:11px; color:#9ca3af; width:50px; text-align:right; }

/* ====== FINANCE OVERVIEW ====== */
.finance-overview {
    display:grid; grid-template-columns:repeat(4,1fr); gap:12px;
    margin-bottom:16px;
}
.fin-kpi {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:16px;
}
.fin-kpi .f-label {
    font-size:10.5px; font-weight:600; color:#6b7280;
    text-transform:uppercase; letter-spacing:.6px;
    margin-bottom:8px;
}
.fin-kpi .f-val {
    font-family:'IBM Plex Mono',monospace;
    font-size:20px; font-weight:600; color:#111827;
    line-height:1.2;
}
.fin-kpi .f-val small {
    font-size:12px; color:#9ca3af; font-family:'IBM Plex Sans',sans-serif;
    font-weight:400; margin-left:2px;
}
.fin-kpi .f-sub { font-size:11px; color:#9ca3af; margin-top:4px; }
.fin-kpi .f-gauge {
    height:4px; background:#f3f4f6; border-radius:2px; margin-top:10px;
    overflow:hidden;
}
.fin-kpi .f-gauge .fill { height:100%; border-radius:2px; }

/* ====== STATUS CHIP ====== */
.chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:2px 8px; border-radius:10px;
    font-size:11px; font-weight:600;
}
.chip-green { background:#dcfce7; color:#166534; }
.chip-red   { background:#fee2e2; color:#991b1b; }
.chip-amber { background:#fef3c7; color:#92400e; }
.chip-blue  { background:#dbeafe; color:#1e40af; }
.chip-gray  { background:#f3f4f6; color:#374151; }

/* ====== SCORE BAR ====== */
.score-bar {
    display:flex; align-items:center; gap:8px;
}
.score-track {
    flex:1; height:5px; background:#f3f4f6;
    border-radius:3px; overflow:hidden;
}
.score-fill { height:100%; border-radius:3px; }

/* ====== EMPTY STATE ====== */
.empty-analysis {
    text-align:center; padding:40px 20px;
    color:#9ca3af;
}
.empty-analysis i { font-size:32px; margin-bottom:10px; display:block; }

/* ====== RESPONSIVE ====== */
@media(max-width:1200px) {
    .kpi-grid-top { grid-template-columns:repeat(3,1fr); }
    .finance-overview { grid-template-columns:1fr 1fr; }
    .three-col { grid-template-columns:1fr; }
    .wide-narrow,.narrow-wide { grid-template-columns:1fr; }
}
@media(max-width:900px) {
    .two-col { grid-template-columns:1fr; }
    .kpi-grid-top { grid-template-columns:1fr 1fr; }
}
</style>
</head>
<body>
<?php include("sidebar_cnma.php"); ?>
<?php include("header_cnma.php"); ?>

<div class="cnma-main">

<!-- ── PAGE HEADER ── -->
<div class="stat-page-header">
    <div>
        <h1><i class="fa fa-chart-line" style="font-size:16px;margin-right:8px;color:#374151;"></i>Analyse &amp; Statistiques</h1>
        <div class="subtitle">Vue analytique du système · Performance, blocages, décisions, finances</div>
    </div>
    <div class="last-update">
        Données au <?php echo date('d/m/Y H:i'); ?>
    </div>
</div>

<!-- ── FILTRES ── -->
<form method="GET" class="stat-filter-bar">
    <label>Agence</label>
    <select name="agence">
        <option value="0">Toutes</option>
        <?php while($a = mysqli_fetch_assoc($agences_list)): ?>
        <option value="<?php echo $a['id_agence']; ?>" <?php echo $filtre_agence==$a['id_agence']?'selected':''; ?>>
            <?php echo $a['nom_agence']; ?>
        </option>
        <?php endwhile; ?>
    </select>

    <label>Expert</label>
    <select name="expert">
        <option value="0">Tous</option>
        <?php while($e = mysqli_fetch_assoc($experts_list)): ?>
        <option value="<?php echo $e['id_expert']; ?>" <?php echo $filtre_expert==$e['id_expert']?'selected':''; ?>>
            <?php echo $e['nom'].' '.$e['prenom']; ?>
        </option>
        <?php endwhile; ?>
    </select>

    <label>Année</label>
    <select name="annee">
        <option value="0">Toutes</option>
        <?php while($y = mysqli_fetch_assoc($annees_list)): ?>
        <option value="<?php echo $y['y']; ?>" <?php echo $filtre_annee==$y['y']?'selected':''; ?>>
            <?php echo $y['y']; ?>
        </option>
        <?php endwhile; ?>
    </select>

    <label>Mois</label>
    <select name="mois">
        <option value="0">Tous</option>
        <?php $mnames=['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        for($i=1;$i<=12;$i++): ?>
        <option value="<?php echo $i; ?>" <?php echo $filtre_mois==$i?'selected':''; ?>><?php echo $mnames[$i]; ?></option>
        <?php endfor; ?>
    </select>

    <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> Appliquer</button>
    <a href="statistiques_cnma.php" class="btn-reset"><i class="fa fa-times"></i> Réinitialiser</a>

    <?php if($filtre_agence || $filtre_expert || $filtre_mois): ?>
    <span class="filter-active-badge"><i class="fa fa-circle" style="font-size:7px;"></i> Filtres actifs</span>
    <?php endif; ?>
    
</form>

<!-- ═══════════════════════════════════════════════
     BLOC 1 — INDICATEURS DÉCISIONNELS
════════════════════════════════════════════════ -->
<div class="section-header">
    <span class="sect-icon" style="background:#f3f4f6; color:#374151;"><i class="fa fa-gauge"></i></span>
    <h2>Indicateurs décisionnels</h2>
    
</div>
<div class="kpi-grid-top">

    <!-- Volume -->
    <div class="kpi-card" style="--accent:#6b7280">
        <div class="kpi-label">
            <i class="fa fa-folder"></i> Volume total
        </div>
        <div class="kpi-value">
            <?php echo $kpi['total']; ?>
        </div>
        <div class="kpi-sub">Total des dossiers</div>
    </div>

    <!-- En cours CRMA -->
    <div class="kpi-card" style="--accent:#3b82f6">
        <div class="kpi-label">
            <i class="fa fa-spinner"></i> Dossiers en traitement 
        </div>
        <div class="kpi-value <?php echo $kpi['en_traitement']>10?'bad':($kpi['en_traitement']>5?'warn':'good'); ?>">
           <?php echo $kpi['en_traitement']; ?>
        </div>
       <div class="kpi-sub">toutes phases confondues</div>
    </div>

    <!-- Retard -->
    <div class="kpi-card" style="--accent:#dc2626">
        <div class="kpi-label">
            <i class="fa fa-exclamation-triangle"></i> Dossiers en retard
        </div>
        <div class="kpi-value <?php echo $retard>10?'bad':($retard>5?'warn':'good'); ?>">
            <?php echo $retard; ?>
        </div>
        <div class="kpi-sub">Plus de 15 jours sans clôture</div>
    </div>

    <!-- Clôture -->
    <div class="kpi-card" style="--accent:#10b981">
        <div class="kpi-label">
            <i class="fa fa-archive"></i> Taux de clôture
        </div>
        <?php $taux_cloture = $kpi['total'] > 0 ? round($kpi['clotures']/$kpi['total']*100,1) : 0; ?>
        <div class="kpi-value <?php echo $taux_cloture>=40?'good':($taux_cloture>=20?'warn':'bad'); ?>">
            <?php echo $taux_cloture; ?>%
        </div>
       <?php echo $kpi['clotures']; ?> <?php echo $kpi['clotures'] == 1 ? 'clôturé' : 'clôturés'; ?>
    </div>

</div>


<!-- ═══════════════════════════════════════════════
     BLOC 2 — ÉVOLUTION TEMPORELLE
════════════════════════════════════════════════ -->
<div class="section-header">
    <span class="sect-icon" style="background:#eff6ff; color:#1d4ed8;"><i class="fa fa-chart-line"></i></span>
    <h2>Évolution temporelle</h2>

</div>

<div class="two-col">
    <div class="chart-card">
        <div class="cc-header">
            <div>
                <div class="cc-title">Flux mensuel des dossiers — <?php echo $filtre_annee ?: 'Toutes années'; ?></div>
                
            </div>
        </div>
        <canvas id="chartFluxMensuel"></canvas>
    </div>

    <div class="chart-card">
        <div class="cc-header">
            <div>
                <div class="cc-title">Décisions CNMA par mois</div>
                
            </div>
        </div>
        <canvas id="chartDecisionsMois"></canvas>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     BLOC 3 — PIPELINE DE TRAITEMENT (CRMA)
════════════════════════════════════════════════ -->
<div class="section-header">
    <span class="sect-icon" style="background:#fef3c7; color:#92400e;"><i class="fa fa-sitemap"></i></span>
    <h2>Pipeline de traitement CRMA</h2>
   
</div>

<div class="pipeline-container">
    <div class="cc-header" style="margin-bottom:0;">
        <div>
            <div class="cc-title">Répartition des <?php echo $kpi['total']; ?> dossiers par état</div>
            
        </div>
     
    </div>
    <div class="pipeline-bar" id="pipelineBar"></div>
    <div class="pipeline-legend" id="pipelineLegend"></div>
</div>

<!-- ═══════════════════════════════════════════════
     BLOC 4 — PERFORMANCE PAR AGENCE
════════════════════════════════════════════════ -->
<div class="section-header">
    <span class="sect-icon" style="background:#f0fdf4; color:#166534;"><i class="fa fa-building"></i></span>
    <h2>Performance par agence CRMA</h2>

</div>

<div class="wide-narrow">
    <div class="chart-card" style="overflow:auto;">
        <div class="cc-header">
            <div>
                <div class="cc-title">Répartition des dossiers par agence</div>
               
            </div>
        </div>
        <table class="perf-table">
            <thead>
                <tr>
                    <th>Agence</th>
                    <th>Dossiers</th>
                    <th>Validés</th>
                    <th>Refusés</th>
                   
                </tr>
            </thead>
            <tbody>
            <?php while($ag = mysqli_fetch_assoc($perf_agence)):
                $td = ($ag['valides'] + $ag['refuses']);
                $tv = $td > 0 ? round($ag['valides']/$td*100,0) : null;
                $conso = $ag['total_reserve'] > 0 ? round($ag['total_regle']/$ag['total_reserve']*100,0) : 0;
            ?>
            <tr>
                <td style="font-weight:500;"><?php echo htmlspecialchars($ag['nom_agence']); ?></td>
                <td class="mono"><?php echo $ag['total']; ?></td>
                <td>
                    <span class="chip chip-green"><?php echo $ag['valides']; ?></span>
                </td>
                <td>
                    <?php if($ag['refuses']>0): ?>
                    <span class="chip chip-red"><?php echo $ag['refuses']; ?></span>
                    <?php else: echo '<span style="color:#9ca3af">0</span>'; endif; ?>
                </td>
               
              
                
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="chart-card">
        <div class="cc-header">
            <div>
                <div class="cc-title">Dossiers par agence</div>
                <div class="cc-question">Répartition de la charge</div>
            </div>
        </div>
        <canvas id="chartAgencePie"></canvas>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     BLOC 5 — PERFORMANCE EXPERTS
════════════════════════════════════════════════ -->
<div class="section-header">
    <span class="sect-icon" style="background:#fdf4ff; color:#7e22ce;"><i class="fa fa-user-tie"></i></span>
    <h2>Performance des experts</h2>
   
</div>

<div class="chart-card">
    <div class="cc-header">
        <div>
            <div class="cc-title">Tableau de bord experts</div>
            <div class="cc-question">Volume et montants d'indemnisation</div>
        </div>
    </div>
    <?php $has_experts = false; ?>
    <table class="perf-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Expert</th>
                <th>Dossiers</th>
                <th>Expertises</th>
                <th>Moy. indemnité</th>
                <th>Max. indemnité</th>
              
            
            </tr>
        </thead>
        <tbody>
       <?php $rank=1; while($ex = mysqli_fetch_assoc($perf_experts)): $has_experts=true; ?>
<tr>
    <td style="color:#9ca3af; font-family:'IBM Plex Mono',monospace; font-size:11px;">
        <?php echo str_pad($rank,2,'0',STR_PAD_LEFT); ?>
    </td>

    <td style="font-weight:500;">
        <?php echo htmlspecialchars($ex['expert_nom']); ?>
    </td>

    <?php
    $color = $ex['nb_dossiers'] >= 4 ? '#059669' : ($ex['nb_dossiers'] >= 2 ? '#d97706' : '#6b7280');
    ?>

    <!-- ✅ DOSSIERS -->
    <td class="mono" style="color:<?php echo $color; ?>">
        <?php echo $ex['nb_dossiers']; ?>
    </td>

    <!-- ✅ EXPERTISES -->
    <td class="mono">
        <?php echo $ex['nb_expertises']; ?>
    </td>

    <!-- ✅ MOY -->
    <td class="mono">
        <?php echo $ex['moy_indemnite'] ? number_format($ex['moy_indemnite'],0,',',' ').' DA' : '—'; ?>
    </td>

    <!-- ✅ MAX -->
    <td class="mono" style="color:#6b7280;">
        <?php echo $ex['max_indemnite'] ? number_format($ex['max_indemnite'],0,',',' ').' DA' : '—'; ?>
    </td>
</tr>
<?php $rank++; endwhile; ?>
        <?php if(!$has_experts): ?>
        <tr><td colspan="8"><div class="empty-analysis"><i class="fa fa-user-tie"></i><p>Aucune expertise enregistrée</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ═══════════════════════════════════════════════
     BLOC 6 — ANALYSE DES BLOCAGES
════════════════════════════════════════════════ -->
<div class="section-header">
    <span class="sect-icon" style="background:#fef2f2; color:#991b1b;"><i class="fa fa-triangle-exclamation"></i></span>
    <h2>Analyse des blocages</h2>
    
</div>

<div class="two-col">
    <!-- Goulots d'étranglement par état -->
    <div class="chart-card">
        <div class="cc-header">
            <div>
                <div class="cc-title">Durée moyenne des dossiers en cours par état</div>
                
            </div>
        </div>
        <?php
        $goulot_max = 1;
        $goulot_rows = [];
        while($g = mysqli_fetch_assoc($goulots)) { $goulot_rows[] = $g; if($g['age_moy']>$goulot_max) $goulot_max=$g['age_moy']; }
        ?>
        <?php foreach($goulot_rows as $g):
            $pct_g = $goulot_max > 0 ? round($g['age_moy']/$goulot_max*100) : 0;
            $col_g = $g['age_moy'] >= 30 ? '#dc2626' : ($g['age_moy'] >= 15 ? '#d97706' : '#059669');
        ?>
        <div class="goulot-item">
            <div class="goulot-label"><?php echo htmlspecialchars($g['nom_etat']); ?></div>
            <div class="goulot-bar-wrap">
                <div class="goulot-bar-bg"><div class="goulot-bar-fill" style="width:<?php echo $pct_g; ?>%; background:<?php echo $col_g; ?>;"></div></div>
            </div>
            <div class="goulot-val" style="color:<?php echo $col_g; ?>;"><?php echo $g['age_moy']; ?> j</div>
            <div class="goulot-count"><?php echo $g['nb']; ?> dos.</div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($goulot_rows)): ?><div class="empty-analysis"><i class="fa fa-check-circle" style="color:#059669;"></i><p>Aucun blocage détecté</p></div><?php endif; ?>
    </div>

    <!-- Dossiers bloqués -->
    <div class="chart-card" style="overflow:auto;">
        <div class="cc-header">
            <div>
               <?php
$nb_blocages = mysqli_num_rows($blocages);
mysqli_data_seek($blocages, 0); // important sinon boucle vide après
?>
<div class="cc-title">Dossiers critiques (<?php echo $nb_blocages; ?>)</div>
                <div class="cc-question">Dossiers présentant un retard de traitement</div>
              
            </div>
            <span class="cc-badge chip chip-red">+15 jours</span>
        </div>
        <table class="perf-table">
            <thead><tr><th>Dossier</th><th>Agence</th><th>État</th><th>Durée en cours</th></tr></thead>
            <tbody>
            <?php $has_bloc = false; while($b = mysqli_fetch_assoc($blocages)):
                $has_bloc = true;
                $crit = $b['age_jours'] >= 30;
                $warn = $b['age_jours'] >= 15;
            ?>
            <tr class="<?php echo $crit?'blocage-row-critical':($warn?'blocage-row-warn':''); ?>">
                <td>
                    <a href="voir_dossier_cnma.php?id=<?php echo $b['id_dossier']; ?>"
                       style="font-family:'IBM Plex Mono',monospace; font-size:12px; font-weight:500; color:#1d4ed8; text-decoration:none;">
                        <?php echo $b['numero_dossier']; ?>
                    </a>
                  <div style="font-size:11px; color:#6b7280;">
    <?php echo htmlspecialchars($b['nom_assure'].' '.$b['prenom_assure']); ?>
</div>
<div style="font-size:10px; color:#9ca3af;">
    Créé le : <?php echo date('d/m/Y', strtotime($b['date_creation'])); ?>
</div>
                </td>
                <td style="font-size:12px; color:#6b7280;"><?php echo htmlspecialchars($b['nom_agence']); ?></td>
                <td><span class="chip chip-amber" style="font-size:10px;"><?php echo htmlspecialchars($b['nom_etat']); ?></span></td>
                <td>
                    <span class="age-badge <?php echo $crit?'crit':($warn?'warn':'ok'); ?>">
                        <?php echo $b['age_jours']; ?> j
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if(!$has_bloc): ?><tr><td colspan="4"><div class="empty-analysis" style="padding:20px;"><i class="fa fa-check-circle" style="color:#059669;"></i><p>Aucun dossier bloqué</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     BLOC 7 — ANALYSE FINANCIÈRE
════════════════════════════════════════════════ -->
<div class="section-header">
    <span class="sect-icon" style="background:#f0fdf4; color:#166534;"><i class="fa fa-scale-balanced"></i></span>
    <h2>Analyse financière</h2>
    <span class="sect-tag">Le coût réel du sinistre — réserves, règlements, consommation</span>
</div>

<div class="finance-overview">
    <div class="fin-kpi">
        <div class="f-label">Réserves totales</div>
        <div class="f-val"><?php echo number_format($fin['total_reserve'],0,',',' '); ?><small>DA</small></div>
        <div class="f-sub">Provision constituée</div>
    </div>
    <div class="fin-kpi">
        <div class="f-label">Total réglé</div>
        <div class="f-val" style="color:#059669;"><?php echo number_format($fin['total_regle'],0,',',' '); ?><small>DA</small></div>
        <div class="f-sub">Décaissements effectués</div>
        <div class="f-gauge"><div class="fill" style="width:<?php echo min(100,$taux_conso); ?>%; background:#059669;"></div></div>
    </div>
    <div class="fin-kpi">
        <div class="f-label">Reste à régler</div>
        <?php $reste_fin = $fin['total_reserve'] - $fin['total_regle']; ?>
        <div class="f-val" style="color:<?php echo $reste_fin>0?'#dc2626':'#059669'; ?>;">
            <?php echo number_format(abs($reste_fin),0,',',' '); ?><small>DA</small>
        </div>
        <div class="f-sub"><?php echo $reste_fin>0?'Solde non réglé':'Aucun impayé'; ?></div>
    </div>
    <div class="fin-kpi">
        <div class="f-label">Taux de consommation</div>
        <div class="f-val" style="color:<?php echo $taux_conso>=80?'#059669':($taux_conso>=50?'#d97706':'#6b7280'); ?>;">
            <?php echo $taux_conso; ?><small>%</small>
        </div>
        <div class="f-sub">Réglé / Réserve</div>
        <div class="f-gauge"><div class="fill" style="width:<?php echo $taux_conso; ?>%; background:<?php echo $taux_conso>=80?'#059669':($taux_conso>=50?'#d97706':'#9ca3af'); ?>;"></div></div>
    </div>
</div>

<div class="chart-card">
    <div class="cc-header">
        <div>
            <div class="cc-title">Évolution Réserves vs Réglé — <?php echo $filtre_annee ?: 'Toutes années'; ?></div>
            <div class="cc-question">Le rythme de règlement suit-il la constitution des réserves ?</div>
        </div>
        <?php $gap = $fin['total_reserve'] - $fin['total_regle'];
        $gap_pct = $fin['total_reserve']>0 ? round($gap/$fin['total_reserve']*100,1) : 0; ?>
        <span class="cc-badge" style="background:<?php echo $gap_pct>30?'#fee2e2':($gap_pct>10?'#fef3c7':'#dcfce7'); ?>; color:<?php echo $gap_pct>30?'#991b1b':($gap_pct>10?'#92400e':'#166534'); ?>;">
            Écart : <?php echo $gap_pct; ?>%
        </span>
    </div>
    <canvas id="chartFinanceMois" style="max-height:200px;"></canvas>
</div>

</div><!-- /cnma-main -->

<script>
// ── Données JS ──
const moisLabels = <?php echo json_encode($mois_labels); ?>;
const mCrees   = <?php echo json_encode($m_crees); ?>;
const mValides = <?php echo json_encode($m_valides); ?>;
const mRefuses = <?php echo json_encode($m_refuses); ?>;

const fmReserve = <?php echo json_encode($fm_reserve); ?>;
const fmRegle   = <?php echo json_encode($fm_regle); ?>;

const pipelineLabels = <?php echo json_encode(array_column($pipeline_states,'label')); ?>;
const pipelineVals   = <?php echo json_encode($pipeline_vals); ?>;
const pipelineColors = <?php echo json_encode(array_column($pipeline_states,'color')); ?>;

const agenceLabels = <?php
    $tmp = []; mysqli_data_seek($perf_agence, 0);
    while($ag = mysqli_fetch_assoc($perf_agence)) $tmp[] = $ag['nom_agence'];
    echo json_encode($tmp);
?>;
const agenceVals = <?php
    $tmp = []; mysqli_data_seek($perf_agence, 0);
    while($ag = mysqli_fetch_assoc($perf_agence)) $tmp[] = intval($ag['total']);
    echo json_encode($tmp);
?>;

// ── Chart defaults ──
Chart.defaults.font.family = "'IBM Plex Sans', sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#6b7280';
Chart.defaults.plugins.legend.labels.boxWidth = 10;
Chart.defaults.plugins.legend.labels.padding  = 14;

const gridStyle = {
    color: '#f3f4f6',
    drawBorder: false
};
const tickStyle = { font: { size: 10 } };

// ── Flux mensuel ──
new Chart(document.getElementById('chartFluxMensuel'), {
    type: 'bar',
    data: {
        labels: moisLabels,
        datasets: [
            {
                label: 'Créés', data: mCrees,
                backgroundColor: '#93c5fd', borderRadius: 4, borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: gridStyle, ticks: tickStyle },
            x: { grid: { display: false }, ticks: tickStyle }
        }
    }
});

// ── Décisions par mois ──
new Chart(document.getElementById('chartDecisionsMois'), {
    type: 'bar',
    data: {
        labels: moisLabels,
        datasets: [
            {
                label: 'Validés', data: mValides,
                backgroundColor: '#86efac', borderRadius: 4, borderSkipped: false, stack: 'stack'
            },
            {
                label: 'Refusés', data: mRefuses,
                backgroundColor: '#fca5a5', borderRadius: 4, borderSkipped: false, stack: 'stack'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'bottom' }
        },
        scales: {
            y: { beginAtZero: true, grid: gridStyle, ticks: tickStyle, stacked: true },
            x: { grid: { display: false }, ticks: tickStyle, stacked: true }
        }
    }
});

// ── Pipeline bar ──
(function() {
    const bar    = document.getElementById('pipelineBar');
    const legend = document.getElementById('pipelineLegend');
    const total  = pipelineVals.reduce((a,b) => a+b, 0) || 1;
    pipelineLabels.forEach((lbl, i) => {
        const val = pipelineVals[i];
        if(val === 0) return;
        const pct = (val / total * 100).toFixed(1);
        const seg = document.createElement('div');
        seg.className = 'pipeline-segment';
        seg.style.width = pct + '%';
        seg.style.background = pipelineColors[i];
        if(parseFloat(pct) > 6) seg.textContent = pct + '%';
        seg.title = lbl + ' : ' + val + ' (' + pct + '%)';
        bar.appendChild(seg);

        const li = document.createElement('div');
        li.className = 'pipeline-legend-item';
        li.innerHTML = `<div class="pipeline-legend-dot" style="background:${pipelineColors[i]};"></div>
            <span>${lbl}</span><span style="font-family:'IBM Plex Mono',monospace; margin-left:4px; font-weight:600;">${val}</span>
            <span style="color:#9ca3af; font-size:10px; margin-left:2px;">(${pct}%)</span>`;
        legend.appendChild(li);
    });
})();

// ── Agence donut ──
new Chart(document.getElementById('chartAgencePie'), {
    type: 'doughnut',
    data: {
        labels: agenceLabels,
        datasets: [{
            data: agenceVals,
            backgroundColor: ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        cutout: '62%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } }
        }
    }
});

// ── Finance évolution ──
new Chart(document.getElementById('chartFinanceMois'), {
    type: 'line',
    data: {
        labels: moisLabels,
        datasets: [
            {
                label: 'Réserves (DA)', data: fmReserve,
                borderColor: '#6b7280', backgroundColor: 'rgba(107,114,128,.06)',
                borderWidth: 1.5, pointRadius: 3, fill: true, tension: .35
            },
            {
                label: 'Réglé (DA)', data: fmRegle,
                borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.06)',
                borderWidth: 2, pointRadius: 3, fill: true, tension: .35
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.dataset.label + ' : ' + ctx.parsed.y.toLocaleString('fr-DZ') + ' DA'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true, grid: gridStyle, ticks: {
                    ...tickStyle,
                    callback: v => v.toLocaleString('fr-DZ') + ' DA'
                }
            },
            x: { grid: { display: false }, ticks: tickStyle }
        }
    }
});
</script>
</body>
</html>