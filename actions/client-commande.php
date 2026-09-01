<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

if (!isPost()) {
    redirect('panier.php');
}

verifyCsrfToken();

$checkout = [
    'nom_client' => postString('nom_client', 150),
    'telephone_client' => postString('telephone_client', 30),
    'email_client' => mb_strtolower(postString('email_client', 191)),
    'mode_retrait' => postString('mode_retrait', 30),
    'id_zone_livraison' => null,
    'id_quartier_livraison' => null,
    'adresse_livraison' => postString('adresse_livraison', 500),
    'mode_paiement' => postString('mode_paiement', 30),
    'note_client' => postString('note_client', 500),
];

try {
    $allowedPaymentModes = ['MTN_MOMO', 'MOOV_MONEY', 'CELTIS_CASH', 'ESPECES'];
    $checkout['id_zone_livraison'] = postOptionalPositiveInt('id_zone_livraison');
    $checkout['id_quartier_livraison'] = postOptionalPositiveInt('id_quartier_livraison');
    if ($checkout['nom_client'] === '' || $checkout['telephone_client'] === '' || $checkout['email_client'] === '') {
        throw new RuntimeException('Votre nom, votre téléphone et votre e-mail sont obligatoires.');
    }
    if (!filter_var($checkout['email_client'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('L’adresse e-mail n’est pas valide.');
    }
    $checkout['mode_retrait'] = postEnum('mode_retrait', ['LIVRAISON', 'RETRAIT_BOUTIQUE']);
    if ($checkout['mode_retrait'] === 'LIVRAISON' && $checkout['adresse_livraison'] === '') {
        throw new RuntimeException('L’adresse de livraison est obligatoire.');
    }
    $deliveryZoneId = $checkout['id_zone_livraison'];
    $deliveryQuarterId = $checkout['id_quartier_livraison'];
    if ($checkout['mode_retrait'] === 'LIVRAISON' && $deliveryZoneId === null) {
        throw new RuntimeException('Sélectionnez votre zone de livraison.');
    }
    if ($checkout['mode_retrait'] === 'LIVRAISON' && $deliveryQuarterId === null) {
        throw new RuntimeException('Sélectionnez votre quartier de livraison.');
    }
    $checkout['mode_paiement'] = postEnum('mode_paiement', $allowedPaymentModes);
    $_SESSION['client_checkout'] = $checkout;

    $items = clientCart();
    if ($items === []) {
        throw new RuntimeException('Votre panier est vide.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    $settingsStatement = $pdo->query(
        'SELECT est_livraison_active
           FROM parametres_boutique
          WHERE id_parametre = 1
          FOR UPDATE'
    );
    $settings = $settingsStatement->fetch();
    if (!$settings) {
        throw new RuntimeException('Les paramètres de la boutique sont indisponibles.');
    }
    if ($checkout['mode_retrait'] === 'LIVRAISON' && (int) $settings['est_livraison_active'] !== 1) {
        throw new RuntimeException('La livraison à domicile n’est pas disponible actuellement.');
    }
    $deliveryZone = null;
    $deliveryQuarter = null;
    $deliveryFee = 0.0;
    if ($checkout['mode_retrait'] === 'LIVRAISON') {
        $zoneStatement = $pdo->prepare(
            'SELECT id_zone_livraison, libelle, frais_livraison
               FROM zones_livraison
              WHERE id_zone_livraison = :zone
                AND est_active = 1
              FOR UPDATE'
        );
        $zoneStatement->execute(['zone' => $deliveryZoneId]);
        $deliveryZone = $zoneStatement->fetch();
        if (!$deliveryZone) {
            throw new RuntimeException('La zone de livraison sélectionnée n’est plus disponible.');
        }
        $deliveryFee = (float) $deliveryZone['frais_livraison'];
        $quarterStatement = $pdo->prepare(
            'SELECT id_quartier_livraison, libelle
               FROM quartiers_livraison
              WHERE id_quartier_livraison = :quartier
                AND id_zone_livraison = :zone
                AND est_actif = 1
              FOR UPDATE'
        );
        $quarterStatement->execute(['quartier' => $deliveryQuarterId, 'zone' => $deliveryZoneId]);
        $deliveryQuarter = $quarterStatement->fetch();
        if (!$deliveryQuarter) {
            throw new RuntimeException('Le quartier sélectionné ne correspond pas à la zone de livraison.');
        }
    }

    $placeholders = [];
    $parameters = [];
    foreach (array_keys($items) as $index => $productId) {
        $key = 'product_' . $index;
        $placeholders[] = ':' . $key;
        $parameters[$key] = $productId;
    }
    $productsStatement = $pdo->prepare(
        'SELECT p.id_produit, p.libelle, p.prix_unitaire, p.quantite_stock
           FROM produits p
           JOIN categories c ON c.id_categorie = p.id_categorie
          WHERE p.id_produit IN (' . implode(', ', $placeholders) . ')
            AND p.est_actif = 1
            AND c.est_active = 1
          ORDER BY p.id_produit
          FOR UPDATE'
    );
    $productsStatement->execute($parameters);
    $products = [];
    foreach ($productsStatement->fetchAll() as $product) {
        $products[(int) $product['id_produit']] = $product;
    }
    if (count($products) !== count($items)) {
        throw new RuntimeException('Un produit de votre panier n’est plus proposé. Vérifiez votre panier.');
    }

    $productsTotal = 0.0;
    foreach ($items as $productId => $quantity) {
        $product = $products[$productId];
        if ((float) $product['quantite_stock'] < $quantity) {
            throw new RuntimeException('Le stock de « ' . $product['libelle'] . ' » a changé. Ajustez votre panier.');
        }
        $productsTotal += round($quantity * (float) $product['prix_unitaire'], 2);
    }
    $total = round($productsTotal + $deliveryFee, 2);

    $clientStatement = $pdo->prepare('SELECT id_client FROM clients WHERE telephone = :telephone FOR UPDATE');
    $clientStatement->execute(['telephone' => $checkout['telephone_client']]);
    $clientId = (int) $clientStatement->fetchColumn();
    if ($clientId > 0) {
        $pdo->prepare(
            'UPDATE clients
                SET nom_complet = :nom,
                    email = :email
              WHERE id_client = :client'
        )->execute([
            'nom' => $checkout['nom_client'],
            'email' => $checkout['email_client'],
            'client' => $clientId,
        ]);
    } else {
        $pdo->prepare('INSERT INTO clients (nom_complet, telephone, email) VALUES (:nom, :telephone, :email)')
            ->execute([
                'nom' => $checkout['nom_client'],
                'telephone' => $checkout['telephone_client'],
                'email' => $checkout['email_client'],
            ]);
        $clientId = (int) $pdo->lastInsertId();
    }

    $orderId = 0;
    $orderNumber = '';
    for ($attempt = 0; $attempt < 3 && $orderId === 0; $attempt++) {
        $orderNumber = generateClientOrderNumber();
        try {
            $pdo->prepare(
                'INSERT INTO commandes (
                    id_client, numero_commande, origine_commande, mode_retrait, id_zone_livraison, id_quartier_livraison, adresse_livraison,
                    total_produits, frais_livraison, montant_total, note_client
                 ) VALUES (
                    :client, :numero, \'EN_LIGNE\', :retrait, :zone, :quartier, :adresse, 0, :frais, :total, :note
                 )'
            )->execute([
                'client' => $clientId,
                'numero' => $orderNumber,
                'retrait' => $checkout['mode_retrait'],
                'zone' => $deliveryZone ? (int) $deliveryZone['id_zone_livraison'] : null,
                'quartier' => $deliveryQuarter ? (int) $deliveryQuarter['id_quartier_livraison'] : null,
                'adresse' => $checkout['mode_retrait'] === 'LIVRAISON' ? $checkout['adresse_livraison'] : null,
                'frais' => $deliveryFee,
                'total' => $total,
                'note' => $checkout['note_client'] !== '' ? $checkout['note_client'] : null,
            ]);
            $orderId = (int) $pdo->lastInsertId();
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000' || $attempt === 2) {
                throw $exception;
            }
        }
    }
    if ($orderId === 0) {
        throw new RuntimeException('Impossible de générer votre numéro de commande.');
    }

    $lineStatement = $pdo->prepare(
        'INSERT INTO lignes_commande (id_commande, id_produit, quantite, prix_unitaire_applique)
         VALUES (:commande, :produit, :quantite, :prix)'
    );
    $movementStatement = $pdo->prepare(
        "INSERT INTO mouvements_stock (
            id_produit, id_ligne_commande, id_utilisateur, type_mouvement, quantite,
            stock_avant, stock_apres, motif, reference_source
         ) VALUES (
            :produit, :ligne, NULL, 'SORTIE', :quantite, 0, 0, :motif, :reference
         )"
    );
    foreach ($items as $productId => $quantity) {
        $lineStatement->execute([
            'commande' => $orderId,
            'produit' => $productId,
            'quantite' => $quantity,
            'prix' => $products[$productId]['prix_unitaire'],
        ]);
        $movementStatement->execute([
            'produit' => $productId,
            'ligne' => (int) $pdo->lastInsertId(),
            'quantite' => $quantity,
            'motif' => 'Commande en ligne ' . $orderNumber,
            'reference' => $orderNumber,
        ]);
    }

    $pdo->prepare(
        "INSERT INTO paiements (id_commande, mode_paiement, statut_paiement, montant)
         VALUES (:commande, :mode, 'EN_ATTENTE', :montant)"
    )->execute(['commande' => $orderId, 'mode' => $checkout['mode_paiement'], 'montant' => $total]);

    $managerStatement = $pdo->query(
        "SELECT u.id_utilisateur
           FROM utilisateurs u
           JOIN roles r ON r.id_role = u.id_role
          WHERE u.est_actif = 1
            AND r.est_actif = 1
            AND r.code_role IN ('GERANT', 'ADMINISTRATEUR')"
    );
    foreach ($managerStatement->fetchAll() as $manager) {
        createNotification(
            $pdo,
            $orderId,
            'NOUVELLE_COMMANDE',
            sprintf('Nouvelle commande en ligne %s à traiter (%s).', $orderNumber, moneyFcfa($total)),
            null,
            (int) $manager['id_utilisateur']
        );
    }
    recordAuditEvent(
        $pdo,
        'CREATION_COMMANDE_EN_LIGNE',
        'COMMANDE',
        $orderId,
        null,
        [
            'numero' => $orderNumber,
            'client' => $clientId,
            'articles' => count($items),
            'total' => $total,
            'paiement' => $checkout['mode_paiement'],
            'quartier' => $deliveryQuarter['libelle'] ?? null,
        ]
    );
    $pdo->commit();

    clearClientCart();
    unset($_SESSION['client_checkout']);
    grantClientOrderAccess($clientId, $orderNumber);
    if ($deliveryZone !== null) {
        flash('warning', 'Zone « ' . $deliveryZone['libelle'] . ' » et quartier « ' . $deliveryQuarter['libelle'] . ' » retenus. Le coût final pourra être ajusté par la boutique si l’adresse indiquée appartient à une autre zone.');
    }
    redirect('paiement-commande.php?commande=' . rawurlencode($orderNumber));
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Votre commande n’a pas pu être enregistrée. Vérifiez le panier et réessayez.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('validation-commande.php');
