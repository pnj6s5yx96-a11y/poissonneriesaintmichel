<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR', 'LIVREUR']);

$role = (string) currentUser()['role'];
$redirectPath = $role === 'LIVREUR' ? 'livreur/livraisons.php' : 'gerant/livraisons.php';
if (!isPost()) {
    redirect($redirectPath);
}

verifyCsrfToken();

/** @param array<string, mixed> $delivery */
function notifyDeliveryManagers(PDO $pdo, array $delivery, string $message): void
{
    $managerIds = [(int) $delivery['id_affectant']];
    $managerStatement = $pdo->query(
        "SELECT u.id_utilisateur
           FROM utilisateurs u
           JOIN roles r ON r.id_role = u.id_role
          WHERE u.est_actif = 1
            AND r.est_actif = 1
            AND r.code_role IN ('GERANT', 'ADMINISTRATEUR')"
    );
    foreach ($managerStatement->fetchAll() as $manager) {
        $managerIds[] = (int) $manager['id_utilisateur'];
    }

    foreach (array_unique($managerIds) as $managerId) {
        createNotification($pdo, (int) $delivery['id_commande'], 'AFFECTATION_LIVRAISON', $message, null, $managerId);
    }
}

try {
    $action = postEnum('action', ['assign', 'accept', 'refuse', 'start', 'complete', 'cancel']);
    $pdo = db();

    if ($action === 'assign') {
        if (!in_array($role, ['GERANT', 'ADMINISTRATEUR'], true)) {
            throw new RuntimeException('Seul un gérant ou un administrateur peut affecter une livraison.');
        }

        $orderId = postPositiveInt('id_commande');
        $courierId = postPositiveInt('id_livreur');
        $address = postString('adresse_livraison', 500);
        if ($address === '') {
            throw new RuntimeException('La commande, le livreur et l’adresse de livraison sont obligatoires.');
        }

        $pdo->beginTransaction();
        $orderStatement = $pdo->prepare(
            "SELECT c.id_commande, c.id_client, c.numero_commande, c.statut_courant, c.mode_retrait,
                    cl.telephone, p.statut_paiement
               FROM commandes c
               JOIN clients cl ON cl.id_client = c.id_client
               JOIN paiements p ON p.id_commande = c.id_commande
              WHERE c.id_commande = :commande
              FOR UPDATE"
        );
        $orderStatement->execute(['commande' => $orderId]);
        $order = $orderStatement->fetch();
        if (!$order || $order['mode_retrait'] !== 'LIVRAISON' || $order['statut_courant'] !== 'TRAITEE') {
            throw new RuntimeException('Seule une commande traitée, prévue en livraison, peut être affectée.');
        }
        if ($order['statut_paiement'] !== 'REUSSI') {
            throw new RuntimeException('Le paiement doit être confirmé avant l’affectation au livreur.');
        }

        $existingStatement = $pdo->prepare(
            "SELECT COUNT(*) FROM livraisons
              WHERE id_commande = :commande
                AND statut_livraison IN ('AFFECTEE', 'ACCEPTEE', 'EN_COURS')"
        );
        $existingStatement->execute(['commande' => $orderId]);
        if ((int) $existingStatement->fetchColumn() > 0) {
            throw new RuntimeException('Cette commande est déjà affectée à un livreur.');
        }

        $courierStatement = $pdo->prepare(
            "SELECT u.id_utilisateur, u.nom_complet
               FROM utilisateurs u
               JOIN roles r ON r.id_role = u.id_role
              WHERE u.id_utilisateur = :livreur
                AND u.est_actif = 1 AND r.est_actif = 1 AND r.code_role = 'LIVREUR'
              FOR UPDATE"
        );
        $courierStatement->execute(['livreur' => $courierId]);
        $courier = $courierStatement->fetch();
        if (!$courier) {
            throw new RuntimeException('Le livreur sélectionné est introuvable ou inactif.');
        }

        $pdo->prepare('UPDATE commandes SET adresse_livraison = :adresse WHERE id_commande = :commande')
            ->execute(['adresse' => $address, 'commande' => $orderId]);
        $pdo->prepare(
            "INSERT INTO livraisons (
                id_commande, id_livreur, id_affectant, adresse_livraison, contact_livraison, statut_livraison
             ) VALUES (:commande, :livreur, :affectant, :adresse, :contact, 'AFFECTEE')"
        )->execute([
            'commande' => $orderId,
            'livreur' => $courierId,
            'affectant' => (int) currentUser()['id'],
            'adresse' => $address,
            'contact' => $order['telephone'],
        ]);
        $deliveryId = (int) $pdo->lastInsertId();
        createNotification(
            $pdo,
            $orderId,
            'AFFECTATION_LIVRAISON',
            sprintf('Nouvelle livraison affectée : commande %s, client %s.', $order['numero_commande'], $order['telephone']),
            null,
            $courierId
        );
        recordAuditEvent(
            $pdo,
            'AFFECTATION_LIVRAISON',
            'LIVRAISON',
            $deliveryId,
            null,
            ['commande' => $orderId, 'livreur' => $courierId]
        );
        $pdo->commit();
        flash('success', 'La livraison a été affectée à ' . $courier['nom_complet'] . '.');
    } elseif (in_array($action, ['accept', 'refuse', 'start', 'complete', 'cancel'], true)) {
        if ($role !== 'LIVREUR') {
            throw new RuntimeException('Cette action est réservée au livreur concerné.');
        }

        $deliveryId = postPositiveInt('id_livraison');
        $reason = postString('motif', 500);
        if (in_array($action, ['refuse', 'cancel'], true) && $reason === '') {
            throw new RuntimeException('Les informations de la livraison sont invalides.');
        }

        $pdo->beginTransaction();
        $deliveryStatement = $pdo->prepare(
            "SELECT l.id_livraison, l.id_commande, l.id_affectant, l.statut_livraison,
                    c.id_client, c.numero_commande, c.statut_courant,
                    cl.nom_complet AS client, cl.telephone
               FROM livraisons l
               JOIN commandes c ON c.id_commande = l.id_commande
               JOIN clients cl ON cl.id_client = c.id_client
              WHERE l.id_livraison = :livraison
                AND l.id_livreur = :livreur
              FOR UPDATE"
        );
        $deliveryStatement->execute(['livraison' => $deliveryId, 'livreur' => (int) currentUser()['id']]);
        $delivery = $deliveryStatement->fetch();
        if (!$delivery) {
            throw new RuntimeException('Cette livraison est introuvable ou ne vous est pas affectée.');
        }

        $currentStatus = (string) $delivery['statut_livraison'];
        if ($action === 'accept') {
            if ($currentStatus !== 'AFFECTEE' || $delivery['statut_courant'] !== 'TRAITEE') {
                throw new RuntimeException('Cette livraison n’est plus disponible pour acceptation.');
            }
            $pdo->prepare(
                "UPDATE livraisons
                    SET statut_livraison = 'ACCEPTEE', date_acceptation = NOW()
                  WHERE id_livraison = :livraison"
            )->execute(['livraison' => $deliveryId]);
            changeOrderStatus($pdo, (int) $delivery['id_commande'], 'EN_COURS_LIVRAISON', 'LIVREUR', (int) currentUser()['id'], 'Livraison acceptée par le livreur.');
            createNotification($pdo, (int) $delivery['id_commande'], 'CHANGEMENT_STATUT', sprintf('Votre commande %s est prise en charge par le livreur.', $delivery['numero_commande']), (int) $delivery['id_client']);
            notifyDeliveryManagers($pdo, $delivery, sprintf('Le livreur a accepté la commande %s.', $delivery['numero_commande']));
            recordAuditEvent($pdo, 'ACCEPTATION_LIVRAISON', 'LIVRAISON', $deliveryId, ['statut' => 'AFFECTEE'], ['statut' => 'ACCEPTEE']);
            flash('success', 'Livraison acceptée. Vous pouvez signaler votre départ dès que vous partez.');
        } elseif ($action === 'refuse') {
            if ($currentStatus !== 'AFFECTEE') {
                throw new RuntimeException('Seule une mission non acceptée peut être refusée.');
            }
            $pdo->prepare(
                "UPDATE livraisons
                    SET statut_livraison = 'REFUSEE', motif_refus_annulation = :motif
                  WHERE id_livraison = :livraison"
            )->execute(['motif' => $reason, 'livraison' => $deliveryId]);
            notifyDeliveryManagers($pdo, $delivery, sprintf('La livraison de la commande %s doit être réaffectée : %s', $delivery['numero_commande'], $reason));
            recordAuditEvent($pdo, 'REFUS_LIVRAISON', 'LIVRAISON', $deliveryId, ['statut' => 'AFFECTEE'], ['statut' => 'REFUSEE', 'motif' => $reason]);
            flash('success', 'Le refus a été enregistré. La commande peut maintenant être réaffectée.');
        } elseif ($action === 'start') {
            if ($currentStatus !== 'ACCEPTEE' || $delivery['statut_courant'] !== 'EN_COURS_LIVRAISON') {
                throw new RuntimeException('Cette livraison n’est pas prête pour le départ.');
            }
            $pdo->prepare(
                "UPDATE livraisons
                    SET statut_livraison = 'EN_COURS', date_depart = NOW()
                  WHERE id_livraison = :livraison"
            )->execute(['livraison' => $deliveryId]);
            createNotification($pdo, (int) $delivery['id_commande'], 'CHANGEMENT_STATUT', sprintf('Le livreur est en route pour votre commande %s.', $delivery['numero_commande']), (int) $delivery['id_client']);
            notifyDeliveryManagers($pdo, $delivery, sprintf('Le livreur est parti pour la commande %s.', $delivery['numero_commande']));
            recordAuditEvent($pdo, 'DEPART_LIVRAISON', 'LIVRAISON', $deliveryId, ['statut' => 'ACCEPTEE'], ['statut' => 'EN_COURS']);
            flash('success', 'Départ enregistré. Bonne route.');
        } elseif ($action === 'complete') {
            if ($currentStatus !== 'EN_COURS' || $delivery['statut_courant'] !== 'EN_COURS_LIVRAISON') {
                throw new RuntimeException('Cette livraison ne peut pas encore être déclarée effectuée.');
            }
            $pdo->prepare(
                "UPDATE livraisons
                    SET statut_livraison = 'LIVREE', date_livraison = NOW()
                  WHERE id_livraison = :livraison"
            )->execute(['livraison' => $deliveryId]);
            createNotification($pdo, (int) $delivery['id_commande'], 'CHANGEMENT_STATUT', sprintf('Votre commande %s a été livrée. Confirmez sa réception depuis le suivi de commande.', $delivery['numero_commande']), (int) $delivery['id_client']);
            notifyDeliveryManagers($pdo, $delivery, sprintf('La livraison de la commande %s est déclarée effectuée. En attente de confirmation du client.', $delivery['numero_commande']));
            recordAuditEvent($pdo, 'LIVRAISON_EFFECTUEE', 'LIVRAISON', $deliveryId, ['statut' => 'EN_COURS'], ['statut' => 'LIVREE']);
            flash('success', 'Livraison déclarée effectuée. Le client peut maintenant confirmer la réception.');
        } else {
            if ($currentStatus !== 'ACCEPTEE' || $delivery['statut_courant'] !== 'EN_COURS_LIVRAISON') {
                throw new RuntimeException('Seule une livraison acceptée et non partie peut être annulée.');
            }
            $pdo->prepare(
                "UPDATE livraisons
                    SET statut_livraison = 'ANNULEE', motif_refus_annulation = :motif
                  WHERE id_livraison = :livraison"
            )->execute(['motif' => $reason, 'livraison' => $deliveryId]);
            changeOrderStatus($pdo, (int) $delivery['id_commande'], 'TRAITEE', 'LIVREUR', (int) currentUser()['id'], 'Livraison annulée : ' . $reason);
            createNotification($pdo, (int) $delivery['id_commande'], 'CHANGEMENT_STATUT', sprintf('La livraison de votre commande %s est en cours de réorganisation.', $delivery['numero_commande']), (int) $delivery['id_client']);
            notifyDeliveryManagers($pdo, $delivery, sprintf('La livraison de la commande %s a été annulée : %s', $delivery['numero_commande'], $reason));
            recordAuditEvent($pdo, 'ANNULATION_LIVRAISON', 'LIVRAISON', $deliveryId, ['statut' => 'ACCEPTEE'], ['statut' => 'ANNULEE', 'motif' => $reason]);
            flash('success', 'La mission a été annulée et la commande peut être réaffectée.');
        }

        $pdo->commit();
    } else {
        throw new RuntimeException('Action de livraison inconnue.');
    }
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'La livraison ne peut pas être mise à jour dans son état actuel.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect($redirectPath);
