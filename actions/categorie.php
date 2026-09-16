<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

if (!isPost()) {
    redirect('admin/categories.php');
}

verifyCsrfToken();

try {
    $action = postEnum('action', ['create', 'update', 'toggle', 'delete']);
    $id = postOptionalPositiveInt('id_categorie') ?? 0;
    $pdo = db();

    if ($action === 'toggle') {
        if ($id <= 0) {
            throw new RuntimeException('Catégorie introuvable.');
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'SELECT id_categorie, libelle, description, est_active
             FROM categories
             WHERE id_categorie = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $id]);
        $before = $statement->fetch();
        if (!$before) {
            throw new RuntimeException('Catégorie introuvable.');
        }

        $nextState = (int) $before['est_active'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE categories SET est_active = :etat WHERE id_categorie = :id')
            ->execute(['etat' => $nextState, 'id' => $id]);
        $after = $before;
        $after['est_active'] = $nextState;
        recordAuditEvent($pdo, $nextState === 1 ? 'ACTIVATION_CATEGORIE' : 'DESACTIVATION_CATEGORIE', 'PARAMETRAGE', $id, $before, $after);
        $pdo->commit();
        flash('success', 'État de la catégorie mis à jour.');
    } elseif ($action === 'delete') {
        if ($id <= 0) {
            throw new RuntimeException('Catégorie introuvable.');
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'SELECT id_categorie, libelle, description, est_active
             FROM categories
             WHERE id_categorie = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $id]);
        $before = $statement->fetch();
        if (!$before) {
            throw new RuntimeException('Catégorie introuvable.');
        }

        $references = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE id_categorie = :id');
        $references->execute(['id' => $id]);
        if ((int) $references->fetchColumn() > 0) {
            throw new RuntimeException('Cette catégorie contient encore des produits. Désactivez-la ou déplacez les produits avant suppression.');
        }

        $pdo->prepare('DELETE FROM categories WHERE id_categorie = :id')->execute(['id' => $id]);
        recordAuditEvent($pdo, 'SUPPRESSION_CATEGORIE', 'PARAMETRAGE', $id, $before, null);
        $pdo->commit();
        flash('success', 'La catégorie a été supprimée.');
    } elseif ($action === 'create' || $action === 'update') {
        $label = postString('libelle', 100);
        $description = postString('description', 500);
        if ($label === '') {
            throw new RuntimeException('Le libellé est obligatoire.');
        }

        $pdo->beginTransaction();
        if ($action === 'create') {
            $pdo->prepare('INSERT INTO categories (libelle, description) VALUES (:libelle, :description)')
                ->execute(['libelle' => $label, 'description' => $description !== '' ? $description : null]);
            $id = (int) $pdo->lastInsertId();
            $after = ['id_categorie' => $id, 'libelle' => $label, 'description' => $description !== '' ? $description : null, 'est_active' => 1];
            recordAuditEvent($pdo, 'CREATION_CATEGORIE', 'PARAMETRAGE', $id, null, $after);
            $pdo->commit();
            flash('success', 'La catégorie a été ajoutée.');
        } else {
            if ($id <= 0) {
                throw new RuntimeException('Catégorie introuvable.');
            }
            $statement = $pdo->prepare(
                'SELECT id_categorie, libelle, description, est_active
                 FROM categories
                 WHERE id_categorie = :id
                 FOR UPDATE'
            );
            $statement->execute(['id' => $id]);
            $before = $statement->fetch();
            if (!$before) {
                throw new RuntimeException('Catégorie introuvable.');
            }

            $pdo->prepare('UPDATE categories SET libelle = :libelle, description = :description WHERE id_categorie = :id')
                ->execute(['libelle' => $label, 'description' => $description !== '' ? $description : null, 'id' => $id]);
            $after = ['id_categorie' => $id, 'libelle' => $label, 'description' => $description !== '' ? $description : null, 'est_active' => (int) $before['est_active']];
            recordAuditEvent($pdo, 'MODIFICATION_CATEGORIE', 'PARAMETRAGE', $id, $before, $after);
            $pdo->commit();
            flash('success', 'La catégorie a été modifiée.');
        }
    } else {
        throw new RuntimeException('Action de catégorie inconnue.');
    }
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Cette catégorie existe déjà ou ne peut pas être enregistrée.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('admin/categories.php');
