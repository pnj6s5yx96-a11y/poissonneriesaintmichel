<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

try {
    requireAuth();
    $userId = (int) currentUser()['id'];
    $limit = queryPositiveInt('limite', 10, 30) ?? 10;
    $statement = db()->prepare(
        'SELECT n.id_notification, n.type_notification, n.message, n.date_creation,
                n.date_lecture, n.statut_envoi, c.numero_commande
           FROM notifications n
           JOIN commandes c ON c.id_commande = n.id_commande
          WHERE n.id_utilisateur = :utilisateur
          ORDER BY n.date_creation DESC
          LIMIT ' . $limit
    );
    $statement->execute(['utilisateur' => $userId]);
    $notifications = $statement->fetchAll();

    $unreadStatement = db()->prepare(
        "SELECT COUNT(*) FROM notifications
          WHERE id_utilisateur = :utilisateur
            AND statut_envoi IN ('A_ENVOYER', 'ENVOYEE')"
    );
    $unreadStatement->execute(['utilisateur' => $userId]);

    echo json_encode([
        'non_lues' => (int) $unreadStatement->fetchColumn(),
        'notifications' => $notifications,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(['erreur' => 'Les notifications sont momentanément indisponibles.'], JSON_UNESCAPED_UNICODE);
}
