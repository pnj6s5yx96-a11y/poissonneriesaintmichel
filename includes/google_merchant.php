<?php
declare(strict_types=1);

/**
 * Outils communs au flux Google Merchant et aux pages produit.
 * Les données sont volontairement limitées aux informations réellement
 * présentes dans le catalogue : aucun GTIN, MPN ou marque n'est inventé.
 */

/** @param array<string, mixed> $product */
function merchantProductId(array $product): string
{
    return 'psm-' . (int) ($product['id_produit'] ?? 0);
}

/** @param array<string, mixed> $product */
function merchantProductUrl(array $product): string
{
    return publicUrl('produit.php?produit=' . (int) ($product['id_produit'] ?? 0));
}

function merchantUnitLabel(string $unit): string
{
    return match ($unit) {
        'KG' => 'kilogramme',
        'CARTON' => 'carton',
        'ALVEOLE' => 'alvéole',
        'UNITE' => 'unité',
        'PAQUET' => 'paquet',
        default => 'unité',
    };
}

/** @param array<string, mixed> $product */
function merchantProductTitle(array $product): string
{
    $label = trim((string) ($product['libelle'] ?? 'Produit'));
    $category = trim((string) ($product['categorie'] ?? 'Produits alimentaires'));
    $unit = merchantUnitLabel((string) ($product['unite_vente'] ?? 'AUTRE'));

    return mb_strimwidth($label . ' — ' . $category . ' (' . $unit . ')', 0, 150, '…');
}

/** @param array<string, mixed> $product */
function merchantProductDescription(array $product): string
{
    $description = trim(strip_tags((string) ($product['description'] ?? '')));
    $description = (string) preg_replace('/\s+/u', ' ', $description);

    if ($description === '') {
        $description = trim((string) ($product['libelle'] ?? 'Produit'))
            . ', catégorie ' . trim((string) ($product['categorie'] ?? 'produits alimentaires'))
            . ', vendu à l’' . merchantUnitLabel((string) ($product['unite_vente'] ?? 'AUTRE')) . '.';
    }

    return mb_strimwidth($description, 0, 5000, '…');
}

/**
 * Retourne uniquement une photo produit téléversée par l'administration.
 * Une image générique de la boutique ne doit pas être envoyée à Merchant.
 */
function merchantProductImageUrl(?string $photoPath): ?string
{
    $photoPath = ltrim(trim((string) $photoPath), '/');
    if (preg_match('#^assets/uploads/produits/[A-Za-z0-9._-]+\.(?:jpe?g|png|webp)$#i', $photoPath) !== 1) {
        return null;
    }

    return publicUrl($photoPath);
}

/** @param array<string, mixed> $product */
function merchantProductAvailability(array $product): string
{
    return (float) ($product['quantite_stock'] ?? 0) > 0 ? 'in_stock' : 'out_of_stock';
}

/** @param array<string, mixed> $product */
function merchantProductIsReady(array $product): bool
{
    return (int) ($product['id_produit'] ?? 0) > 0
        && trim((string) ($product['libelle'] ?? '')) !== ''
        && (float) ($product['prix_unitaire'] ?? -1) >= 0
        && merchantProductImageUrl((string) ($product['photo_url'] ?? '')) !== null;
}

function merchantXml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
