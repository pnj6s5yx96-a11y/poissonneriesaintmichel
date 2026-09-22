<?php
declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));
// Le site est déployé à la racine de httpdocs. Définir APP_URL uniquement si
// l'installation est placée dans un sous-dossier (par exemple en local).
define('BASE_URL', rtrim((string) (getenv('APP_URL') ?: ''), '/'));
// URL publique de référence pour les balises canoniques, le sitemap et Open Graph.
// À remplacer par une variable d'environnement PUBLIC_SITE_URL si le domaine change.
define('PUBLIC_SITE_URL', rtrim((string) (getenv('PUBLIC_SITE_URL') ?: 'https://poissonnerie-saint-michel.yes.bj'), '/'));
define('UPLOADS_PATH', PROJECT_ROOT . '/assets/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('MAX_UPLOAD_PIXELS', 20_000_000);
define('MAX_CART_ITEMS', 100);

date_default_timezone_set('Africa/Porto-Novo');
