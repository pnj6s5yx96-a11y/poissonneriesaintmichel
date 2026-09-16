<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

http_response_code(405);
header('Allow: POST');
exit('Utilisez le formulaire de déconnexion.');
