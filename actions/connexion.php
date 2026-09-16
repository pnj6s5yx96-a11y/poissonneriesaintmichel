<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

if (!isPost()) {
    redirect('auth/connexion.php');
}

verifyCsrfToken();

try {
    $identifiant = postString('identifiant', 191);
    $motDePasse = postPassword();
    $isEmail = filter_var($identifiant, FILTER_VALIDATE_EMAIL) !== false;
    $isPhone = preg_match('/^[0-9+(). -]{6,30}$/', $identifiant) === 1;
    if ($identifiant === '' || $motDePasse === '' || (!$isEmail && !$isPhone)) {
        throw new RuntimeException('Identifiants incorrects ou compte désactivé.');
    }
    if ($isEmail) {
        $identifiant = mb_strtolower($identifiant);
    }

    $pdo = db();
    enforceRateLimit($pdo, 'connexion_equipe_ip', '', 10, 900);
    enforceRateLimit($pdo, 'connexion_equipe_identifiant', $identifiant, 5, 900);
    $statement = db()->prepare(
        'SELECT u.id_utilisateur, u.nom_complet, u.mot_de_passe_hash, r.code_role
         FROM utilisateurs u
         JOIN roles r ON r.id_role = u.id_role
         WHERE u.est_actif = 1
           AND r.est_actif = 1
           AND (u.email = :identifiant_email OR u.telephone = :identifiant_telephone)
         LIMIT 1'
    );
    $statement->execute([
        'identifiant_email' => $identifiant,
        'identifiant_telephone' => $identifiant,
    ]);
    $user = $statement->fetch();

    $hash = (string) ($user['mot_de_passe_hash'] ?? '$2y$12$kHWGqoltJ10foYO3zm6JZe.j.ywbnxuKbz4ktPV3X944c93Di8I7O');
    if (!$user || !password_verify($motDePasse, $hash)) {
        flash('error', 'Identifiants incorrects ou compte désactivé.');
        redirect('auth/connexion.php');
    }

    $updates = ['dernier_acces = NOW()'];
    $parameters = ['id' => $user['id_utilisateur']];
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $updates[] = 'mot_de_passe_hash = :hash';
        $parameters['hash'] = password_hash($motDePasse, PASSWORD_DEFAULT);
    }
    $pdo->prepare('UPDATE utilisateurs SET ' . implode(', ', $updates) . ' WHERE id_utilisateur = :id')
        ->execute($parameters);

    loginUser($user);
    clearRateLimit($pdo, 'connexion_equipe_identifiant', $identifiant);
    flash('success', 'Bienvenue ' . $user['nom_complet'] . ' !');
    redirect(dashboardPathForRole((string) $user['code_role']));
} catch (SecurityRateLimitException $exception) {
    flash('error', $exception->getMessage());
    redirect('auth/connexion.php');
} catch (Throwable $exception) {
    flash('error', 'Connexion impossible pour le moment. Réessayez dans quelques instants.');
    redirect('auth/connexion.php');
}
