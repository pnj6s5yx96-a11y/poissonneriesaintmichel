<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$editing = null;

try {
    $categories = db()->query(
        'SELECT c.id_categorie, c.libelle, c.description, c.est_active, COUNT(p.id_produit) AS total_produits
         FROM categories c LEFT JOIN produits p ON p.id_categorie = c.id_categorie
         GROUP BY c.id_categorie, c.libelle, c.description, c.est_active
         ORDER BY c.libelle'
    )->fetchAll();

    if ($editId !== null) {
        $statement = db()->prepare('SELECT id_categorie, libelle, description FROM categories WHERE id_categorie = :id');
        $statement->execute(['id' => $editId]);
        $editing = $statement->fetch() ?: null;
    }
} catch (Throwable $exception) {
    $categories = [];
    flash('error', 'Impossible de charger les catégories.');
}

$pageTitle = 'Catégories — Administration';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('admin/index.php')) ?>">Administration</a> / Catégories</p><p class="eyebrow">Module 2 · Catalogue</p><h1>Structurez votre vitrine.</h1><p>Les catégories organisent les produits et facilitent la navigation des clients.</p></section>

<section class="section panel">
  <div class="panel-heading"><div><h2><?= $editing ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h2><p>Une catégorie inactive disparaît du catalogue sans supprimer les produits associés.</p></div><?php if ($editing): ?><a class="button button-soft button-small" href="<?= e(url('admin/categories.php')) ?>">Annuler</a><?php endif; ?></div>
  <form method="post" action="<?= e(url('actions/categorie.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>"><?php if ($editing): ?><input type="hidden" name="id_categorie" value="<?= (int) $editing['id_categorie'] ?>"><?php endif; ?>
    <div class="form-grid"><div class="field"><label for="libelle">Libellé</label><input id="libelle" name="libelle" value="<?= e($editing['libelle'] ?? '') ?>" placeholder="Ex. Poissons" required></div><div class="field"><label for="description">Description <small>facultative</small></label><input id="description" name="description" value="<?= e($editing['description'] ?? '') ?>" placeholder="Courte description de la catégorie"></div></div>
    <div class="form-actions"><button class="button button-yellow" type="submit"><?= $editing ? 'Enregistrer' : 'Ajouter la catégorie' ?></button></div>
  </form>
</section>

<section class="section panel"><div class="panel-heading"><div><h2>Catégories existantes</h2><p><?= count($categories) ?> catégorie<?= count($categories) > 1 ? 's' : '' ?></p></div></div>
  <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Catégorie</th><th>Description</th><th>Produits</th><th>État</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($categories as $category): ?><tr><td><strong><?= e($category['libelle']) ?></strong></td><td><?= e($category['description'] ?: '—') ?></td><td><?= (int) $category['total_produits'] ?></td><td><span class="badge <?= $category['est_active'] ? '' : 'badge-muted' ?>"><?= $category['est_active'] ? 'Active' : 'Inactive' ?></span></td><td class="actions"><a class="button button-soft button-small" href="<?= e(url('admin/categories.php?edit=' . (int) $category['id_categorie'])) ?>">Modifier</a><form class="inline-form" method="post" action="<?= e(url('actions/categorie.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id_categorie" value="<?= (int) $category['id_categorie'] ?>"><button class="button button-small <?= $category['est_active'] ? 'button-danger' : 'button-soft' ?>" type="submit"><?= $category['est_active'] ? 'Désactiver' : 'Activer' ?></button></form><?php if ((int) $category['total_produits'] === 0): ?><form class="inline-form" method="post" action="<?= e(url('actions/categorie.php')) ?>" data-confirm-delete><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_categorie" value="<?= (int) $category['id_categorie'] ?>"><button class="button button-danger button-small" type="submit">Supprimer</button></form><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
