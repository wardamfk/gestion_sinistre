<?php
if (!function_exists('pfe_session_start')) {
    function pfe_session_base_path(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = '/';
        if (preg_match('#^(.*?/cnma)(/|$)#', $script, $m)) {
            $base = $m[1];
        }
        return rtrim($base, '/');
    }

    function pfe_session_legacy_paths(string $base): array
    {
        $paths = [
            $base . '/crma/',
            $base . '/assure/',
            $base . '/pages/',
        ];
        $out = [];
        foreach ($paths as $p) {
            $p = preg_replace('#//+#', '/', $p);
            if (!in_array($p, $out, true)) {
                $out[] = $p;
            }
        }
        return $out;
    }

    function pfe_session_start(?string $app = null): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
    
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
    
        if ($app === null || $app === '') {
            if (strpos($script, '/crma/') !== false) {
                $app = 'crma';
            } elseif (strpos($script, '/assure/') !== false) {
                $app = 'assure';
            } else {
                $app = 'cnma';
            }
        }
    
        $app = strtolower($app);
        if (!in_array($app, ['crma', 'assure', 'cnma'], true)) {
            $app = 'cnma';
        }
    
        $base = pfe_session_base_path();
        $cookiePath = ($base === '' ? '' : $base) . '/';
    
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('PFESESS_' . strtoupper($app));

        $legacyPaths = pfe_session_legacy_paths($base === '' ? '' : $base);
        foreach ($legacyPaths as $p) {
            if ($p === $cookiePath) {
                continue;
            }
            setcookie(session_name(), '', time() - 42000, $p, '', $secure, true);
        }
    
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => $cookiePath,
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(0, $cookiePath, '', $secure, true);
        }
    
        session_start();
    }
    
    function pfe_session_destroy(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            pfe_session_start();
        }
    
        $_SESSION = [];
    
        $params = session_get_cookie_params();
        $base = pfe_session_base_path();
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $legacyPaths = pfe_session_legacy_paths($base === '' ? '' : $base);
        foreach ($legacyPaths as $p) {
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p,
                $params['domain'] ?? '',
                $secure,
                (bool)($params['httponly'] ?? true)
            );
        }
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    
        session_destroy();
    }
}
