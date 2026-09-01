<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Poissonnerie Saint-Michel';
$activePage = $activePage ?? '';
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
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0c6b55">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/app.css') . '?v=' . $stylesheetVersion) ?>">
</head>
<body class="page-<?= e($activePage) ?>">
<header class="site-header">
  <div class="header-inner">
    <a class="brand" href="<?= e(url()) ?>" aria-label="Accueil Poissonnerie Saint-Michel">
      <span class="brand-mark" aria-hidden="true">S</span>
      <span>Saint-Michel<small>Poissonnerie</small></span>
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
      <span class="dashboard-sidebar__mark" aria-hidden="true">S</span>
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
