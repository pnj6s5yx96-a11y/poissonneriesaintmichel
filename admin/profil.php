<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

$profile = ['nom_complet' => '', 'email' => '', 'telephone' => ''];

try {
    $statement = db()->prepare(
        'SELECT nom_complet, email, telephone
         FROM utilisateurs
         WHERE id_utilisateur = :id AND est_actif = 1'
    );
    $statement->execute(['id' => currentUser()['id']]);
    $profile = $statement->fetch() ?: $profile;
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger votre profil.');
}

$pageTitle = 'Mon profil — Administration';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('admin/index.php')) ?>">Administration</a> / Mon profil</p><p class="eyebrow">Compte administrateur</p><h1>Vos informations, sous votre contrôle.</h1><p>Modifiez vos coordonnées et changez votre mot de passe sans exposer les autres comptes administrateur.</p></section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Informations de connexion</h2><p>Le téléphone ou l’e-mail peut être utilisé pour vous connecter.</p></div></div>
  <form method="post" action="<?= e(url('actions/profil.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <div class="form-grid">
      <div class="field"><label for="nom_complet">Nom complet</label><input id="nom_complet" name="nom_complet" maxlength="150" value="<?= e((string) $profile['nom_complet']) ?>" required></div>
      <div class="field"><label for="telephone">Téléphone</label><input id="telephone" name="telephone" maxlength="30" inputmode="tel" value="<?= e((string) $profile['telephone']) ?>" required></div>
      <div class="field full"><label for="email">E-mail <small>facultatif</small></label><input id="email" name="email" type="email" maxlength="191" value="<?= e((string) ($profile['email'] ?? '')) ?>"></div>
    </div>

    <div class="settings-divider"><div><h2>Changer le mot de passe</h2><p>Laissez ces champs vides pour conserver votre mot de passe actuel.</p></div></div>
    <div class="form-grid">
      <div class="field"><label for="mot_de_passe_actuel">Mot de passe actuel</label><input id="mot_de_passe_actuel" name="mot_de_passe_actuel" type="password" autocomplete="current-password"></div>
      <div class="field"><label for="nouveau_mot_de_passe">Nouveau mot de passe <small>12 caractères, 3 types de caractères minimum</small></label><input id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" type="password" minlength="12" autocomplete="new-password"></div>
      <div class="field full"><label for="confirmation_mot_de_passe">Confirmer le nouveau mot de passe</label><input id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" type="password" minlength="12" autocomplete="new-password"></div>
    </div>
    <div class="form-actions"><button class="button button-yellow" type="submit">Enregistrer mon profil</button></div>
  </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
