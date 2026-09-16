<?php
declare(strict_types=1);

/**
 * Le panier client reste anonyme : il est conservé dans la session et les prix
 * comme les stocks sont toujours relus en base avant toute commande.
 *
 * @return array<int, float>
 */
function clientCart(): array
{
    $storedCart = $_SESSION['client_cart'] ?? [];
    if (!is_array($storedCart)) {
        $_SESSION['client_cart'] = [];

        return [];
    }

    $cart = [];
    foreach ($storedCart as $productId => $quantity) {
        if (count($cart) >= MAX_CART_ITEMS) {
            break;
        }
        $id = filter_var($productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $value = is_scalar($quantity) ? (float) $quantity : 0.0;
        if ($id !== false && is_finite($value) && $value >= 0.001 && $value <= 999999.999) {
            $cart[(int) $id] = round($value, 3);
        }
    }

    $_SESSION['client_cart'] = $cart;

    return $cart;
}

function clientCartQuantity(mixed $value): float
{
    if (!is_scalar($value)) {
        throw new RuntimeException('La quantité doit être un nombre valide.');
    }

    $normalized = str_replace(',', '.', trim((string) $value));
    if ($normalized === '' || preg_match('/^[0-9]+(?:\.[0-9]{1,3})?$/', $normalized) !== 1) {
        throw new RuntimeException('La quantité doit être un nombre valide.');
    }

    $quantity = round((float) $normalized, 3);
    if (!is_finite($quantity) || $quantity < 0.001 || $quantity > 999999.999) {
        throw new RuntimeException('La quantité doit être comprise entre 0,001 et 999 999,999.');
    }

    return $quantity;
}

function addToClientCart(int $productId, float $quantity): void
{
    if ($productId <= 0) {
        throw new RuntimeException('Produit introuvable.');
    }

    $cart = clientCart();
    $cart[$productId] = round(($cart[$productId] ?? 0.0) + $quantity, 3);
    if ($cart[$productId] > 999999.999) {
        throw new RuntimeException('La quantité maximale par produit a été dépassée.');
    }

    $_SESSION['client_cart'] = $cart;
}

/** @param array<int, float> $cart */
function replaceClientCart(array $cart): void
{
    $_SESSION['client_cart'] = $cart;
    clientCart();
}

function removeFromClientCart(int $productId): void
{
    $cart = clientCart();
    unset($cart[$productId]);
    $_SESSION['client_cart'] = $cart;
}

function clearClientCart(): void
{
    $_SESSION['client_cart'] = [];
}

function generateClientOrderNumber(): string
{
    return 'PSM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(8)));
}

function grantClientOrderAccess(int $clientId, string $orderNumber): void
{
    if ($clientId <= 0 || $orderNumber === '') {
        return;
    }

    $access = $_SESSION['client_order_access'] ?? [];
    if (!is_array($access)) {
        $access = [];
    }

    $now = time();
    foreach ($access as $number => $entry) {
        if (!is_array($entry) || (int) ($entry['expires'] ?? 0) < $now) {
            unset($access[$number]);
        }
    }
    $access[$orderNumber] = ['client_id' => $clientId, 'expires' => $now + 60 * 60 * 24 * 30];
    $_SESSION['client_order_access'] = $access;
}

function clientCanAccessOrder(int $clientId, string $orderNumber): bool
{
    $entry = $_SESSION['client_order_access'][$orderNumber] ?? null;

    return is_array($entry)
        && (int) ($entry['client_id'] ?? 0) === $clientId
        && (int) ($entry['expires'] ?? 0) >= time();
}

function clientCartItemCount(): int
{
    return count(clientCart());
}

/**
 * @return list<array<string, mixed>>
 */
function clientCartProducts(PDO $pdo): array
{
    $cart = clientCart();
    if ($cart === []) {
        return [];
    }

    $placeholders = [];
    $parameters = [];
    foreach (array_keys($cart) as $index => $productId) {
        $key = 'product_' . $index;
        $placeholders[] = ':' . $key;
        $parameters[$key] = $productId;
    }

    $statement = $pdo->prepare(
        'SELECT p.id_produit, p.libelle, p.description, p.prix_unitaire, p.unite_vente,
                p.quantite_stock, p.photo_url, p.est_actif, c.libelle AS categorie,
                c.est_active AS categorie_active
           FROM produits p
           JOIN categories c ON c.id_categorie = p.id_categorie
          WHERE p.id_produit IN (' . implode(', ', $placeholders) . ')
          ORDER BY c.libelle, p.libelle'
    );
    $statement->execute($parameters);

    $products = [];
    foreach ($statement->fetchAll() as $product) {
        $id = (int) $product['id_produit'];
        $product['quantite_panier'] = $cart[$id];
        $product['est_disponible'] = (int) $product['est_actif'] === 1
            && (int) $product['categorie_active'] === 1
            && (float) $product['quantite_stock'] > 0;
        $product['stock_suffisant'] = $product['est_disponible']
            && (float) $product['quantite_stock'] >= (float) $product['quantite_panier'];
        $product['sous_total'] = round((float) $product['prix_unitaire'] * (float) $product['quantite_panier'], 2);
        $products[] = $product;
    }

    return $products;
}

/**
 * @param list<array<string, mixed>> $products
 * @return array{articles: float, total: float, is_valid: bool}
 */
function clientCartSummary(array $products, float $deliveryFee = 0.0): array
{
    $articlesTotal = 0.0;
    $isValid = $products !== [] && count($products) === clientCartItemCount();

    foreach ($products as $product) {
        $articlesTotal += (float) $product['sous_total'];
        $isValid = $isValid && (bool) $product['stock_suffisant'];
    }

    return [
        'articles' => round($articlesTotal, 2),
        'total' => round($articlesTotal + $deliveryFee, 2),
        'is_valid' => $isValid,
    ];
}
