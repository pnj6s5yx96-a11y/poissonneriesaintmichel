<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

if (!isPost()) {
    redirect('admin/utilisateurs.php');
}

verifyCsrfToken();

try {
    $action = postEnum('action', ['create', 'update', 'toggle']);
    $pdo = db();

    if ($action === 'toggle') {
        $id = postPositiveInt('id_utilisateur');
        if ($id <= 0 || $id === (int) currentUser()['id']) {
            throw new RuntimeException('Vous ne pouvez pas modifier votre propre accès depuis cette action.');
        }

        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'SELECT u.id_utilisateur, u.nom_complet, u.email, u.telephone, u.est_actif, r.code_role
             FROM utilisateurs u
             JOIN roles r ON r.id_role = u.id_role
             WHERE u.id_utilisateur = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $id]);
        $before = $statement->fetch();

        if (!$before || $before['code_role'] === 'ADMINISTRATEUR') {
            throw new RuntimeException('Les comptes administrateur sont protégés depuis cette interface.');
        }

        $nextState = (int) $before['est_actif'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE utilisateurs SET est_actif = :etat WHERE id_utilisateur = :id')
            ->execute(['etat' => $nextState, 'id' => $id]);
        $after = $before;
        $after['est_actif'] = $nextState;
        recordAuditEvent($pdo, $nextState === 1 ? 'ACTIVATION_UTILISATEUR' : 'DESACTIVATION_UTILISATEUR', 'UTILISATEUR', $id, $before, $after);
        $pdo->commit();
        flash('success', 'État du compte mis à jour.');
        redirect('admin/utilisateurs.php');
    }

    if (!in_array($action, ['create', 'update'], true)) {
        throw new RuntimeException('Action utilisateur inconnue.');
    }

    $name = postString('nom_complet', 150);
    $phone = postString('telephone', 30);
    $email = postString('email', 191);
    $roleId = postPositiveInt('id_role');
    $password = postPassword();
    $id = postOptionalPositiveInt('id_utilisateur') ?? 0;

    if ($name === '' || $phone === '' || $roleId <= 0) {
        throw new RuntimeException('Le nom, le téléphone et le rôle sont obligatoires.');
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('L’adresse e-mail n’est pas valide.');
    }

    if ($action === 'create') {
        assertStrongPassword($password);
    }

    if ($action === 'update' && $id <= 0) {
        throw new RuntimeException('Utilisateur introuvable.');
    }

    $roleStatement = $pdo->prepare(
        "SELECT code_role FROM roles
         WHERE id_role = :role AND est_actif = 1 AND code_role IN ('GERANT', 'LIVREUR')"
    );
    $roleStatement->execute(['role' => $roleId]);
    if (!$roleStatement->fetchColumn()) {
        throw new RuntimeException('Seuls les rôles Gérant et Livreur peuvent être gérés depuis cette interface.');
    }

    $pdo->beginTransaction();
    if ($action === 'create') {
        $pdo->prepare(
            'INSERT INTO utilisateurs (id_role, nom_complet, email, telephone, mot_de_passe_hash)
             VALUES (:role, :nom, :email, :telephone, :hash)'
        )->execute([
            'role' => $roleId,
            'nom' => $name,
            'email' => $email !== '' ? $email : null,
            'telephone' => $phone,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $id = (int) $pdo->lastInsertId();
        $after = ['id_utilisateur' => $id, 'id_role' => $roleId, 'nom_complet' => $name, 'email' => $email !== '' ? $email : null, 'telephone' => $phone, 'est_actif' => 1];
        recordAuditEvent($pdo, 'CREATION_UTILISATEUR', 'UTILISATEUR', $id, null, $after);
        $pdo->commit();
        flash('success', 'Le compte interne a été créé.');
    } else {
        $statement = $pdo->prepare(
            'SELECT u.id_utilisateur, u.id_role, u.nom_complet, u.email, u.telephone, u.est_actif, r.code_role
             FROM utilisateurs u
             JOIN roles r ON r.id_role = u.id_role
             WHERE u.id_utilisateur = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $id]);
        $before = $statement->fetch();

        if (!$before || $before['code_role'] === 'ADMINISTRATEUR') {
            throw new RuntimeException('Ce compte ne peut pas être modifié depuis cette interface.');
        }

        $query = 'UPDATE utilisateurs SET id_role = :role, nom_complet = :nom, email = :email, telephone = :telephone';
        $params = ['role' => $roleId, 'nom' => $name, 'email' => $email !== '' ? $email : null, 'telephone' => $phone, 'id' => $id];
        if ($password !== '') {
            assertStrongPassword($password);
            $query .= ', mot_de_passe_hash = :hash';
            $params['hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $query .= ' WHERE id_utilisateur = :id';
        $pdo->prepare($query)->execute($params);
        $after = ['id_utilisateur' => $id, 'id_role' => $roleId, 'nom_complet' => $name, 'email' => $email !== '' ? $email : null, 'telephone' => $phone, 'est_actif' => (int) $before['est_actif'], 'mot_de_passe_modifie' => $password !== ''];
        recordAuditEvent($pdo, 'MODIFICATION_UTILISATEUR', 'UTILISATEUR', $id, $before, $after);
        $pdo->commit();
        flash('success', 'Le compte a été modifié.');
    }
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'L’e-mail ou le téléphone est déjà utilisé par un autre compte.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('admin/utilisateurs.php');
