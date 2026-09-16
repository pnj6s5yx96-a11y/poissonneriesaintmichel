<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!isPost()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}

verifyCsrfToken();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'] ?? '/',
        'domain' => $cookie['domain'] ?? '',
        'secure' => (bool) ($cookie['secure'] ?? false),
        'httponly' => (bool) ($cookie['httponly'] ?? true),
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
redirect('auth/connexion.php');
