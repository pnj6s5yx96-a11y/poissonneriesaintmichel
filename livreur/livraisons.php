<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['LIVREUR']);

$courierId = (int) currentUser()['id'];
$statusLabels = [
    'AFFECTEE' => 'À accepter',
    'ACCEPTEE' => 'Prête au départ',
    'EN_COURS' => 'En route',
];
$selectedStatus = queryEnum('statut', array_keys($statusLabels));
$deliveries = [];

try {
    $conditions = ["l.id_livreur = :livreur", "l.statut_livraison IN ('AFFECTEE', 'ACCEPTEE', 'EN_COURS')"];
    $parameters = ['livreur' => $courierId];
    if ($selectedStatus !== '') {
        $conditions[] = 'l.statut_livraison = :statut';
        $parameters['statut'] = $selectedStatus;
    }
    $statement = db()->prepare(
        'SELECT l.id_livraison, l.statut_livraison, l.date_affectation, l.date_acceptation, l.date_depart,
                l.adresse_livraison, l.contact_livraison, c.id_commande, c.numero_commande,
                c.montant_total, c.note_client, z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                cl.nom_complet AS client,
                (SELECT GROUP_CONCAT(CONCAT(p.libelle, \' × \', lc.quantite, \' \', LOWER(p.unite_vente))
                                     ORDER BY p.libelle SEPARATOR \' · \')
                   FROM lignes_commande lc
                   JOIN produits p ON p.id_produit = lc.id_produit
                  WHERE lc.id_commande = c.id_commande) AS articles
           FROM livraisons l
           JOIN commandes c ON c.id_commande = l.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
          WHERE ' . implode(' AND ', $conditions) . "
          ORDER BY FIELD(l.statut_livraison, 'EN_COURS', 'ACCEPTEE', 'AFFECTEE'), l.date_affectation ASC"
    );
    $statement->execute($parameters);
    $deliveries = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger vos livraisons.');
}

function courierDeliveriesUrl(string $status = ''): string
{
    return url('livreur/livraisons.php' . ($status === '' ? '' : '?statut=' . rawurlencode($status)));
}

$pageTitle = 'Mes livraisons — Poissonnerie Saint-Michel';
require __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-welcome courier-welcome"><div><p class="dashboard-kicker">Missions affectées</p><h1>Mes livraisons à effectuer.</h1><p>Consultez les coordonnées utiles, mettez à jour la mission et tenez le client informé à chaque étape.</p></div><span class="dashboard-badge"><?= count($deliveries) ?> ACTIVE<?= count($deliveries) > 1 ? 'S' : '' ?></span></section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Mes missions</h2><p>Les données de livraison ne sont visibles que par le livreur affecté.</p></div></div>
  <nav class="filter-pills" aria-label="Filtrer les livraisons"><a class="<?= $selectedStatus === '' ? 'is-active' : '' ?>" href="<?= e(courierDeliveriesUrl()) ?>">Toutes</a><?php foreach ($statusLabels as $status => $label): ?><a class="<?= $selectedStatus === $status ? 'is-active' : '' ?>" href="<?= e(courierDeliveriesUrl($status)) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
  <?php if ($deliveries === []): ?>
    <div class="empty-state"><strong>Aucune mission dans cette vue</strong><span>Les nouvelles affectations apparaîtront ici dès que la boutique vous les confie.</span></div>
  <?php else: ?>
    <div class="courier-delivery-list">
      <?php foreach ($deliveries as $delivery): ?>
        <article class="courier-delivery-card courier-delivery-card--<?= e(mb_strtolower((string) $delivery['statut_livraison'])) ?>">
          <div class="courier-delivery-card__head"><div><span class="badge <?= e(deliveryStatusBadgeClass((string) $delivery['statut_livraison'])) ?>"><?= e(deliveryStatusLabel((string) $delivery['statut_livraison'])) ?></span><h2><?= e((string) $delivery['numero_commande']) ?></h2><p>Affectée le <?= e(date('d/m/Y à H:i', strtotime((string) $delivery['date_affectation']))) ?></p></div><strong><?= e(moneyFcfa($delivery['montant_total'])) ?></strong></div>
          <div class="courier-delivery-card__details"><div><span>Client</span><strong><?= e((string) $delivery['client']) ?></strong><a href="tel:<?= e((string) $delivery['contact_livraison']) ?>"><?= e((string) $delivery['contact_livraison']) ?></a></div><div><span>Zone et quartier</span><strong><?= e((string) ($delivery['zone_livraison'] ?? 'Zone à confirmer')) ?><?= !empty($delivery['quartier_livraison']) ? ' · ' . e((string) $delivery['quartier_livraison']) : '' ?></strong></div><div><span>Adresse complète</span><strong><?= e((string) $delivery['adresse_livraison']) ?></strong></div><div><span>Articles</span><strong><?= e((string) ($delivery['articles'] ?: 'Détail indisponible')) ?></strong></div><?php if (!empty($delivery['note_client'])): ?><div><span>Note du client</span><strong><?= e((string) $delivery['note_client']) ?></strong></div><?php endif; ?></div>
          <div class="courier-delivery-card__actions">
            <?php if ($delivery['statut_livraison'] === 'AFFECTEE'): ?>
              <form method="post" action="<?= e(url('actions/livraison.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id_livraison" value="<?= (int) $delivery['id_livraison'] ?>"><input type="hidden" name="action" value="accept"><button class="button button-yellow" type="submit">Accepter la mission</button></form>
              <form class="courier-reason-form" method="post" action="<?= e(url('actions/livraison.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id_livraison" value="<?= (int) $delivery['id_livraison'] ?>"><input type="hidden" name="action" value="refuse"><label for="motif-refus-<?= (int) $delivery['id_livraison'] ?>">Motif du refus</label><div><input id="motif-refus-<?= (int) $delivery['id_livraison'] ?>" name="motif" maxlength="500" placeholder="Ex. Indisponible pour ce créneau" required><button class="button button-soft button-small" type="submit">Refuser</button></div></form>
            <?php elseif ($delivery['statut_livraison'] === 'ACCEPTEE'): ?>
              <form method="post" action="<?= e(url('actions/livraison.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id_livraison" value="<?= (int) $delivery['id_livraison'] ?>"><input type="hidden" name="action" value="start"><button class="button button-yellow" type="submit">Je pars en livraison</button></form>
              <form class="courier-reason-form" method="post" action="<?= e(url('actions/livraison.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id_livraison" value="<?= (int) $delivery['id_livraison'] ?>"><input type="hidden" name="action" value="cancel"><label for="motif-annulation-<?= (int) $delivery['id_livraison'] ?>">Motif de l’annulation</label><div><input id="motif-annulation-<?= (int) $delivery['id_livraison'] ?>" name="motif" maxlength="500" placeholder="Ex. Incident avant départ" required><button class="button button-soft button-small" type="submit">Annuler la mission</button></div></form>
            <?php else: ?>
              <div class="courier-departure"><span>Départ signalé le <?= e(date('d/m/Y à H:i', strtotime((string) $delivery['date_depart']))) ?></span><form method="post" action="<?= e(url('actions/livraison.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id_livraison" value="<?= (int) $delivery['id_livraison'] ?>"><input type="hidden" name="action" value="complete"><button class="button button-yellow" type="submit">Déclarer la livraison effectuée</button></form></div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
