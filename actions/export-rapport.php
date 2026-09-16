<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

$reports = ['ventes', 'commandes', 'clients', 'livraisons', 'stock', 'audit'];
$report = queryEnum('rapport', $reports, '');
if ($report === '') {
    http_response_code(400);
    exit('Type de rapport invalide.');
}

$range = adminDateRange(30);
$from = $range['from']->format('Y-m-d');
$to = $range['to']->format('Y-m-d');
$params = ['date_debut' => $from, 'date_fin' => adminDateTimeEnd($range['to'])];

try {
    $pdo = db();
    switch ($report) {
        case 'commandes':
            $headers = ['Numéro de commande', 'Date', 'Client', 'Téléphone', 'Mode de retrait', 'Statut commande', 'Montant total', 'Mode de paiement', 'Statut paiement'];
            $statement = $pdo->prepare(
                'SELECT co.numero_commande, co.date_commande, cl.nom_complet, cl.telephone,
                        co.mode_retrait, co.statut_courant, co.montant_total,
                        pa.mode_paiement, pa.statut_paiement
                 FROM commandes co
                 JOIN clients cl ON cl.id_client = co.id_client
                 LEFT JOIN paiements pa ON pa.id_commande = co.id_commande
                 WHERE co.date_commande >= :date_debut AND co.date_commande < :date_fin
                 ORDER BY co.date_commande DESC'
            );
            $mapper = static fn (array $row): array => [
                $row['numero_commande'], $row['date_commande'], $row['nom_complet'], $row['telephone'],
                $row['mode_retrait'], orderStatusLabel($row['statut_courant']), $row['montant_total'],
                $row['mode_paiement'] ? str_replace('_', ' ', $row['mode_paiement']) : '',
                $row['statut_paiement'] ? paymentStatusLabel($row['statut_paiement']) : '',
            ];
            break;

        case 'clients':
            $headers = ['ID client', 'Nom complet', 'Téléphone', 'E-mail', 'Client depuis', 'Commandes sur la période', 'Montant commandé'];
            $statement = $pdo->prepare(
                'SELECT c.id_client, c.nom_complet, c.telephone, c.email, c.created_at,
                        COUNT(co.id_commande) AS commandes_periode,
                        COALESCE(SUM(co.montant_total), 0) AS montant_periode
                 FROM clients c
                 LEFT JOIN commandes co ON co.id_client = c.id_client
                    AND co.date_commande >= :date_debut AND co.date_commande < :date_fin
                 WHERE c.created_at >= :date_debut AND c.created_at < :date_fin
                    OR co.id_commande IS NOT NULL
                 GROUP BY c.id_client, c.nom_complet, c.telephone, c.email, c.created_at
                 ORDER BY c.created_at DESC'
            );
            $mapper = static fn (array $row): array => [
                $row['id_client'], $row['nom_complet'], $row['telephone'], $row['email'], $row['created_at'],
                $row['commandes_periode'], $row['montant_periode'],
            ];
            break;

        case 'livraisons':
            $headers = ['ID livraison', 'Commande', 'Date d’affectation', 'Client', 'Livreur', 'Contact', 'Adresse', 'Statut', 'Date de livraison', 'Motif refus/annulation'];
            $statement = $pdo->prepare(
                'SELECT l.id_livraison, co.numero_commande, l.date_affectation, cl.nom_complet AS client,
                        u.nom_complet AS livreur, l.contact_livraison, l.adresse_livraison,
                        l.statut_livraison, l.date_livraison, l.motif_refus_annulation
                 FROM livraisons l
                 JOIN commandes co ON co.id_commande = l.id_commande
                 JOIN clients cl ON cl.id_client = co.id_client
                 JOIN utilisateurs u ON u.id_utilisateur = l.id_livreur
                 WHERE l.date_affectation >= :date_debut AND l.date_affectation < :date_fin
                 ORDER BY l.date_affectation DESC'
            );
            $mapper = static fn (array $row): array => [
                $row['id_livraison'], $row['numero_commande'], $row['date_affectation'], $row['client'],
                $row['livreur'], $row['contact_livraison'], $row['adresse_livraison'],
                deliveryStatusLabel($row['statut_livraison']), $row['date_livraison'], $row['motif_refus_annulation'],
            ];
            break;

        case 'stock':
            $headers = ['Date', 'Produit', 'Unité', 'Type', 'Quantité', 'Stock avant', 'Stock après', 'Motif', 'Référence', 'Auteur'];
            $statement = $pdo->prepare(
                'SELECT m.date_mouvement, p.libelle AS produit, p.unite_vente, m.type_mouvement,
                        m.quantite, m.stock_avant, m.stock_apres, m.motif, m.reference_source,
                        u.nom_complet AS auteur
                 FROM mouvements_stock m
                 JOIN produits p ON p.id_produit = m.id_produit
                 LEFT JOIN utilisateurs u ON u.id_utilisateur = m.id_utilisateur
                 WHERE m.date_mouvement >= :date_debut AND m.date_mouvement < :date_fin
                 ORDER BY m.date_mouvement DESC'
            );
            $mapper = static fn (array $row): array => [
                $row['date_mouvement'], $row['produit'], $row['unite_vente'], str_replace('_', ' ', $row['type_mouvement']),
                $row['quantite'], $row['stock_avant'], $row['stock_apres'], $row['motif'],
                $row['reference_source'], $row['auteur'],
            ];
            break;

        case 'audit':
            $headers = ['Date', 'Action', 'Type de cible', 'ID cible', 'Auteur', 'Adresse IP'];
            $statement = $pdo->prepare(
                'SELECT a.date_evenement, a.type_evenement, a.cible_type, a.cible_id, a.adresse_ip,
                        u.nom_complet AS auteur
                 FROM journal_audit a
                 LEFT JOIN utilisateurs u ON u.id_utilisateur = a.id_utilisateur
                 WHERE a.date_evenement >= :date_debut AND a.date_evenement < :date_fin
                 ORDER BY a.date_evenement DESC'
            );
            $mapper = static fn (array $row): array => [
                $row['date_evenement'], str_replace('_', ' ', $row['type_evenement']),
                auditEventTargetLabel($row['type_evenement'], $row['cible_type']), $row['cible_id'], $row['auteur'], $row['adresse_ip'],
            ];
            break;

        default:
            $headers = ['Date de paiement', 'Commande', 'Client', 'Téléphone', 'Mode de paiement', 'Référence', 'Produit', 'Catégorie', 'Quantité', 'Prix appliqué', 'Sous-total'];
            $statement = $pdo->prepare(
                "SELECT pa.date_confirmation, co.numero_commande, cl.nom_complet AS client, cl.telephone,
                        pa.mode_paiement, pa.reference_transaction, p.libelle AS produit, c.libelle AS categorie,
                        lc.quantite, lc.prix_unitaire_applique, lc.sous_total
                 FROM paiements pa
                 JOIN commandes co ON co.id_commande = pa.id_commande
                 JOIN clients cl ON cl.id_client = co.id_client
                 JOIN lignes_commande lc ON lc.id_commande = co.id_commande
                 JOIN produits p ON p.id_produit = lc.id_produit
                 JOIN categories c ON c.id_categorie = p.id_categorie
                 WHERE pa.statut_paiement = 'REUSSI'
                   AND pa.date_confirmation >= :date_debut AND pa.date_confirmation < :date_fin
                 ORDER BY pa.date_confirmation DESC, co.numero_commande, p.libelle"
            );
            $mapper = static fn (array $row): array => [
                $row['date_confirmation'], $row['numero_commande'], $row['client'], $row['telephone'],
                str_replace('_', ' ', $row['mode_paiement']), $row['reference_transaction'], $row['produit'],
                $row['categorie'], $row['quantite'], $row['prix_unitaire_applique'], $row['sous_total'],
            ];
    }
    $statement->execute($params);
} catch (Throwable $exception) {
    http_response_code(500);
    exit('Le rapport ne peut pas être généré pour le moment.');
}

$filename = sprintf('poissonnerie-saint-michel-%s-%s-au-%s.csv', $report, $from, $to);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");

$safeCell = static function (mixed $value): string {
    $value = (string) ($value ?? '');

    return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
};

fputcsv($output, array_map($safeCell, $headers), ';', '"', '');
while ($row = $statement->fetch()) {
    fputcsv($output, array_map($safeCell, $mapper($row)), ';', '"', '');
}
fclose($output);
exit;
