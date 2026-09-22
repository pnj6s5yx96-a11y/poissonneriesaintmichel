<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/database.php';

try {
    $hasUsers = (int) db()->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn() > 0;
} catch (Throwable $exception) {
    $hasUsers = false;
    $setupError = 'Importez la base de données et configurez config/database.local.php avant de créer le premier compte.';
}

if ($hasUsers) {
    flash('warning', 'Un compte interne existe déjà. Connectez-vous pour gérer les utilisateurs.');
    redirect('auth/connexion.php');
}

if (isPost() && !isset($setupError)) {
    verifyCsrfToken();
    $nom = postString('nom_complet', 150);
    $email = postString('email', 191);
    $telephone = postString('telephone', 30);
    $password = postPassword();

    if ($nom === '' || $telephone === '' || $password === '') {
        flash('error', 'Renseignez le nom, le téléphone et un mot de passe robuste.');
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'L’adresse e-mail n’est pas valide.');
    } else {
        try {
            assertStrongPassword($password);
            $pdo = db();
            $pdo->beginTransaction();
            $existingUserId = $pdo->query('SELECT id_utilisateur FROM utilisateurs LIMIT 1 FOR UPDATE')->fetchColumn();
            if ($existingUserId !== false) {
                throw new RuntimeException('Un compte interne existe déjà.');
            }

            $roleId = (int) $pdo->query("SELECT id_role FROM roles WHERE code_role = 'ADMINISTRATEUR' LIMIT 1")->fetchColumn();

            if ($roleId === 0) {
                throw new RuntimeException('Le rôle ADMINISTRATEUR est absent. Réimportez le script SQL.');
            }

            $pdo->prepare(
                'INSERT INTO utilisateurs (id_role, nom_complet, email, telephone, mot_de_passe_hash)
                 VALUES (:role, :nom, :email, :telephone, :hash)'
            )->execute([
                'role' => $roleId,
                'nom' => $nom,
                'email' => $email !== '' ? $email : null,
                'telephone' => $telephone,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $pdo->commit();

            flash('success', 'Compte administrateur créé. Connectez-vous maintenant.');
            redirect('auth/connexion.php');
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Création impossible. L’e-mail ou le téléphone est peut-être déjà utilisé.');
        }
    }
}

$pageTitle = 'Premier administrateur — Poissonnerie Saint-Michel';
$metaDescription = 'Configuration interne sécurisée de Poissonnerie Saint-Michel.';
$seoIndexable = false;
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
  <div class="auth-intro"><p class="eyebrow">Installation sécurisée</p><h1>Créez le premier accès administrateur.</h1><p>Cette page ne fonctionne que tant qu’aucun compte interne n’existe dans la base.</p></div>
  <div class="auth-form">
    <h2>Compte initial</h2>
    <p>Conservez ces identifiants en lieu sûr.</p>
    <?php if (isset($setupError)): ?><p class="notice"><?= e($setupError) ?></p><?php else: ?>
      <form method="post" action="<?= e(url('setup-admin.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="form-grid">
          <div class="field full"><label for="nom_complet">Nom complet</label><input id="nom_complet" name="nom_complet" required></div>
          <div class="field"><label for="telephone">Téléphone</label><input id="telephone" name="telephone" inputmode="tel" required></div>
          <div class="field"><label for="email">E-mail <small>facultatif</small></label><input id="email" name="email" type="email"></div>
          <div class="field full"><label for="mot_de_passe">Mot de passe <small>12 caractères, 3 types de caractères minimum</small></label><input id="mot_de_passe" name="mot_de_passe" type="password" minlength="12" autocomplete="new-password" required></div>
        </div>
        <div class="form-actions"><button class="button button-yellow" type="submit">Créer l’administrateur</button></div>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
