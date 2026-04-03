<?php
include("../includes/auth.php");
include("../includes/config.php");

if(!in_array($_SESSION['role'], ['CRMA','CNMA'])) {
    header("Location: ../pages/login.php"); exit();
}

$id_user = $_SESSION['id_user'];

// Marquer comme lu si demandé
if(isset($_GET['read'])) {
    $id_notif = intval($_GET['read']);
    mysqli_query($conn, "UPDATE notification SET lu=1 WHERE id_notification=$id_notif AND id_destinataire=$id_user");
    header("Location: notifications.php"); exit();
}

// Tout marquer comme lu
if(isset($_GET['read_all'])) {
    mysqli_query($conn, "UPDATE notification SET lu=1 WHERE id_destinataire=$id_user");
    header("Location: notifications.php"); exit();
}

// Récupérer notifications
$notifications = mysqli_query($conn, "
    SELECT n.*,
           d.numero_dossier,
           u.nom AS expediteur_nom
    FROM notification n
    LEFT JOIN dossier d ON n.id_dossier = d.id_dossier
    LEFT JOIN utilisateur u ON n.id_expediteur = u.id_user
    WHERE n.id_destinataire = $id_user
    ORDER BY n.date_notification DESC
");

$nb_non_lues = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as n FROM notification WHERE id_destinataire=$id_user AND lu=0"))['n'];

$type_icons = [
    'validation' => ['fa-check-circle', '#2e7d32', '#e8f5e9'],
    'refus'      => ['fa-times-circle', '#c62828', '#ffebee'],
    'complement' => ['fa-paper-plane',  '#e65100', '#fff3e0'],
    'reglement'  => ['fa-money-bill',   '#1a237e', '#e8eaf6'],
    'cloture'    => ['fa-archive',      '#6a1b9a', '#f3e5f5'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .notif-list { display:flex; flex-direction:column; gap:10px; }
        .notif-item {
            background: white; border-radius: 12px; padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex; align-items: flex-start; gap: 16px;
            border-left: 4px solid #e0e0e0;
            transition: 0.2s;
        }
        .notif-item.non-lu {
            border-left-color: #1a237e;
            background: #f5f7ff;
        }
        .notif-item:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .notif-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .notif-body { flex: 1; }
        .notif-body .msg { font-size: 14px; color: #2c3e50; margin-bottom: 6px; font-weight: 500; }
        .notif-body .meta { font-size: 12px; color: #90a4ae; display:flex; gap:14px; flex-wrap:wrap; }
        .notif-actions { display:flex; gap:8px; align-items:center; }
        .non-lu-dot { width:10px; height:10px; border-radius:50%; background:#1a237e; flex-shrink:0; margin-top:6px; }
    </style>
</head>
<body>
<?php
// Inclure la bonne sidebar selon le rôle
if($_SESSION['role'] == 'CNMA') {
    include("../pages/sidebar_cnma.php");
    include("../pages/header_cnma.php");
} else {
    include("../includes/sidebar.php");
    include("../includes/header.php");
}
?>

<div class="<?php echo $_SESSION['role']=='CNMA' ? 'cnma-main' : 'main'; ?>">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 style="color:#1a237e; display:flex; align-items:center; gap:10px; font-size:20px;">
            <i class="fa fa-bell"></i> Mes Notifications
            <?php if($nb_non_lues > 0): ?>
            <span style="background:#ef5350; color:white; border-radius:20px; padding:3px 12px; font-size:13px;">
                <?php echo $nb_non_lues; ?> non lue(s)
            </span>
            <?php endif; ?>
        </h2>
        <?php if($nb_non_lues > 0): ?>
        <a href="?read_all=1" class="cnma-btn secondary sm">
            <i class="fa fa-check-double"></i> Tout marquer comme lu
        </a>
        <?php endif; ?>
    </div>

    <?php
    $nb_total = mysqli_num_rows($notifications);
    if($nb_total == 0):
    ?>
    <div style="text-align:center; padding:60px 20px; background:white; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
        <i class="fa fa-bell-slash" style="font-size:48px; color:#cfd8dc; display:block; margin-bottom:16px;"></i>
        <p style="color:#90a4ae; font-size:15px;">Aucune notification pour le moment.</p>
    </div>
    <?php else: ?>
    <div class="notif-list">
    <?php
    mysqli_data_seek($notifications, 0);
    while($n = mysqli_fetch_assoc($notifications)):
        $type = $n['type'];
        $icon_info = $type_icons[$type] ?? ['fa-info-circle', '#546e7a', '#eceff1'];
        $non_lu = !$n['lu'];
    ?>
    <div class="notif-item <?php echo $non_lu ? 'non-lu' : ''; ?>">
        <?php if($non_lu): ?><div class="non-lu-dot"></div><?php endif; ?>

        <div class="notif-icon" style="background:<?php echo $icon_info[2]; ?>; color:<?php echo $icon_info[1]; ?>;">
            <i class="fa <?php echo $icon_info[0]; ?>"></i>
        </div>

        <div class="notif-body">
            <div class="msg"><?php echo htmlspecialchars($n['message']); ?></div>
            <div class="meta">
                <span><i class="fa fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($n['date_notification'])); ?></span>
                <span><i class="fa fa-user-shield"></i> <?php echo htmlspecialchars($n['expediteur_nom']); ?></span>
                <span><i class="fa fa-folder"></i> <?php echo htmlspecialchars($n['numero_dossier']); ?></span>
            </div>
        </div>

        <div class="notif-actions">
            <?php
            $voir_url = $_SESSION['role']=='CNMA'
                ? "voir_dossier_cnma.php?id=".$n['id_dossier']
                : "../crma/voir_dossier.php?id=".$n['id_dossier'];
            ?>
            <a href="<?php echo $voir_url; ?>" class="cnma-btn primary sm">
                <i class="fa fa-eye"></i> Voir
            </a>
            <?php if($non_lu): ?>
            <a href="?read=<?php echo $n['id_notification']; ?>" class="cnma-btn secondary sm">
                <i class="fa fa-check"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
