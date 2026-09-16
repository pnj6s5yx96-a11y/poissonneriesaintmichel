<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

function isAuthenticated(): bool
{
    return isset($_SESSION['utilisateur']['id']);
}

function currentUser(): ?array
{
    return isAuthenticated() ? $_SESSION['utilisateur'] : null;
}

function dashboardPathForRole(string $role): string
{
    return match ($role) {
        'ADMINISTRATEUR' => 'admin/index.php',
        'GERANT' => 'gerant/index.php',
        'LIVREUR' => 'livreur/index.php',
        default => '',
    };
}

function dashboardSectionFromScript(string $scriptName): ?string
{
    if (preg_match('#/(admin|gerant|livreur)/[^/]+$#', str_replace('\\', '/', $scriptName), $matches) !== 1) {
        return null;
    }

    return $matches[1];
}

/**
 * @return list<array{href: string, label: string, icon: string}>
 */
function dashboardMenuForSection(string $section): array
{
    return match ($section) {
        'admin' => [
            ['href' => 'admin/index.php', 'label' => 'Vue d’ensemble', 'icon' => '⌂'],
            ['href' => 'gerant/commandes.php', 'label' => 'Commandes & ventes', 'icon' => '▣'],
            ['href' => 'admin/utilisateurs.php', 'label' => 'Utilisateurs', 'icon' => '◉'],
            ['href' => 'admin/categories.php', 'label' => 'Catégories', 'icon' => '◇'],
            ['href' => 'admin/produits.php', 'label' => 'Produits', 'icon' => '✦'],
            ['href' => 'admin/historique.php', 'label' => 'Historique', 'icon' => '◷'],
            ['href' => 'admin/rapports.php', 'label' => 'Rapports', 'icon' => '⇩'],
            ['href' => 'admin/parametres.php', 'label' => 'Paramètres', 'icon' => '⚙'],
            ['href' => 'admin/profil.php', 'label' => 'Mon profil', 'icon' => '◌'],
        ],
        'gerant' => [
            ['href' => 'gerant/index.php', 'label' => 'Vue d’ensemble', 'icon' => '⌂'],
            ['href' => 'gerant/commandes.php', 'label' => 'Commandes', 'icon' => '▣'],
            ['href' => 'gerant/stock.php', 'label' => 'Stock', 'icon' => '◫'],
            ['href' => 'gerant/paiements.php', 'label' => 'Paiements', 'icon' => '¤'],
            ['href' => 'gerant/livraisons.php', 'label' => 'Livraisons', 'icon' => '↗'],
        ],
        'livreur' => [
            ['href' => 'livreur/index.php', 'label' => 'Vue d’ensemble', 'icon' => '⌂'],
            ['href' => 'livreur/livraisons.php', 'label' => 'Mes livraisons', 'icon' => '↗'],
            ['href' => 'livreur/historique.php', 'label' => 'Historique', 'icon' => '◷'],
        ],
        default => [],
    };
}

function dashboardSectionLabel(string $section): string
{
    return match ($section) {
        'admin' => 'Administration',
        'gerant' => 'Gestion boutique',
        'livreur' => 'Espace livreur',
        default => 'Espace équipe',
    };
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['utilisateur'] = [
        'id' => (int) $user['id_utilisateur'],
        'nom' => (string) $user['nom_complet'],
        'role' => (string) $user['code_role'],
    ];
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        redirect('auth/connexion.php');
    }

    try {
        require_once __DIR__ . '/../config/database.php';
        $statement = db()->prepare(
            'SELECT u.nom_complet, r.code_role
             FROM utilisateurs u
             JOIN roles r ON r.id_role = u.id_role
             WHERE u.id_utilisateur = :id
               AND u.est_actif = 1
               AND r.est_actif = 1'
        );
        $statement->execute(['id' => (int) $_SESSION['utilisateur']['id']]);
        $user = $statement->fetch();
    } catch (Throwable $exception) {
        http_response_code(503);
        exit('La vérification de votre accès est momentanément indisponible.');
    }

    if (!$user) {
        $_SESSION = [];
        session_destroy();
        redirect('auth/connexion.php');
    }

    $_SESSION['utilisateur']['nom'] = (string) $user['nom_complet'];
    $_SESSION['utilisateur']['role'] = (string) $user['code_role'];
    header('Cache-Control: no-store, private');
}

function requireRole(array $roles): void
{
    requireAuth();

    $role = $_SESSION['utilisateur']['role'] ?? '';

    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('Accès non autorisé.');
    }
}
