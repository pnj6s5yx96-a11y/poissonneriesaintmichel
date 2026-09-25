<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/google_merchant.php';
require_once __DIR__ . '/config/database.php';

$productId = queryPositiveInt('produit');
$product = null;
$databaseUnavailable = false;

if ($productId !== null) {
    try {
        $statement = db()->prepare(
            'SELECT p.id_produit, p.libelle, p.description, p.prix_unitaire, p.unite_vente,
                    p.quantite_stock, p.photo_url, c.libelle AS categorie
               FROM produits p
               JOIN categories c ON c.id_categorie = p.id_categorie
              WHERE p.id_produit = :id
                AND p.est_actif = 1
                AND c.est_active = 1'
        );
        $statement->execute(['id' => $productId]);
        $product = $statement->fetch() ?: null;
    } catch (Throwable $exception) {
        $databaseUnavailable = true;
    }
}

if ($product === null) {
    http_response_code($databaseUnavailable ? 503 : 404);
    $pageTitle = $databaseUnavailable ? 'Catalogue temporairement indisponible' : 'Produit introuvable';
    $metaDescription = 'Découvrez les produits de la Poissonnerie Saint-Michel à Cotonou.';
    $seoIndexable = false;
    $activePage = 'catalogue';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="error-page section">
      <p class="eyebrow">Catalogue</p>
      <h1><?= $databaseUnavailable ? 'Le catalogue est temporairement indisponible.' : 'Ce produit n’est plus disponible.' ?></h1>
      <p><?= $databaseUnavailable ? 'Réessayez dans quelques instants.' : 'Consultez le catalogue pour découvrir les produits actuellement proposés.' ?></p>
      <a class="button button-yellow" href="<?= e(url('catalogue.php')) ?>">Voir le catalogue</a>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$title = merchantProductTitle($product);
$description = merchantProductDescription($product);
$productUrl = merchantProductUrl($product);
$imageUrl = merchantProductImageUrl((string) $product['photo_url']);
$pageTitle = $title . ' | Poissonnerie Saint-Michel';
$metaDescription = mb_strimwidth($description, 0, 160, '…');
$seoIndexable = true;
$seoCanonicalPath = 'produit.php?produit=' . (int) $product['id_produit'];
$seoImagePath = $imageUrl !== null ? (string) $product['photo_url'] : 'assets/images/hero-market.jpg';
$seoStructuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $title,
    'sku' => merchantProductId($product),
    'description' => $description,
    'category' => (string) $product['categorie'],
    'image' => $imageUrl ?? publicUrl('assets/images/hero-market.jpg'),
    'offers' => [
        '@type' => 'Offer',
        'url' => $productUrl,
        'priceCurrency' => 'XOF',
        'price' => number_format((float) $product['prix_unitaire'], 2, '.', ''),
        'availability' => 'https://schema.org/' . (merchantProductAvailability($product) === 'in_stock' ? 'InStock' : 'OutOfStock'),
        'itemCondition' => 'https://schema.org/NewCondition',
        'seller' => [
            '@type' => 'Store',
            'name' => 'Poissonnerie Saint-Michel',
        ],
    ],
];
$activePage = 'catalogue';
require __DIR__ . '/includes/header.php';
?>
<section class="product-detail section">
  <div class="product-detail__media">
    <?php if ($imageUrl !== null): ?>
      <img src="<?= e(url((string) $product['photo_url'])) ?>" alt="<?= e($product['libelle'] . ' — ' . $product['categorie']) ?>">
    <?php else: ?>
      <span aria-hidden="true">~&lt;°)))&gt;</span>
    <?php endif; ?>
  </div>
  <div class="product-detail__content">
    <p class="breadcrumbs"><a href="<?= e(url()) ?>">Accueil</a> / <a href="<?= e(url('catalogue.php')) ?>">Catalogue</a> / <?= e($product['categorie']) ?></p>
    <p class="eyebrow"><?= e($product['categorie']) ?></p>
    <h1><?= e($product['libelle']) ?></h1>
    <p class="product-detail__description"><?= e($description) ?></p>
    <p class="product-detail__price"><?= number_format((float) $product['prix_unitaire'], 0, ',', ' ') ?> FCFA <small>/ <?= e(mb_strtolower((string) $product['unite_vente'])) ?></small></p>
    <?php if ((float) $product['quantite_stock'] > 0): ?>
      <p class="product-detail__availability">En stock · <?= number_format((float) $product['quantite_stock'], 3, ',', ' ') ?> <?= e(mb_strtolower((string) $product['unite_vente'])) ?> disponible<?= (float) $product['quantite_stock'] > 1 ? 's' : '' ?></p>
      <form class="product-detail__add" method="post" action="<?= e(url('actions/panier.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id_produit" value="<?= (int) $product['id_produit'] ?>">
        <label for="quantite">Quantité</label>
        <input id="quantite" name="quantite" type="number" min="0.001" max="<?= e((string) $product['quantite_stock']) ?>" step="0.001" value="1" inputmode="decimal" required>
        <button class="button button-yellow" type="submit">Ajouter au panier</button>
      </form>
    <?php else: ?>
      <p class="product-unavailable">Indisponible pour le moment.</p>
    <?php endif; ?>
    <a class="text-link" href="<?= e(url('catalogue.php')) ?>">Retour au catalogue <span aria-hidden="true">→</span></a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
