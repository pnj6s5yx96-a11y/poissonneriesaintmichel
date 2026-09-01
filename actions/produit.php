<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

if (!isPost()) {
    redirect('admin/produits.php');
}

verifyCsrfToken();

$auditSnapshot = static function (array $product): array {
    return [
        'id_produit' => (int) $product['id_produit'],
        'id_categorie' => (int) $product['id_categorie'],
        'libelle' => $product['libelle'],
        'description' => $product['description'],
        'prix_unitaire' => $product['prix_unitaire'],
        'unite_vente' => $product['unite_vente'],
        'quantite_stock' => $product['quantite_stock'],
        'seuil_alerte' => $product['seuil_alerte'],
        'photo_url' => $product['photo_url'],
        'est_actif' => (int) $product['est_actif'],
    ];
};

try {
    $action = postEnum('action', ['create', 'update', 'toggle', 'delete']);
    $id = postOptionalPositiveInt('id_produit') ?? 0;
    $pdo = db();

    if ($action === 'toggle') {
        if ($id <= 0) {
            throw new RuntimeException('Produit introuvable.');
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT * FROM produits WHERE id_produit = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();
        if (!$product) {
            throw new RuntimeException('Produit introuvable.');
        }

        $before = $auditSnapshot($product);
        $nextState = (int) $product['est_actif'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE produits SET est_actif = :etat WHERE id_produit = :id')
            ->execute(['etat' => $nextState, 'id' => $id]);
        $after = $before;
        $after['est_actif'] = $nextState;
        recordAuditEvent($pdo, $nextState === 1 ? 'PUBLICATION_PRODUIT' : 'MASQUAGE_PRODUIT', 'PRODUIT', $id, $before, $after);
        $pdo->commit();
        flash('success', 'Visibilité du produit mise à jour.');
        redirect('admin/produits.php');
    }

    if ($action === 'delete') {
        if ($id <= 0) {
            throw new RuntimeException('Produit introuvable.');
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT * FROM produits WHERE id_produit = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();
        if (!$product) {
            throw new RuntimeException('Produit introuvable.');
        }

        $references = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM lignes_commande WHERE id_produit = :id_lignes)
                + (SELECT COUNT(*) FROM mouvements_stock WHERE id_produit = :id_mouvements) AS total'
        );
        $references->execute(['id_lignes' => $id, 'id_mouvements' => $id]);
        if ((int) $references->fetchColumn() > 0) {
            throw new RuntimeException('Ce produit possède déjà un historique. Masquez-le plutôt que de le supprimer.');
        }

        $before = $auditSnapshot($product);
        $pdo->prepare('DELETE FROM produits WHERE id_produit = :id')->execute(['id' => $id]);
        recordAuditEvent($pdo, 'SUPPRESSION_PRODUIT', 'PRODUIT', $id, $before, null);
        $pdo->commit();
        deleteProductImage($product['photo_url'] ?? null);
        flash('success', 'Le produit a été supprimé.');
        redirect('admin/produits.php');
    }

    if (!in_array($action, ['create', 'update'], true)) {
        throw new RuntimeException('Action de produit inconnue.');
    }

    $label = postString('libelle', 150);
    $description = postString('description', 5000);
    $categoryId = postPositiveInt('id_categorie');
    $price = postDecimal('prix_unitaire', 0, 9_999_999_999.99, 2);
    $threshold = postDecimal('seuil_alerte', 0, 9_999_999_999.999, 3);
    $unit = postString('unite_vente', 20);
    $allowedUnits = ['KG', 'CARTON', 'ALVEOLE', 'UNITE', 'PAQUET', 'AUTRE'];

    if ($label === '' || !in_array($unit, $allowedUnits, true)) {
        throw new RuntimeException('Renseignez des informations produit valides.');
    }

    $newPhoto = uploadProductImage($_FILES['photo'] ?? []);
    $pdo->beginTransaction();

    if ($action === 'create') {
        $pdo->prepare(
            'INSERT INTO produits (id_categorie, libelle, description, prix_unitaire, unite_vente, seuil_alerte, photo_url)
             VALUES (:categorie, :libelle, :description, :prix, :unite, :seuil, :photo)'
        )->execute([
            'categorie' => $categoryId,
            'libelle' => $label,
            'description' => $description !== '' ? $description : null,
            'prix' => $price,
            'unite' => $unit,
            'seuil' => $threshold,
            'photo' => $newPhoto,
        ]);
        $id = (int) $pdo->lastInsertId();
        $statement = $pdo->prepare('SELECT * FROM produits WHERE id_produit = :id');
        $statement->execute(['id' => $id]);
        $createdProduct = $statement->fetch();
        if (!$createdProduct) {
            throw new RuntimeException('Produit introuvable après sa création.');
        }
        $after = $auditSnapshot($createdProduct);
        recordAuditEvent($pdo, 'CREATION_PRODUIT', 'PRODUIT', $id, null, $after);
        $pdo->commit();
        flash('success', 'Le produit a été ajouté au catalogue.');
    } else {
        if ($id <= 0) {
            throw new RuntimeException('Produit introuvable.');
        }
        $statement = $pdo->prepare('SELECT * FROM produits WHERE id_produit = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();
        if (!$product) {
            throw new RuntimeException('Produit introuvable.');
        }

        $before = $auditSnapshot($product);
        $photoPath = $newPhoto ?? $product['photo_url'];
        $pdo->prepare(
            'UPDATE produits
             SET id_categorie = :categorie, libelle = :libelle, description = :description,
                 prix_unitaire = :prix, unite_vente = :unite, seuil_alerte = :seuil, photo_url = :photo
             WHERE id_produit = :id'
        )->execute([
            'categorie' => $categoryId,
            'libelle' => $label,
            'description' => $description !== '' ? $description : null,
            'prix' => $price,
            'unite' => $unit,
            'seuil' => $threshold,
            'photo' => $photoPath,
            'id' => $id,
        ]);
        $statement = $pdo->prepare('SELECT * FROM produits WHERE id_produit = :id');
        $statement->execute(['id' => $id]);
        $updatedProduct = $statement->fetch();
        if (!$updatedProduct) {
            throw new RuntimeException('Produit introuvable après sa modification.');
        }
        $after = $auditSnapshot($updatedProduct);
        recordAuditEvent($pdo, 'MODIFICATION_PRODUIT', 'PRODUIT', $id, $before, $after);
        $pdo->commit();

        if ($newPhoto !== null) {
            deleteProductImage($product['photo_url'] ?? null);
        }
        flash('success', 'Le produit a été modifié.');
    }
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($newPhoto) && $newPhoto !== null) {
        deleteProductImage($newPhoto);
    }
    flash('error', 'Ce produit existe déjà dans cette catégorie ou les données sont incorrectes.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($newPhoto) && $newPhoto !== null) {
        deleteProductImage($newPhoto);
    }
    flash('error', $exception->getMessage());
}

redirect('admin/produits.php');
