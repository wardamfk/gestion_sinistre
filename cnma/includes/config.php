<?php
$conn = mysqli_connect("127.0.0.1", "root", "", "gestion_sinistre", 3306);

if (!$conn) {
    die("Connexion échouée");
}

if (PHP_SAPI !== 'cli' && !defined('PFE_RESPONSIVE_BOOTSTRAP')) {
    define('PFE_RESPONSIVE_BOOTSTRAP', 1);
    ob_start(function ($buffer) {
        if (stripos($buffer, '<meta name="viewport"') !== false) {
            return $buffer;
        }
        $headPos = stripos($buffer, '<head');
        if ($headPos === false) {
            return $buffer;
        }
        $headEnd = strpos($buffer, '>', $headPos);
        if ($headEnd === false) {
            return $buffer;
        }
        $insert = "\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
        $charsetPos = stripos($buffer, '<meta charset');
        if ($charsetPos !== false) {
            $charsetEnd = strpos($buffer, '>', $charsetPos);
            if ($charsetEnd !== false) {
                return substr($buffer, 0, $charsetEnd + 1) . $insert . substr($buffer, $charsetEnd + 1);
            }
        }
        return substr($buffer, 0, $headEnd + 1) . $insert . substr($buffer, $headEnd + 1);
    });
}
?>
