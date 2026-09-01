<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

if (!isPost()) {
    redirect('suivi-commande.php');
}

verifyCsrfToken();
$orderNumber = '';

try {
    $orderNumber = postOrderNumber();
    $phone = postString('telephone', 30);
    if ($phone === '') {
        throw new RuntimeException('Le numéro de commande et votre téléphone sont obligatoires.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'SELECT c.id_commande, c.id_client, c.numero_commande, c.statut_courant, c.mode_retrait,
                cl.telephone, p.statut_paiement,
                (SELECT l.statut_livraison
                   FROM livraisons l
                  WHERE l.id_commande = c.id_commande
                  ORDER BY l.id_livraison DESC
                  LIMIT 1) AS statut_livraison
           FROM commandes c
           JOIN clients cl ON cl.id_client = c.id_client
           LEFT JOIN paiements p ON p.id_commande = c.id_commande
          WHERE c.numero_commande = :numero
          FOR UPDATE'
    );
    $statement->execute(['numero' => $orderNumber]);
    $order = $statement->fetch();

    if (!$order || $order['telephone'] !== $phone) {
        throw new RuntimeException('Les informations de commande ne correspondent pas.');
    }
    if ($order['statut_paiement'] !== 'REUSSI') {
        throw new RuntimeException('Le paiement doit être confirmé avant la confirmation de réception.');
    }
    $isDeliveryReady = $order['statut_courant'] === 'EN_COURS_LIVRAISON'
        && $order['statut_livraison'] === 'LIVREE';
    $isCollectionReady = $order['statut_courant'] === 'TRAITEE' && $order['mode_retrait'] === 'RETRAIT_BOUTIQUE';
    if (!$isDeliveryReady && !$isCollectionReady) {
        throw new RuntimeException('Cette commande n’est pas encore prête à être confirmée.');
    }

    changeOrderStatus($pdo, (int) $order['id_commande'], 'LIVREE', 'CLIENT', null, 'Réception confirmée par le client.');
    $managerStatement = $pdo->query(
        "SELECT u.id_utilisateur
           FROM utilisateurs u
           JOIN roles r ON r.id_role = u.id_role
          WHERE u.est_actif = 1
            AND r.est_actif = 1
            AND r.code_role IN ('GERANT', 'ADMINISTRATEUR')"
    );
    foreach ($managerStatement->fetchAll() as $manager) {
        createNotification(
            $pdo,
            (int) $order['id_commande'],
            'CHANGEMENT_STATUT',
            sprintf('Le client a confirmé la réception de la commande %s.', $order['numero_commande']),
            null,
            (int) $manager['id_utilisateur']
        );
    }
    recordAuditEvent(
        $pdo,
        'CONFIRMATION_RECEPTION_CLIENT',
        'COMMANDE',
        (int) $order['id_commande'],
        ['statut' => $order['statut_courant']],
        ['statut' => 'LIVREE', 'confirmation_reception' => true]
    );
    $pdo->commit();
    grantClientOrderAccess((int) $order['id_client'], (string) $order['numero_commande']);
    flash('success', 'Merci, la réception de votre commande a été confirmée.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'La confirmation ne peut pas être enregistrée pour le moment.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('suivi-commande.php?numero=' . rawurlencode($orderNumber));
