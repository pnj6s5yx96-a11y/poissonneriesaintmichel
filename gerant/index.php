<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);
$metrics = ['produits' => 0, 'stock_bas' => 0, 'mouvements_jour' => 0, 'commandes_attente' => 0, 'especes_attente' => 0];

try {
    $metrics['produits'] = (int) db()->query('SELECT COUNT(*) FROM produits WHERE est_actif = 1')->fetchColumn();
    $metrics['stock_bas'] = (int) db()->query('SELECT COUNT(*) FROM produits WHERE est_actif = 1 AND quantite_stock <= seuil_alerte')->fetchColumn();
    $metrics['mouvements_jour'] = (int) db()->query('SELECT COUNT(*) FROM mouvements_stock WHERE DATE(date_mouvement) = CURDATE()')->fetchColumn();
    $metrics['commandes_attente'] = (int) db()->query("SELECT COUNT(*) FROM commandes WHERE statut_courant = 'EN_ATTENTE'")->fetchColumn();
    $metrics['especes_attente'] = (int) db()->query("SELECT COUNT(*) FROM paiements WHERE mode_paiement = 'ESPECES' AND statut_paiement = 'EN_ATTENTE'")->fetchColumn();
} catch (Throwable $exception) {
    flash('warning', 'Les données seront chargées après la configuration de la base.');
}

$pageTitle = 'Espace gérant — Poissonnerie Saint-Michel';
require __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-welcome"><div><h1>Bonjour, <?= e((string) currentUser()['nom']) ?>.</h1><p>Enregistrez les ventes au comptoir, traitez les commandes et sécurisez les encaissements.</p></div><span class="dashboard-badge">GÉRANT</span></section>
<section class="metrics"><article class="metric"><span>Commandes à traiter</span><strong><?= $metrics['commandes_attente'] ?></strong></article><article class="metric yellow"><span>Espèces à confirmer</span><strong><?= $metrics['especes_attente'] ?></strong></article><article class="metric"><span>Mouvements aujourd’hui</span><strong><?= $metrics['mouvements_jour'] ?></strong></article><article class="metric yellow"><span>Stocks à surveiller</span><strong><?= $metrics['stock_bas'] ?></strong></article></section>
<section class="panel"><div class="panel-heading"><div><h2>Actions de vente</h2><p>Gérez les ventes comptoir et les commandes reçues en ligne.</p></div></div><div class="quick-links"><a class="quick-link" href="<?= e(url('gerant/nouvelle-commande.php')) ?>"><strong>Nouvelle vente comptoir</strong><span>Enregistrer la commande d’un client présent en boutique.</span></a><a class="quick-link" href="<?= e(url('gerant/commandes.php')) ?>"><strong>Traiter les commandes</strong><span>Consulter et faire avancer les commandes reçues.</span></a><a class="quick-link" href="<?= e(url('gerant/paiements.php')) ?>"><strong>Confirmer les espèces</strong><span>Valider un encaissement et imprimer le reçu.</span></a><a class="quick-link" href="<?= e(url('gerant/stock.php')) ?>"><strong>Gérer le stock</strong><span>Entrées, sorties et historique des mouvements.</span></a><a class="quick-link" href="<?= e(url('catalogue.php')) ?>"><strong>Voir le catalogue</strong><span>Contrôler la présentation côté client.</span></a></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
