<?php
if (!function_exists('pfe_mailer_env')) {
    function pfe_mailer_env(string $key, ?string $default = null): ?string
    {
        $v = getenv($key);
        if ($v === false) {
            return $default;
        }
        $v = trim((string)$v);
        return $v === '' ? $default : $v;
    }

    function pfe_mailer_project_root(): string
    {
        return dirname(__DIR__, 2);
    }

    function pfe_mailer_try_require(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }
        require_once $path;
        return true;
    }

    function pfe_mailer_load_dependencies(): bool
    {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return true;
        }

        $root = pfe_mailer_project_root();
        $candidates = [
            $root . '/vendor/autoload.php',
            dirname(__DIR__) . '/vendor/autoload.php',
        ];

        foreach ($candidates as $autoload) {
            if (pfe_mailer_try_require($autoload) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                return true;
            }
        }

        $local = __DIR__ . '/PHPMailer/src/PHPMailer.php';
        if (is_file($local)) {
            require_once __DIR__ . '/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        }

        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    function pfe_mailer_log(string $message, array $context = []): void
    {
        $payload = [
            'ts' => date('c'),
            'message' => $message,
            'context' => $context,
        ];

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = date('c') . ' ' . $message;
        }

        $dir = dirname(__DIR__) . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/mailer.log';
        if (@error_log($line . PHP_EOL, 3, $file) === false) {
            error_log($line);
        }
    }

    function pfe_mailer_escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function pfe_mailer_template(string $title, string $contentHtml, string $preheader = ''): string
    {
        $brand = '#0b8f3a';
        $preheaderSafe = pfe_mailer_escape($preheader);
        $titleSafe = pfe_mailer_escape($title);

        return '
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . $titleSafe . '</title>

<style>
body{
    margin:0;
    padding:0;
    background:#f3f5f7;
    font-family:Arial,Helvetica,sans-serif;
    color:#1f2937;
}

.container{
    max-width:640px;
    margin:0 auto;
    padding:24px;
}

.card{
    background:#111827;
    border-radius:14px;
    overflow:hidden;
}

.header{
    padding:22px 24px;
    background:' . $brand . ';
    color:#ffffff;
}

.header-title{
    font-size:20px;
    line-height:1.4;
    font-weight:700;
    margin:0;
}

.body{
    padding:24px;
    color:#ffffff;
}

.footer{
    padding:18px 24px;
    background:#e5e7eb;
    color:#374151;
    font-size:12px;
    line-height:1.6;
}

.divider{
    height:1px;
    background:#374151;
    margin:20px 0;
}

.row{
    margin-bottom:16px;
}

.label{
    color:#9ca3af;
    font-size:13px;
    margin-bottom:4px;
}

.value{
    font-size:16px;
    font-weight:700;
    color:#ffffff;
}

p{
    color:#ffffff;
}

@media(max-width:520px){
    .container{
        padding:12px;
    }

    .body{
        padding:18px;
    }
}
</style>

</head>

<body>

<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent">
' . $preheaderSafe . '
</div>

<div class="container">

<div class="card">

<div class="header">
<p class="header-title">' . $titleSafe . '</p>
</div>

<div class="body">
' . $contentHtml . '
</div>

<div class="footer">
CRMA / CNMA – Gestion des sinistres automobile
</div>

</div>

</div>

</body>
</html>
';
    }

    function pfe_mailer_send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '') {
            return ['ok' => false, 'skipped' => true, 'error' => 'Adresse email vide'];
        }

        if (!pfe_mailer_load_dependencies()) {
            $msg = 'PHPMailer non disponible (installer via Composer: phpmailer/phpmailer).';
            pfe_mailer_log($msg, ['to' => $toEmail]);
            return ['ok' => false, 'skipped' => false, 'error' => $msg];
        }

       $user = 'warda.moufouki@gmail.com';

$pass = 'gjllufhahutejxvr';

$fromEmail = 'warda.moufouki@gmail.com';

$fromName = 'CRMA / CNMA';

        if (!$user || !$pass || !$fromEmail) {
            $msg = 'Configuration SMTP Gmail incomplète (GMAIL_SMTP_USER / GMAIL_SMTP_PASS).';
            pfe_mailer_log($msg, ['to' => $toEmail]);
            return ['ok' => false, 'skipped' => false, 'error' => $msg];
        }

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($subject);

            $mail->send();
            return ['ok' => true, 'skipped' => false, 'error' => null];
        } catch (Throwable $e) {
            pfe_mailer_log('Echec envoi email', ['to' => $toEmail, 'error' => $e->getMessage()]);
            return ['ok' => false, 'skipped' => false, 'error' => $e->getMessage()];
        }
    }

    function pfe_notification_update_email($conn, int $idNotification, array $payload): void
    {
        if (!$conn || $idNotification <= 0) {
            return;
        }

        $idNotification = (int)$idNotification;

        $emailTo = mysqli_real_escape_string($conn, (string)($payload['email_to'] ?? ''));
        $subject = mysqli_real_escape_string($conn, (string)($payload['email_subject'] ?? ''));
        $body = mysqli_real_escape_string($conn, (string)($payload['email_body_html'] ?? ''));
        $status = mysqli_real_escape_string($conn, (string)($payload['email_status'] ?? ''));
        $error = mysqli_real_escape_string($conn, (string)($payload['email_error'] ?? ''));

        $sentAtSql = 'NULL';
        if (!empty($payload['email_sent_at'])) {
            $sentAt = mysqli_real_escape_string($conn, (string)$payload['email_sent_at']);
            $sentAtSql = "'" . $sentAt . "'";
        }

        $q = "UPDATE notification
            SET email_to = NULLIF('$emailTo',''),
                email_subject = NULLIF('$subject',''),
                email_body_html = NULLIF('$body',''),
                email_status = NULLIF('$status',''),
                email_error = NULLIF('$error',''),
                email_last_attempt_at = NOW(),
                email_attempts = COALESCE(email_attempts,0) + 1,
                email_sent_at = $sentAtSql
            WHERE id_notification = $idNotification";

        $ok = mysqli_query($conn, $q);
        if ($ok === false) {
            pfe_mailer_log('Echec update notification email', ['id_notification' => $idNotification, 'sql_error' => mysqli_error($conn)]);
        }
    }
}

