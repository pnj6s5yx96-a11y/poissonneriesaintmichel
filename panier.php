<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$products = [];
$summary = ['articles' => 0.0, 'total' => 0.0, 'is_valid' => false];
$databaseError = null;

try {
    $products = clientCartProducts(db());
    $summary = clientCartSummary($products);
} catch (Throwable $exception) {
    $databaseError = 'Votre panier est conservé, mais son contenu ne peut pas être chargé pour le moment.';
}

$missingProducts = max(0, clientCartItemCount() - count($products));
$pageTitle = 'Mon panier — Poissonnerie Saint-Michel';
$metaDescription = 'Préparez votre commande de poissons, viandes et produits congelés chez Poissonnerie Saint-Michel à Cotonou.';
$seoIndexable = false;
$activePage = 'panier';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
  <p class="breadcrumbs"><a href="<?= e(url()) ?>">Accueil</a> / Panier</p>
  <p class="eyebrow">Votre sélection</p>
  <h1>Un panier simple, des produits bien choisis.</h1>
  <p>Vérifiez les quantités avant de renseigner vos coordonnées et votre mode de récupération.</p>
</section>

<?php if ($databaseError !== null): ?>
  <section class="section notice"><?= e($databaseError) ?></section>
<?php elseif ($products === [] && $missingProducts === 0): ?>
  <section class="section empty-state cart-empty">
    <strong>Votre panier est vide</strong>
    <span>Parcourez le catalogue pour ajouter les produits dont vous avez besoin.</span>
    <a class="button button-yellow" href="<?= e(url('catalogue.php')) ?>">Voir le catalogue</a>
  </section>
<?php else: ?>
  <?php if ($missingProducts > 0): ?>
    <section class="section notice">
      <span>Un ou plusieurs produits ne sont plus proposés. Retirez-les du panier avant de continuer.</span>
      <form class="inline-form" method="post" action="<?= e(url('actions/panier.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="remove_missing">
        <button class="button button-soft button-small" type="submit">Retirer les articles indisponibles</button>
      </form>
    </section>
  <?php endif; ?>
  <form method="post" action="<?= e(url('actions/panier.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <section class="section cart-layout">
      <div class="panel">
        <div class="panel-heading"><div><h2>Mes articles</h2><p>Les disponibilités seront contrôlées une dernière fois à la validation.</p></div><a class="button button-soft button-small" href="<?= e(url('catalogue.php')) ?>">Ajouter des produits</a></div>
        <div class="cart-lines">
          <?php foreach ($products as $product): ?>
            <article class="cart-line<?= !$product['stock_suffisant'] ? ' is-unavailable' : '' ?>">
              <div class="cart-line__image">
                <?php if (!empty($product['photo_url'])): ?><img src="<?= e(url((string) $product['photo_url'])) ?>" alt="<?= e((string) $product['libelle']) ?>"><?php else: ?><span aria-hidden="true">~&lt;°)))&gt;</span><?php endif; ?>
              </div>
              <div class="cart-line__product"><span><?= e((string) $product['categorie']) ?></span><h3><?= e((string) $product['libelle']) ?></h3><p><?= e(moneyFcfa($product['prix_unitaire'])) ?> / <?= e(mb_strtolower((string) $product['unite_vente'])) ?></p><?php if (!$product['stock_suffisant']): ?><small>Stock disponible : <?= number_format((float) $product['quantite_stock'], 3, ',', ' ') ?> <?= e(mb_strtolower((string) $product['unite_vente'])) ?></small><?php endif; ?></div>
              <div class="cart-line__quantity field"><label for="quantite-<?= (int) $product['id_produit'] ?>">Quantité</label><input id="quantite-<?= (int) $product['id_produit'] ?>" name="quantites[<?= (int) $product['id_produit'] ?>]" type="number" min="0.001" max="999999.999" step="0.001" value="<?= e((string) $product['quantite_panier']) ?>" inputmode="decimal" required></div>
              <strong class="cart-line__subtotal"><?= e(moneyFcfa($product['sous_total'])) ?></strong>
              <button class="cart-line__remove" type="submit" name="id_produit" value="<?= (int) $product['id_produit'] ?>" formnovalidate>Retirer</button>
            </article>
          <?php endforeach; ?>
        </div>
        <div class="form-actions"><button class="button button-soft" type="submit" name="action" value="update">Mettre à jour le panier</button></div>
      </div>
      <aside class="cart-summary panel">
        <h2>Récapitulatif</h2>
        <div><span>Sous-total</span><strong><?= e(moneyFcfa($summary['articles'])) ?></strong></div>
        <div><span>Livraison</span><strong>Calculée à l’étape suivante</strong></div>
        <div class="cart-summary__total"><span>Total estimé</span><strong><?= e(moneyFcfa($summary['total'])) ?></strong></div>
        <p>Le prix des produits et le stock sont revalidés avant l’enregistrement de votre commande.</p>
        <?php if ($summary['is_valid']): ?>
          <a class="button button-yellow cart-summary__checkout" href="<?= e(url('validation-commande.php')) ?>">Continuer la commande</a>
        <?php else: ?>
          <button class="button button-yellow cart-summary__checkout" type="button" disabled>Corrigez le panier pour continuer</button>
        <?php endif; ?>
      </aside>
    </section>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
