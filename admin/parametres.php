<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

$settings = [
    'nom_boutique' => 'Poissonnerie Saint-Michel',
    'adresse_boutique' => 'Akpakpa, Cotonou',
    'telephone_boutique' => '',
    'email_boutique' => '',
    'horaires' => '',
    'est_livraison_active' => 1,
];
$deliveryZones = [];
$deliveryQuarters = [];
$quartersByZone = [];

try {
    $pdo = db();
    $storedSettings = $pdo->query('SELECT * FROM parametres_boutique WHERE id_parametre = 1')->fetch();
    if ($storedSettings) {
        $settings = array_replace($settings, $storedSettings);
    }
    $deliveryZones = $pdo->query(
        'SELECT id_zone_livraison, libelle, description, frais_livraison, est_active
           FROM zones_livraison
          ORDER BY est_active DESC, frais_livraison, libelle'
    )->fetchAll();
    $deliveryQuarters = $pdo->query(
        'SELECT q.id_quartier_livraison, q.id_zone_livraison, q.libelle, q.arrondissement, q.est_actif,
                z.libelle AS zone_livraison
           FROM quartiers_livraison q
           JOIN zones_livraison z ON z.id_zone_livraison = q.id_zone_livraison
          ORDER BY z.frais_livraison, z.libelle, q.arrondissement, q.libelle'
    )->fetchAll();
    foreach ($deliveryQuarters as $quarter) {
        $quartersByZone[(int) $quarter['id_zone_livraison']][] = $quarter;
    }
} catch (Throwable $exception) {
    flash('error', 'Impossible de charger les paramètres de la boutique ou les zones de livraison.');
}

$pageTitle = 'Paramètres — Administration';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-heading"><p class="breadcrumbs"><a href="<?= e(url('admin/index.php')) ?>">Administration</a> / Paramètres</p><p class="eyebrow">Configuration de la boutique</p><h1>Les informations utiles, toujours à jour.</h1><p>Définissez les coordonnées de la poissonnerie et les règles de livraison utilisées par les clients et l’équipe.</p></section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Informations générales</h2><p>Ces informations sont utilisées dans les interfaces et les documents de la boutique.</p></div></div>
  <form method="post" action="<?= e(url('actions/parametres.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="save_settings">
    <div class="form-grid">
      <div class="field"><label for="nom_boutique">Nom de la boutique</label><input id="nom_boutique" name="nom_boutique" maxlength="150" value="<?= e((string) $settings['nom_boutique']) ?>" required></div>
      <div class="field"><label for="telephone_boutique">Téléphone de la boutique <small>facultatif</small></label><input id="telephone_boutique" name="telephone_boutique" maxlength="30" inputmode="tel" value="<?= e((string) ($settings['telephone_boutique'] ?? '')) ?>"></div>
      <div class="field full"><label for="adresse_boutique">Adresse de la boutique</label><input id="adresse_boutique" name="adresse_boutique" maxlength="500" value="<?= e((string) $settings['adresse_boutique']) ?>" required></div>
      <div class="field"><label for="email_boutique">E-mail de la boutique <small>adresse d’expédition des factures ; recommandé</small></label><input id="email_boutique" name="email_boutique" type="email" maxlength="191" value="<?= e((string) ($settings['email_boutique'] ?? '')) ?>"></div>
      <div class="field"><label for="horaires">Horaires <small>facultatif</small></label><input id="horaires" name="horaires" maxlength="500" placeholder="Ex. Lun–Sam : 8h–19h" value="<?= e((string) ($settings['horaires'] ?? '')) ?>"></div>
    </div>

    <div class="settings-divider"><div><h2>Service de livraison</h2><p>Activez le service pour permettre aux clients de choisir une zone tarifaire ci-dessous.</p></div></div>
    <div class="form-grid">
      <div class="field checkbox-field"><input id="est_livraison_active" name="est_livraison_active" type="checkbox" value="1" <?= (int) $settings['est_livraison_active'] === 1 ? 'checked' : '' ?>><label for="est_livraison_active"><strong>Livraison à domicile active</strong><small>Les clients peuvent sélectionner la livraison au moment de commander.</small></label></div>
    </div>
    <div class="form-actions"><button class="button button-yellow" type="submit">Enregistrer les paramètres</button></div>
  </form>
</section>

<section class="section panel">
  <div class="panel-heading"><div><h2>Zones et tarifs de livraison</h2><p>Le montant sélectionné est ajouté automatiquement au total de la commande. Les tarifs modifiés ne changent pas les commandes déjà créées.</p></div></div>
  <?php if ($deliveryZones === []): ?>
    <div class="empty-state"><strong>Aucune zone configurée</strong><span>Ajoutez au moins une zone active pour proposer la livraison aux clients.</span></div>
  <?php else: ?>
    <div class="delivery-zone-list">
      <?php foreach ($deliveryZones as $zone): ?>
        <form class="delivery-zone-card" method="post" action="<?= e(url('actions/parametres.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="update_delivery_zone">
          <input type="hidden" name="id_zone_livraison" value="<?= (int) $zone['id_zone_livraison'] ?>">
          <div class="field"><label for="zone-libelle-<?= (int) $zone['id_zone_livraison'] ?>">Zone</label><input id="zone-libelle-<?= (int) $zone['id_zone_livraison'] ?>" name="libelle" maxlength="100" value="<?= e((string) $zone['libelle']) ?>" required></div>
          <div class="field"><label for="zone-frais-<?= (int) $zone['id_zone_livraison'] ?>">Coût (FCFA)</label><input id="zone-frais-<?= (int) $zone['id_zone_livraison'] ?>" name="frais_livraison" type="number" min="0" step="1" value="<?= e((string) $zone['frais_livraison']) ?>" required></div>
          <div class="field"><label for="zone-description-<?= (int) $zone['id_zone_livraison'] ?>">Précision <small>facultative</small></label><input id="zone-description-<?= (int) $zone['id_zone_livraison'] ?>" name="description" maxlength="500" value="<?= e((string) ($zone['description'] ?? '')) ?>" placeholder="Quartiers ou repères concernés"></div>
          <div class="field checkbox-field delivery-zone-card__active"><input id="zone-active-<?= (int) $zone['id_zone_livraison'] ?>" name="est_active" type="checkbox" value="1" <?= (int) $zone['est_active'] === 1 ? 'checked' : '' ?>><label for="zone-active-<?= (int) $zone['id_zone_livraison'] ?>"><strong>Disponible</strong><small><?= (int) $zone['est_active'] === 1 ? 'Proposée aux clients' : 'Masquée aux clients' ?></small></label></div>
          <button class="button button-soft button-small" type="submit">Mettre à jour</button>
        </form>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="settings-divider"><div><h2>Ajouter une zone</h2><p>Créez une nouvelle zone lorsqu’un tarif distinct est nécessaire.</p></div></div>
  <form class="delivery-zone-create" method="post" action="<?= e(url('actions/parametres.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="create_delivery_zone">
    <div class="field"><label for="new-zone-libelle">Nom de la zone</label><input id="new-zone-libelle" name="libelle" maxlength="100" placeholder="Ex. Fidjrossè" required></div>
    <div class="field"><label for="new-zone-frais">Coût (FCFA)</label><input id="new-zone-frais" name="frais_livraison" type="number" min="0" step="1" placeholder="0" required></div>
    <div class="field"><label for="new-zone-description">Précision <small>facultative</small></label><input id="new-zone-description" name="description" maxlength="500" placeholder="Quartiers ou repères concernés"></div>
    <button class="button button-yellow" type="submit">Ajouter la zone</button>
  </form>
</section>
 
<section class="section panel">
  <div class="panel-heading"><div><h2>Quartiers de livraison</h2><p>Chaque quartier est relié à une zone tarifaire. Les clients ne voient que les quartiers correspondant à la zone qu’ils ont choisie.</p></div></div>
  <?php foreach ($deliveryZones as $zone): ?>
    <?php $quarters = $quartersByZone[(int) $zone['id_zone_livraison']] ?? []; ?>
    <details class="delivery-neighborhood-group">
      <summary><span><strong><?= e((string) $zone['libelle']) ?></strong><small><?= count($quarters) ?> quartier<?= count($quarters) > 1 ? 's' : '' ?> configuré<?= count($quarters) > 1 ? 's' : '' ?> · <?= e(moneyFcfa($zone['frais_livraison'])) ?></small></span><span aria-hidden="true">⌄</span></summary>
      <div class="delivery-neighborhood-list">
        <?php foreach ($quarters as $quarter): ?>
          <form class="delivery-neighborhood-row" method="post" action="<?= e(url('actions/parametres.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update_delivery_neighborhood"><input type="hidden" name="id_quartier_livraison" value="<?= (int) $quarter['id_quartier_livraison'] ?>"><input type="hidden" name="id_zone_livraison" value="<?= (int) $zone['id_zone_livraison'] ?>">
            <div class="field"><label for="quarter-label-<?= (int) $quarter['id_quartier_livraison'] ?>">Quartier</label><input id="quarter-label-<?= (int) $quarter['id_quartier_livraison'] ?>" name="libelle" maxlength="150" value="<?= e((string) $quarter['libelle']) ?>" required></div>
            <div class="field"><label for="quarter-district-<?= (int) $quarter['id_quartier_livraison'] ?>">Arrondissement</label><input id="quarter-district-<?= (int) $quarter['id_quartier_livraison'] ?>" name="arrondissement" maxlength="120" value="<?= e((string) ($quarter['arrondissement'] ?? '')) ?>"></div>
            <div class="field checkbox-field"><input id="quarter-active-<?= (int) $quarter['id_quartier_livraison'] ?>" name="est_actif" type="checkbox" value="1" <?= (int) $quarter['est_actif'] === 1 ? 'checked' : '' ?>><label for="quarter-active-<?= (int) $quarter['id_quartier_livraison'] ?>"><strong>Disponible</strong></label></div>
            <button class="button button-soft button-small" type="submit">Enregistrer</button>
          </form>
        <?php endforeach; ?>
      </div>
      <form class="delivery-neighborhood-create" method="post" action="<?= e(url('actions/parametres.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_delivery_neighborhood"><input type="hidden" name="id_zone_livraison" value="<?= (int) $zone['id_zone_livraison'] ?>">
        <div class="field"><label for="new-quarter-label-<?= (int) $zone['id_zone_livraison'] ?>">Ajouter un quartier</label><input id="new-quarter-label-<?= (int) $zone['id_zone_livraison'] ?>" name="libelle" maxlength="150" placeholder="Nom du quartier" required></div>
        <div class="field"><label for="new-quarter-district-<?= (int) $zone['id_zone_livraison'] ?>">Arrondissement <small>facultatif</small></label><input id="new-quarter-district-<?= (int) $zone['id_zone_livraison'] ?>" name="arrondissement" maxlength="120" placeholder="Ex. Godomey"></div>
        <button class="button button-yellow button-small" type="submit">Ajouter</button>
      </form>
    </details>
  <?php endforeach; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
