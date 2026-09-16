<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

$metrics = [
    'revenue_today' => 0.0,
    'revenue_week' => 0.0,
    'revenue_month' => 0.0,
    'pending_orders' => 0,
    'low_stock' => 0,
];
$revenueByDay = [];
$topProducts = [];
$salesByCategory = [];
$recentOrders = [];
$lowStockProducts = [];

try {
    $pdo = db();
    $metrics['revenue_today'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(montant), 0) FROM paiements
         WHERE statut_paiement = 'REUSSI' AND DATE(date_confirmation) = CURDATE()"
    )->fetchColumn();
    $metrics['revenue_week'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(montant), 0) FROM paiements
         WHERE statut_paiement = 'REUSSI'
           AND date_confirmation >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
           AND date_confirmation < DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
    )->fetchColumn();
    $metrics['revenue_month'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(montant), 0) FROM paiements
         WHERE statut_paiement = 'REUSSI'
           AND date_confirmation >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
           AND date_confirmation < DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
    )->fetchColumn();
    $metrics['pending_orders'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM commandes WHERE statut_courant IN ('EN_ATTENTE', 'EN_COURS_TRAITEMENT')"
    )->fetchColumn();
    $metrics['low_stock'] = (int) $pdo->query(
        'SELECT COUNT(*) FROM produits WHERE est_actif = 1 AND quantite_stock <= seuil_alerte'
    )->fetchColumn();

    $revenueByDay = $pdo->query(
        "SELECT DATE(date_confirmation) AS jour, COUNT(*) AS paiements, COALESCE(SUM(montant), 0) AS recettes
         FROM paiements
         WHERE statut_paiement = 'REUSSI'
           AND date_confirmation >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
           AND date_confirmation < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
         GROUP BY DATE(date_confirmation)
         ORDER BY jour"
    )->fetchAll();
    $topProducts = $pdo->query(
        "SELECT p.libelle AS produit, c.libelle AS categorie,
                COALESCE(SUM(lc.quantite), 0) AS quantite_vendue,
                COALESCE(SUM(lc.sous_total), 0) AS chiffre_affaires
         FROM lignes_commande lc
         JOIN produits p ON p.id_produit = lc.id_produit
         JOIN categories c ON c.id_categorie = p.id_categorie
         JOIN paiements pa ON pa.id_commande = lc.id_commande
         WHERE pa.statut_paiement = 'REUSSI'
           AND pa.date_confirmation >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
           AND pa.date_confirmation < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
         GROUP BY p.id_produit, p.libelle, c.libelle
         ORDER BY chiffre_affaires DESC, quantite_vendue DESC
         LIMIT 5"
    )->fetchAll();
    $salesByCategory = $pdo->query(
        "SELECT c.libelle AS categorie, COALESCE(SUM(lc.sous_total), 0) AS chiffre_affaires
         FROM lignes_commande lc
         JOIN produits p ON p.id_produit = lc.id_produit
         JOIN categories c ON c.id_categorie = p.id_categorie
         JOIN paiements pa ON pa.id_commande = lc.id_commande
         WHERE pa.statut_paiement = 'REUSSI'
           AND pa.date_confirmation >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
           AND pa.date_confirmation < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
         GROUP BY c.id_categorie, c.libelle
         ORDER BY chiffre_affaires DESC"
    )->fetchAll();
    $recentOrders = $pdo->query(
        "SELECT co.numero_commande, co.date_commande, co.statut_courant, co.montant_total,
                cl.nom_complet AS client, pa.statut_paiement
         FROM commandes co
         JOIN clients cl ON cl.id_client = co.id_client
         LEFT JOIN paiements pa ON pa.id_commande = co.id_commande
         ORDER BY co.date_commande DESC
         LIMIT 6"
    )->fetchAll();
    $lowStockProducts = $pdo->query(
        'SELECT libelle, quantite_stock, seuil_alerte, unite_vente
         FROM produits
         WHERE est_actif = 1 AND quantite_stock <= seuil_alerte
         ORDER BY quantite_stock ASC, libelle ASC
         LIMIT 6'
    )->fetchAll();
} catch (Throwable $exception) {
    flash('warning', 'Les indicateurs seront visibles dès que la base de données sera prête.');
}

$revenuePerDay = [];
foreach ($revenueByDay as $row) {
    $revenuePerDay[(string) $row['jour']] = (float) $row['recettes'];
}
$dailyChart = [];
$maximumDailyRevenue = 0.0;
$weekdayLabels = ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Jeu', 'Fri' => 'Ven', 'Sat' => 'Sam', 'Sun' => 'Dim'];
for ($offset = 6; $offset >= 0; $offset--) {
    $date = (new DateTimeImmutable('today'))->modify('-' . $offset . ' days');
    $revenue = $revenuePerDay[$date->format('Y-m-d')] ?? 0.0;
    $dailyChart[] = ['label' => $weekdayLabels[$date->format('D')], 'revenue' => $revenue];
    $maximumDailyRevenue = max($maximumDailyRevenue, $revenue);
}
$maximumCategoryRevenue = max(array_map(static fn (array $row): float => (float) $row['chiffre_affaires'], $salesByCategory) ?: [0.0]);

$pageTitle = 'Administration — Poissonnerie Saint-Michel';
require __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-welcome">
  <div><h1>Bonjour, <?= e((string) currentUser()['nom']) ?>.</h1><p>Suivez l’activité, les recettes et les points à traiter de la poissonnerie.</p></div>
  <span class="dashboard-badge">ADMINISTRATEUR</span>
</section>

<section class="metrics metrics-five" aria-label="Indicateurs clés">
  <article class="metric"><span>Recettes aujourd’hui</span><strong><?= e(moneyFcfa($metrics['revenue_today'])) ?></strong></article>
  <article class="metric"><span>Cette semaine</span><strong><?= e(moneyFcfa($metrics['revenue_week'])) ?></strong></article>
  <article class="metric"><span>Ce mois</span><strong><?= e(moneyFcfa($metrics['revenue_month'])) ?></strong></article>
  <article class="metric yellow"><span>Commandes à traiter</span><strong><?= (int) $metrics['pending_orders'] ?></strong></article>
  <article class="metric <?= $metrics['low_stock'] > 0 ? 'yellow' : '' ?>"><span>Stocks à surveiller</span><strong><?= (int) $metrics['low_stock'] ?></strong></article>
</section>

<section class="dashboard-grid section">
  <article class="panel">
    <div class="panel-heading"><div><h2>Recettes des 7 derniers jours</h2><p>Uniquement les paiements confirmés.</p></div><a class="button button-soft button-small" href="<?= e(url('admin/rapports.php?rapport=ventes')) ?>">Voir les rapports</a></div>
    <div class="revenue-chart" role="img" aria-label="Recettes quotidiennes des sept derniers jours">
      <?php foreach ($dailyChart as $day): ?>
        <?php $height = $maximumDailyRevenue > 0 ? max(4, (int) round(($day['revenue'] / $maximumDailyRevenue) * 100)) : 4; ?>
        <div class="revenue-chart__item"><span class="revenue-chart__value"><?= e(moneyFcfa($day['revenue'])) ?></span><div class="revenue-chart__bar" data-chart-height="<?= $height ?>"></div><strong><?= e($day['label']) ?></strong></div>
      <?php endforeach; ?>
    </div>
  </article>

  <article class="panel">
    <div class="panel-heading"><div><h2>Ventes par catégorie</h2><p>Chiffre d’affaires du mois en cours.</p></div></div>
    <?php if ($salesByCategory === []): ?>
      <p class="empty-inline">Aucune vente confirmée ce mois-ci.</p>
    <?php else: ?>
      <div class="category-bars">
        <?php foreach ($salesByCategory as $sale): ?>
          <?php $width = $maximumCategoryRevenue > 0 ? max(3, (int) round(((float) $sale['chiffre_affaires'] / $maximumCategoryRevenue) * 100)) : 3; ?>
          <div class="category-bars__item"><div><strong><?= e($sale['categorie']) ?></strong><span><?= e(moneyFcfa($sale['chiffre_affaires'])) ?></span></div><div class="category-bars__track"><i data-bar-width="<?= $width ?>"></i></div></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
</section>

<section class="dashboard-grid section">
  <article class="panel">
    <div class="panel-heading"><div><h2>Produits les plus vendus</h2><p>Classement du mois selon les paiements confirmés.</p></div><a class="button button-soft button-small" href="<?= e(url('admin/historique.php?vue=ventes')) ?>">Historique</a></div>
    <?php if ($topProducts === []): ?><p class="empty-inline">Les meilleures ventes apparaîtront dès les premiers paiements confirmés.</p><?php else: ?>
      <div class="data-table-wrap"><table class="data-table compact-table"><thead><tr><th>Produit</th><th>Catégorie</th><th>Qté vendue</th><th>CA</th></tr></thead><tbody>
      <?php foreach ($topProducts as $product): ?><tr><td><strong><?= e($product['produit']) ?></strong></td><td><?= e($product['categorie']) ?></td><td><?= number_format((float) $product['quantite_vendue'], 3, ',', ' ') ?></td><td><?= e(moneyFcfa($product['chiffre_affaires'])) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </article>

  <article class="panel">
    <div class="panel-heading"><div><h2>Stocks à surveiller</h2><p>Produits actifs au seuil ou en rupture.</p></div><a class="button button-soft button-small" href="<?= e(url('gerant/stock.php')) ?>">Gérer le stock</a></div>
    <?php if ($lowStockProducts === []): ?><p class="empty-inline">Aucune alerte de stock pour le moment.</p><?php else: ?>
      <div class="data-table-wrap"><table class="data-table compact-table"><thead><tr><th>Produit</th><th>Disponible</th><th>Seuil</th></tr></thead><tbody>
      <?php foreach ($lowStockProducts as $product): ?><tr><td><strong><?= e($product['libelle']) ?></strong></td><td><span class="badge badge-warning"><?= number_format((float) $product['quantite_stock'], 3, ',', ' ') ?> <?= e(strtolower($product['unite_vente'])) ?></span></td><td><?= number_format((float) $product['seuil_alerte'], 3, ',', ' ') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </article>
</section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Dernières commandes</h2><p>Vue globale du cycle de vente.</p></div><a class="button button-soft button-small" href="<?= e(url('gerant/commandes.php')) ?>">Traiter les commandes</a></div>
  <?php if ($recentOrders === []): ?><p class="empty-inline">Aucune commande enregistrée.</p><?php else: ?>
  <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Commande</th><th>Client</th><th>Date</th><th>Montant</th><th>Statut</th><th>Paiement</th></tr></thead><tbody>
  <?php foreach ($recentOrders as $order): ?><tr><td><strong><?= e($order['numero_commande']) ?></strong></td><td><?= e($order['client']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($order['date_commande']))) ?></td><td><?= e(moneyFcfa($order['montant_total'])) ?></td><td><span class="badge"><?= e(orderStatusLabel($order['statut_courant'])) ?></span></td><td><span class="badge <?= ($order['statut_paiement'] ?? '') === 'REUSSI' ? '' : 'badge-muted' ?>"><?= e($order['statut_paiement'] ? paymentStatusLabel($order['statut_paiement']) : 'Non initié') ?></span></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Accès rapides</h2><p>Configurez et pilotez la poissonnerie depuis un même espace.</p></div></div>
  <div class="quick-links">
    <a class="quick-link" href="<?= e(url('admin/utilisateurs.php')) ?>"><strong>Utilisateurs</strong><span>Créer, modifier ou désactiver les comptes internes.</span></a>
    <a class="quick-link" href="<?= e(url('gerant/commandes.php')) ?>"><strong>Commandes &amp; ventes</strong><span>Consulter toutes les commandes et faire avancer leur traitement.</span></a>
    <a class="quick-link" href="<?= e(url('gerant/nouvelle-commande.php')) ?>"><strong>Vente comptoir</strong><span>Enregistrer la commande d’un client venu directement en boutique.</span></a>
    <a class="quick-link" href="<?= e(url('admin/parametres.php')) ?>"><strong>Paramètres boutique</strong><span>Coordonnées, horaires, zones et service de livraison.</span></a>
    <a class="quick-link" href="<?= e(url('admin/rapports.php')) ?>"><strong>Rapports & exports</strong><span>Télécharger les données de l’activité au format CSV.</span></a>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
