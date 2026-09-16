<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

if (!isPost()) {
    redirect('admin/profil.php');
}

verifyCsrfToken();

try {
    $name = postString('nom_complet', 150);
    $phone = postString('telephone', 30);
    $email = postString('email', 191);
    $currentPassword = postPassword('mot_de_passe_actuel');
    $newPassword = postPassword('nouveau_mot_de_passe');
    $confirmation = postPassword('confirmation_mot_de_passe');

    if ($name === '' || $phone === '') {
        throw new RuntimeException('Le nom et le téléphone sont obligatoires.');
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('L’adresse e-mail n’est pas valide.');
    }

    $changingPassword = $currentPassword !== '' || $newPassword !== '' || $confirmation !== '';
    if ($changingPassword && $newPassword !== $confirmation) {
        throw new RuntimeException('Le nouveau mot de passe et sa confirmation ne correspondent pas.');
    }
    if ($changingPassword) {
        assertStrongPassword($newPassword);
    }

    $pdo = db();
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'SELECT id_utilisateur, nom_complet, email, telephone, mot_de_passe_hash
         FROM utilisateurs
         WHERE id_utilisateur = :id
         FOR UPDATE'
    );
    $statement->execute(['id' => currentUser()['id']]);
    $stored = $statement->fetch();

    if (!$stored) {
        throw new RuntimeException('Votre compte est introuvable.');
    }

    if ($changingPassword && !password_verify($currentPassword, (string) $stored['mot_de_passe_hash'])) {
        throw new RuntimeException('Le mot de passe actuel est incorrect.');
    }

    $before = ['id_utilisateur' => (int) $stored['id_utilisateur'], 'nom_complet' => $stored['nom_complet'], 'email' => $stored['email'], 'telephone' => $stored['telephone']];
    $query = 'UPDATE utilisateurs SET nom_complet = :nom, email = :email, telephone = :telephone';
    $params = ['nom' => $name, 'email' => $email !== '' ? $email : null, 'telephone' => $phone, 'id' => (int) $stored['id_utilisateur']];

    if ($changingPassword) {
        $query .= ', mot_de_passe_hash = :hash';
        $params['hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $query .= ' WHERE id_utilisateur = :id';
    $pdo->prepare($query)->execute($params);
    $after = ['id_utilisateur' => (int) $stored['id_utilisateur'], 'nom_complet' => $name, 'email' => $email !== '' ? $email : null, 'telephone' => $phone, 'mot_de_passe_modifie' => $changingPassword];
    recordAuditEvent($pdo, 'MODIFICATION_PROFIL', 'UTILISATEUR', (int) $stored['id_utilisateur'], $before, $after);
    $pdo->commit();

    $_SESSION['utilisateur']['nom'] = $name;
    flash('success', 'Votre profil a été mis à jour.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Cet e-mail ou ce téléphone est déjà utilisé par un autre compte.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('admin/profil.php');
