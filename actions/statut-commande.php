<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

if (!isPost()) {
    redirect('gerant/commandes.php');
}

verifyCsrfToken();

try {
    $orderId = postPositiveInt('id_commande');
    $newStatus = postEnum('statut', ['EN_COURS_TRAITEMENT', 'TRAITEE', 'LIVREE']);
    $comment = postString('commentaire', 500);

    $pdo = db();
    $pdo->beginTransaction();
    $orderStatement = $pdo->prepare(
        'SELECT c.id_commande, c.id_client, c.numero_commande, c.statut_courant, c.mode_retrait,
                p.statut_paiement
           FROM commandes c
           LEFT JOIN paiements p ON p.id_commande = c.id_commande
          WHERE c.id_commande = :commande
          FOR UPDATE'
    );
    $orderStatement->execute(['commande' => $orderId]);
    $order = $orderStatement->fetch();
    if (!$order) {
        throw new RuntimeException('Commande introuvable.');
    }
    if ($newStatus === 'LIVREE' && $order['mode_retrait'] !== 'RETRAIT_BOUTIQUE') {
        throw new RuntimeException('Une commande en livraison est finalisée après le parcours du livreur.');
    }
    if ($newStatus === 'LIVREE' && $order['statut_paiement'] !== 'REUSSI') {
        throw new RuntimeException('Le paiement doit être confirmé avant de remettre la commande au client.');
    }

    $oldStatus = (string) $order['statut_courant'];
    // La procédure SQL distingue les intervenants opérationnels ; l'audit conserve le rôle exact.
    changeOrderStatus($pdo, $orderId, $newStatus, 'GERANT', (int) currentUser()['id'], $comment);
    createNotification(
        $pdo,
        $orderId,
        'CHANGEMENT_STATUT',
        sprintf('Votre commande %s est désormais %s.', $order['numero_commande'], mb_strtolower(orderStatusLabel($newStatus))),
        (int) $order['id_client']
    );
    recordAuditEvent(
        $pdo,
        'CHANGEMENT_STATUT_COMMANDE',
        'COMMANDE',
        $orderId,
        ['statut' => $oldStatus],
        ['statut' => $newStatus, 'role' => currentUser()['role']]
    );
    $pdo->commit();

    flash('success', 'Le statut de la commande a été mis à jour.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Le statut ne peut pas être modifié dans l’état actuel de cette commande.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('gerant/commandes.php');
