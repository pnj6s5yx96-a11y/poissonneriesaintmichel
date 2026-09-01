<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['LIVREUR']);

$courierId = (int) currentUser()['id'];
$statuses = [
    'LIVREE' => 'Livrées',
    'REFUSEE' => 'Refusées',
    'ANNULEE' => 'Annulées',
];
$selectedStatus = queryEnum('statut', array_keys($statuses));
$deliveries = [];

try {
    $conditions = ['l.id_livreur = :livreur', "l.statut_livraison IN ('LIVREE', 'REFUSEE', 'ANNULEE')"];
    $parameters = ['livreur' => $courierId];
    if ($selectedStatus !== '') {
        $conditions[] = 'l.statut_livraison = :statut';
        $parameters['statut'] = $selectedStatus;
    }
    $statement = db()->prepare(
        'SELECT l.id_livraison, l.statut_livraison, l.date_affectation, l.date_acceptation,
                l.date_depart, l.date_livraison, l.motif_refus_annulation,
                c.numero_commande, c.montant_total, c.confirmation_reception,
                z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                cl.nom_complet AS client, cl.telephone, l.adresse_livraison
           FROM livraisons l
           JOIN commandes c ON c.id_commande = l.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
          WHERE ' . implode(' AND ', $conditions) . '
          ORDER BY COALESCE(l.date_livraison, l.updated_at) DESC
          LIMIT 100'
    );
    $statement->execute($parameters);
    $deliveries = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger votre historique.');
}

function courierHistoryUrl(string $status = ''): string
{
    return url('livreur/historique.php' . ($status === '' ? '' : '?statut=' . rawurlencode($status)));
}

$pageTitle = 'Historique des livraisons — Poissonnerie Saint-Michel';
require __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-welcome courier-welcome"><div><p class="dashboard-kicker">Traçabilité</p><h1>Historique de mes livraisons.</h1><p>Retrouvez les missions effectuées, refusées ou annulées et leur statut de réception.</p></div><span class="dashboard-badge"><?= count($deliveries) ?> MISSION<?= count($deliveries) > 1 ? 'S' : '' ?></span></section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Missions terminées</h2><p>Les 100 dernières missions selon vos filtres.</p></div></div>
  <nav class="filter-pills" aria-label="Filtrer l’historique"><a class="<?= $selectedStatus === '' ? 'is-active' : '' ?>" href="<?= e(courierHistoryUrl()) ?>">Toutes</a><?php foreach ($statuses as $status => $label): ?><a class="<?= $selectedStatus === $status ? 'is-active' : '' ?>" href="<?= e(courierHistoryUrl($status)) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
  <?php if ($deliveries === []): ?>
    <div class="empty-state"><strong>Aucun historique dans cette vue</strong><span>Les missions finalisées ou annulées seront conservées ici.</span></div>
  <?php else: ?>
    <div class="data-table-wrap"><table class="data-table courier-history-table"><thead><tr><th>Commande</th><th>Client</th><th>Adresse</th><th>Affectée le</th><th>Résultat</th><th>Information</th></tr></thead><tbody>
      <?php foreach ($deliveries as $delivery): ?>
        <?php $information = $delivery['motif_refus_annulation'] ?: ($delivery['date_livraison'] ? 'Livrée le ' . date('d/m/Y à H:i', strtotime((string) $delivery['date_livraison'])) : '—'); ?>
        <tr><td><strong><?= e((string) $delivery['numero_commande']) ?></strong><br><small><?= e(moneyFcfa($delivery['montant_total'])) ?></small></td><td><?= e((string) $delivery['client']) ?><br><small><?= e((string) $delivery['telephone']) ?></small></td><td><strong><?= e((string) ($delivery['zone_livraison'] ?? 'Zone à confirmer')) ?><?= !empty($delivery['quartier_livraison']) ? ' · ' . e((string) $delivery['quartier_livraison']) : '' ?></strong><br><?= e((string) $delivery['adresse_livraison']) ?></td><td><?= e(date('d/m/Y H:i', strtotime((string) $delivery['date_affectation']))) ?></td><td><span class="badge <?= e(deliveryStatusBadgeClass((string) $delivery['statut_livraison'])) ?>"><?= e(deliveryStatusLabel((string) $delivery['statut_livraison'])) ?></span><?php if ($delivery['statut_livraison'] === 'LIVREE'): ?><br><small><?= (int) $delivery['confirmation_reception'] === 1 ? 'Réception confirmée' : 'En attente du client' ?></small><?php endif; ?></td><td><?= e($information) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
