<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

try {
    $categoryId = queryPositiveInt('categorie');
    $search = queryString('q', 100);
    $sql = 'SELECT p.id_produit, p.libelle, p.description, p.prix_unitaire, p.unite_vente,
                   p.quantite_stock, p.photo_url, c.id_categorie, c.libelle AS categorie
              FROM produits p
              JOIN categories c ON c.id_categorie = p.id_categorie
             WHERE p.est_actif = 1
               AND c.est_active = 1';
    $parameters = [];
    if ($categoryId !== null) {
        $sql .= ' AND c.id_categorie = :categorie';
        $parameters['categorie'] = $categoryId;
    }
    if ($search !== '') {
        $sql .= ' AND (p.libelle LIKE :recherche OR p.description LIKE :recherche)';
        $parameters['recherche'] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY c.libelle, p.libelle';
    $statement = db()->prepare($sql);
    $statement->execute($parameters);

    echo json_encode([
        'produits' => $statement->fetchAll(),
        'recherche' => $search,
        'categorie' => $categoryId,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(['erreur' => 'Le catalogue est momentanément indisponible.'], JSON_UNESCAPED_UNICODE);
}
