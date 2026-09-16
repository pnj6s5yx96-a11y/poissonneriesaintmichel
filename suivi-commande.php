<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$orderNumber = '';
$order = null;
$history = [];
$notifications = [];
$lookupError = null;

$requestedOrderNumber = queryString('numero', 40);
if ($requestedOrderNumber !== '') {
    try {
        $orderNumber = queryOrderNumber('numero');
        $pdo = db();
        enforceRateLimit($pdo, 'suivi_commande', '', 20, 300);
        $statement = $pdo->prepare(
            'SELECT c.id_commande, c.id_client, c.numero_commande, c.date_commande, c.statut_courant,
                    c.mode_retrait, c.montant_total, c.frais_livraison, c.confirmation_reception, c.date_confirmation_reception,
                    p.mode_paiement, p.statut_paiement,
                    z.libelle AS zone_livraison, q.libelle AS quartier_livraison, j.numero_document AS numero_facture,
                    (SELECT l.statut_livraison
                       FROM livraisons l
                      WHERE l.id_commande = c.id_commande
                      ORDER BY l.id_livraison DESC
                      LIMIT 1) AS statut_livraison
               FROM commandes c
               LEFT JOIN paiements p ON p.id_commande = c.id_commande
               LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
               LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
               LEFT JOIN justificatifs_paiement j ON j.id_paiement = p.id_paiement AND j.type_document = \'FACTURE\'
              WHERE c.numero_commande = :numero'
        );
        $statement->execute(['numero' => $orderNumber]);
        $order = $statement->fetch();
        if (!$order) {
            $lookupError = 'Aucune commande ne correspond à ce numéro.';
        } else {
            $historyStatement = $pdo->prepare(
                'SELECT nouveau_statut, origine, commentaire, date_changement
                   FROM historique_statuts_commande
                  WHERE id_commande = :commande
                  ORDER BY date_changement ASC, id_historique_statut ASC'
            );
            $historyStatement->execute(['commande' => (int) $order['id_commande']]);
            $history = $historyStatement->fetchAll();

            $notificationStatement = $pdo->prepare(
                'SELECT message, date_creation
                   FROM notifications
                  WHERE id_commande = :commande
                    AND id_client = :client
                  ORDER BY date_creation DESC
                  LIMIT 5'
            );
            $notificationStatement->execute(['commande' => (int) $order['id_commande'], 'client' => (int) $order['id_client']]);
            $notifications = $notificationStatement->fetchAll();
        }
    } catch (SecurityRateLimitException $exception) {
        $lookupError = $exception->getMessage();
    } catch (InvalidArgumentException $exception) {
        $lookupError = 'Le numéro de commande est invalide.';
    } catch (Throwable $exception) {
        $lookupError = 'Le suivi est momentanément indisponible. Réessayez dans quelques instants.';
    }
}

$timeline = ['EN_ATTENTE', 'EN_COURS_TRAITEMENT', 'TRAITEE'];
if ($order && $order['mode_retrait'] === 'LIVRAISON') {
    $timeline[] = 'EN_COURS_LIVRAISON';
}
$timeline[] = 'LIVREE';
$historyByStatus = [];
foreach ($history as $event) {
    $historyByStatus[$event['nouveau_statut']] = $event;
}
$currentStatusIndex = $order ? array_search($order['statut_courant'], $timeline, true) : false;
$canConfirmReception = $order
    && $order['statut_paiement'] === 'REUSSI'
    && (($order['statut_courant'] === 'EN_COURS_LIVRAISON' && $order['statut_livraison'] === 'LIVREE')
        || ($order['statut_courant'] === 'TRAITEE' && $order['mode_retrait'] === 'RETRAIT_BOUTIQUE'));
$canViewInvoice = $order
    && !empty($order['numero_facture'])
    && clientCanAccessOrder((int) $order['id_client'], (string) $order['numero_commande']);
$pageTitle = 'Suivre ma commande — Poissonnerie Saint-Michel';
$activePage = 'suivi';
require __DIR__ . '/includes/header.php';
?>
<section class="tracking-hero">
  <div><p class="eyebrow">Suivi de commande</p><h1>Votre commande, étape par étape.</h1><p>Renseignez son numéro pour connaître son état et confirmer sa réception lorsqu’elle vous est remise.</p></div>
  <form class="tracking-search" method="get" action="<?= e(url('suivi-commande.php')) ?>"><label for="numero">Numéro de commande</label><div><input id="numero" name="numero" value="<?= e($orderNumber) ?>" maxlength="40" placeholder="Ex. PSM-20260831-ABC123" required><button class="button button-yellow" type="submit">Rechercher</button></div></form>
</section>

<?php if ($lookupError !== null): ?>
  <section class="section notice"><?= e($lookupError) ?></section>
<?php elseif ($order !== null): ?>
  <section class="section tracking-order-head panel"><div><span class="order-number"><?= e((string) $order['numero_commande']) ?></span><h2><?= e(orderStatusLabel((string) $order['statut_courant'])) ?></h2><p>Commande enregistrée le <?= e(date('d/m/Y à H:i', strtotime((string) $order['date_commande']))) ?></p></div><div><span class="badge <?= e(orderStatusBadgeClass((string) $order['statut_courant'])) ?>"><?= e(orderStatusLabel((string) $order['statut_courant'])) ?></span><strong><?= e(moneyFcfa($order['montant_total'])) ?></strong><small><?= $order['mode_retrait'] === 'LIVRAISON' ? 'Livraison à domicile' : 'Retrait en boutique' ?></small></div></section>

  <section class="section panel tracking-timeline"><div class="panel-heading"><div><h2>Avancement</h2><p>La boutique met à jour cette progression à chaque étape.</p></div></div><ol data-timeline-steps="<?= count($timeline) ?>">
    <?php foreach ($timeline as $index => $status): ?>
      <?php $event = $historyByStatus[$status] ?? null; $isComplete = $currentStatusIndex !== false && $index <= $currentStatusIndex; $isCurrent = $status === $order['statut_courant']; ?>
      <li class="<?= $isComplete ? 'is-complete' : '' ?><?= $isCurrent ? ' is-current' : '' ?>"><span><?= $isComplete ? '✓' : $index + 1 ?></span><div><strong><?= e(orderStatusLabel($status)) ?></strong><small><?= $event ? e(date('d/m/Y à H:i', strtotime((string) $event['date_changement']))) : 'À venir' ?></small><?php if ($event && !empty($event['commentaire'])): ?><p><?= e((string) $event['commentaire']) ?></p><?php endif; ?></div></li>
    <?php endforeach; ?>
  </ol></section>

  <section class="section tracking-details">
    <article class="panel"><div class="panel-heading"><div><h2>Paiement</h2><p><?= e(paymentModeLabel((string) ($order['mode_paiement'] ?? ''))) ?></p></div></div><span class="payment-state <?= $order['statut_paiement'] === 'REUSSI' ? 'payment-state--success' : 'payment-state--pending' ?>"><?= e(paymentStatusLabel((string) ($order['statut_paiement'] ?? 'EN_ATTENTE'))) ?></span><?php if ($order['mode_retrait'] === 'LIVRAISON'): ?><p class="tracking-zone">Zone <?= e((string) ($order['zone_livraison'] ?? 'à confirmer')) ?><?= !empty($order['quartier_livraison']) ? ' · ' . e((string) $order['quartier_livraison']) : '' ?> · frais <?= e(moneyFcfa($order['frais_livraison'])) ?></p><?php endif; ?><?php if ($canViewInvoice): ?><div class="form-actions"><a class="button button-soft button-small" href="<?= e(url('facture.php?commande=' . rawurlencode((string) $order['numero_commande']))) ?>">Voir ma facture</a><a class="text-link" href="<?= e(url('retrouver-facture.php')) ?>">Facture perdue ?</a></div><?php elseif (($order['mode_paiement'] ?? '') !== 'ESPECES' && ($order['statut_paiement'] ?? '') === 'EN_ATTENTE' && clientCanAccessOrder((int) $order['id_client'], (string) $order['numero_commande'])): ?><div class="form-actions"><a class="button button-yellow button-small" href="<?= e(url('paiement-commande.php?commande=' . rawurlencode((string) $order['numero_commande']))) ?>">Finaliser le paiement</a></div><?php endif; ?></article>
    <article class="panel"><div class="panel-heading"><div><h2>Réception</h2><p><?= $order['mode_retrait'] === 'LIVRAISON' ? 'À confirmer après la livraison effectuée par le livreur.' : 'À confirmer après le retrait en boutique.' ?></p></div></div><?php if ((int) $order['confirmation_reception'] === 1): ?><span class="payment-state payment-state--success">✓ Réception confirmée le <?= e(date('d/m/Y à H:i', strtotime((string) $order['date_confirmation_reception']))) ?></span><?php elseif ($canConfirmReception): ?><form method="post" action="<?= e(url('actions/confirmer-reception.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="numero_commande" value="<?= e((string) $order['numero_commande']) ?>"><div class="field"><label for="telephone_confirmation">Votre téléphone</label><input id="telephone_confirmation" name="telephone" maxlength="30" inputmode="tel" placeholder="Celui renseigné à la commande" required></div><div class="form-actions"><button class="button button-yellow button-small" type="submit">Je confirme avoir reçu ma commande</button></div></form><?php else: ?><p class="empty-inline">La confirmation sera disponible dès que votre commande sera remise et que le paiement aura été confirmé.</p><?php endif; ?></article>
  </section>

  <?php if ($notifications !== []): ?><section class="section panel customer-notifications"><div class="panel-heading"><div><h2>Vos derniers messages</h2><p>Informations relatives à cette commande.</p></div></div><ul><?php foreach ($notifications as $notification): ?><li><span><?= e((string) $notification['message']) ?></span><small><?= e(date('d/m/Y à H:i', strtotime((string) $notification['date_creation']))) ?></small></li><?php endforeach; ?></ul></section><?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
