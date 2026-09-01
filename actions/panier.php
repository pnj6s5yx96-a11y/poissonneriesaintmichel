<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

if (!isPost()) {
    redirect('panier.php');
}

verifyCsrfToken();

try {
    $action = postString('action', 20);
    if ($action === '' && postHasValue('id_produit')) {
        $action = 'remove';
    }
    if (!in_array($action, ['add', 'update', 'remove', 'remove_missing'], true)) {
        throw new RuntimeException('Action de panier inconnue.');
    }

    if ($action === 'add') {
        $productId = postPositiveInt('id_produit');

        $quantity = clientCartQuantity(postRequiredScalar('quantite', 32));
        $statement = db()->prepare(
            'SELECT p.libelle, p.quantite_stock
               FROM produits p
               JOIN categories c ON c.id_categorie = p.id_categorie
              WHERE p.id_produit = :produit
                AND p.est_actif = 1
                AND c.est_active = 1'
        );
        $statement->execute(['produit' => $productId]);
        $product = $statement->fetch();

        if (!$product || (float) $product['quantite_stock'] <= 0) {
            throw new RuntimeException('Ce produit n’est plus disponible.');
        }

        $cart = clientCart();
        if (!isset($cart[$productId]) && count($cart) >= MAX_CART_ITEMS) {
            throw new RuntimeException('Votre panier ne peut pas contenir plus de ' . MAX_CART_ITEMS . ' articles différents.');
        }
        $currentQuantity = $cart[$productId] ?? 0.0;
        if ($currentQuantity + $quantity > (float) $product['quantite_stock']) {
            throw new RuntimeException('La quantité demandée dépasse le stock disponible pour « ' . $product['libelle'] . ' ».');
        }

        addToClientCart((int) $productId, $quantity);
        flash('success', '« ' . $product['libelle'] . ' » a été ajouté au panier.');
        back('catalogue.php');
    }

    if ($action === 'update') {
        $submittedQuantities = postScalarMap('quantites', MAX_CART_ITEMS, 32);

        $updatedCart = [];
        foreach ($submittedQuantities as $productId => $quantity) {
            if (preg_match('/^[0-9]+$/', $productId) !== 1) {
                throw new RuntimeException('Un produit du panier est invalide.');
            }
            $id = filter_var($productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false || $id === null) {
                throw new RuntimeException('Un produit du panier est invalide.');
            }
            $updatedCart[(int) $id] = clientCartQuantity($quantity);
        }

        replaceClientCart($updatedCart);
        flash('success', 'Votre panier a été mis à jour.');
        redirect('panier.php');
    }

    if ($action === 'remove') {
        $productId = postPositiveInt('id_produit');

        removeFromClientCart((int) $productId);
        flash('success', 'Le produit a été retiré du panier.');
        redirect('panier.php');
    }

    if ($action === 'remove_missing') {
        $cart = clientCart();
        if ($cart === []) {
            throw new RuntimeException('Votre panier est déjà vide.');
        }

        $placeholders = [];
        $parameters = [];
        foreach (array_keys($cart) as $index => $productId) {
            $key = 'product_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = $productId;
        }
        $statement = db()->prepare(
            'SELECT p.id_produit
               FROM produits p
               JOIN categories c ON c.id_categorie = p.id_categorie
              WHERE p.id_produit IN (' . implode(', ', $placeholders) . ')
                AND p.est_actif = 1
                AND c.est_active = 1'
        );
        $statement->execute($parameters);
        $availableProductIds = array_map(
            static fn (array $product): int => (int) $product['id_produit'],
            $statement->fetchAll()
        );
        $availableProducts = array_fill_keys($availableProductIds, true);
        $cleanCart = array_intersect_key($cart, $availableProducts);
        $removedCount = count($cart) - count($cleanCart);

        replaceClientCart($cleanCart);
        flash(
            'success',
            $removedCount > 0
                ? $removedCount . ' article' . ($removedCount > 1 ? 's ont été retirés' : ' a été retiré') . ' du panier.'
                : 'Votre panier ne contient plus d’article indisponible.'
        );
        redirect('panier.php');
    }

    throw new RuntimeException('Action de panier inconnue.');
} catch (Throwable $exception) {
    flash('error', $exception->getMessage());
    back('panier.php');
}
