<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Poissonnerie Saint-Michel — Le goût de la mer, simplement';
$activePage = 'accueil';
require __DIR__ . '/includes/header.php';
?>
<section class="home-hero" aria-labelledby="home-hero-title">
  <div class="home-hero__slides" aria-hidden="true">
    <div class="home-hero__slide home-hero__slide--market is-active"></div>
    <div class="home-hero__slide home-hero__slide--catch"></div>
    <div class="home-hero__slide home-hero__slide--table"></div>
  </div>
  <div class="home-hero__overlay" aria-hidden="true"></div>
  <div class="home-hero__content">
    <p class="home-kicker">Poissonnerie Saint-Michel · Cotonou</p>
    <h1 id="home-hero-title">La mer arrive<br>dans votre cuisine.</h1>
    <p class="home-hero__lead">Des produits choisis avec soin, conservés dans le respect de la chaîne du froid et disponibles pour le retrait ou la livraison.</p>
    <div class="home-hero__actions">
      <a class="button button-sun" href="<?= e(url('catalogue.php')) ?>">Voir le catalogue <span aria-hidden="true">→</span></a>
      <a class="hero-link" href="#notre-promesse">Notre engagement <span aria-hidden="true">↓</span></a>
    </div>
  </div>
  <div class="home-hero__trust" aria-label="Nos engagements">
    <div><span class="trust-icon" aria-hidden="true">❄</span><span><strong>Chaîne du froid</strong>Maîtrisée à chaque étape</span></div>
    <div><span class="trust-icon" aria-hidden="true">✦</span><span><strong>Produits sélectionnés</strong>Pour vos meilleures recettes</span></div>
    <div><span class="trust-icon" aria-hidden="true">⌂</span><span><strong>Retrait ou livraison</strong>Selon votre rythme</span></div>
  </div>
  <div class="home-hero__controls" aria-label="Changer l’image d’arrière-plan">
    <button class="hero-control hero-control--previous" type="button" data-hero-previous aria-label="Image précédente"><span aria-hidden="true">←</span></button>
    <div class="home-hero__pagination" role="group" aria-label="Images de la sélection">
      <button type="button" class="is-active" data-hero-slide="0" aria-current="true" aria-label="Afficher les produits de la sélection du marché"></button>
      <button type="button" data-hero-slide="1" aria-current="false" aria-label="Afficher les arrivages sur glace"></button>
      <button type="button" data-hero-slide="2" aria-current="false" aria-label="Afficher les produits préparés pour la cuisine"></button>
    </div>
    <button class="hero-control hero-control--next" type="button" data-hero-next aria-label="Image suivante"><span aria-hidden="true">→</span></button>
  </div>
  <p class="sr-only" data-hero-status aria-live="polite">Image 1 sur 3</p>
</section>

<section id="notre-promesse" class="home-story">
  <div class="home-story__photo" role="img" aria-label="Un plat de saumon et légumes prêt à déguster">
    <span class="home-story__seal"><b>Saint-</b><b>Michel</b><small>Le bon frais</small></span>
  </div>
  <div class="home-story__copy">
    <p class="section-kicker">Une adresse de confiance</p>
    <h2>Bien manger commence par de bons produits.</h2>
    <p>À Saint-Michel, nous réunissons les essentiels du quotidien : poissons, viandes et œufs congelés. Une sélection pratique, savoureuse et pensée pour les cuisines de Cotonou.</p>
    <p>Notre catalogue vous permet de commander simplement, quand vous le souhaitez, puis de choisir la solution qui vous convient.</p>
    <a class="text-link" href="<?= e(url('catalogue.php')) ?>">Découvrir nos produits <span aria-hidden="true">→</span></a>
  </div>
</section>

<section class="home-selection" aria-labelledby="selection-title">
  <div class="home-section-heading">
    <div>
      <p class="section-kicker">Nos essentiels</p>
      <h2 id="selection-title">De quoi composer de très bons repas.</h2>
    </div>
    <p>Des produits du quotidien à garder à portée de main, sélectionnés pour vous simplifier la cuisine.</p>
  </div>
  <div class="category-grid">
    <a class="category-card category-card--fish" href="<?= e(url('catalogue.php?categorie=1')) ?>">
      <span class="category-card__shade" aria-hidden="true"></span>
      <span class="category-card__content"><small>La sélection marine</small><strong>Poissons &amp;<br>fruits de mer</strong><em>Explorer <b aria-hidden="true">→</b></em></span>
    </a>
    <a class="category-card category-card--meat" href="<?= e(url('catalogue.php?categorie=2')) ?>">
      <span class="category-card__shade" aria-hidden="true"></span>
      <span class="category-card__content"><small>Pour vos recettes</small><strong>Viandes<br>sélectionnées</strong><em>Explorer <b aria-hidden="true">→</b></em></span>
    </a>
    <a class="category-card category-card--eggs" href="<?= e(url('catalogue.php?categorie=3')) ?>">
      <span class="category-card__shade" aria-hidden="true"></span>
      <span class="category-card__content"><small>Les indispensables</small><strong>Œufs &amp;<br>quotidien</strong><em>Explorer <b aria-hidden="true">→</b></em></span>
    </a>
  </div>
</section>

<section class="home-process" aria-labelledby="process-title">
  <div class="home-process__intro">
    <p class="section-kicker">Simple comme bonjour</p>
    <h2 id="process-title">Votre commande en trois temps.</h2>
    <p>Quelques minutes suffisent pour consulter nos produits et avancer sereinement vers votre commande.</p>
  </div>
  <ol class="process-list">
    <li><span>01</span><div><h3>Parcourez</h3><p>Découvrez la sélection et vérifiez les disponibilités du moment.</p></div></li>
    <li><span>02</span><div><h3>Choisissez</h3><p>Préparez votre commande avec les produits adaptés à vos envies.</p></div></li>
    <li><span>03</span><div><h3>Recevez</h3><p>Optez pour le retrait ou la livraison, puis suivez son avancement.</p></div></li>
  </ol>
</section>

<section class="home-delivery" aria-labelledby="delivery-title">
  <div class="home-delivery__media" aria-hidden="true"></div>
  <div class="home-delivery__overlay" aria-hidden="true"></div>
  <div class="home-delivery__content">
    <p class="home-kicker">Prêt quand vous l’êtes</p>
    <h2 id="delivery-title">Le bon produit.<br>Au bon moment.</h2>
    <p>Retrouvez toute notre sélection en ligne et choisissez le retrait en boutique ou la livraison qui s’adapte à votre journée.</p>
    <a class="button button-sun" href="<?= e(url('catalogue.php')) ?>">Commencer mes achats <span aria-hidden="true">→</span></a>
  </div>
</section>

<section class="home-order-help" aria-label="Suivi de commande">
  <div><span aria-hidden="true">↗</span><p><strong>Une commande déjà en cours ?</strong> Consultez son statut en quelques instants.</p></div>
  <a class="text-link" href="<?= e(url('suivi-commande.php')) ?>">Suivre ma commande <span aria-hidden="true">→</span></a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
