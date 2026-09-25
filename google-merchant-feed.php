<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/google_merchant.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-cache, must-revalidate');

try {
    $statement = db()->query(
        'SELECT p.id_produit, p.libelle, p.description, p.prix_unitaire, p.unite_vente,
                p.quantite_stock, p.photo_url, c.libelle AS categorie
           FROM produits p
           JOIN categories c ON c.id_categorie = p.id_categorie
          WHERE p.est_actif = 1
            AND c.est_active = 1
            AND p.photo_url IS NOT NULL
            AND TRIM(p.photo_url) <> \'\'
          ORDER BY c.libelle, p.libelle'
    );
    $products = array_values(array_filter(
        $statement->fetchAll(),
        static fn (array $product): bool => merchantProductIsReady($product)
    ));
} catch (Throwable $exception) {
    http_response_code(503);
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo '<error>Le flux produit est temporairement indisponible.</error>';
    exit;
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
  <channel>
    <title>Poissonnerie Saint-Michel — Catalogue</title>
    <link><?= merchantXml(publicUrl()) ?></link>
    <description>Produits actifs de la Poissonnerie Saint-Michel à Cotonou.</description>
<?php foreach ($products as $product): ?>
<?php
    $title = merchantProductTitle($product);
    $description = merchantProductDescription($product);
    $productUrl = merchantProductUrl($product);
    $imageUrl = merchantProductImageUrl((string) $product['photo_url']);
    $price = number_format((float) $product['prix_unitaire'], 2, '.', '') . ' XOF';
    $productType = 'Alimentation > ' . trim((string) $product['categorie']) . ' > ' . trim((string) $product['libelle']);
?>
    <item>
      <g:id><?= merchantXml(merchantProductId($product)) ?></g:id>
      <g:title><?= merchantXml($title) ?></g:title>
      <g:description><?= merchantXml($description) ?></g:description>
      <g:link><?= merchantXml($productUrl) ?></g:link>
      <g:canonical_link><?= merchantXml($productUrl) ?></g:canonical_link>
      <g:image_link><?= merchantXml((string) $imageUrl) ?></g:image_link>
      <g:availability><?= merchantProductAvailability($product) ?></g:availability>
      <g:price><?= merchantXml($price) ?></g:price>
      <g:condition>new</g:condition>
      <g:product_type><?= merchantXml($productType) ?></g:product_type>
      <g:identifier_exists>no</g:identifier_exists>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
