<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['ADMINISTRATEUR']);

if (!isPost()) {
    redirect('admin/parametres.php');
}

verifyCsrfToken();

/** @return array{libelle: string, description: ?string, frais_livraison: float, est_active: int} */
function deliveryZoneFormValues(): array
{
    $label = postString('libelle', 100);
    $description = postString('description', 500);
    if ($label === '') {
        throw new RuntimeException('Le nom de la zone est obligatoire.');
    }
    $fee = postDecimal('frais_livraison', 0, 9_999_999_999.99, 2);

    return [
        'libelle' => $label,
        'description' => $description !== '' ? $description : null,
        'frais_livraison' => $fee,
        'est_active' => postCheckbox('est_active'),
    ];
}

/** @return array{id_zone_livraison: int, libelle: string, arrondissement: ?string, est_actif: int} */
function deliveryNeighborhoodFormValues(): array
{
    $zoneId = postPositiveInt('id_zone_livraison');
    $label = postString('libelle', 150);
    $district = postString('arrondissement', 120);
    if ($label === '') {
        throw new RuntimeException('La zone et le nom du quartier sont obligatoires.');
    }

    return [
        'id_zone_livraison' => $zoneId,
        'libelle' => $label,
        'arrondissement' => $district !== '' ? $district : null,
        'est_actif' => postCheckbox('est_actif'),
    ];
}

try {
    $action = postEnum('action', [
        'save_settings',
        'create_delivery_zone',
        'update_delivery_zone',
        'create_delivery_neighborhood',
        'update_delivery_neighborhood',
    ]);
    $pdo = db();
    $pdo->beginTransaction();

    if ($action === 'save_settings') {
        $nom = postString('nom_boutique', 150);
        $adresse = postString('adresse_boutique', 500);
        $telephone = postString('telephone_boutique', 30);
        $email = postString('email_boutique', 191);
        $horaires = postString('horaires', 500);
        $livraisonActive = postCheckbox('est_livraison_active');

        if ($nom === '' || $adresse === '') {
            throw new RuntimeException('Le nom et l’adresse de la boutique sont obligatoires.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('L’adresse e-mail de la boutique n’est pas valide.');
        }

        $before = $pdo->query('SELECT * FROM parametres_boutique WHERE id_parametre = 1 FOR UPDATE')->fetch() ?: null;
        $statement = $pdo->prepare(
            'INSERT INTO parametres_boutique (
                id_parametre, nom_boutique, adresse_boutique, telephone_boutique,
                email_boutique, horaires, zones_livraison, frais_livraison_defaut,
                est_livraison_active
             ) VALUES (
                1, :nom, :adresse, :telephone, :email, :horaires, NULL, 0, :livraison_active
             ) ON DUPLICATE KEY UPDATE
                nom_boutique = VALUES(nom_boutique),
                adresse_boutique = VALUES(adresse_boutique),
                telephone_boutique = VALUES(telephone_boutique),
                email_boutique = VALUES(email_boutique),
                horaires = VALUES(horaires),
                est_livraison_active = VALUES(est_livraison_active)'
        );
        $statement->execute([
            'nom' => $nom,
            'adresse' => $adresse,
            'telephone' => $telephone !== '' ? $telephone : null,
            'email' => $email !== '' ? $email : null,
            'horaires' => $horaires !== '' ? $horaires : null,
            'livraison_active' => $livraisonActive,
        ]);
        $after = $pdo->query('SELECT * FROM parametres_boutique WHERE id_parametre = 1')->fetch() ?: [];
        recordAuditEvent($pdo, 'MODIFICATION_PARAMETRAGE', 'PARAMETRAGE', 1, $before, $after);
        $message = 'Les paramètres de la boutique ont été enregistrés.';
    } elseif ($action === 'create_delivery_zone') {
        $zone = deliveryZoneFormValues();
        $zone['est_active'] = 1;
        $statement = $pdo->prepare(
            'INSERT INTO zones_livraison (libelle, description, frais_livraison, est_active)
             VALUES (:libelle, :description, :frais, :active)'
        );
        $statement->execute([
            'libelle' => $zone['libelle'],
            'description' => $zone['description'],
            'frais' => $zone['frais_livraison'],
            'active' => $zone['est_active'],
        ]);
        $zoneId = (int) $pdo->lastInsertId();
        recordAuditEvent($pdo, 'CREATION_ZONE_LIVRAISON', 'PARAMETRAGE', $zoneId, null, $zone);
        $message = 'La zone de livraison a été ajoutée.';
    } elseif ($action === 'update_delivery_zone') {
        $zoneId = postPositiveInt('id_zone_livraison');
        $zone = deliveryZoneFormValues();
        $beforeStatement = $pdo->prepare('SELECT * FROM zones_livraison WHERE id_zone_livraison = :zone FOR UPDATE');
        $beforeStatement->execute(['zone' => $zoneId]);
        $before = $beforeStatement->fetch();
        if (!$before) {
            throw new RuntimeException('Zone de livraison introuvable.');
        }
        $statement = $pdo->prepare(
            'UPDATE zones_livraison
                SET libelle = :libelle,
                    description = :description,
                    frais_livraison = :frais,
                    est_active = :active
              WHERE id_zone_livraison = :zone'
        );
        $statement->execute([
            'libelle' => $zone['libelle'],
            'description' => $zone['description'],
            'frais' => $zone['frais_livraison'],
            'active' => $zone['est_active'],
            'zone' => $zoneId,
        ]);
        recordAuditEvent($pdo, 'MODIFICATION_ZONE_LIVRAISON', 'PARAMETRAGE', (int) $zoneId, $before, $zone);
        $message = 'La zone de livraison a été mise à jour.';
    } elseif ($action === 'create_delivery_neighborhood') {
        $quarter = deliveryNeighborhoodFormValues();
        $quarter['est_actif'] = 1;
        $zoneStatement = $pdo->prepare('SELECT id_zone_livraison FROM zones_livraison WHERE id_zone_livraison = :zone FOR UPDATE');
        $zoneStatement->execute(['zone' => $quarter['id_zone_livraison']]);
        if (!$zoneStatement->fetch()) {
            throw new RuntimeException('La zone de livraison du quartier est introuvable.');
        }
        $statement = $pdo->prepare(
            'INSERT INTO quartiers_livraison (id_zone_livraison, libelle, arrondissement, est_actif)
             VALUES (:zone, :libelle, :arrondissement, :active)'
        );
        $statement->execute([
            'zone' => $quarter['id_zone_livraison'],
            'libelle' => $quarter['libelle'],
            'arrondissement' => $quarter['arrondissement'],
            'active' => $quarter['est_actif'],
        ]);
        $quarterId = (int) $pdo->lastInsertId();
        recordAuditEvent($pdo, 'CREATION_QUARTIER_LIVRAISON', 'PARAMETRAGE', $quarterId, null, $quarter);
        $message = 'Le quartier de livraison a été ajouté.';
    } elseif ($action === 'update_delivery_neighborhood') {
        $quarterId = postPositiveInt('id_quartier_livraison');
        $quarter = deliveryNeighborhoodFormValues();
        $beforeStatement = $pdo->prepare('SELECT * FROM quartiers_livraison WHERE id_quartier_livraison = :quartier FOR UPDATE');
        $beforeStatement->execute(['quartier' => $quarterId]);
        $before = $beforeStatement->fetch();
        if (!$before || (int) $before['id_zone_livraison'] !== $quarter['id_zone_livraison']) {
            throw new RuntimeException('Le quartier de livraison est introuvable dans cette zone.');
        }
        $statement = $pdo->prepare(
            'UPDATE quartiers_livraison
                SET libelle = :libelle,
                    arrondissement = :arrondissement,
                    est_actif = :active
              WHERE id_quartier_livraison = :quartier'
        );
        $statement->execute([
            'libelle' => $quarter['libelle'],
            'arrondissement' => $quarter['arrondissement'],
            'active' => $quarter['est_actif'],
            'quartier' => $quarterId,
        ]);
        recordAuditEvent($pdo, 'MODIFICATION_QUARTIER_LIVRAISON', 'PARAMETRAGE', (int) $quarterId, $before, $quarter);
        $message = 'Le quartier de livraison a été mis à jour.';
    } else {
        throw new RuntimeException('Action de paramétrage inconnue.');
    }

    $pdo->commit();
    flash('success', $message);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'La zone ou les paramètres n’ont pas pu être enregistrés. Vérifiez notamment que le nom de zone est unique.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('admin/parametres.php');
