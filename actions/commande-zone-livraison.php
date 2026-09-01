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
    $zoneId = postPositiveInt('id_zone_livraison');
    $quarterId = postPositiveInt('id_quartier_livraison');

    $pdo = db();
    $pdo->beginTransaction();
    $orderStatement = $pdo->prepare(
        'SELECT c.id_commande, c.id_client, c.numero_commande, c.mode_retrait,
                c.id_zone_livraison, c.id_quartier_livraison, c.frais_livraison, c.total_produits, c.montant_total,
                p.id_paiement, p.statut_paiement, p.montant AS montant_paiement
           FROM commandes c
           JOIN paiements p ON p.id_commande = c.id_commande
          WHERE c.id_commande = :commande
          FOR UPDATE'
    );
    $orderStatement->execute(['commande' => $orderId]);
    $order = $orderStatement->fetch();
    if (!$order || $order['mode_retrait'] !== 'LIVRAISON') {
        throw new RuntimeException('Cette commande n’est pas une commande à livrer.');
    }
    if ($order['statut_paiement'] !== 'EN_ATTENTE') {
        throw new RuntimeException('La zone ne peut plus être modifiée après la confirmation du paiement.');
    }

    $zoneStatement = $pdo->prepare(
        'SELECT id_zone_livraison, libelle, frais_livraison
           FROM zones_livraison
          WHERE id_zone_livraison = :zone
            AND est_active = 1
          FOR UPDATE'
    );
    $zoneStatement->execute(['zone' => $zoneId]);
    $zone = $zoneStatement->fetch();
    if (!$zone) {
        throw new RuntimeException('La zone de livraison sélectionnée n’est plus disponible.');
    }
    $quarterStatement = $pdo->prepare(
        'SELECT id_quartier_livraison, libelle
           FROM quartiers_livraison
          WHERE id_quartier_livraison = :quartier
            AND id_zone_livraison = :zone
            AND est_actif = 1
          FOR UPDATE'
    );
    $quarterStatement->execute(['quartier' => $quarterId, 'zone' => $zoneId]);
    $quarter = $quarterStatement->fetch();
    if (!$quarter) {
        throw new RuntimeException('Le quartier sélectionné ne correspond pas à la zone de livraison.');
    }

    $total = round((float) $order['total_produits'] + (float) $zone['frais_livraison'], 2);
    $pdo->prepare(
        'UPDATE commandes
            SET id_zone_livraison = :zone,
                id_quartier_livraison = :quartier,
                frais_livraison = :frais,
                montant_total = :total
          WHERE id_commande = :commande'
    )->execute([
        'zone' => (int) $zone['id_zone_livraison'],
        'quartier' => (int) $quarter['id_quartier_livraison'],
        'frais' => $zone['frais_livraison'],
        'total' => $total,
        'commande' => $orderId,
    ]);
    $pdo->prepare('UPDATE paiements SET montant = :montant WHERE id_paiement = :paiement')
        ->execute(['montant' => $total, 'paiement' => (int) $order['id_paiement']]);
    createNotification(
        $pdo,
        $orderId,
        'CHANGEMENT_STATUT',
        sprintf(
            'La zone et le quartier de livraison de votre commande %s ont été ajustés à « %s - %s ». Nouveau total : %s.',
            $order['numero_commande'],
            $zone['libelle'],
            $quarter['libelle'],
            moneyFcfa($total)
        ),
        (int) $order['id_client']
    );
    recordAuditEvent(
        $pdo,
        'CORRECTION_ZONE_LIVRAISON',
        'COMMANDE',
        $orderId,
        [
            'id_zone_livraison' => $order['id_zone_livraison'],
            'id_quartier_livraison' => $order['id_quartier_livraison'],
            'frais_livraison' => $order['frais_livraison'],
            'montant_total' => $order['montant_total'],
        ],
        [
            'id_zone_livraison' => $zone['id_zone_livraison'],
            'zone' => $zone['libelle'],
            'id_quartier_livraison' => $quarter['id_quartier_livraison'],
            'quartier' => $quarter['libelle'],
            'frais_livraison' => $zone['frais_livraison'],
            'montant_total' => $total,
        ]
    );
    $pdo->commit();
    flash('success', 'Zone et quartier corrigés : le coût de livraison et le total de la commande ont été mis à jour.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'La zone de livraison n’a pas pu être mise à jour.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('gerant/commandes.php');
