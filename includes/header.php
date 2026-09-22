<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Poissonnerie Saint-Michel';
$activePage = $activePage ?? '';
$metaDescription = trim((string) ($metaDescription ?? 'Poissonnerie Saint-Michel à Akpakpa, Cotonou : poissons, viandes et produits congelés, à commander en ligne.'));
$metaDescription = mb_strimwidth($metaDescription, 0, 190, '…');
$seoIndexable = (bool) ($seoIndexable ?? false);
$seoCanonicalPath = (string) ($seoCanonicalPath ?? '');
$seoCanonicalUrl = $seoIndexable ? publicUrl($seoCanonicalPath) : null;
$seoImagePath = ltrim((string) ($seoImagePath ?? 'assets/images/hero-market.jpg'), '/');
$seoImageUrl = publicUrl($seoImagePath);
$seoStructuredData = $seoStructuredData ?? null;
$robotsDirective = $seoIndexable
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,nofollow,noarchive';
$user = currentUser();
$stylesheetVersion = (string) (@filemtime(PROJECT_ROOT . '/assets/css/app.css') ?: time());
$scriptVersion = (string) (@filemtime(PROJECT_ROOT . '/assets/js/app.js') ?: time());
$dashboardSection = $user !== null ? dashboardSectionFromScript((string) ($_SERVER['SCRIPT_NAME'] ?? '')) : null;
$hasDashboardSidebar = $dashboardSection !== null;
$dashboardMenu = $hasDashboardSidebar ? dashboardMenuForSection($dashboardSection) : [];
$dashboardCurrentPath = $hasDashboardSidebar ? $dashboardSection . '/' . basename((string) $_SERVER['SCRIPT_NAME']) : '';
$dashboardUserName = $user !== null ? (string) $user['nom'] : '';
$dashboardInitial = $dashboardUserName !== '' ? mb_strtoupper(mb_substr($dashboardUserName, 0, 1)) : 'S';
$cartItemCount = clientCartItemCount();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!$seoIndexable) {
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0c6b55">
  <link rel="icon" type="image/png" sizes="512x512" href="<?= e(url('assets/images/icon-saint-michel-512.png')) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/images/favicon-32.png')) ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= e(url('assets/images/favicon-16.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('assets/images/apple-touch-icon.png')) ?>">
  <meta name="description" content="<?= e($metaDescription) ?>">
  <meta name="robots" content="<?= e($robotsDirective) ?>">
  <?php if ($seoCanonicalUrl !== null): ?><link rel="canonical" href="<?= e($seoCanonicalUrl) ?>"><?php endif; ?>
  <meta property="og:locale" content="fr_BJ">
  <meta property="og:site_name" content="Poissonnerie Saint-Michel">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <?php if ($seoCanonicalUrl !== null): ?><meta property="og:url" content="<?= e($seoCanonicalUrl) ?>"><?php endif; ?>
  <meta property="og:image" content="<?= e($seoImageUrl) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($metaDescription) ?>">
  <meta name="twitter:image" content="<?= e($seoImageUrl) ?>">
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="<?= e(url('assets/css/app.css') . '?v=' . $stylesheetVersion) ?>">
  <?php if (is_array($seoStructuredData)): ?>
    <script type="application/ld+json" nonce="<?= e(cspNonce()) ?>"><?= json_encode($seoStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  <?php endif; ?>
</head>
<body class="page-<?= e($activePage) ?>">
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="<?= e(url()) ?>" aria-label="Accueil Poissonnerie Saint-Michel">
      <img class="brand-logo" src="<?= e(url('assets/images/logo-saint-michel.jpg')) ?>" width="151" height="52" alt="Poissonnerie Saint-Michel">
    </a>
    <button class="nav-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="primary-nav">
      <span></span><span></span><span></span>
    </button>
    <nav id="primary-nav" class="primary-nav" aria-label="Navigation principale">
      <a class="<?= $activePage === 'accueil' ? 'is-active' : '' ?>" href="<?= e(url()) ?>">Accueil</a>
      <a class="<?= $activePage === 'catalogue' ? 'is-active' : '' ?>" href="<?= e(url('catalogue.php')) ?>">Catalogue</a>
      <a class="nav-cart <?= $activePage === 'panier' || $activePage === 'validation' || $activePage === 'paiement' ? 'is-active' : '' ?>" href="<?= e(url('panier.php')) ?>">Panier <span aria-label="<?= $cartItemCount ?> article<?= $cartItemCount > 1 ? 's' : '' ?>"><?= $cartItemCount ?></span></a>
      <a class="<?= $activePage === 'suivi' ? 'is-active' : '' ?>" href="<?= e(url('suivi-commande.php')) ?>">Suivre ma commande</a>
      <?php if ($user !== null): ?>
        <a class="nav-dashboard" href="<?= e(url(dashboardPathForRole($user['role']))) ?>">Mon espace</a>
        <form class="nav-logout-form" method="post" action="<?= e(url('actions/deconnexion.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button class="nav-logout" type="submit">Déconnexion</button></form>
      <?php else: ?>
        <a class="nav-login" href="<?= e(url('auth/connexion.php')) ?>">Espace équipe</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php if ($hasDashboardSidebar): ?>
<div class="dashboard-shell">
  <aside class="dashboard-sidebar" aria-label="Navigation <?= e(dashboardSectionLabel($dashboardSection)) ?>">
    <a class="dashboard-sidebar__brand" href="<?= e(url($dashboardSection . '/index.php')) ?>">
      <img class="dashboard-sidebar__mark" src="<?= e(url('assets/images/logo-saint-michel-mark.jpg')) ?>" width="37" height="37" alt="">
      <span><strong>Saint-Michel</strong><small><?= e(dashboardSectionLabel($dashboardSection)) ?></small></span>
    </a>
    <div class="dashboard-sidebar__profile">
      <span class="dashboard-sidebar__avatar" aria-hidden="true"><?= e($dashboardInitial) ?></span>
      <span><small>Connecté en tant que</small><strong><?= e($dashboardUserName) ?></strong></span>
    </div>
    <nav class="dashboard-menu" aria-label="Menu principal">
      <p>Menu principal</p>
      <?php foreach ($dashboardMenu as $menuItem): ?>
        <?php $isActiveDashboardMenu = $dashboardCurrentPath === $menuItem['href']; ?>
        <a class="<?= $isActiveDashboardMenu ? 'is-active' : '' ?>"<?= $isActiveDashboardMenu ? ' aria-current="page"' : '' ?> href="<?= e(url($menuItem['href'])) ?>">
          <i aria-hidden="true"><?= e($menuItem['icon']) ?></i><span><?= e($menuItem['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="dashboard-sidebar__footer">
      <a href="<?= e(url('catalogue.php')) ?>"><span aria-hidden="true">↗</span> Voir la boutique</a>
      <form class="dashboard-sidebar__logout-form" method="post" action="<?= e(url('actions/deconnexion.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button class="dashboard-sidebar__logout" type="submit"><span aria-hidden="true">←</span> Déconnexion</button></form>
    </div>
  </aside>
<?php endif; ?>
<main class="container<?= $activePage === 'accueil' ? ' home-main' : '' ?><?= $hasDashboardSidebar ? ' dashboard-content' : '' ?>">
<?php foreach (consumeFlashes() as $flash): ?>
  <div class="flash flash-<?= e($flash['type']) ?>" role="status">
    <span><?= e($flash['message']) ?></span>
    <button type="button" aria-label="Fermer" data-dismiss-flash>×</button>
  </div>
<?php endforeach; ?>
