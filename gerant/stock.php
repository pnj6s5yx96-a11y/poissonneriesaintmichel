<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

try {
    $products = db()->query(
        'SELECT p.id_produit, p.libelle, p.quantite_stock, p.seuil_alerte, p.unite_vente, c.libelle AS categorie
         FROM produits p JOIN categories c ON c.id_categorie = p.id_categorie
         WHERE p.est_actif = 1 ORDER BY c.libelle, p.libelle'
    )->fetchAll();
    $movements = db()->query(
        'SELECT m.*, p.libelle AS produit, u.nom_complet AS auteur
         FROM mouvements_stock m
         JOIN produits p ON p.id_produit = m.id_produit
         LEFT JOIN utilisateurs u ON u.id_utilisateur = m.id_utilisateur
         ORDER BY m.date_mouvement DESC LIMIT 30'
    )->fetchAll();
} catch (Throwable $exception) {
    $products = [];
    $movements = [];
    flash('error', 'Impossible de charger les informations de stock.');
}

$pageTitle = 'Stock — Espace gérant';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('gerant/index.php')) ?>">Espace gérant</a> / Stock</p><p class="eyebrow">Module 3 · Traçabilité</p><h1>Le stock, sans approximation.</h1><p>Chaque entrée, sortie ou correction est enregistrée et synchronise automatiquement le stock disponible du produit.</p></section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Ajouter un mouvement</h2><p>Les sorties de vente seront automatisées lors de l’activation du module Commandes.</p></div></div>
  <?php if ($products === []): ?><p class="notice">Ajoutez d’abord des produits dans l’espace administrateur.</p><?php else: ?>
  <form method="post" action="<?= e(url('actions/stock.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <div class="form-grid">
      <div class="field"><label for="id_produit">Produit</label><select id="id_produit" name="id_produit" required><?php foreach ($products as $product): ?><option value="<?= (int) $product['id_produit'] ?>"><?= e($product['libelle']) ?> · <?= number_format((float) $product['quantite_stock'], 3, ',', ' ') ?> <?= e(strtolower($product['unite_vente'])) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="type_mouvement">Type</label><select id="type_mouvement" name="type_mouvement"><option value="ENTREE">Entrée de stock</option><option value="AJUSTEMENT_POSITIF">Ajustement positif</option><option value="AJUSTEMENT_NEGATIF">Ajustement négatif</option></select></div>
      <div class="field"><label for="quantite">Quantité</label><input id="quantite" name="quantite" type="number" min="0.001" step="0.001" required></div>
      <div class="field"><label for="reference_source">Référence <small>facultative</small></label><input id="reference_source" name="reference_source" placeholder="Ex. Bon fournisseur #12"></div>
      <div class="field full"><label for="motif">Motif</label><input id="motif" name="motif" placeholder="Ex. Réapprovisionnement hebdomadaire" required></div>
    </div>
    <div class="form-actions"><button class="button button-yellow" type="submit">Enregistrer le mouvement</button></div>
  </form><?php endif; ?>
</section>

<section class="section panel"><div class="panel-heading"><div><h2>Stock actuel</h2><p>Les niveaux atteignant le seuil apparaissent en jaune.</p></div></div><div class="data-table-wrap"><table class="data-table"><thead><tr><th>Produit</th><th>Catégorie</th><th>Disponible</th><th>Seuil</th><th>État</th></tr></thead><tbody><?php foreach ($products as $product): ?><?php $low = (float) $product['quantite_stock'] <= (float) $product['seuil_alerte']; ?><tr><td><strong><?= e($product['libelle']) ?></strong></td><td><?= e($product['categorie']) ?></td><td><?= number_format((float) $product['quantite_stock'], 3, ',', ' ') ?> <?= e(strtolower($product['unite_vente'])) ?></td><td><?= number_format((float) $product['seuil_alerte'], 3, ',', ' ') ?></td><td><span class="badge <?= $low ? 'badge-warning' : '' ?>"><?= $low ? 'À réapprovisionner' : 'Disponible' ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>

<section class="section panel"><div class="panel-heading"><div><h2>Derniers mouvements</h2><p>Les 30 opérations les plus récentes.</p></div></div><div class="data-table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Produit</th><th>Type</th><th>Quantité</th><th>Avant → Après</th><th>Motif</th></tr></thead><tbody><?php foreach ($movements as $movement): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($movement['date_mouvement']))) ?></td><td><strong><?= e($movement['produit']) ?></strong></td><td><span class="badge <?= in_array($movement['type_mouvement'], ['SORTIE', 'AJUSTEMENT_NEGATIF'], true) ? 'badge-warning' : '' ?>"><?= e(str_replace('_', ' ', $movement['type_mouvement'])) ?></span></td><td><?= number_format((float) $movement['quantite'], 3, ',', ' ') ?></td><td><?= number_format((float) $movement['stock_avant'], 3, ',', ' ') ?> → <?= number_format((float) $movement['stock_apres'], 3, ',', ' ') ?></td><td><?= e($movement['motif']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
