<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

/** Retourne une connexion PDO unique à la base MySQL locale. */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $localConfig = __DIR__ . '/database.local.php';

    if (!is_file($localConfig)) {
        throw new RuntimeException(
            'Configuration absente : copiez config/database.local.php.example vers config/database.local.php.'
        );
    }

    /** @var array{host:string, port:string, database:string, username:string, password:string} $config */
    $config = require $localConfig;
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['database']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
