<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/invoice_pdf.php';

$orderNumber = '';
$invoice = null;
$lines = [];
$settings = ['nom_boutique' => 'Poissonnerie Saint-Michel', 'adresse_boutique' => 'Akpakpa, Cotonou'];

try {
    $orderNumber = queryOrderNumber('commande');

    $pdo = db();
    $statement = $pdo->prepare(
        'SELECT j.numero_document, j.date_emission, p.id_paiement, p.mode_paiement, p.montant,
                p.reference_transaction, p.date_confirmation, c.id_commande, c.id_client,
                c.numero_commande, c.mode_retrait, c.adresse_livraison, c.frais_livraison, c.total_produits,
                z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                cl.nom_complet AS client, cl.telephone, cl.email
           FROM justificatifs_paiement j
           JOIN paiements p ON p.id_paiement = j.id_paiement
           JOIN commandes c ON c.id_commande = p.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
          WHERE c.numero_commande = :numero
            AND j.type_document = \'FACTURE\'
            AND p.statut_paiement = \'REUSSI\''
    );
    $statement->execute(['numero' => $orderNumber]);
    $invoice = $statement->fetch();
    if (!$invoice || !clientCanAccessOrder((int) $invoice['id_client'], (string) $invoice['numero_commande'])) {
        throw new RuntimeException('Cette facture n’est pas accessible.');
    }

    $lineStatement = $pdo->prepare(
        'SELECT p.libelle, lc.quantite, p.unite_vente, lc.prix_unitaire_applique, lc.sous_total
           FROM lignes_commande lc
           JOIN produits p ON p.id_produit = lc.id_produit
          WHERE lc.id_commande = :commande
          ORDER BY lc.id_ligne_commande'
    );
    $lineStatement->execute(['commande' => (int) $invoice['id_commande']]);
    $lines = $lineStatement->fetchAll();
    $storedSettings = $pdo->query('SELECT nom_boutique, adresse_boutique FROM parametres_boutique WHERE id_parametre = 1')->fetch();
    if ($storedSettings) {
        $settings = array_replace($settings, $storedSettings);
    }
} catch (Throwable $exception) {
    flash('error', $exception->getMessage());
    redirect('suivi-commande.php');
}

if (queryString('telecharger', 5) === '1') {
    $pdf = buildInvoicePdf($invoice, $lines, $settings);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="facture-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $invoice['numero_document']) . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

$pageTitle = 'Commande ' . $invoice['numero_commande'] . ' — Facture ' . $invoice['numero_document'];
$metaDescription = 'Facture privée associée à une commande Poissonnerie Saint-Michel.';
$seoIndexable = false;
$activePage = 'paiement';
require __DIR__ . '/includes/header.php';
?>
<section class="invoice-page section">
  <div class="invoice-page__top"><div><span class="invoice-page__brand"><?= e((string) $settings['nom_boutique']) ?></span><p><?= e((string) $settings['adresse_boutique']) ?></p></div><span class="invoice-page__tag">FACTURE</span></div>
  <div class="invoice-page__heading"><div><p>Numéro de commande</p><h1><?= e((string) $invoice['numero_commande']) ?></h1><p>Facture n° <?= e((string) $invoice['numero_document']) ?> · Émise le <?= e(date('d/m/Y à H:i', strtotime((string) $invoice['date_emission']))) ?></p></div><div><strong><?= e((string) $invoice['client']) ?></strong><span><?= e((string) $invoice['telephone']) ?></span><?php if (!empty($invoice['email'])): ?><span><?= e((string) $invoice['email']) ?></span><?php endif; ?></div></div>
  <div class="data-table-wrap"><table class="data-table invoice-table"><thead><tr><th>Article</th><th>Quantité</th><th>Prix unitaire</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($lines as $line): ?><tr><td><strong><?= e((string) $line['libelle']) ?></strong></td><td><?= number_format((float) $line['quantite'], 3, ',', ' ') ?> <?= e(mb_strtolower((string) $line['unite_vente'])) ?></td><td><?= e(moneyFcfa($line['prix_unitaire_applique'])) ?></td><td><?= e(moneyFcfa($line['sous_total'])) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <div class="invoice-page__totals"><div><span>Sous-total</span><strong><?= e(moneyFcfa($invoice['total_produits'])) ?></strong></div><div><span>Livraison<?= !empty($invoice['zone_livraison']) ? ' · ' . e((string) $invoice['zone_livraison']) : '' ?><?= !empty($invoice['quartier_livraison']) ? ' · ' . e((string) $invoice['quartier_livraison']) : '' ?></span><strong><?= e(moneyFcfa($invoice['frais_livraison'])) ?></strong></div><div><span>Total réglé</span><strong><?= e(moneyFcfa($invoice['montant'])) ?></strong></div></div>
  <div class="invoice-page__foot"><span>Commande <?= e((string) $invoice['numero_commande']) ?> · <?= e(paymentModeLabel((string) $invoice['mode_paiement'])) ?> · Réf. <?= e((string) $invoice['reference_transaction']) ?></span><div><a class="button button-yellow" href="<?= e(url('facture.php?commande=' . rawurlencode((string) $invoice['numero_commande']) . '&telecharger=1')) ?>">Télécharger le PDF</a><a class="button button-soft" href="<?= e(url('suivi-commande.php?numero=' . rawurlencode((string) $invoice['numero_commande']))) ?>">Suivre la commande</a></div></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
