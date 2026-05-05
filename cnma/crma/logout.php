<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
pfe_session_destroy();
header("Location: /PfeCnma/cnma/pages/login.php");
exit();

