<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/invoice_mail.php';

if (!isPost()) {
    redirect('retrouver-facture.php');
}

verifyCsrfToken();

try {
    $email = postEmail('email');

    $pdo = db();
    enforceRateLimit($pdo, 'renvoi_facture_ip', '', 5, 900);
    enforceRateLimit($pdo, 'renvoi_facture_email', $email, 3, 900);
    $statement = $pdo->prepare(
        "SELECT j.numero_document, j.date_emission,
                p.id_paiement, p.mode_paiement, p.montant, p.reference_transaction,
                c.id_commande, c.numero_commande, c.mode_retrait, c.frais_livraison, c.total_produits,
                z.libelle AS zone_livraison, q.libelle AS quartier_livraison,
                cl.nom_complet AS client, cl.telephone, cl.email
           FROM justificatifs_paiement j
           JOIN paiements p ON p.id_paiement = j.id_paiement
           JOIN commandes c ON c.id_commande = p.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
      LEFT JOIN zones_livraison z ON z.id_zone_livraison = c.id_zone_livraison
      LEFT JOIN quartiers_livraison q ON q.id_quartier_livraison = c.id_quartier_livraison
          WHERE LOWER(cl.email) = :email
            AND j.type_document = 'FACTURE'
            AND p.statut_paiement = 'REUSSI'
          ORDER BY j.date_emission DESC"
    );
    $statement->execute(['email' => $email]);
    $records = $statement->fetchAll();

    $settings = [
        'nom_boutique' => 'Poissonnerie Saint-Michel',
        'adresse_boutique' => 'Akpakpa, Cotonou',
        'email_boutique' => null,
    ];
    $storedSettings = $pdo->query(
        'SELECT nom_boutique, adresse_boutique, telephone_boutique, email_boutique
           FROM parametres_boutique
          WHERE id_parametre = 1'
    )->fetch();
    if ($storedSettings) {
        $settings = array_replace($settings, $storedSettings);
    }
    $transport = invoiceMailTransportConfig($settings);

    if ($records !== []) {
        $lineStatement = $pdo->prepare(
            'SELECT p.libelle, lc.quantite, p.unite_vente, lc.prix_unitaire_applique, lc.sous_total
               FROM lignes_commande lc
               JOIN produits p ON p.id_produit = lc.id_produit
              WHERE lc.id_commande = :commande
              ORDER BY lc.id_ligne_commande'
        );
        $invoices = [];
        foreach ($records as $record) {
            $lineStatement->execute(['commande' => (int) $record['id_commande']]);
            $invoices[] = ['invoice' => $record, 'lines' => $lineStatement->fetchAll()];
        }

        if (!sendInvoiceRecoveryEmail($email, $invoices, $settings)) {
            throw new RuntimeException('Le service d’envoi est momentanément indisponible. Réessayez plus tard.');
        }
    }

    flash(
        'success',
        $transport['transport'] === 'file'
            ? 'Si des factures confirmées sont associées à cette adresse, elles ont été préparées dans la boîte d’envoi locale. Configurez SMTP pour les envoyer vers une boîte e-mail réelle.'
            : 'Si des factures confirmées sont associées à cette adresse, elles viennent d’être envoyées par e-mail.'
    );
} catch (SecurityRateLimitException $exception) {
    flash('error', $exception->getMessage());
} catch (Throwable $exception) {
    error_log('Échec du renvoi de facture : ' . $exception->getMessage());
    $message = $exception->getMessage();
    if (str_contains($message, '(535)') || str_contains($message, 'mot de passe d’application Gmail')) {
        $message = 'Le compte Gmail d’envoi refuse le mot de passe d’application. L’administrateur doit enregistrer un nouveau mot de passe d’application Google de 16 caractères.';
    }
    flash('error', $message);
}

redirect('retrouver-facture.php');
