<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

$allowedFilters = ['TOUS', 'ESPECES_ATTENTE', 'REUSSIS'];
$filter = queryEnum('filtre', $allowedFilters, 'TOUS');

$payments = [];
$metrics = ['cash_pending' => 0, 'successful_today' => 0];
try {
    $pdo = db();
    $metrics['cash_pending'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM paiements WHERE mode_paiement = 'ESPECES' AND statut_paiement = 'EN_ATTENTE'"
    )->fetchColumn();
    $metrics['successful_today'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM paiements WHERE statut_paiement = 'REUSSI' AND DATE(date_confirmation) = CURDATE()"
    )->fetchColumn();
    $where = match ($filter) {
        'ESPECES_ATTENTE' => "WHERE p.mode_paiement = 'ESPECES' AND p.statut_paiement = 'EN_ATTENTE'",
        'REUSSIS' => "WHERE p.statut_paiement = 'REUSSI'",
        default => '',
    };
    $payments = $pdo->query(
        'SELECT p.id_paiement, p.mode_paiement, p.statut_paiement, p.montant, p.reference_transaction,
                p.date_initiation, p.date_confirmation, c.numero_commande,
                cl.nom_complet AS client, cl.telephone,
                j.numero_document, j.type_document
           FROM paiements p
           JOIN commandes c ON c.id_commande = p.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
           LEFT JOIN justificatifs_paiement j ON j.id_paiement = p.id_paiement '
         . $where
         . ' ORDER BY p.date_initiation DESC LIMIT 100'
    )->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger les paiements.');
}

$isAdministrator = currentUser()['role'] === 'ADMINISTRATEUR';
$portalPath = $isAdministrator ? 'admin/index.php' : 'gerant/index.php';
$portalLabel = $isAdministrator ? 'Administration' : 'Espace gérant';
$pageTitle = 'Paiements — Gestion des ventes';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
  <p class="breadcrumbs"><a href="<?= e(url($portalPath)) ?>"><?= e($portalLabel) ?></a> / Paiements</p>
  <p class="eyebrow">Encaissements</p>
  <h1>Des règlements traçables.</h1>
  <p>Validez les espèces reçues au comptoir. Chaque confirmation crée une référence, un reçu imprimable et une trace dans le journal d’audit.</p>
</section>

<section class="metrics compact-metrics" aria-label="Indicateurs des paiements">
  <article class="metric yellow"><span>Espèces à confirmer</span><strong><?= $metrics['cash_pending'] ?></strong></article>
  <article class="metric"><span>Paiements réussis aujourd’hui</span><strong><?= $metrics['successful_today'] ?></strong></article>
</section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Registre des paiements</h2><p>Les paiements mobile money restent consultables ; les espèces sont confirmées directement au comptoir.</p></div></div>
  <div class="filter-pills" aria-label="Filtrer les paiements">
    <a class="<?= $filter === 'TOUS' ? 'is-active' : '' ?>" href="<?= e(url('gerant/paiements.php')) ?>">Tous</a>
    <a class="<?= $filter === 'ESPECES_ATTENTE' ? 'is-active' : '' ?>" href="<?= e(url('gerant/paiements.php?filtre=ESPECES_ATTENTE')) ?>">Espèces à confirmer</a>
    <a class="<?= $filter === 'REUSSIS' ? 'is-active' : '' ?>" href="<?= e(url('gerant/paiements.php?filtre=REUSSIS')) ?>">Réussis</a>
  </div>

  <?php if ($payments === []): ?>
    <div class="empty-state"><strong>Aucun paiement dans cette vue</strong><span>Les règlements apparaîtront ici dès qu’une commande est enregistrée.</span></div>
  <?php else: ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Commande</th><th>Client</th><th>Mode</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($payments as $payment): ?>
      <tr>
        <td><strong><?= e($payment['numero_commande']) ?></strong><br><small><?= e(date('d/m/Y H:i', strtotime($payment['date_initiation']))) ?></small></td>
        <td><?= e($payment['client']) ?><br><small><?= e($payment['telephone']) ?></small></td>
        <td><?= e(paymentModeLabel($payment['mode_paiement'])) ?></td>
        <td><strong><?= e(moneyFcfa($payment['montant'])) ?></strong></td>
        <td><span class="badge <?= $payment['statut_paiement'] === 'REUSSI' ? 'badge-success' : ($payment['statut_paiement'] === 'EN_ATTENTE' ? 'badge-warning' : 'badge-danger') ?>"><?= e(paymentStatusLabel($payment['statut_paiement'])) ?></span></td>
        <td><?= e($payment['reference_transaction'] ?: '—') ?></td>
        <td class="actions">
          <?php if ($payment['mode_paiement'] === 'ESPECES' && $payment['statut_paiement'] === 'EN_ATTENTE'): ?>
            <form class="inline-form" method="post" action="<?= e(url('actions/paiement.php')) ?>">
              <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="confirm_cash"><input type="hidden" name="id_paiement" value="<?= (int) $payment['id_paiement'] ?>">
              <button class="button button-small" type="submit">Confirmer les espèces</button>
            </form>
          <?php elseif ($payment['type_document'] === 'RECU'): ?>
            <a class="button button-soft button-small" href="<?= e(url('gerant/justificatif.php?paiement=' . (int) $payment['id_paiement'])) ?>" target="_blank" rel="noopener">Imprimer le reçu</a>
          <?php else: ?>
            <span class="table-note">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
