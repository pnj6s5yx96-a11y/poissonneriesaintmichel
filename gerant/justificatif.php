<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

$paymentId = filter_input(INPUT_GET, 'paiement', FILTER_VALIDATE_INT);
if (!$paymentId) {
    http_response_code(404);
    exit('Reçu introuvable.');
}

try {
    $statement = db()->prepare(
        "SELECT j.numero_document, j.date_emission, p.montant, p.reference_transaction, p.date_confirmation,
                c.numero_commande, cl.nom_complet AS client, cl.telephone
           FROM justificatifs_paiement j
           JOIN paiements p ON p.id_paiement = j.id_paiement
           JOIN commandes c ON c.id_commande = p.id_commande
           JOIN clients cl ON cl.id_client = c.id_client
          WHERE p.id_paiement = :paiement
            AND j.type_document = 'RECU'
            AND p.statut_paiement = 'REUSSI'"
    );
    $statement->execute(['paiement' => $paymentId]);
    $receipt = $statement->fetch();
    if (!$receipt) {
        throw new RuntimeException('Reçu introuvable.');
    }
} catch (Throwable $exception) {
    http_response_code(404);
    exit('Reçu introuvable.');
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="512x512" href="<?= e(url('assets/images/icon-saint-michel-512.png')) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/images/favicon-32.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('assets/images/apple-touch-icon.png')) ?>">
  <title>Reçu <?= e($receipt['numero_document']) ?></title>
  <style nonce="<?= e(cspNonce()) ?>">
    body { margin: 0; color: #153d32; background: #f2f7f4; font: 15px Arial, sans-serif; }
    .receipt { max-width: 620px; margin: 40px auto; padding: 42px; background: #fff; border: 1px solid #dbe9e2; border-radius: 16px; box-shadow: 0 12px 30px #153d3218; }
    .top { display: flex; justify-content: space-between; gap: 20px; padding-bottom: 20px; border-bottom: 2px solid #0c6b55; }
    .brand { display: flex; align-items: center; color: #064534; }.brand img { width: 220px; height: auto; }.tag { height: max-content; padding: 7px 10px; font-weight: 700; background: #f5be30; border-radius: 999px; }
    h1 { margin: 30px 0 7px; font: 700 31px Georgia, serif; } p { color: #5b746b; }
    .details { width: 100%; margin: 26px 0; border-collapse: collapse; }.details td { padding: 12px 0; border-bottom: 1px solid #e6eee9; }.details td:last-child { font-weight: 700; text-align: right; }
    .total { display: flex; justify-content: space-between; padding: 18px; margin-top: 28px; font-size: 20px; font-weight: 700; background: #e4f7eb; border-radius: 10px; }.foot { margin-top: 34px; font-size: 12px; text-align: center; }
    .print { padding: 10px 14px; color: #fff; font-weight: 700; background: #0c6b55; border: 0; border-radius: 8px; cursor: pointer; }
    @media print { body { background: #fff; }.receipt { padding: 0; margin: 0; border: 0; box-shadow: none; }.print { display: none; } }
  </style>
</head>
<body>
  <main class="receipt">
    <div class="top"><div><div class="brand"><img src="<?= e(url('assets/images/logo-saint-michel.jpg')) ?>" alt="Poissonnerie Saint-Michel"></div><p>Akpakpa · Cotonou</p></div><span class="tag">REÇU DE PAIEMENT</span></div>
    <h1>Reçu <?= e($receipt['numero_document']) ?></h1>
    <p>Émis le <?= e(date('d/m/Y à H:i', strtotime($receipt['date_emission']))) ?></p>
    <table class="details"><tr><td>Commande</td><td><?= e($receipt['numero_commande']) ?></td></tr><tr><td>Client</td><td><?= e($receipt['client']) ?></td></tr><tr><td>Contact</td><td><?= e($receipt['telephone']) ?></td></tr><tr><td>Mode</td><td>Espèces</td></tr><tr><td>Référence</td><td><?= e($receipt['reference_transaction']) ?></td></tr></table>
    <div class="total"><span>Montant encaissé</span><span><?= e(moneyFcfa($receipt['montant'])) ?></span></div>
    <p class="foot">Paiement confirmé le <?= e(date('d/m/Y à H:i', strtotime($receipt['date_confirmation']))) ?>.<br>Merci pour votre confiance.</p>
    <button class="print" type="button" data-print-receipt>Imprimer</button>
  </main>
  <script nonce="<?= e(cspNonce()) ?>">document.querySelector('[data-print-receipt]')?.addEventListener('click', () => window.print());</script>
</body>
</html>
