<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$editing = null;

try {
    $categories = db()->query('SELECT id_categorie, libelle FROM categories WHERE est_active = 1 ORDER BY libelle')->fetchAll();
    $products = db()->query(
        'SELECT p.*, c.libelle AS categorie FROM produits p JOIN categories c ON c.id_categorie = p.id_categorie ORDER BY p.created_at DESC'
    )->fetchAll();

    if ($editId !== null) {
        $statement = db()->prepare('SELECT * FROM produits WHERE id_produit = :id');
        $statement->execute(['id' => $editId]);
        $editing = $statement->fetch() ?: null;
    }
} catch (Throwable $exception) {
    $categories = [];
    $products = [];
    flash('error', 'Impossible de charger les produits.');
}

$pageTitle = 'Produits — Administration';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('admin/index.php')) ?>">Administration</a> / Produits</p><p class="eyebrow">Module 2 · Catalogue</p><h1>Présentez vos meilleurs produits.</h1><p>Ajoutez le prix, l’unité de vente, le seuil d’alerte et une photo pour enrichir le catalogue client.</p></section>

<section class="section panel">
  <div class="panel-heading"><div><h2><?= $editing ? 'Modifier le produit' : 'Ajouter un produit' ?></h2><p>Le stock actuel est géré depuis l’espace gérant.</p></div><?php if ($editing): ?><a class="button button-soft button-small" href="<?= e(url('admin/produits.php')) ?>">Annuler</a><?php endif; ?></div>
  <?php if ($categories === []): ?><p class="notice">Créez d’abord au moins une catégorie active.</p><?php else: ?>
  <form method="post" action="<?= e(url('actions/produit.php')) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>"><?php if ($editing): ?><input type="hidden" name="id_produit" value="<?= (int) $editing['id_produit'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="field"><label for="libelle">Nom du produit</label><input id="libelle" name="libelle" value="<?= e($editing['libelle'] ?? '') ?>" required></div>
      <div class="field"><label for="id_categorie">Catégorie</label><select id="id_categorie" name="id_categorie" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id_categorie'] ?>" <?= (int) ($editing['id_categorie'] ?? 0) === (int) $category['id_categorie'] ? 'selected' : '' ?>><?= e($category['libelle']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="prix_unitaire">Prix unitaire (FCFA)</label><input id="prix_unitaire" name="prix_unitaire" type="number" min="0" step="1" value="<?= e(isset($editing['prix_unitaire']) ? (string) $editing['prix_unitaire'] : '') ?>" required></div>
      <div class="field"><label for="unite_vente">Unité de vente</label><select id="unite_vente" name="unite_vente" required><?php foreach (['KG', 'CARTON', 'ALVEOLE', 'UNITE', 'PAQUET', 'AUTRE'] as $unit): ?><option value="<?= $unit ?>" <?= ($editing['unite_vente'] ?? '') === $unit ? 'selected' : '' ?>><?= e($unit) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="seuil_alerte">Seuil d’alerte</label><input id="seuil_alerte" name="seuil_alerte" type="number" min="0" step="0.001" value="<?= e(isset($editing['seuil_alerte']) ? (string) $editing['seuil_alerte'] : '0') ?>" required></div>
      <div class="field"><label for="photo">Photo <small>JPG, PNG ou WEBP · 5 Mo max</small></label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"></div>
      <div class="field full"><label for="description">Description <small>facultative</small></label><textarea id="description" name="description" placeholder="Description courte à afficher aux clients"><?= e($editing['description'] ?? '') ?></textarea></div>
    </div>
    <div class="form-actions"><button class="button button-yellow" type="submit"><?= $editing ? 'Enregistrer les modifications' : 'Ajouter le produit' ?></button></div>
  </form><?php endif; ?>
</section>

<section class="section panel"><div class="panel-heading"><div><h2>Produits du catalogue</h2><p><?= count($products) ?> produit<?= count($products) > 1 ? 's' : '' ?></p></div></div>
  <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>État</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($products as $product): ?><tr><td><?php if ($product['photo_url']): ?><img class="photo-preview" src="<?= e(url($product['photo_url'])) ?>" alt=""><?php else: ?><span class="table-product-image">~&lt;°</span><?php endif; ?><strong><?= e($product['libelle']) ?></strong></td><td><?= e($product['categorie']) ?></td><td><?= number_format((float) $product['prix_unitaire'], 0, ',', ' ') ?> FCFA<br><small>/ <?= e($product['unite_vente']) ?></small></td><td><?= number_format((float) $product['quantite_stock'], 3, ',', ' ') ?><br><small>seuil <?= number_format((float) $product['seuil_alerte'], 3, ',', ' ') ?></small></td><td><span class="badge <?= $product['est_actif'] ? '' : 'badge-muted' ?>"><?= $product['est_actif'] ? 'Visible' : 'Masqué' ?></span></td><td class="actions"><a class="button button-soft button-small" href="<?= e(url('admin/produits.php?edit=' . (int) $product['id_produit'])) ?>">Modifier</a><form class="inline-form" method="post" action="<?= e(url('actions/produit.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id_produit" value="<?= (int) $product['id_produit'] ?>"><button class="button button-small <?= $product['est_actif'] ? 'button-danger' : 'button-soft' ?>" type="submit"><?= $product['est_actif'] ? 'Masquer' : 'Publier' ?></button></form><form class="inline-form" method="post" action="<?= e(url('actions/produit.php')) ?>" data-confirm-delete><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_produit" value="<?= (int) $product['id_produit'] ?>"><button class="button button-danger button-small" type="submit">Supprimer</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
