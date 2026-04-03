<?php
session_start();
session_destroy();
header("Location: /PfeCnma/cnma/pages/login.php");
exit();
?>
