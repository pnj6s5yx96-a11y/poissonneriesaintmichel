<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$editing = null;

try {
    $roles = db()->query(
        "SELECT id_role, code_role, libelle
         FROM roles
         WHERE est_actif = 1 AND code_role IN ('GERANT', 'LIVREUR')
         ORDER BY id_role"
    )->fetchAll();
    $users = db()->query(
        'SELECT u.id_utilisateur, u.nom_complet, u.email, u.telephone, u.est_actif, u.dernier_acces, r.code_role, r.libelle AS role_libelle
         FROM utilisateurs u JOIN roles r ON r.id_role = u.id_role
         ORDER BY u.created_at DESC'
    )->fetchAll();

    if ($editId !== null) {
        $statement = db()->prepare(
            "SELECT u.id_utilisateur, u.id_role, u.nom_complet, u.email, u.telephone
             FROM utilisateurs u
             JOIN roles r ON r.id_role = u.id_role
             WHERE u.id_utilisateur = :id AND r.code_role IN ('GERANT', 'LIVREUR')"
        );
        $statement->execute(['id' => $editId]);
        $editing = $statement->fetch() ?: null;
    }
} catch (Throwable $exception) {
    $roles = [];
    $users = [];
    flash('error', 'Impossible de charger les utilisateurs. Vérifiez la base de données.');
}

$pageTitle = 'Utilisateurs — Administration';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('admin/index.php')) ?>">Administration</a> / Utilisateurs</p><p class="eyebrow">Module 1 · accès interne</p><h1>Une équipe, des droits précis.</h1><p>Créez les comptes gérant et livreur, attribuez leur rôle puis activez ou désactivez leur accès.</p></section>

<section class="section panel">
  <div class="panel-heading"><div><h2><?= $editing ? 'Modifier le compte' : 'Ajouter un compte interne' ?></h2><p>Les mots de passe sont enregistrés de manière chiffrée.</p></div><?php if ($editing): ?><a class="button button-soft button-small" href="<?= e(url('admin/utilisateurs.php')) ?>">Annuler</a><?php endif; ?></div>
  <form method="post" action="<?= e(url('actions/utilisateur.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?><input type="hidden" name="id_utilisateur" value="<?= (int) $editing['id_utilisateur'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="field"><label for="nom_complet">Nom complet</label><input id="nom_complet" name="nom_complet" value="<?= e($editing['nom_complet'] ?? '') ?>" required></div>
      <div class="field"><label for="id_role">Rôle</label><select id="id_role" name="id_role" required><?php foreach ($roles as $role): ?><option value="<?= (int) $role['id_role'] ?>" <?= (int) ($editing['id_role'] ?? 0) === (int) $role['id_role'] ? 'selected' : '' ?>><?= e($role['libelle']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="telephone">Téléphone</label><input id="telephone" name="telephone" value="<?= e($editing['telephone'] ?? '') ?>" inputmode="tel" required></div>
      <div class="field"><label for="email">E-mail <small>facultatif</small></label><input id="email" name="email" type="email" value="<?= e($editing['email'] ?? '') ?>"></div>
      <div class="field full"><label for="mot_de_passe">Mot de passe <?= $editing ? '<small>(laisser vide pour le conserver)</small>' : '<small>12 caractères, 3 types de caractères minimum</small>' ?></label><input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="new-password" <?= $editing ? '' : 'minlength="12" required' ?>></div>
    </div>
    <div class="form-actions"><button class="button button-yellow" type="submit"><?= $editing ? 'Enregistrer les modifications' : 'Créer le compte' ?></button></div>
  </form>
</section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Comptes internes</h2><p><?= count($users) ?> compte<?= count($users) > 1 ? 's' : '' ?> enregistré<?= count($users) > 1 ? 's' : '' ?></p></div></div>
  <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Utilisateur</th><th>Rôle</th><th>Contact</th><th>Dernier accès</th><th>État</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?>
      <tr><td><strong><?= e($user['nom_complet']) ?></strong><br><small>#<?= (int) $user['id_utilisateur'] ?></small></td><td><span class="badge"><?= e($user['role_libelle']) ?></span></td><td><?= e($user['telephone']) ?><br><small><?= e($user['email'] ?: '—') ?></small></td><td><?= $user['dernier_acces'] ? e(date('d/m/Y H:i', strtotime($user['dernier_acces']))) : 'Jamais' ?></td><td><span class="badge <?= $user['est_actif'] ? '' : 'badge-muted' ?>"><i class="status-dot"></i><?= $user['est_actif'] ? 'Actif' : 'Désactivé' ?></span></td><td class="actions"><?php if ($user['code_role'] === 'ADMINISTRATEUR'): ?><?php if ((int) $user['id_utilisateur'] === (int) currentUser()['id']): ?><a class="button button-soft button-small" href="<?= e(url('admin/profil.php')) ?>">Mon profil</a><?php else: ?><span class="table-note">Compte administrateur protégé</span><?php endif; ?><?php else: ?><a class="button button-soft button-small" href="<?= e(url('admin/utilisateurs.php?edit=' . (int) $user['id_utilisateur'])) ?>">Modifier</a><form class="inline-form" method="post" action="<?= e(url('actions/utilisateur.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id_utilisateur" value="<?= (int) $user['id_utilisateur'] ?>"><button class="button button-small <?= $user['est_actif'] ? 'button-danger' : 'button-soft' ?>" type="submit"><?= $user['est_actif'] ? 'Désactiver' : 'Activer' ?></button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
