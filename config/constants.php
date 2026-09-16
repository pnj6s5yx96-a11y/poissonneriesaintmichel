<?php
declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));
define('BASE_URL', rtrim((string) (getenv('APP_URL') ?: '/poissonnerie-saint-michel'), '/'));
define('UPLOADS_PATH', PROJECT_ROOT . '/assets/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('MAX_UPLOAD_PIXELS', 20_000_000);
define('MAX_CART_ITEMS', 100);

date_default_timezone_set('Africa/Porto-Novo');
