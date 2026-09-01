<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

$readyOrders = [];
$couriers = [];
$deliveries = [];
try {
    $pdo = db();
    $readyOrders = $pdo->query(
        "SELECT c.id_commande, c.numero_commande, c.montant_total, c.adresse_livraison,
                z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                cl.nom_complet AS client, cl.telephone
           FROM commandes c
           JOIN clients cl ON cl.id_client = c.id_client
           JOIN paiements p ON p.id_commande = c.id_commande AND p.statut_paiement = 'REUSSI'
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
          WHERE c.statut_courant = 'TRAITEE' AND c.mode_retrait = 'LIVRAISON'
            AND NOT EXISTS (
                SELECT 1 FROM livraisons l
                 WHERE l.id_commande = c.id_commande
                   AND l.statut_livraison IN ('AFFECTEE', 'ACCEPTEE', 'EN_COURS')
            )
          ORDER BY c.date_commande ASC"
    )->fetchAll();
    $couriers = $pdo->query(
        "SELECT u.id_utilisateur, u.nom_complet, u.telephone
           FROM utilisateurs u
           JOIN roles r ON r.id_role = u.id_role
          WHERE r.code_role = 'LIVREUR' AND u.est_actif = 1 AND r.est_actif = 1
          ORDER BY u.nom_complet"
    )->fetchAll();
    $deliveries = $pdo->query(
        "SELECT l.id_livraison, l.statut_livraison, l.date_affectation, l.date_acceptation,
                l.date_livraison, l.motif_refus_annulation, c.numero_commande, c.montant_total,
                z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                cl.nom_complet AS client, cl.telephone, u.nom_complet AS livreur
           FROM livraisons l
           JOIN commandes c ON c.id_commande = l.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
           JOIN utilisateurs u ON u.id_utilisateur = l.id_livreur
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
          ORDER BY l.date_affectation DESC LIMIT 100"
    )->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger les livraisons. Vérifiez la mise à jour de la base de données.');
}

$isAdministrator = currentUser()['role'] === 'ADMINISTRATEUR';
$portalPath = $isAdministrator ? 'admin/index.php' : 'gerant/index.php';
$portalLabel = $isAdministrator ? 'Administration' : 'Espace gérant';
$pageTitle = 'Livraisons — Gestion des ventes';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading">
  <p class="breadcrumbs"><a href="<?= e(url($portalPath)) ?>"><?= e($portalLabel) ?></a> / Livraisons</p>
  <p class="eyebrow">Affectations</p>
  <h1>Une livraison, un responsable.</h1>
  <p>Affectez uniquement les commandes préparées et réglées. Le livreur reçoit ensuite une mission claire à exécuter.</p>
</section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Commandes prêtes à livrer</h2><p>Une affectation est possible dès que le paiement est confirmé.</p></div></div>
  <?php if ($readyOrders === []): ?>
    <div class="empty-state"><strong>Aucune commande à affecter</strong><span>Les commandes livrables et traitées apparaîtront ici.</span></div>
  <?php elseif ($couriers === []): ?>
    <p class="notice">Créez ou activez au moins un compte livreur depuis l’administration avant toute affectation.</p>
  <?php else: ?>
    <div class="assignment-list">
      <?php foreach ($readyOrders as $order): ?>
        <article class="assignment-card">
          <div><strong><?= e($order['numero_commande']) ?></strong><span><?= e($order['client']) ?> · <?= e($order['telephone']) ?></span><small><?= e((string) ($order['zone_livraison'] ?? 'Zone à confirmer')) ?><?= !empty($order['quartier_livraison']) ? ' · ' . e((string) $order['quartier_livraison']) : '' ?></small><small><?= e(moneyFcfa($order['montant_total'])) ?></small></div>
          <form method="post" action="<?= e(url('actions/livraison.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="assign"><input type="hidden" name="id_commande" value="<?= (int) $order['id_commande'] ?>">
            <div class="field"><label for="livreur-<?= (int) $order['id_commande'] ?>">Livreur</label><select id="livreur-<?= (int) $order['id_commande'] ?>" name="id_livreur" required><?php foreach ($couriers as $courier): ?><option value="<?= (int) $courier['id_utilisateur'] ?>"><?= e($courier['nom_complet']) ?> · <?= e($courier['telephone']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="adresse-<?= (int) $order['id_commande'] ?>">Adresse de livraison</label><textarea id="adresse-<?= (int) $order['id_commande'] ?>" name="adresse_livraison" required><?= e($order['adresse_livraison'] ?? '') ?></textarea></div>
            <button class="button button-small" type="submit">Affecter</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Suivi des affectations</h2><p>Les 100 dernières missions créées.</p></div></div>
  <?php if ($deliveries === []): ?>
    <div class="empty-state"><strong>Aucune livraison enregistrée</strong><span>Le suivi des livreurs apparaîtra dans ce tableau.</span></div>
  <?php else: ?>
    <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Commande</th><th>Livreur</th><th>Client</th><th>Affectée le</th><th>Statut</th><th>Information</th></tr></thead><tbody>
    <?php foreach ($deliveries as $delivery): ?>
      <tr><td><strong><?= e($delivery['numero_commande']) ?></strong><br><small><?= e((string) ($delivery['zone_livraison'] ?? 'Zone à confirmer')) ?><?= !empty($delivery['quartier_livraison']) ? ' · ' . e((string) $delivery['quartier_livraison']) : '' ?></small><br><small><?= e(moneyFcfa($delivery['montant_total'])) ?></small></td><td><?= e($delivery['livreur']) ?></td><td><?= e($delivery['client']) ?><br><small><?= e($delivery['telephone']) ?></small></td><td><?= e(date('d/m/Y H:i', strtotime($delivery['date_affectation']))) ?></td><td><span class="badge <?= in_array($delivery['statut_livraison'], ['REFUSEE', 'ANNULEE'], true) ? 'badge-danger' : ($delivery['statut_livraison'] === 'LIVREE' ? 'badge-success' : 'badge-info') ?>"><?= e(deliveryStatusLabel($delivery['statut_livraison'])) ?></span></td><td><?= e($delivery['motif_refus_annulation'] ?: ($delivery['date_livraison'] ? 'Livrée le ' . date('d/m/Y H:i', strtotime($delivery['date_livraison'])) : '—')) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
