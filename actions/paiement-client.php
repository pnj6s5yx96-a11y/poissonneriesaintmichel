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
    $paymentPhone = postString('telephone_paiement', 30);
    if ($paymentPhone === '') {
        throw new RuntimeException('Le numéro de commande et le téléphone de paiement sont obligatoires.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'SELECT p.id_paiement, p.id_commande, p.mode_paiement, p.statut_paiement, p.montant,
                c.id_client, c.numero_commande, cl.telephone
           FROM paiements p
           JOIN commandes c ON c.id_commande = p.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
          WHERE c.numero_commande = :numero
          FOR UPDATE'
    );
    $statement->execute(['numero' => $orderNumber]);
    $payment = $statement->fetch();

    if (!$payment || !clientCanAccessOrder((int) $payment['id_client'], (string) $payment['numero_commande'])) {
        throw new RuntimeException('Cette demande de paiement n’est plus accessible.');
    }
    if ($payment['telephone'] !== $paymentPhone) {
        throw new RuntimeException('Le téléphone de paiement ne correspond pas à cette commande.');
    }
    if ($payment['mode_paiement'] === 'ESPECES') {
        throw new RuntimeException('Cette commande est réglée en espèces.');
    }
    if ($payment['statut_paiement'] === 'REUSSI') {
        $pdo->commit();
        redirect('facture.php?commande=' . rawurlencode((string) $payment['numero_commande']));
    }
    if ($payment['statut_paiement'] !== 'EN_ATTENTE') {
        throw new RuntimeException('Ce paiement ne peut plus être confirmé.');
    }

    $paymentId = (int) $payment['id_paiement'];
    $reference = sprintf('DEMO-%s-%06d', date('Ymd'), $paymentId);
    $invoiceNumber = sprintf('FAC-%s-%06d', date('Ymd'), $paymentId);
    $invoicePath = 'facture.php?commande=' . rawurlencode((string) $payment['numero_commande']);

    $pdo->prepare(
        "UPDATE paiements
            SET statut_paiement = 'REUSSI', reference_transaction = :reference, date_confirmation = NOW()
          WHERE id_paiement = :paiement"
    )->execute(['reference' => $reference, 'paiement' => $paymentId]);
    $pdo->prepare(
        "INSERT INTO justificatifs_paiement (id_paiement, type_document, numero_document, chemin_document)
         VALUES (:paiement, 'FACTURE', :numero, :chemin)"
    )->execute(['paiement' => $paymentId, 'numero' => $invoiceNumber, 'chemin' => $invoicePath]);
    createNotification(
        $pdo,
        (int) $payment['id_commande'],
        'PAIEMENT',
        sprintf('Le paiement de votre commande %s a été confirmé. Facture : %s.', $payment['numero_commande'], $invoiceNumber),
        (int) $payment['id_client']
    );
    recordAuditEvent(
        $pdo,
        'CONFIRMATION_PAIEMENT_MOBILE_DEMO',
        'PAIEMENT',
        $paymentId,
        ['statut' => 'EN_ATTENTE'],
        ['statut' => 'REUSSI', 'reference' => $reference, 'facture' => $invoiceNumber]
    );
    $pdo->commit();

    flash('success', 'Paiement confirmé. Votre facture est disponible.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Le paiement n’a pas pu être confirmé. Réessayez dans quelques instants.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('paiement-commande.php?commande=' . rawurlencode($orderNumber));
