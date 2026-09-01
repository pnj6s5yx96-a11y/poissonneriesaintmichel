<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['LIVREUR']);

$courierId = (int) currentUser()['id'];
$metrics = ['assigned' => 0, 'in_progress' => 0, 'completed_month' => 0, 'to_confirm' => 0];
$nextDeliveries = [];
$notifications = [];

try {
    $pdo = db();
    $assignedStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM livraisons WHERE id_livreur = :livreur AND statut_livraison = 'AFFECTEE'"
    );
    $assignedStatement->execute(['livreur' => $courierId]);
    $metrics['assigned'] = (int) $assignedStatement->fetchColumn();

    $activeStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM livraisons
          WHERE id_livreur = :livreur AND statut_livraison IN ('ACCEPTEE', 'EN_COURS')"
    );
    $activeStatement->execute(['livreur' => $courierId]);
    $metrics['in_progress'] = (int) $activeStatement->fetchColumn();

    $completedStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM livraisons
          WHERE id_livreur = :livreur
            AND statut_livraison = 'LIVREE'
            AND date_livraison >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
    );
    $completedStatement->execute(['livreur' => $courierId]);
    $metrics['completed_month'] = (int) $completedStatement->fetchColumn();

    $confirmationStatement = $pdo->prepare(
        "SELECT COUNT(*)
           FROM livraisons l
           JOIN commandes c ON c.id_commande = l.id_commande
          WHERE l.id_livreur = :livreur
            AND l.statut_livraison = 'LIVREE'
            AND c.confirmation_reception = 0"
    );
    $confirmationStatement->execute(['livreur' => $courierId]);
    $metrics['to_confirm'] = (int) $confirmationStatement->fetchColumn();

    $deliveryStatement = $pdo->prepare(
        "SELECT l.id_livraison, l.statut_livraison, l.date_affectation, l.adresse_livraison,
                c.numero_commande, c.montant_total, z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                cl.nom_complet AS client, cl.telephone
           FROM livraisons l
           JOIN commandes c ON c.id_commande = l.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
          WHERE l.id_livreur = :livreur
            AND l.statut_livraison IN ('AFFECTEE', 'ACCEPTEE', 'EN_COURS')
          ORDER BY FIELD(l.statut_livraison, 'EN_COURS', 'ACCEPTEE', 'AFFECTEE'), l.date_affectation ASC
          LIMIT 4"
    );
    $deliveryStatement->execute(['livreur' => $courierId]);
    $nextDeliveries = $deliveryStatement->fetchAll();

    $notificationStatement = $pdo->prepare(
        "SELECT n.message, n.date_creation, n.statut_envoi, c.numero_commande
           FROM notifications n
           JOIN commandes c ON c.id_commande = n.id_commande
          WHERE n.id_utilisateur = :livreur
          ORDER BY n.date_creation DESC
          LIMIT 5"
    );
    $notificationStatement->execute(['livreur' => $courierId]);
    $notifications = $notificationStatement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger vos informations de livraison.');
}

$pageTitle = 'Espace livreur — Poissonnerie Saint-Michel';
require __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-welcome courier-welcome"><div><p class="dashboard-kicker">Tournée du jour</p><h1>Bonjour, <?= e((string) currentUser()['nom']) ?>.</h1><p>Retrouvez vos missions, mettez à jour chaque étape et gardez la boutique informée.</p></div><span class="dashboard-badge">LIVREUR</span></section>

<section class="metrics metrics-four" aria-label="Résumé des livraisons"><article class="metric yellow"><span>Nouvelles missions</span><strong><?= $metrics['assigned'] ?></strong></article><article class="metric"><span>À effectuer</span><strong><?= $metrics['in_progress'] ?></strong></article><article class="metric"><span>Livrées ce mois</span><strong><?= $metrics['completed_month'] ?></strong></article><article class="metric <?= $metrics['to_confirm'] > 0 ? 'yellow' : '' ?>"><span>En attente du client</span><strong><?= $metrics['to_confirm'] ?></strong></article></section>

<section class="section dashboard-grid courier-dashboard-grid">
  <article class="panel"><div class="panel-heading"><div><h2>Mes prochaines missions</h2><p>Les priorités sont affichées selon leur avancement.</p></div><a class="button button-soft button-small" href="<?= e(url('livreur/livraisons.php')) ?>">Tout voir</a></div>
    <?php if ($nextDeliveries === []): ?><p class="empty-inline">Aucune livraison active pour le moment.</p><?php else: ?><div class="courier-mini-list"><?php foreach ($nextDeliveries as $delivery): ?><a href="<?= e(url('livreur/livraisons.php')) ?>" class="courier-mini-card"><span class="badge <?= e(deliveryStatusBadgeClass((string) $delivery['statut_livraison'])) ?>"><?= e(deliveryStatusLabel((string) $delivery['statut_livraison'])) ?></span><strong><?= e((string) $delivery['numero_commande']) ?></strong><span><?= e((string) $delivery['client']) ?> · <?= e((string) $delivery['telephone']) ?></span><small><?= e((string) ($delivery['zone_livraison'] ?? 'Zone à confirmer')) ?><?= !empty($delivery['quartier_livraison']) ? ' · ' . e((string) $delivery['quartier_livraison']) : '' ?></small><small><?= e((string) $delivery['adresse_livraison']) ?></small></a><?php endforeach; ?></div><?php endif; ?>
  </article>
  <article class="panel"><div class="panel-heading"><div><h2>Dernières alertes</h2><p>Vos affectations et messages de la boutique.</p></div></div>
    <?php if ($notifications === []): ?><p class="empty-inline">Vous recevrez ici les nouvelles missions et les informations importantes.</p><?php else: ?><ul class="courier-alerts"><?php foreach ($notifications as $notification): ?><li><span><?= e((string) $notification['message']) ?></span><small><?= e(date('d/m/Y à H:i', strtotime((string) $notification['date_creation']))) ?></small></li><?php endforeach; ?></ul><?php endif; ?>
  </article>
</section>

<section class="section panel courier-guide"><div class="panel-heading"><div><h2>Votre parcours de livraison</h2><p>Chaque mise à jour est immédiatement visible par la boutique et le client.</p></div></div><ol><li><span>1</span><div><strong>Acceptez ou refusez</strong><small>Une mission affectée doit être acceptée ou refusée avec un motif.</small></div></li><li><span>2</span><div><strong>Signalez votre départ</strong><small>Le client sait alors que sa commande est en route.</small></div></li><li><span>3</span><div><strong>Déclarez la livraison effectuée</strong><small>Le client reçoit une invitation à confirmer la réception.</small></div></li></ol></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
