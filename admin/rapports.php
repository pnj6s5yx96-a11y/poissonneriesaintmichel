<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

$reports = [
    'ventes' => ['label' => 'Ventes confirmées', 'description' => 'Paiements réussis et détail des produits vendus.'],
    'commandes' => ['label' => 'Commandes', 'description' => 'Toutes les commandes enregistrées sur la période.'],
    'clients' => ['label' => 'Clients', 'description' => 'Clients créés ou actifs sur la période.'],
    'livraisons' => ['label' => 'Livraisons', 'description' => 'Affectations et états de livraison.'],
    'stock' => ['label' => 'Mouvements de stock', 'description' => 'Entrées, sorties et ajustements de stock.'],
    'audit' => ['label' => 'Journal d’audit', 'description' => 'Actions sensibles réalisées par les comptes internes.'],
];
$report = queryEnum('rapport', array_keys($reports), 'ventes');

$range = adminDateRange(30);
$from = $range['from']->format('Y-m-d');
$to = $range['to']->format('Y-m-d');
$params = ['date_debut' => $from, 'date_fin' => adminDateTimeEnd($range['to'])];
$summary = ['principal' => 0, 'secondary' => 0, 'principal_label' => 'Enregistrements', 'secondary_label' => 'Montant'];

try {
    $pdo = db();
    switch ($report) {
        case 'commandes':
            $statement = $pdo->prepare(
                'SELECT COUNT(*) AS principal, COALESCE(SUM(montant_total), 0) AS secondary
                 FROM commandes
                 WHERE date_commande >= :date_debut AND date_commande < :date_fin'
            );
            $summary['principal_label'] = 'Commandes enregistrées';
            $summary['secondary_label'] = 'Montant commandé';
            break;
        case 'clients':
            $statement = $pdo->prepare(
                'SELECT COUNT(DISTINCT c.id_client) AS principal, COUNT(co.id_commande) AS secondary
                 FROM clients c
                 LEFT JOIN commandes co ON co.id_client = c.id_client
                    AND co.date_commande >= :date_debut AND co.date_commande < :date_fin
                 WHERE c.created_at >= :date_debut AND c.created_at < :date_fin
                    OR co.id_commande IS NOT NULL'
            );
            $summary['principal_label'] = 'Clients actifs ou créés';
            $summary['secondary_label'] = 'Commandes associées';
            break;
        case 'livraisons':
            $statement = $pdo->prepare(
                "SELECT COUNT(*) AS principal,
                        SUM(CASE WHEN statut_livraison = 'LIVREE' THEN 1 ELSE 0 END) AS secondary
                 FROM livraisons
                 WHERE date_affectation >= :date_debut AND date_affectation < :date_fin"
            );
            $summary['principal_label'] = 'Livraisons affectées';
            $summary['secondary_label'] = 'Livraisons effectuées';
            break;
        case 'stock':
            $statement = $pdo->prepare(
                "SELECT COUNT(*) AS principal,
                        SUM(CASE WHEN type_mouvement IN ('SORTIE', 'AJUSTEMENT_NEGATIF') THEN quantite ELSE 0 END) AS secondary
                 FROM mouvements_stock
                 WHERE date_mouvement >= :date_debut AND date_mouvement < :date_fin"
            );
            $summary['principal_label'] = 'Mouvements enregistrés';
            $summary['secondary_label'] = 'Quantité sortie';
            break;
        case 'audit':
            $statement = $pdo->prepare(
                'SELECT COUNT(*) AS principal, COUNT(DISTINCT id_utilisateur) AS secondary
                 FROM journal_audit
                 WHERE date_evenement >= :date_debut AND date_evenement < :date_fin'
            );
            $summary['principal_label'] = 'Actions journalisées';
            $summary['secondary_label'] = 'Comptes concernés';
            break;
        default:
            $statement = $pdo->prepare(
                "SELECT COUNT(*) AS principal, COALESCE(SUM(montant), 0) AS secondary
                 FROM paiements
                 WHERE statut_paiement = 'REUSSI'
                   AND date_confirmation >= :date_debut AND date_confirmation < :date_fin"
            );
            $summary['principal_label'] = 'Paiements confirmés';
            $summary['secondary_label'] = 'Recettes encaissées';
    }
    $statement->execute($params);
    $storedSummary = $statement->fetch();
    if ($storedSummary) {
        $summary['principal'] = (float) $storedSummary['principal'];
        $summary['secondary'] = (float) $storedSummary['secondary'];
    }
} catch (Throwable $exception) {
    flash('error', 'Impossible de préparer ce rapport.');
}

$exportUrl = url('actions/export-rapport.php?rapport=' . rawurlencode($report) . '&date_debut=' . rawurlencode($from) . '&date_fin=' . rawurlencode($to));
$secondaryValue = $report === 'stock'
    ? number_format($summary['secondary'], 3, ',', ' ')
    : ($report === 'clients' || $report === 'livraisons' || $report === 'audit'
        ? number_format($summary['secondary'], 0, ',', ' ')
        : moneyFcfa($summary['secondary']));
$pageTitle = 'Rapports — Administration';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('admin/index.php')) ?>">Administration</a> / Rapports</p><p class="eyebrow">Exports périodiques</p><h1>Des données prêtes à analyser.</h1><p>Préparez un rapport sur une période donnée, puis téléchargez-le au format CSV, lisible avec Excel, Numbers ou Google Sheets.</p></section>

<section class="section panel">
  <div class="panel-heading"><div><h2><?= e($reports[$report]['label']) ?></h2><p><?= e($reports[$report]['description']) ?></p></div></div>
  <form class="filters-bar filters-bar--report" method="get">
    <div class="field"><label for="rapport">Rapport</label><select id="rapport" name="rapport"><?php foreach ($reports as $key => $definition): ?><option value="<?= e($key) ?>" <?= $key === $report ? 'selected' : '' ?>><?= e($definition['label']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="date_debut">Du</label><input id="date_debut" name="date_debut" type="date" value="<?= e($from) ?>" max="<?= e(date('Y-m-d')) ?>"></div>
    <div class="field"><label for="date_fin">Au</label><input id="date_fin" name="date_fin" type="date" value="<?= e($to) ?>" max="<?= e(date('Y-m-d')) ?>"></div>
    <button class="button button-soft button-small" type="submit">Prévisualiser</button>
  </form>

  <div class="report-summary">
    <article><span><?= e($summary['principal_label']) ?></span><strong><?= number_format($summary['principal'], 0, ',', ' ') ?></strong></article>
    <article><span><?= e($summary['secondary_label']) ?></span><strong><?= e($secondaryValue) ?></strong></article>
    <div><p>Période sélectionnée : <strong>du <?= e($range['from']->format('d/m/Y')) ?> au <?= e($range['to']->format('d/m/Y')) ?></strong>.</p><a class="button button-yellow" href="<?= e($exportUrl) ?>">Télécharger le CSV</a></div>
  </div>
</section>

<section class="section panel report-help">
  <div><h2>Contenu du fichier</h2><p>Le téléchargement respecte les filtres choisis et n’inclut jamais de mot de passe ou de donnée de session.</p></div>
  <ul>
    <li><strong>Ventes :</strong> paiements confirmés, commande, client et produits vendus.</li>
    <li><strong>Commandes :</strong> statut, mode de retrait, montant et état du paiement.</li>
    <li><strong>Clients, livraisons et stock :</strong> informations utiles au suivi opérationnel.</li>
    <li><strong>Journal d’audit :</strong> événements sensibles, cible, auteur et adresse IP associée.</li>
  </ul>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
