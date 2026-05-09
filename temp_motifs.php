<?php
include('cnma/includes/config.php');

echo "\n=== MOTIFS REFUS (ETAT 5) ===\n";
$refus = mysqli_query($conn, "SELECT id_motif, nom_motif, message_assure FROM motif WHERE id_etat = 5 ORDER BY id_motif");
while($m = mysqli_fetch_assoc($refus)) {
    echo "\nMotif #" . $m['id_motif'] . " : " . $m['nom_motif'] . "\n";
    echo "Message assuré :\n" . ($m['message_assure'] ?: '(vide - message par défaut sera utilisé)') . "\n";
    echo "---\n";
}

echo "\n\n=== MOTIFS COMPLEMENT (ETAT 6) ===\n";
$comp = mysqli_query($conn, "SELECT id_motif, nom_motif FROM motif WHERE id_etat = 6 ORDER BY id_motif");
while($m = mysqli_fetch_assoc($comp)) {
    echo "\nMotif #" . $m['id_motif'] . " : " . $m['nom_motif'] . "\n";
}

mysqli_close($conn);
?>
