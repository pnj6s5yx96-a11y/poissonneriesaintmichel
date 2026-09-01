<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Retrouver ma facture — Poissonnerie Saint-Michel';
$activePage = 'facture';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading invoice-recovery-heading">
  <p class="breadcrumbs"><a href="<?= e(url('suivi-commande.php')) ?>">Suivi de commande</a> / Facture</p>
  <p class="eyebrow">Facture égarée ?</p>
  <h1>Recevez à nouveau votre facture par e-mail.</h1>
  <p>Indiquez simplement l’adresse e-mail renseignée lors de la validation de votre commande. Les factures disponibles y seront renvoyées.</p>
</section>

<section class="section invoice-recovery-layout">
  <form class="panel invoice-recovery-form" method="post" action="<?= e(url('actions/retrouver-facture.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <h2>Renvoyer mes factures</h2>
    <p>Pour protéger votre confidentialité, la demande est traitée uniquement par e-mail.</p>
    <div class="field"><label for="email">E-mail utilisé à la commande</label><input id="email" name="email" type="email" maxlength="191" autocomplete="email" placeholder="vous@exemple.com" required></div>
    <div class="form-actions"><button class="button button-yellow" type="submit">Recevoir mes factures</button><a class="button button-soft" href="<?= e(url('suivi-commande.php')) ?>">Suivre une commande</a></div>
  </form>
  <aside class="panel invoice-recovery-help"><span aria-hidden="true">⌁</span><h2>Bon à savoir</h2><p>Seules les factures de paiements Mobile Money confirmés sont envoyées. Pour un paiement en espèces, le reçu est remis par la boutique.</p></aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
