<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

$statuses = [
    'EN_ATTENTE' => 'En attente',
    'EN_COURS_TRAITEMENT' => 'En cours de traitement',
    'TRAITEE' => 'Traitée',
    'EN_COURS_LIVRAISON' => 'En cours de livraison',
    'LIVREE' => 'Livrée',
];
$sources = ['EN_LIGNE' => 'En ligne', 'COMPTOIR' => 'Comptoir'];
$selectedStatus = queryEnum('statut', array_keys($statuses));
$selectedSource = queryEnum('origine', array_keys($sources));
$page = queryPositiveInt('page', 1, 10_000) ?? 1;
$perPage = 20;
$orders = [];
$totalOrders = 0;
$deliveryZones = [];
$deliveryQuarters = [];

try {
    $pdo = db();
    $deliveryZones = $pdo->query(
        'SELECT id_zone_livraison, libelle, frais_livraison
           FROM zones_livraison
          WHERE est_active = 1
            AND EXISTS (
                SELECT 1 FROM quartiers_livraison q
                 WHERE q.id_zone_livraison = zones_livraison.id_zone_livraison
                   AND q.est_actif = 1
            )
          ORDER BY frais_livraison, libelle'
    )->fetchAll();
    $deliveryQuarters = $pdo->query(
        'SELECT q.id_quartier_livraison, q.id_zone_livraison, q.libelle, q.arrondissement
           FROM quartiers_livraison q
           JOIN zones_livraison z ON z.id_zone_livraison = q.id_zone_livraison
          WHERE q.est_actif = 1 AND z.est_active = 1
          ORDER BY id_zone_livraison, arrondissement, libelle'
    )->fetchAll();
    $conditions = [];
    $parameters = [];
    if ($selectedStatus !== '') {
        $conditions[] = 'co.statut_courant = :statut';
        $parameters['statut'] = $selectedStatus;
    }
    if ($selectedSource !== '') {
        $conditions[] = 'co.origine_commande = :origine';
        $parameters['origine'] = $selectedSource;
    }
    $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM commandes co' . $where);
    $countStatement->execute($parameters);
    $totalOrders = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalOrders / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $statement = $pdo->prepare(
        'SELECT co.id_commande, co.numero_commande, co.date_commande, co.origine_commande,
                co.statut_courant, co.mode_retrait, co.id_zone_livraison, co.id_quartier_livraison, co.adresse_livraison,
                co.frais_livraison, co.montant_total, co.note_client, z.libelle AS zone_livraison,
                q.libelle AS quartier_livraison, q.arrondissement AS arrondissement_quartier,
                cl.nom_complet AS client, cl.telephone,
                pa.mode_paiement, pa.statut_paiement,
                (SELECT GROUP_CONCAT(CONCAT(pr.libelle, \' × \', lc.quantite, \' \', LOWER(pr.unite_vente))
                                     ORDER BY pr.libelle SEPARATOR \' · \')
                   FROM lignes_commande lc
                   JOIN produits pr ON pr.id_produit = lc.id_produit
                  WHERE lc.id_commande = co.id_commande) AS articles
           FROM commandes co
           JOIN clients cl ON cl.id_client = co.id_client
           LEFT JOIN paiements pa ON pa.id_commande = co.id_commande'
         . ' LEFT JOIN zones_livraison z ON z.id_zone_livraison = co.id_zone_livraison'
         . ' LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = co.id_quartier_livraison'
         . $where
         . ' ORDER BY co.date_commande DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
    );
    $statement->execute($parameters);
    $orders = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger les commandes. Vérifiez la migration de la base de données.');
}

function managerNextOrderAction(array $order): ?array
{
    return match ($order['statut_courant']) {
        'EN_ATTENTE' => ['status' => 'EN_COURS_TRAITEMENT', 'label' => 'Commencer le traitement'],
        'EN_COURS_TRAITEMENT' => ['status' => 'TRAITEE', 'label' => 'Marquer comme traitée'],
        'TRAITEE' => $order['mode_retrait'] === 'RETRAIT_BOUTIQUE'
            ? ['status' => 'LIVREE', 'label' => 'Remettre au client']
            : null,
        default => null,
    };
}

function managerOrdersUrl(string $status = '', string $source = '', int $page = 1): string
{
    $query = array_filter([
        'statut' => $status !== '' ? $status : null,
        'origine' => $source !== '' ? $source : null,
        'page' => $page > 1 ? $page : null,
    ], static fn ($value): bool => $value !== null);

    return url('gerant/commandes.php' . ($query === [] ? '' : '?' . http_build_query($query)));
}

$isAdministrator = currentUser()['role'] === 'ADMINISTRATEUR';
$portalPath = $isAdministrator ? 'admin/index.php' : 'gerant/index.php';
$portalLabel = $isAdministrator ? 'Administration' : 'Espace gérant';
$totalPages = max(1, (int) ceil($totalOrders / $perPage));
$pageTitle = 'Commandes — Gestion des ventes';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
  <p class="breadcrumbs"><a href="<?= e(url($portalPath)) ?>"><?= e($portalLabel) ?></a> / Commandes</p>
  <p class="eyebrow">Traitement des ventes</p>
  <h1>Commandes en ligne et au comptoir.</h1>
  <p>Retrouvez toutes les commandes au même endroit, faites avancer leur préparation et finalisez la remise ou la livraison.</p>
</section>

<section class="section panel">
  <div class="panel-heading">
    <div><h2>Commandes reçues</h2><p><?= $totalOrders ?> commande<?= $totalOrders > 1 ? 's' : '' ?> au total.</p></div>
    <a class="button button-yellow button-small" href="<?= e(url('gerant/nouvelle-commande.php')) ?>">Nouvelle vente comptoir</a>
  </div>

  <div class="filter-pills" aria-label="Filtrer par statut">
    <a class="<?= $selectedStatus === '' ? 'is-active' : '' ?>" href="<?= e(managerOrdersUrl('', $selectedSource)) ?>">Tous statuts</a>
    <?php foreach ($statuses as $status => $label): ?>
      <a class="<?= $selectedStatus === $status ? 'is-active' : '' ?>" href="<?= e(managerOrdersUrl($status, $selectedSource)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="filter-pills" aria-label="Filtrer par origine">
    <a class="<?= $selectedSource === '' ? 'is-active' : '' ?>" href="<?= e(managerOrdersUrl($selectedStatus)) ?>">Toutes origines</a>
    <?php foreach ($sources as $source => $label): ?>
      <a class="<?= $selectedSource === $source ? 'is-active' : '' ?>" href="<?= e(managerOrdersUrl($selectedStatus, $source)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($orders === []): ?>
    <div class="empty-state"><strong>Aucune commande dans cette vue</strong><span>Les commandes clients et les ventes comptoir apparaîtront ici.</span></div>
  <?php else: ?>
    <div class="data-table-wrap"><table class="data-table orders-table"><thead><tr><th>Commande</th><th>Client &amp; articles</th><th>Retrait</th><th>Paiement</th><th>Total</th><th>Statut</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($orders as $order): ?>
      <?php $nextAction = managerNextOrderAction($order); $paymentConfirmed = $order['statut_paiement'] === 'REUSSI'; ?>
      <tr>
        <td><strong><?= e($order['numero_commande']) ?></strong><br><small><?= e(date('d/m/Y H:i', strtotime($order['date_commande']))) ?></small><br><span class="badge <?= $order['origine_commande'] === 'COMPTOIR' ? 'badge-info' : 'badge-muted' ?>"><?= e($sources[$order['origine_commande']] ?? 'En ligne') ?></span></td>
        <td><strong><?= e($order['client']) ?></strong><br><small><?= e($order['telephone']) ?></small><br><span class="table-detail"><?= e($order['articles'] ?: 'Articles non disponibles') ?></span><?php if (!empty($order['note_client'])): ?><br><small>Note : <?= e($order['note_client']) ?></small><?php endif; ?></td>
        <td><?= $order['mode_retrait'] === 'LIVRAISON' ? 'Livraison' : 'Retrait boutique' ?><?php if ($order['mode_retrait'] === 'LIVRAISON'): ?><?php if (!empty($order['adresse_livraison'])): ?><br><small><?= e($order['adresse_livraison']) ?></small><?php endif; ?><br><strong class="delivery-zone-label">Zone : <?= e((string) ($order['zone_livraison'] ?? 'Non renseignée')) ?><?= !empty($order['quartier_livraison']) ? ' · ' . e((string) $order['quartier_livraison']) : '' ?> · <?= e(moneyFcfa($order['frais_livraison'])) ?></strong><?php if ($order['statut_paiement'] === 'EN_ATTENTE' && $deliveryZones !== [] && $deliveryQuarters !== []): ?><form class="manager-zone-form" method="post" action="<?= e(url('actions/commande-zone-livraison.php')) ?>" data-manager-delivery-form><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id_commande" value="<?= (int) $order['id_commande'] ?>"><label for="zone-<?= (int) $order['id_commande'] ?>">Corriger la zone et le quartier</label><select id="zone-<?= (int) $order['id_commande'] ?>" name="id_zone_livraison" data-manager-delivery-zone required><?php foreach ($deliveryZones as $zone): ?><option value="<?= (int) $zone['id_zone_livraison'] ?>" <?= (int) $order['id_zone_livraison'] === (int) $zone['id_zone_livraison'] ? 'selected' : '' ?>><?= e((string) $zone['libelle']) ?> — <?= e(moneyFcfa($zone['frais_livraison'])) ?></option><?php endforeach; ?></select><select name="id_quartier_livraison" data-manager-delivery-quarter required><option value="">Choisir le quartier</option><?php foreach ($deliveryQuarters as $quarter): ?><?php $sameZone = (int) $quarter['id_zone_livraison'] === (int) $order['id_zone_livraison']; ?><option value="<?= (int) $quarter['id_quartier_livraison'] ?>" data-zone="<?= (int) $quarter['id_zone_livraison'] ?>"<?= $sameZone ? '' : ' hidden disabled' ?><?= (int) $order['id_quartier_livraison'] === (int) $quarter['id_quartier_livraison'] ? ' selected' : '' ?>><?= e((string) $quarter['libelle']) ?><?= !empty($quarter['arrondissement']) ? ' · ' . e((string) $quarter['arrondissement']) : '' ?></option><?php endforeach; ?></select><button class="button button-soft button-small" type="submit">Mettre à jour</button></form><?php elseif ($order['statut_paiement'] === 'REUSSI'): ?><small class="table-note">Zone et quartier verrouillés après paiement confirmé.</small><?php endif; ?><?php endif; ?></td>
        <td><?= e(paymentModeLabel($order['mode_paiement'])) ?><br><span class="badge <?= $paymentConfirmed ? 'badge-success' : 'badge-warning' ?>"><?= e(paymentStatusLabel((string) ($order['statut_paiement'] ?? 'EN_ATTENTE'))) ?></span></td>
        <td><strong><?= e(moneyFcfa($order['montant_total'])) ?></strong></td>
        <td><span class="badge <?= e(orderStatusBadgeClass($order['statut_courant'])) ?>"><?= e(orderStatusLabel($order['statut_courant'])) ?></span></td>
        <td class="actions">
          <?php if ($nextAction !== null && $nextAction['status'] === 'LIVREE' && !$paymentConfirmed): ?>
            <a class="button button-soft button-small" href="<?= e(url('gerant/paiements.php?filtre=ESPECES_ATTENTE')) ?>">Confirmer le paiement</a>
          <?php elseif ($nextAction !== null): ?>
            <form class="inline-form" method="post" action="<?= e(url('actions/statut-commande.php')) ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id_commande" value="<?= (int) $order['id_commande'] ?>"><input type="hidden" name="statut" value="<?= e($nextAction['status']) ?>">
              <button class="button button-small" type="submit"><?= e($nextAction['label']) ?></button>
            </form>
          <?php elseif ($order['statut_courant'] === 'TRAITEE' && $order['mode_retrait'] === 'LIVRAISON' && $paymentConfirmed): ?>
            <a class="button button-soft button-small" href="<?= e(url('gerant/livraisons.php')) ?>">Affecter un livreur</a>
          <?php elseif ($order['statut_courant'] === 'TRAITEE' && $order['mode_retrait'] === 'LIVRAISON'): ?>
            <a class="button button-soft button-small" href="<?= e(url('gerant/paiements.php?filtre=ESPECES_ATTENTE')) ?>">Paiement à confirmer</a>
          <?php else: ?>
            <span class="table-note">Aucune action</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagination des commandes">
      <?php for ($number = 1; $number <= $totalPages; $number++): ?>
        <a class="<?= $number === $page ? 'is-active' : '' ?>" href="<?= e(managerOrdersUrl($selectedStatus, $selectedSource, $number)) ?>"><?= $number ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>
<script nonce="<?= e(cspNonce()) ?>">
document.querySelectorAll('[data-manager-delivery-form]').forEach((form) => {
  const zone = form.querySelector('[data-manager-delivery-zone]');
  const quarter = form.querySelector('[data-manager-delivery-quarter]');
  const refresh = () => {
    const zoneId = zone.value;
    quarter.querySelectorAll('option[data-zone]').forEach((option) => {
      const allowed = option.dataset.zone === zoneId;
      option.hidden = !allowed;
      option.disabled = !allowed;
    });
    if (quarter.selectedOptions[0]?.dataset.zone !== zoneId) quarter.value = '';
  };
  zone.addEventListener('change', refresh);
  refresh();
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
