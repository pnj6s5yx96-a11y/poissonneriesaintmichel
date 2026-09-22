<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (isAuthenticated()) {
    redirect(dashboardPathForRole((string) currentUser()['role']));
}

$pageTitle = 'Connexion équipe — Poissonnerie Saint-Michel';
$metaDescription = 'Accès sécurisé réservé à l’équipe de la Poissonnerie Saint-Michel.';
$seoIndexable = false;
$activePage = 'connexion';
require __DIR__ . '/../includes/header.php';
?>
<section class="auth-layout">
  <div class="auth-intro">
    <p class="auth-intro__brand"><img src="<?= e(url('assets/images/logo-saint-michel-mark.jpg')) ?>" width="29" height="29" alt=""> Poissonnerie Saint-Michel</p>
    <p class="eyebrow">Espace sécurisé</p>
    <h1>Votre journée commence ici.</h1>
    <p>Accédez aux outils adaptés à votre rôle : gestion du catalogue, suivi des commandes, stock ou livraisons.</p>
    <div class="auth-points">
      <span><i>✓</i> Accès selon vos permissions</span>
      <span><i>✓</i> Suivi des actions importantes</span>
      <span><i>✓</i> Interface pensée pour le mobile</span>
    </div>
  </div>
    <div class="auth-form">
    <p class="auth-form__kicker">Bienvenue</p>
    <h2>Connexion équipe</h2>
    <p>Utilisez votre adresse e-mail ou votre numéro de téléphone.</p>
    <form method="post" action="<?= e(url('actions/connexion.php')) ?>" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <div class="field">
        <label for="identifiant">E-mail ou téléphone</label>
        <input id="identifiant" name="identifiant" type="text" autocomplete="username" required autofocus>
      </div>
      <div class="field field-auth-spaced">
        <label for="mot_de_passe">Mot de passe</label>
        <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" required>
      </div>
      <div class="auth-form__actions"><a href="<?= e(url('auth/mot-de-passe-oublie.php')) ?>">Mot de passe oublié ?</a><button class="button button-yellow" type="submit">Se connecter <span aria-hidden="true">→</span></button></div>
    </form>
    <p class="form-note">Premier accès ? <a href="<?= e(url('setup-admin.php')) ?>">Créer le premier compte administrateur</a>.</p>
    <p class="auth-form__security"><span aria-hidden="true">✓</span> Connexion chiffrée et accès limité selon votre rôle.</p>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
