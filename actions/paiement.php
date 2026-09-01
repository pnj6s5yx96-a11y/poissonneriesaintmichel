<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

if (!isPost()) {
    redirect('gerant/paiements.php');
}

verifyCsrfToken();

try {
    postEnum('action', ['confirm_cash']);
    $paymentId = postPositiveInt('id_paiement');

    $pdo = db();
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'SELECT p.id_paiement, p.id_commande, p.mode_paiement, p.statut_paiement, p.montant,
                c.id_client, c.numero_commande
           FROM paiements p
           JOIN commandes c ON c.id_commande = p.id_commande
          WHERE p.id_paiement = :paiement
          FOR UPDATE'
    );
    $statement->execute(['paiement' => $paymentId]);
    $payment = $statement->fetch();

    if (!$payment) {
        throw new RuntimeException('Paiement introuvable.');
    }
    if ($payment['mode_paiement'] !== 'ESPECES') {
        throw new RuntimeException('Seuls les paiements en espèces peuvent être confirmés au comptoir.');
    }
    if ($payment['statut_paiement'] !== 'EN_ATTENTE') {
        throw new RuntimeException('Ce paiement a déjà été traité.');
    }

    $reference = sprintf('ESP-%s-%06d', date('Ymd'), $paymentId);
    $receiptNumber = sprintf('REC-%s-%06d', date('Ymd'), $paymentId);
    $receiptPath = 'gerant/justificatif.php?paiement=' . $paymentId;

    $pdo->prepare(
        "UPDATE paiements
            SET statut_paiement = 'REUSSI', reference_transaction = :reference, date_confirmation = NOW()
          WHERE id_paiement = :paiement"
    )->execute(['reference' => $reference, 'paiement' => $paymentId]);
    $pdo->prepare(
        "INSERT INTO justificatifs_paiement (id_paiement, type_document, numero_document, chemin_document)
         VALUES (:paiement, 'RECU', :numero, :chemin)"
    )->execute(['paiement' => $paymentId, 'numero' => $receiptNumber, 'chemin' => $receiptPath]);
    createNotification(
        $pdo,
        (int) $payment['id_commande'],
        'PAIEMENT',
        sprintf('Le paiement en espèces de votre commande %s a été confirmé. Reçu : %s.', $payment['numero_commande'], $receiptNumber),
        (int) $payment['id_client']
    );
    recordAuditEvent(
        $pdo,
        'CONFIRMATION_PAIEMENT_ESPECES',
        'PAIEMENT',
        $paymentId,
        ['statut' => 'EN_ATTENTE'],
        ['statut' => 'REUSSI', 'reference' => $reference, 'recu' => $receiptNumber]
    );
    $pdo->commit();

    flash('success', 'Paiement en espèces confirmé. Le reçu est prêt à être imprimé.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'La confirmation du paiement a échoué. Rechargez la page puis réessayez.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('gerant/paiements.php');
