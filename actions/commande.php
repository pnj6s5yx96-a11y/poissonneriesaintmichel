<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['GERANT', 'ADMINISTRATEUR']);

if (!isPost()) {
    redirect('gerant/nouvelle-commande.php');
}

verifyCsrfToken();

/** @return float Quantité positive, compatible avec la virgule décimale. */
function counterOrderQuantity(mixed $value): float
{
    if (!is_scalar($value)) {
        throw new RuntimeException('Chaque quantité doit être un nombre valide.');
    }

    $normalized = str_replace(',', '.', trim((string) $value));
    if ($normalized === '' || preg_match('/^[0-9]+(?:\.[0-9]{1,3})?$/', $normalized) !== 1) {
        throw new RuntimeException('Chaque quantité doit être un nombre valide.');
    }

    $quantity = round((float) $normalized, 3);
    if ($quantity < 0.001 || $quantity > 999999.999) {
        throw new RuntimeException('Une quantité de commande est invalide.');
    }

    return $quantity;
}

/** Génère un numéro lisible ; la contrainte SQL garantit son unicité. */
function counterOrderNumber(): string
{
    return 'PSM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(8)));
}

try {
    postEnum('origine_commande', ['COMPTOIR']);

    $clientName = postString('nom_client', 150);
    $clientPhone = postString('telephone_client', 30);
    $clientEmail = postString('email_client', 191);
    $retrievalMode = postString('mode_retrait', 30);
    $deliveryZoneId = postOptionalPositiveInt('id_zone_livraison');
    $deliveryQuarterId = postOptionalPositiveInt('id_quartier_livraison');
    $deliveryAddress = postString('adresse_livraison', 500);
    $paymentMode = postString('mode_paiement', 30);
    $note = postString('note_client', 500);
    $productIds = postScalarList('produits', MAX_CART_ITEMS, 20);
    $quantities = postScalarList('quantites', MAX_CART_ITEMS, 32);
    $allowedPaymentModes = ['MTN_MOMO', 'MOOV_MONEY', 'CELTIS_CASH', 'ESPECES'];

    if ($clientName === '' || $clientPhone === '') {
        throw new RuntimeException('Le nom et le téléphone du client sont obligatoires.');
    }
    if ($clientEmail !== '' && !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('L’adresse e-mail du client est invalide.');
    }
    $retrievalMode = postEnum('mode_retrait', ['LIVRAISON', 'RETRAIT_BOUTIQUE']);
    if ($retrievalMode === 'LIVRAISON' && $deliveryAddress === '') {
        throw new RuntimeException('L’adresse de livraison est obligatoire.');
    }
    if ($retrievalMode === 'LIVRAISON' && $deliveryZoneId === null) {
        throw new RuntimeException('Sélectionnez une zone de livraison.');
    }
    if ($retrievalMode === 'LIVRAISON' && $deliveryQuarterId === null) {
        throw new RuntimeException('Sélectionnez un quartier de livraison.');
    }
    $paymentMode = postEnum('mode_paiement', $allowedPaymentModes);
    if (count($productIds) === 0 || count($productIds) !== count($quantities)) {
        throw new RuntimeException('Ajoutez au moins un article à la commande.');
    }

    $items = [];
    foreach ($productIds as $index => $productId) {
        $productId = (string) $productId;
        $id = preg_match('/^[0-9]+$/', $productId) === 1
            ? filter_var($productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        if ($id === false || $id === null || isset($items[$id])) {
            throw new RuntimeException('Chaque produit ne peut apparaître qu’une seule fois dans la commande.');
        }
        $items[(int) $id] = counterOrderQuantity($quantities[$index] ?? null);
    }
    ksort($items, SORT_NUMERIC);

    $pdo = db();
    $pdo->beginTransaction();

    $deliveryZone = null;
    $deliveryQuarter = null;
    $deliveryFee = 0.0;
    if ($retrievalMode === 'LIVRAISON') {
        $zoneStatement = $pdo->prepare(
            'SELECT id_zone_livraison, frais_livraison
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
    $params = [];
    foreach (array_keys($items) as $index => $productId) {
        $parameter = 'produit_' . $index;
        $placeholders[] = ':' . $parameter;
        $params[$parameter] = $productId;
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
    $productsStatement->execute($params);
    $products = [];
    foreach ($productsStatement->fetchAll() as $product) {
        $products[(int) $product['id_produit']] = $product;
    }
    if (count($products) !== count($items)) {
        throw new RuntimeException('Un des produits sélectionnés est introuvable ou n’est plus vendu.');
    }

    $productsTotal = 0.0;
    foreach ($items as $productId => $quantity) {
        $product = $products[$productId];
        if ((float) $product['quantite_stock'] < $quantity) {
            throw new RuntimeException('Le stock de « ' . $product['libelle'] . ' » est insuffisant.');
        }
        $productsTotal += round($quantity * (float) $product['prix_unitaire'], 2);
    }
    $total = round($productsTotal + $deliveryFee, 2);

    $clientStatement = $pdo->prepare('SELECT id_client FROM clients WHERE telephone = :telephone FOR UPDATE');
    $clientStatement->execute(['telephone' => $clientPhone]);
    $clientId = (int) $clientStatement->fetchColumn();
    if ($clientId > 0) {
        if ($clientEmail !== '') {
            $pdo->prepare('UPDATE clients SET nom_complet = :nom, email = :email WHERE id_client = :client')
                ->execute(['nom' => $clientName, 'email' => $clientEmail, 'client' => $clientId]);
        } else {
            $pdo->prepare('UPDATE clients SET nom_complet = :nom WHERE id_client = :client')
                ->execute(['nom' => $clientName, 'client' => $clientId]);
        }
    } else {
        $pdo->prepare('INSERT INTO clients (nom_complet, telephone, email) VALUES (:nom, :telephone, :email)')
            ->execute([
                'nom' => $clientName,
                'telephone' => $clientPhone,
                'email' => $clientEmail !== '' ? $clientEmail : null,
            ]);
        $clientId = (int) $pdo->lastInsertId();
    }

    $orderId = 0;
    $number = '';
    for ($attempt = 0; $attempt < 3 && $orderId === 0; $attempt++) {
        $number = counterOrderNumber();
        try {
            $pdo->prepare(
                'INSERT INTO commandes (
                    id_client, numero_commande, origine_commande, mode_retrait, id_zone_livraison, id_quartier_livraison, adresse_livraison,
                    total_produits, frais_livraison, montant_total, note_client
                 ) VALUES (
                    :client, :numero, \'COMPTOIR\', :retrait, :zone, :quartier, :adresse, 0, :frais, :total, :note
                 )'
            )->execute([
                'client' => $clientId,
                'numero' => $number,
                'retrait' => $retrievalMode,
                'zone' => $deliveryZone ? (int) $deliveryZone['id_zone_livraison'] : null,
                'quartier' => $deliveryQuarter ? (int) $deliveryQuarter['id_quartier_livraison'] : null,
                'adresse' => $retrievalMode === 'LIVRAISON' ? $deliveryAddress : null,
                'frais' => $deliveryFee,
                'total' => $total,
                'note' => $note !== '' ? $note : null,
            ]);
            $orderId = (int) $pdo->lastInsertId();
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000' || $attempt === 2) {
                throw $exception;
            }
        }
    }
    if ($orderId === 0) {
        throw new RuntimeException('Impossible de générer le numéro de commande.');
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
            :produit, :ligne, :utilisateur, 'SORTIE', :quantite, 0, 0, :motif, :reference
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
            'utilisateur' => (int) currentUser()['id'],
            'quantite' => $quantity,
            'motif' => 'Vente comptoir ' . $number,
            'reference' => $number,
        ]);
    }

    $pdo->prepare(
        "INSERT INTO paiements (id_commande, mode_paiement, statut_paiement, montant)
         VALUES (:commande, :mode, 'EN_ATTENTE', :montant)"
    )->execute(['commande' => $orderId, 'mode' => $paymentMode, 'montant' => $total]);
    recordAuditEvent(
        $pdo,
        'CREATION_COMMANDE_COMPTOIR',
        'COMMANDE',
        $orderId,
        null,
        [
            'numero' => $number,
            'client' => $clientId,
            'articles' => count($items),
            'total' => $total,
            'paiement' => $paymentMode,
            'quartier' => $deliveryQuarter['libelle'] ?? null,
        ]
    );
    $pdo->commit();

    flash('success', 'Commande ' . $number . ' enregistrée. Finalisez l’encaissement depuis le registre des paiements.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'La commande ne peut pas être enregistrée. Vérifiez le stock et réessayez.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $exception->getMessage());
}

redirect('gerant/commandes.php');
