</main>
<?php if (!empty($hasDashboardSidebar)): ?>
</div>
<?php endif; ?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <img class="footer-mark" src="<?= e(url('assets/images/logo-saint-michel-mark.jpg')) ?>" width="43" height="43" alt="">
      <div><strong>Saint-Michel</strong><span>Poissonnerie · Akpakpa, Cotonou</span></div>
    </div>
    <div class="footer-links" aria-label="Liens utiles">
      <a href="<?= e(url()) ?>">Accueil</a>
      <a href="<?= e(url('catalogue.php')) ?>">Catalogue</a>
      <a href="<?= e(url('suivi-commande.php')) ?>">Suivre ma commande</a>
      <a href="<?= e(url('retrouver-facture.php')) ?>">Retrouver ma facture</a>
    </div>
    <div class="footer-note"><span>Poissons, viandes et œufs congelés</span><span>Retrait ou livraison selon vos besoins</span></div>
  </div>
  <div class="footer-bottom">© <?= date('Y') ?> Poissonnerie Saint-Michel · Le goût de la mer, simplement.</div>
</footer>
<script src="<?= e(url('assets/js/app.js') . '?v=' . $scriptVersion) ?>" defer></script>
</body>
</html>
