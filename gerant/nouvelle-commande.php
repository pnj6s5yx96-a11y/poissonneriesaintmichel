<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

$products = [];
$deliveryZones = [];
$deliveryQuarters = [];
try {
    $pdo = db();
    $products = $pdo->query(
        'SELECT p.id_produit, p.libelle, p.prix_unitaire, p.unite_vente, p.quantite_stock, c.libelle AS categorie
           FROM produits p JOIN categories c ON c.id_categorie = p.id_categorie
          WHERE p.est_actif = 1 AND c.est_active = 1 AND p.quantite_stock > 0
          ORDER BY c.libelle, p.libelle'
    )->fetchAll();
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
          WHERE q.est_actif = 1 AND z.est_active = 1
          ORDER BY z.frais_livraison, z.libelle, q.arrondissement, q.libelle'
    )->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger les produits ou les zones de livraison.');
}

$isAdministrator = currentUser()['role'] === 'ADMINISTRATEUR';
$portalPath = $isAdministrator ? 'admin/index.php' : 'gerant/index.php';
$portalLabel = $isAdministrator ? 'Administration' : 'Espace gérant';
$pageTitle = 'Nouvelle commande comptoir — Gestion des ventes';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url($portalPath)) ?>"><?= e($portalLabel) ?></a> / <a href="<?= e(url('gerant/commandes.php')) ?>">Commandes</a> / Vente comptoir</p><p class="eyebrow">Vente en boutique</p><h1>Enregistrer une commande au comptoir.</h1><p>Les prix, zones tarifaires et quantités en stock sont vérifiés à nouveau au moment de l’enregistrement.</p></section>

<?php if ($products === []): ?>
  <section class="section panel"><div class="empty-state"><strong>Aucun produit vendable</strong><span>Ajoutez du stock et activez les produits avant d’enregistrer une vente comptoir.</span></div></section>
<?php else: ?>
<form class="counter-order-form" method="post" action="<?= e(url('actions/commande.php')) ?>">
  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="origine_commande" value="COMPTOIR">
  <section class="section panel"><div class="panel-heading"><div><h2>1. Articles</h2><p>Le montant affiché est indicatif ; le prix de la base est toujours appliqué.</p></div><button class="button button-soft button-small" type="button" data-add-order-line>Ajouter un article</button></div><div class="counter-order-lines" data-order-lines></div></section>
  <section class="section panel"><div class="panel-heading"><div><h2>2. Client et retrait</h2><p>Le téléphone permet de réutiliser automatiquement un client déjà enregistré.</p></div></div><div class="form-grid"><div class="field"><label for="nom_client">Nom complet</label><input id="nom_client" name="nom_client" maxlength="150" autocomplete="name" required></div><div class="field"><label for="telephone_client">Téléphone</label><input id="telephone_client" name="telephone_client" maxlength="30" inputmode="tel" autocomplete="tel" required></div><div class="field"><label for="email_client">E-mail <small>facultatif</small></label><input id="email_client" name="email_client" maxlength="191" type="email" autocomplete="email"></div><div class="field"><label for="mode_retrait">Mode de retrait</label><select id="mode_retrait" name="mode_retrait" data-retrieval-mode required><option value="RETRAIT_BOUTIQUE">Retrait en boutique</option><?php if ($deliveryZones !== [] && $deliveryQuarters !== []): ?><option value="LIVRAISON">Livraison à domicile</option><?php endif; ?></select></div><div class="field full" data-delivery-zone hidden><label for="id_zone_livraison">Zone de livraison</label><select id="id_zone_livraison" name="id_zone_livraison" data-counter-delivery-zone><option value="">Choisir la zone</option><?php foreach ($deliveryZones as $zone): ?><option value="<?= (int) $zone['id_zone_livraison'] ?>" data-fee="<?= e((string) $zone['frais_livraison']) ?>"><?= e((string) $zone['libelle']) ?> — <?= e(moneyFcfa($zone['frais_livraison'])) ?></option><?php endforeach; ?></select></div><div class="field full" data-delivery-quarter hidden><label for="id_quartier_livraison">Quartier de livraison</label><select id="id_quartier_livraison" name="id_quartier_livraison" data-counter-delivery-quarter disabled><option value="">Choisir d’abord la zone</option><?php foreach ($deliveryQuarters as $quarter): ?><option value="<?= (int) $quarter['id_quartier_livraison'] ?>" data-zone="<?= (int) $quarter['id_zone_livraison'] ?>" hidden disabled><?= e((string) $quarter['libelle']) ?><?= !empty($quarter['arrondissement']) ? ' · ' . e((string) $quarter['arrondissement']) : '' ?></option><?php endforeach; ?></select></div><div class="field full" data-delivery-address hidden><label for="adresse_livraison">Description complète de l’adresse</label><textarea id="adresse_livraison" name="adresse_livraison" maxlength="500" placeholder="Rue, maison, point de repère, étage ou toute indication utile…"></textarea></div><div class="field full"><label for="note_client">Note <small>facultative</small></label><textarea id="note_client" name="note_client" maxlength="500" placeholder="Ex. Client attend sa commande à 16 h."></textarea></div></div></section>
  <section class="section panel"><div class="panel-heading"><div><h2>3. Règlement et validation</h2><p>Le paiement est créé en attente. Les espèces sont ensuite confirmées dans le registre avec émission d’un reçu.</p></div></div><div class="form-grid"><div class="field"><label for="mode_paiement">Mode de paiement</label><select id="mode_paiement" name="mode_paiement" required><option value="ESPECES">Espèces</option><option value="MTN_MOMO">MTN Mobile Money</option><option value="MOOV_MONEY">Moov Money</option><option value="CELTIS_CASH">Celtis Cash</option></select></div><div class="order-total" aria-live="polite"><span>Total estimé</span><strong data-order-total>0 FCFA</strong><small data-order-total-detail>Articles : 0 FCFA</small></div></div><div class="form-actions"><button class="button button-yellow" type="submit">Enregistrer la commande</button><a class="button button-soft" href="<?= e(url('gerant/commandes.php')) ?>">Annuler</a></div></section>
</form>
<template id="counter-order-line-template"><div class="counter-order-line" data-order-line><div class="field"><label>Produit</label><select name="produits[]" data-product required><option value="">Choisir un produit</option><?php foreach ($products as $product): ?><option value="<?= (int) $product['id_produit'] ?>" data-price="<?= e((string) $product['prix_unitaire']) ?>" data-stock="<?= e((string) $product['quantite_stock']) ?>" data-unit="<?= e($product['unite_vente']) ?>"><?= e($product['categorie']) ?> · <?= e($product['libelle']) ?> — <?= e(moneyFcfa($product['prix_unitaire'])) ?> / <?= e(strtolower($product['unite_vente'])) ?> (<?= number_format((float) $product['quantite_stock'], 3, ',', ' ') ?> en stock)</option><?php endforeach; ?></select></div><div class="field"><label>Quantité</label><input name="quantites[]" type="number" min="0.001" step="0.001" inputmode="decimal" data-quantity required></div><div class="counter-order-line__summary" data-line-summary>—</div><button class="button button-danger button-small" type="button" data-remove-order-line>Supprimer</button></div></template>
<script nonce="<?= e(cspNonce()) ?>">
(() => {
  const lines = document.querySelector('[data-order-lines]'); const template = document.getElementById('counter-order-line-template'); const addButton = document.querySelector('[data-add-order-line]'); const mode = document.querySelector('[data-retrieval-mode]'); const zoneWrap = document.querySelector('[data-delivery-zone]'); const zone = document.querySelector('[data-counter-delivery-zone]'); const quarterWrap = document.querySelector('[data-delivery-quarter]'); const quarter = document.querySelector('[data-counter-delivery-quarter]'); const address = document.querySelector('[data-delivery-address]'); const addressInput = document.getElementById('adresse_livraison'); const total = document.querySelector('[data-order-total]'); const detail = document.querySelector('[data-order-total-detail]'); const format = new Intl.NumberFormat('fr-FR', {maximumFractionDigits: 0});
  const refresh = () => { let productsTotal = 0; lines.querySelectorAll('[data-order-line]').forEach((line) => { const option = line.querySelector('[data-product]').selectedOptions[0]; const quantity = Number.parseFloat(line.querySelector('[data-quantity]').value) || 0; const price = Number.parseFloat(option?.dataset.price || '0'); const stock = Number.parseFloat(option?.dataset.stock || '0'); const lineTotal = price * quantity; productsTotal += lineTotal; line.querySelector('[data-line-summary]').textContent = price > 0 && quantity > 0 ? `${format(lineTotal)} FCFA · stock : ${stock} ${option?.dataset.unit?.toLowerCase() || ''}` : 'Sélectionnez un produit et une quantité'; }); const isDelivery = mode.value === 'LIVRAISON'; const zoneId = zone?.value || ''; const deliveryFee = isDelivery ? (Number.parseFloat(zone?.selectedOptions[0]?.dataset.fee || '0') || 0) : 0; if (quarter) { quarter.querySelectorAll('option[data-zone]').forEach((option) => { const isAllowed = isDelivery && zoneId !== '' && option.dataset.zone === zoneId; option.hidden = !isAllowed; option.disabled = !isAllowed; }); if (quarter.selectedOptions[0]?.dataset.zone !== zoneId) quarter.value = ''; quarter.disabled = !isDelivery || zoneId === ''; quarter.required = isDelivery; } total.textContent = `${format(productsTotal + deliveryFee)} FCFA`; detail.textContent = `Articles : ${format(productsTotal)} FCFA${deliveryFee > 0 ? ` · Livraison : ${format(deliveryFee)} FCFA` : ''}`; };
  const addLine = () => { lines.append(template.content.cloneNode(true)); refresh(); };
  addButton.addEventListener('click', addLine); lines.addEventListener('input', refresh); lines.addEventListener('change', refresh); zone?.addEventListener('change', refresh); lines.addEventListener('click', (event) => { if (!event.target.closest('[data-remove-order-line]')) return; const line = event.target.closest('[data-remove-order-line]').closest('[data-order-line]'); if (lines.children.length === 1) { line.querySelector('[data-product]').value = ''; line.querySelector('[data-quantity]').value = ''; } else { line.remove(); } refresh(); }); mode.addEventListener('change', () => { const delivery = mode.value === 'LIVRAISON'; zoneWrap.hidden = !delivery; quarterWrap.hidden = !delivery; address.hidden = !delivery; zone.required = delivery; addressInput.required = delivery; refresh(); }); addLine();
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
