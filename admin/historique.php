<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

$views = [
    'clients' => 'Clients',
    'commandes' => 'Commandes',
    'ventes' => 'Ventes',
    'livraisons' => 'Livraisons',
    'stock' => 'Mouvements de stock',
    'audit' => 'Journal d’audit',
];
$view = queryEnum('vue', array_keys($views), 'commandes');

$range = adminDateRange(90);
$from = $range['from']->format('Y-m-d');
$to = $range['to']->format('Y-m-d');
$params = ['date_debut' => $from, 'date_fin' => adminDateTimeEnd($range['to'])];
$rows = [];

try {
    $pdo = db();
    switch ($view) {
        case 'clients':
            $statement = $pdo->prepare(
                'SELECT c.id_client, c.nom_complet, c.telephone, c.email, c.created_at,
                        COUNT(co.id_commande) AS commandes_periode,
                        COALESCE(SUM(co.montant_total), 0) AS montant_periode
                 FROM clients c
                 LEFT JOIN commandes co ON co.id_client = c.id_client
                    AND co.date_commande >= :date_debut
                    AND co.date_commande < :date_fin
                 WHERE c.created_at < :date_fin OR co.id_commande IS NOT NULL
                 GROUP BY c.id_client, c.nom_complet, c.telephone, c.email, c.created_at
                 ORDER BY MAX(co.date_commande) DESC, c.created_at DESC
                 LIMIT 250'
            );
            $statement->execute($params);
            $rows = $statement->fetchAll();
            break;

        case 'ventes':
            $statement = $pdo->prepare(
                "SELECT pa.id_paiement, pa.date_confirmation, pa.mode_paiement, pa.reference_transaction,
                        pa.montant, co.numero_commande, cl.nom_complet AS client,
                        COUNT(lc.id_ligne_commande) AS lignes
                 FROM paiements pa
                 JOIN commandes co ON co.id_commande = pa.id_commande
                 JOIN clients cl ON cl.id_client = co.id_client
                 LEFT JOIN lignes_commande lc ON lc.id_commande = co.id_commande
                 WHERE pa.statut_paiement = 'REUSSI'
                   AND pa.date_confirmation >= :date_debut
                   AND pa.date_confirmation < :date_fin
                 GROUP BY pa.id_paiement, pa.date_confirmation, pa.mode_paiement, pa.reference_transaction,
                          pa.montant, co.numero_commande, cl.nom_complet
                 ORDER BY pa.date_confirmation DESC
                 LIMIT 250"
            );
            $statement->execute($params);
            $rows = $statement->fetchAll();
            break;

        case 'livraisons':
            $statement = $pdo->prepare(
                'SELECT l.id_livraison, l.date_affectation, l.date_livraison, l.statut_livraison,
                        l.adresse_livraison, co.numero_commande, cl.nom_complet AS client,
                        u.nom_complet AS livreur
                 FROM livraisons l
                 JOIN commandes co ON co.id_commande = l.id_commande
                 JOIN clients cl ON cl.id_client = co.id_client
                 JOIN utilisateurs u ON u.id_utilisateur = l.id_livreur
                 WHERE l.date_affectation >= :date_debut
                   AND l.date_affectation < :date_fin
                 ORDER BY l.date_affectation DESC
                 LIMIT 250'
            );
            $statement->execute($params);
            $rows = $statement->fetchAll();
            break;

        case 'stock':
            $statement = $pdo->prepare(
                'SELECT m.date_mouvement, m.type_mouvement, m.quantite, m.stock_avant, m.stock_apres,
                        m.motif, m.reference_source, p.libelle AS produit, p.unite_vente,
                        u.nom_complet AS auteur
                 FROM mouvements_stock m
                 JOIN produits p ON p.id_produit = m.id_produit
                 LEFT JOIN utilisateurs u ON u.id_utilisateur = m.id_utilisateur
                 WHERE m.date_mouvement >= :date_debut
                   AND m.date_mouvement < :date_fin
                 ORDER BY m.date_mouvement DESC
                 LIMIT 250'
            );
            $statement->execute($params);
            $rows = $statement->fetchAll();
            break;

        case 'audit':
            $statement = $pdo->prepare(
                'SELECT a.date_evenement, a.type_evenement, a.cible_type, a.cible_id, a.adresse_ip,
                        u.nom_complet AS auteur
                 FROM journal_audit a
                 LEFT JOIN utilisateurs u ON u.id_utilisateur = a.id_utilisateur
                 WHERE a.date_evenement >= :date_debut
                   AND a.date_evenement < :date_fin
                 ORDER BY a.date_evenement DESC
                 LIMIT 250'
            );
            $statement->execute($params);
            $rows = $statement->fetchAll();
            break;

        default:
            $statement = $pdo->prepare(
                'SELECT co.numero_commande, co.date_commande, co.statut_courant, co.mode_retrait,
                        co.montant_total, cl.nom_complet AS client, cl.telephone,
                        pa.mode_paiement, pa.statut_paiement
                 FROM commandes co
                 JOIN clients cl ON cl.id_client = co.id_client
                 LEFT JOIN paiements pa ON pa.id_commande = co.id_commande
                 WHERE co.date_commande >= :date_debut
                   AND co.date_commande < :date_fin
                 ORDER BY co.date_commande DESC
                 LIMIT 250'
            );
            $statement->execute($params);
            $rows = $statement->fetchAll();
    }
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger cet historique.');
}

$pageTitle = 'Historique — Administration';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('admin/index.php')) ?>">Administration</a> / Historique</p><p class="eyebrow">Traçabilité de l’activité</p><h1>Un historique complet, au même endroit.</h1><p>Consultez les clients, commandes, ventes, livraisons et mouvements de stock sur la période de votre choix.</p></section>

<section class="section panel">
  <div class="history-tabs" aria-label="Types d’historique">
    <?php foreach ($views as $key => $label): ?><a class="<?= $view === $key ? 'is-active' : '' ?>" <?= $view === $key ? 'aria-current="page"' : '' ?> href="<?= e(url('admin/historique.php?vue=' . $key . '&date_debut=' . $from . '&date_fin=' . $to)) ?>"><?= e($label) ?></a><?php endforeach; ?>
  </div>
  <form class="filters-bar" method="get">
    <input type="hidden" name="vue" value="<?= e($view) ?>">
    <div class="field"><label for="date_debut">Du</label><input id="date_debut" name="date_debut" type="date" value="<?= e($from) ?>" max="<?= e(date('Y-m-d')) ?>"></div>
    <div class="field"><label for="date_fin">Au</label><input id="date_fin" name="date_fin" type="date" value="<?= e($to) ?>" max="<?= e(date('Y-m-d')) ?>"></div>
    <button class="button button-soft button-small" type="submit">Appliquer</button>
    <a class="button button-small" href="<?= e(url('admin/rapports.php?rapport=' . ($view === 'stock' ? 'stock' : $view) . '&date_debut=' . $from . '&date_fin=' . $to)) ?>">Exporter</a>
  </form>
  <p class="results-summary"><?= count($rows) ?> résultat<?= count($rows) > 1 ? 's' : '' ?> · du <?= e($range['from']->format('d/m/Y')) ?> au <?= e($range['to']->format('d/m/Y')) ?> · 250 lignes maximum.</p>

  <?php if ($rows === []): ?><p class="empty-inline">Aucune donnée ne correspond à cette période.</p><?php elseif ($view === 'clients'): ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Client</th><th>Contact</th><th>Client depuis</th><th>Commandes sur la période</th><th>Montant</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['nom_complet']) ?></strong><br><small>#<?= (int) $row['id_client'] ?></small></td><td><?= e($row['telephone']) ?><br><small><?= e($row['email'] ?: '—') ?></small></td><td><?= e(date('d/m/Y', strtotime($row['created_at']))) ?></td><td><?= (int) $row['commandes_periode'] ?></td><td><?= e(moneyFcfa($row['montant_periode'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?php elseif ($view === 'ventes'): ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Commande</th><th>Client</th><th>Paiement</th><th>Référence</th><th>Montant</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($row['date_confirmation']))) ?></td><td><strong><?= e($row['numero_commande']) ?></strong><br><small><?= (int) $row['lignes'] ?> ligne<?= (int) $row['lignes'] > 1 ? 's' : '' ?></small></td><td><?= e($row['client']) ?></td><td><span class="badge"><?= e(str_replace('_', ' ', $row['mode_paiement'])) ?></span></td><td><?= e($row['reference_transaction'] ?: '—') ?></td><td><?= e(moneyFcfa($row['montant'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?php elseif ($view === 'livraisons'): ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Affectée le</th><th>Commande</th><th>Client</th><th>Livreur</th><th>Adresse</th><th>État</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($row['date_affectation']))) ?></td><td><strong><?= e($row['numero_commande']) ?></strong></td><td><?= e($row['client']) ?></td><td><?= e($row['livreur']) ?></td><td><?= e($row['adresse_livraison']) ?></td><td><span class="badge <?= in_array($row['statut_livraison'], ['REFUSEE', 'ANNULEE'], true) ? 'badge-warning' : '' ?>"><?= e(deliveryStatusLabel($row['statut_livraison'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
  <?php elseif ($view === 'stock'): ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Produit</th><th>Type</th><th>Quantité</th><th>Avant → Après</th><th>Motif</th><th>Auteur</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($row['date_mouvement']))) ?></td><td><strong><?= e($row['produit']) ?></strong></td><td><span class="badge <?= in_array($row['type_mouvement'], ['SORTIE', 'AJUSTEMENT_NEGATIF'], true) ? 'badge-warning' : '' ?>"><?= e(str_replace('_', ' ', $row['type_mouvement'])) ?></span></td><td><?= number_format((float) $row['quantite'], 3, ',', ' ') ?> <?= e(strtolower($row['unite_vente'])) ?></td><td><?= number_format((float) $row['stock_avant'], 3, ',', ' ') ?> → <?= number_format((float) $row['stock_apres'], 3, ',', ' ') ?></td><td><?= e($row['motif']) ?><br><small><?= e($row['reference_source'] ?: '—') ?></small></td><td><?= e($row['auteur'] ?: 'Système') ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?php elseif ($view === 'audit'): ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Action</th><th>Cible</th><th>Effectuée par</th><th>Adresse IP</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($row['date_evenement']))) ?></td><td><strong><?= e(str_replace('_', ' ', $row['type_evenement'])) ?></strong></td><td><?= e(auditEventTargetLabel($row['type_evenement'], $row['cible_type'])) ?> <small>#<?= (int) $row['cible_id'] ?></small></td><td><?= e($row['auteur'] ?: 'Système') ?></td><td><?= e($row['adresse_ip'] ?: '—') ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?php else: ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Commande</th><th>Client</th><th>Date</th><th>Retrait</th><th>Montant</th><th>Statut</th><th>Paiement</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['numero_commande']) ?></strong></td><td><?= e($row['client']) ?><br><small><?= e($row['telephone']) ?></small></td><td><?= e(date('d/m/Y H:i', strtotime($row['date_commande']))) ?></td><td><?= e($row['mode_retrait'] === 'LIVRAISON' ? 'Livraison' : 'Retrait boutique') ?></td><td><?= e(moneyFcfa($row['montant_total'])) ?></td><td><span class="badge"><?= e(orderStatusLabel($row['statut_courant'])) ?></span></td><td><span class="badge <?= ($row['statut_paiement'] ?? '') === 'REUSSI' ? '' : 'badge-muted' ?>"><?= e($row['statut_paiement'] ? paymentStatusLabel($row['statut_paiement']) : 'Non initié') ?></span></td></tr><?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
