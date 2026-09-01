<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

if (!isPost()) {
    redirect('gerant/stock.php');
}

verifyCsrfToken();

try {
    $productId = postPositiveInt('id_produit');
    $quantity = postDecimal('quantite', 0.001, 999_999.999, 3);
    $type = postEnum('type_mouvement', ['ENTREE', 'AJUSTEMENT_POSITIF', 'AJUSTEMENT_NEGATIF']);
    $motif = postString('motif', 255);
    $reference = postString('reference_source', 120);

    if ($motif === '') {
        throw new RuntimeException('Les informations du mouvement sont invalides.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'INSERT INTO mouvements_stock (
          id_produit, id_ligne_commande, id_utilisateur, type_mouvement, quantite,
          stock_avant, stock_apres, motif, reference_source
        ) VALUES (:produit, NULL, :utilisateur, :type, :quantite, 0, 0, :motif, :reference)'
    );
    $statement->execute([
        'produit' => $productId,
        'utilisateur' => currentUser()['id'],
        'type' => $type,
        'quantite' => $quantity,
        'motif' => $motif,
        'reference' => $reference !== '' ? $reference : null,
    ]);

    $movementId = (int) $pdo->lastInsertId();
    $auditStatement = $pdo->prepare('SELECT * FROM mouvements_stock WHERE id_mouvement_stock = :id');
    $auditStatement->execute(['id' => $movementId]);
    recordAuditEvent($pdo, 'MOUVEMENT_STOCK', 'STOCK', $movementId, null, $auditStatement->fetch() ?: null);
    $pdo->commit();

    flash('success', 'Le mouvement de stock a été enregistré.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('gerant/stock.php');
