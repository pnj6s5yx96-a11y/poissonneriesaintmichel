<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$orderNumber = '';
$order = null;

try {
    $orderNumber = queryOrderNumber('commande');

    $statement = db()->prepare(
        'SELECT c.id_commande, c.id_client, c.numero_commande, c.montant_total, c.frais_livraison, c.mode_retrait,
                c.statut_courant, cl.nom_complet, cl.telephone,
                z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                p.id_paiement, p.mode_paiement, p.statut_paiement, p.reference_transaction,
                j.numero_document AS numero_facture
           FROM commandes c
           JOIN clients cl ON cl.id_client = c.id_client
           JOIN paiements p ON p.id_commande = c.id_commande
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
           LEFT JOIN justificatifs_paiement j ON j.id_paiement = p.id_paiement AND j.type_document = \'FACTURE\'
          WHERE c.numero_commande = :numero'
    );
    $statement->execute(['numero' => $orderNumber]);
    $order = $statement->fetch();
    if (!$order || !clientCanAccessOrder((int) $order['id_client'], (string) $order['numero_commande'])) {
        throw new RuntimeException('Cette page de paiement n’est plus accessible. Utilisez le suivi de commande avec votre numéro.');
    }
} catch (Throwable $exception) {
    flash('error', $exception->getMessage());
    redirect('suivi-commande.php');
}

$isCash = $order['mode_paiement'] === 'ESPECES';
$isSuccessful = $order['statut_paiement'] === 'REUSSI';
$pageTitle = 'Paiement de la commande — Poissonnerie Saint-Michel';
$activePage = 'paiement';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading payment-heading">
  <p class="breadcrumbs"><a href="<?= e(url('catalogue.php')) ?>">Catalogue</a> / Commande / Paiement</p>
  <p class="eyebrow">Commande enregistrée</p>
  <h1>Merci, votre commande est bien prise en compte.</h1>
  <p>Conservez votre numéro de commande : <strong><?= e((string) $order['numero_commande']) ?></strong>.</p>
</section>

<section class="section payment-layout">
  <div class="panel payment-panel">
    <?php if ($isSuccessful): ?>
      <span class="payment-state payment-state--success">✓ Paiement confirmé</span>
      <h2>Votre paiement a été confirmé.</h2>
      <p>Votre facture <?= e((string) $order['numero_facture']) ?> est prête. La boutique peut désormais traiter votre commande.</p>
      <div class="form-actions"><a class="button button-yellow" href="<?= e(url('facture.php?commande=' . rawurlencode((string) $order['numero_commande']))) ?>">Voir et télécharger la facture</a><a class="button button-soft" href="<?= e(url('suivi-commande.php?numero=' . rawurlencode((string) $order['numero_commande']))) ?>">Suivre ma commande</a></div>
      <p class="invoice-recovery-link">Facture perdue plus tard ? <a href="<?= e(url('retrouver-facture.php')) ?>">Demandez son renvoi à votre adresse e-mail.</a></p>
    <?php elseif ($isCash): ?>
      <span class="payment-state payment-state--pending">◷ Règlement en espèces</span>
      <h2>Réglez à la remise de votre commande.</h2>
      <p>Présentez le numéro <strong><?= e((string) $order['numero_commande']) ?></strong> au gérant, au comptoir ou lors de la réception. Un reçu vous sera remis après encaissement.</p>
      <div class="form-actions"><a class="button button-yellow" href="<?= e(url('suivi-commande.php?numero=' . rawurlencode((string) $order['numero_commande']))) ?>">Suivre ma commande</a><a class="button button-soft" href="<?= e(url('catalogue.php')) ?>">Retour au catalogue</a></div>
    <?php else: ?>
      <span class="payment-state payment-state--mobile">◈ <?= e(paymentModeLabel((string) $order['mode_paiement'])) ?></span>
      <h2>Confirmez votre règlement mobile.</h2>
      <p>Montant à régler : <strong><?= e(moneyFcfa($order['montant_total'])) ?></strong>. Une facture téléchargeable sera générée dès la confirmation.</p>
      <div class="notice payment-demo-notice"><strong>Mode démonstration</strong> — cette installation locale simule la réponse de <?= e(paymentModeLabel((string) $order['mode_paiement'])) ?>. Le branchement réel nécessite les identifiants API de l’opérateur.</div>
      <form class="payment-confirm-form" method="post" action="<?= e(url('actions/paiement-client.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="numero_commande" value="<?= e((string) $order['numero_commande']) ?>">
        <div class="field"><label for="telephone_paiement">Numéro utilisé pour le paiement</label><input id="telephone_paiement" name="telephone_paiement" maxlength="30" inputmode="tel" value="<?= e((string) $order['telephone']) ?>" required></div>
        <div class="form-actions"><button class="button button-yellow" type="submit">Confirmer le paiement de <?= e(moneyFcfa($order['montant_total'])) ?></button><a class="button button-soft" href="<?= e(url('suivi-commande.php?numero=' . rawurlencode((string) $order['numero_commande']))) ?>">Payer plus tard</a></div>
      </form>
    <?php endif; ?>
  </div>
  <aside class="payment-summary panel">
    <h2>Récapitulatif</h2>
    <div><span>Commande</span><strong><?= e((string) $order['numero_commande']) ?></strong></div>
    <div><span>Montant</span><strong><?= e(moneyFcfa($order['montant_total'])) ?></strong></div>
    <div><span>Récupération</span><strong><?= $order['mode_retrait'] === 'LIVRAISON' ? 'Livraison' : 'Retrait boutique' ?></strong></div>
    <?php if ($order['mode_retrait'] === 'LIVRAISON'): ?><div><span>Zone de livraison</span><strong><?= e((string) ($order['zone_livraison'] ?? 'À confirmer')) ?><?= !empty($order['quartier_livraison']) ? ' · ' . e((string) $order['quartier_livraison']) : '' ?> · <?= e(moneyFcfa($order['frais_livraison'])) ?></strong></div><?php endif; ?>
    <div><span>Paiement</span><strong><?= e(paymentModeLabel((string) $order['mode_paiement'])) ?></strong></div>
  </aside>
</section>
<?php if ($order['mode_retrait'] === 'LIVRAISON'): ?><section class="section notice payment-zone-notice">La boutique peut corriger la zone de livraison si elle ne correspond pas à l’adresse fournie. Dans ce cas, votre total sera mis à jour avant la confirmation du paiement.</section><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
