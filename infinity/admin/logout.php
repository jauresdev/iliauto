<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';

start_secure_session();
send_security_headers();

// Déconnexion uniquement par POST + jeton CSRF (évite une déconnexion forcée par un lien externe).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    logout_now();
}
redirect('login.php');
