<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/database.php';

$products = [];
$settings = [
    'nom_boutique' => 'Poissonnerie Saint-Michel',
    'adresse_boutique' => 'Akpakpa, Cotonou',
    'horaires' => null,
    'est_livraison_active' => 0,
];
$deliveryZones = [];
$deliveryQuarters = [];
$databaseError = null;

try {
    $pdo = db();
    $products = clientCartProducts($pdo);
    $storedSettings = $pdo->query('SELECT * FROM parametres_boutique WHERE id_parametre = 1')->fetch();
    if ($storedSettings) {
        $settings = array_replace($settings, $storedSettings);
    }
    $deliveryZones = $pdo->query(
        'SELECT id_zone_livraison, libelle, description, frais_livraison
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
          WHERE q.est_actif = 1
            AND z.est_active = 1
          ORDER BY z.frais_livraison, z.libelle, q.arrondissement, q.libelle'
    )->fetchAll();
} catch (Throwable $exception) {
    $databaseError = 'La validation est momentanément indisponible. Réessayez dans quelques instants.';
}

$summary = clientCartSummary($products);
if (!$summary['is_valid'] || $databaseError !== null) {
    if ($databaseError === null) {
        flash('warning', 'Vérifiez les articles et les quantités de votre panier avant de continuer.');
    }
    redirect('panier.php');
}

$checkout = $_SESSION['client_checkout'] ?? [];
unset($_SESSION['client_checkout']);
$deliveryAvailable = (int) $settings['est_livraison_active'] === 1 && $deliveryZones !== [] && $deliveryQuarters !== [];
$selectedRetrieval = (string) ($checkout['mode_retrait'] ?? 'RETRAIT_BOUTIQUE');
if ($selectedRetrieval === 'LIVRAISON' && !$deliveryAvailable) {
    $selectedRetrieval = 'RETRAIT_BOUTIQUE';
}
$selectedPayment = (string) ($checkout['mode_paiement'] ?? 'MTN_MOMO');
$allowedPayments = ['MTN_MOMO', 'MOOV_MONEY', 'CELTIS_CASH', 'ESPECES'];
if (!in_array($selectedPayment, $allowedPayments, true)) {
    $selectedPayment = 'MTN_MOMO';
}
$selectedZoneId = filter_var($checkout['id_zone_livraison'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$selectedZoneId = $selectedZoneId === false ? null : $selectedZoneId;
$selectedZone = null;
foreach ($deliveryZones as $zone) {
    if ((int) $zone['id_zone_livraison'] === $selectedZoneId) {
        $selectedZone = $zone;
        break;
    }
}
$selectedQuarterId = filter_var($checkout['id_quartier_livraison'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$selectedQuarterId = $selectedQuarterId === false ? null : $selectedQuarterId;
$selectedQuarter = null;
foreach ($deliveryQuarters as $quarter) {
    if ((int) $quarter['id_quartier_livraison'] === $selectedQuarterId
        && $selectedZone !== null
        && (int) $quarter['id_zone_livraison'] === (int) $selectedZone['id_zone_livraison']) {
        $selectedQuarter = $quarter;
        break;
    }
}
$deliveryFee = $selectedRetrieval === 'LIVRAISON' && $selectedZone !== null ? (float) $selectedZone['frais_livraison'] : 0.0;
$pageTitle = 'Finaliser ma commande — Poissonnerie Saint-Michel';
$metaDescription = 'Validation sécurisée d’une commande Saint-Michel : coordonnées, retrait en boutique ou livraison à Cotonou et paiement.';
$seoIndexable = false;
$activePage = 'validation';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
  <p class="breadcrumbs"><a href="<?= e(url('panier.php')) ?>">Panier</a> / Finalisation</p>
  <p class="eyebrow">Dernière étape</p>
  <h1>Où et comment souhaitez-vous recevoir votre commande&nbsp;?</h1>
  <p>Vous n’avez pas besoin de créer un compte. Ces informations servent uniquement à préparer et suivre cette commande.</p>
</section>

<form class="checkout-layout section" method="post" action="<?= e(url('actions/client-commande.php')) ?>" data-checkout-form data-products-total="<?= e((string) $summary['articles']) ?>">
  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
  <div class="checkout-form">
    <section class="panel">
      <div class="panel-heading"><div><h2>1. Vos coordonnées</h2><p>Le téléphone sert au suivi. Votre e-mail permet de vous renvoyer votre facture si elle est perdue.</p></div></div>
      <div class="form-grid">
        <div class="field"><label for="nom_client">Nom complet</label><input id="nom_client" name="nom_client" maxlength="150" autocomplete="name" value="<?= e((string) ($checkout['nom_client'] ?? '')) ?>" required></div>
        <div class="field"><label for="telephone_client">Téléphone</label><input id="telephone_client" name="telephone_client" maxlength="30" inputmode="tel" autocomplete="tel" value="<?= e((string) ($checkout['telephone_client'] ?? '')) ?>" required></div>
        <div class="field full"><label for="email_client">E-mail <small>obligatoire — utilisé pour le renvoi de votre facture</small></label><input id="email_client" name="email_client" type="email" maxlength="191" autocomplete="email" value="<?= e((string) ($checkout['email_client'] ?? '')) ?>" required></div>
      </div>
    </section>

    <section class="panel">
      <div class="panel-heading"><div><h2>2. Retrait ou livraison</h2><p>Choisissez la solution la plus pratique pour vous.</p></div></div>
      <div class="choice-grid">
        <label class="choice-card"><input type="radio" name="mode_retrait" value="RETRAIT_BOUTIQUE" <?= $selectedRetrieval === 'RETRAIT_BOUTIQUE' ? 'checked' : '' ?> data-retrieval-choice><span><strong>Retrait en boutique</strong><small><?= e((string) $settings['adresse_boutique']) ?><?= !empty($settings['horaires']) ? ' · ' . e((string) $settings['horaires']) : '' ?></small></span></label>
        <?php if ($deliveryAvailable): ?>
          <label class="choice-card"><input type="radio" name="mode_retrait" value="LIVRAISON" <?= $selectedRetrieval === 'LIVRAISON' ? 'checked' : '' ?> data-retrieval-choice><span><strong>Livraison à domicile</strong><small>Choisissez votre zone pour calculer automatiquement les frais.</small></span></label>
        <?php endif; ?>
      </div>
      <div class="delivery-details" data-delivery-address<?= $selectedRetrieval === 'LIVRAISON' ? '' : ' hidden' ?>>
        <div class="field"><label for="id_zone_livraison">Zone de livraison</label><select id="id_zone_livraison" name="id_zone_livraison" data-delivery-zone<?= $selectedRetrieval === 'LIVRAISON' ? ' required' : '' ?>><option value="">Sélectionnez votre zone</option><?php foreach ($deliveryZones as $zone): ?><option value="<?= (int) $zone['id_zone_livraison'] ?>" data-fee="<?= e((string) $zone['frais_livraison']) ?>" data-description="<?= e((string) ($zone['description'] ?? '')) ?>" <?= $selectedZone !== null && (int) $selectedZone['id_zone_livraison'] === (int) $zone['id_zone_livraison'] ? 'selected' : '' ?>><?= e((string) $zone['libelle']) ?> — <?= e(moneyFcfa($zone['frais_livraison'])) ?></option><?php endforeach; ?></select><small data-delivery-zone-description><?= $selectedZone && !empty($selectedZone['description']) ? e((string) $selectedZone['description']) : 'Le coût sera ajouté au total de votre commande.' ?></small></div>
        <div class="field" data-delivery-quarter-wrap<?= $selectedRetrieval === 'LIVRAISON' ? '' : ' hidden' ?>><label for="id_quartier_livraison">Quartier de livraison</label><select id="id_quartier_livraison" name="id_quartier_livraison" data-delivery-quarter<?= $selectedRetrieval === 'LIVRAISON' && $selectedZone !== null ? ' required' : ' disabled' ?>><option value="">Sélectionnez d’abord votre zone</option><?php foreach ($deliveryQuarters as $quarter): ?><?php $belongsToSelectedZone = $selectedZone !== null && (int) $quarter['id_zone_livraison'] === (int) $selectedZone['id_zone_livraison']; ?><option value="<?= (int) $quarter['id_quartier_livraison'] ?>" data-zone="<?= (int) $quarter['id_zone_livraison'] ?>"<?= $belongsToSelectedZone ? '' : ' hidden disabled' ?><?= $selectedQuarter !== null && (int) $selectedQuarter['id_quartier_livraison'] === (int) $quarter['id_quartier_livraison'] ? ' selected' : '' ?>><?= e((string) $quarter['libelle']) ?><?= !empty($quarter['arrondissement']) ? ' · ' . e((string) $quarter['arrondissement']) : '' ?></option><?php endforeach; ?></select><small>Seuls les quartiers de la zone choisie sont proposés.</small></div>
        <div class="field full"><label for="adresse_livraison">Description complète de l’adresse</label><textarea id="adresse_livraison" name="adresse_livraison" maxlength="500" placeholder="Rue, maison, point de repère, étage ou toute indication utile…" <?= $selectedRetrieval === 'LIVRAISON' ? 'required' : '' ?>><?= e((string) ($checkout['adresse_livraison'] ?? '')) ?></textarea><small>Indiquez les précisions nécessaires pour que le livreur puisse vous retrouver.</small></div>
      </div>
    </section>

    <section class="panel">
      <div class="panel-heading"><div><h2>3. Mode de paiement</h2><p>Les paiements Mobile Money sont confirmés en ligne ; les espèces se règlent à la remise.</p></div></div>
      <div class="choice-grid payment-choice-grid">
        <?php foreach (['MTN_MOMO', 'MOOV_MONEY', 'CELTIS_CASH', 'ESPECES'] as $paymentMode): ?>
          <label class="choice-card"><input type="radio" name="mode_paiement" value="<?= e($paymentMode) ?>" <?= $selectedPayment === $paymentMode ? 'checked' : '' ?>><span><strong><?= e(paymentModeLabel($paymentMode)) ?></strong><small><?= $paymentMode === 'ESPECES' ? 'À régler au comptoir ou à la réception.' : 'Une facture sera disponible après confirmation.' ?></small></span></label>
        <?php endforeach; ?>
      </div>
      <div class="field full checkout-note"><label for="note_client">Une précision pour la boutique <small>facultatif</small></label><textarea id="note_client" name="note_client" maxlength="500" placeholder="Ex. Merci d’appeler avant la livraison."><?= e((string) ($checkout['note_client'] ?? '')) ?></textarea></div>
    </section>
  </div>

  <aside class="checkout-summary panel">
    <h2>Votre commande</h2>
    <ul>
      <?php foreach ($products as $product): ?><li><span><?= e((string) $product['libelle']) ?> <small>× <?= number_format((float) $product['quantite_panier'], 3, ',', ' ') ?></small></span><strong><?= e(moneyFcfa($product['sous_total'])) ?></strong></li><?php endforeach; ?>
    </ul>
    <div><span>Articles</span><strong><?= e(moneyFcfa($summary['articles'])) ?></strong></div>
    <div><span>Livraison</span><strong data-checkout-delivery><?= e(moneyFcfa($deliveryFee)) ?></strong></div>
    <div class="checkout-summary__total"><span>Total à régler</span><strong data-checkout-total><?= e(moneyFcfa($summary['articles'] + $deliveryFee)) ?></strong></div>
    <p class="checkout-zone-notice" data-checkout-zone-notice<?= $selectedRetrieval === 'LIVRAISON' ? '' : ' hidden' ?>>Le coût de livraison pourra être ajusté par la boutique si la zone choisie ne correspond pas à l’adresse indiquée. Vous serez informé avant le paiement.</p>
    <button class="button button-yellow checkout-summary__submit" type="submit">Valider ma commande</button>
    <a class="text-link checkout-summary__back" href="<?= e(url('panier.php')) ?>">← Modifier le panier</a>
  </aside>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
