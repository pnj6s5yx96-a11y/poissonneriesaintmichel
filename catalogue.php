<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$categoryId = queryPositiveInt('categorie');
$search = queryString('q', 100);
$pageTitle = 'Poissons, viandes et produits congelés à Cotonou | Saint-Michel';
$metaDescription = 'Découvrez le catalogue Saint-Michel à Cotonou : poissons, viandes, œufs et produits congelés, avec prix et disponibilités. Commande en ligne, retrait ou livraison.';
$seoIndexable = $categoryId === null && $search === '';
$seoCanonicalPath = 'catalogue.php';
$activePage = 'catalogue';
$categories = [];
$produits = [];
$databaseError = null;

try {
    $categories = db()->query(
        'SELECT c.id_categorie, c.libelle, COUNT(p.id_produit) AS total_produits
         FROM categories c
         LEFT JOIN produits p ON p.id_categorie = c.id_categorie AND p.est_actif = 1
         WHERE c.est_active = 1
         GROUP BY c.id_categorie, c.libelle
         ORDER BY c.libelle'
    )->fetchAll();

    $sql = 'SELECT p.id_produit, p.libelle, p.description, p.prix_unitaire, p.unite_vente, p.quantite_stock, p.photo_url, c.libelle AS categorie
            FROM produits p
            JOIN categories c ON c.id_categorie = p.id_categorie
            WHERE p.est_actif = 1 AND c.est_active = 1';
    $params = [];

    if ($categoryId !== null) {
        $sql .= ' AND p.id_categorie = :categorie';
        $params['categorie'] = $categoryId;
    }

    if ($search !== '') {
        $sql .= ' AND (p.libelle LIKE :recherche OR p.description LIKE :recherche)';
        $params['recherche'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY c.libelle, p.libelle';
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $produits = $statement->fetchAll();
} catch (Throwable $exception) {
    $databaseError = 'Le catalogue sera disponible dès que la base de données aura été importée et configurée.';
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
  <p class="breadcrumbs"><a href="<?= e(url()) ?>">Accueil</a> / Catalogue</p>
  <p class="eyebrow">Produits congelés</p>
  <h1>Poissons, viandes et produits congelés à Cotonou.</h1>
  <p>Une sélection organisée par catégorie, avec le prix, l’unité de vente et les disponibilités clairement indiqués.</p>
</section>

<?php if ($databaseError !== null): ?>
  <div class="notice section"><?= e($databaseError) ?></div>
<?php else: ?>
  <section class="catalogue-layout">
    <aside class="filters panel">
      <div class="panel-heading"><div><h2>Catégories</h2><p>Affinez votre recherche</p></div></div>
      <ul class="filter-list">
        <li><a class="<?= $categoryId === null ? 'is-active' : '' ?>" href="<?= e(url('catalogue.php')) ?><?= $search !== '' ? '?q=' . urlencode($search) : '' ?>"><span>Tout le catalogue</span><em><?= array_sum(array_column($categories, 'total_produits')) ?></em></a></li>
        <?php foreach ($categories as $category): ?>
          <?php $categoryUrl = 'catalogue.php?categorie=' . (int) $category['id_categorie'] . ($search !== '' ? '&q=' . urlencode($search) : ''); ?>
          <li><a class="<?= $categoryId === (int) $category['id_categorie'] ? 'is-active' : '' ?>" href="<?= e(url($categoryUrl)) ?>"><span><?= e($category['libelle']) ?></span><em><?= (int) $category['total_produits'] ?></em></a></li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <div>
      <div class="catalogue-toolbar">
        <p><strong><?= count($produits) ?></strong> produit<?= count($produits) > 1 ? 's' : '' ?> disponible<?= count($produits) > 1 ? 's' : '' ?></p>
        <form class="search-form" method="get" action="<?= e(url('catalogue.php')) ?>">
          <?php if ($categoryId !== null): ?><input type="hidden" name="categorie" value="<?= $categoryId ?>"><?php endif; ?>
          <input name="q" type="search" value="<?= e($search) ?>" placeholder="Rechercher un produit">
          <button class="button button-small" type="submit">Chercher</button>
        </form>
      </div>

      <?php if ($produits === []): ?>
        <div class="empty-state"><strong>Aucun produit trouvé</strong><span>Essayez une autre catégorie ou modifiez votre recherche.</span></div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($produits as $produit): ?>
            <article class="product-card">
              <div class="product-image">
                <?php if (!empty($produit['photo_url'])): ?>
                  <img src="<?= e(url($produit['photo_url'])) ?>" alt="<?= e($produit['libelle'] . ' — ' . $produit['categorie']) ?>">
                <?php else: ?>
                  <span aria-hidden="true">~&lt;°)))&gt;</span>
                <?php endif; ?>
              </div>
              <div class="product-body">
                <span class="product-category"><?= e($produit['categorie']) ?></span>
                <h3><?= e($produit['libelle']) ?></h3>
                <p><?= e($produit['description'] ?: 'Produit congelé sélectionné par la Poissonnerie Saint-Michel.') ?></p>
                <div class="price"><?= number_format((float) $produit['prix_unitaire'], 0, ',', ' ') ?> FCFA <small>/ <?= e(strtolower($produit['unite_vente'])) ?></small></div>
                <?php if ((float) $produit['quantite_stock'] > 0): ?>
                  <form class="product-add-form" method="post" action="<?= e(url('actions/panier.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id_produit" value="<?= (int) $produit['id_produit'] ?>">
                    <label class="sr-only" for="quantite-<?= (int) $produit['id_produit'] ?>">Quantité de <?= e($produit['libelle']) ?></label>
                    <input id="quantite-<?= (int) $produit['id_produit'] ?>" name="quantite" type="number" min="0.001" max="<?= e((string) $produit['quantite_stock']) ?>" step="0.001" value="1" inputmode="decimal" required>
                    <button class="button button-small" type="submit">Ajouter</button>
                  </form>
                  <small class="product-stock">En stock : <?= number_format((float) $produit['quantite_stock'], 3, ',', ' ') ?> <?= e(strtolower($produit['unite_vente'])) ?></small>
                <?php else: ?>
                  <p class="product-unavailable">Indisponible pour le moment</p>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
