<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header(
        "Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; "
        . "object-src 'none'; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com; "
        . "style-src 'self' 'nonce-" . cspNonce() . "' https://fonts.googleapis.com; script-src 'self' 'nonce-" . cspNonce() . "'"
    );

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function cspNonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return $nonce;
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Retourne une URL absolue stable pour les moteurs de recherche et le partage. */
function publicUrl(string $path = ''): string
{
    return PUBLIC_SITE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): void
{
    $submittedValue = $_POST['csrf_token'] ?? '';
    $submitted = is_scalar($submittedValue) ? (string) $submittedValue : '';
    $stored = (string) ($_SESSION['csrf_token'] ?? '');

    if ($submitted === '' || $stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(419);
        exit('Votre session a expiré. Rechargez la page puis réessayez.');
    }
}

function postCheckbox(string $key): int
{
    $value = $_POST[$key] ?? null;

    return is_scalar($value) && in_array((string) $value, ['1', 'on'], true) ? 1 : 0;
}

function postHasValue(string $key): bool
{
    return array_key_exists($key, $_POST);
}

function postRequiredScalar(string $key, int $maximumLength = 4096): string
{
    $value = $_POST[$key] ?? null;
    if (!is_scalar($value)) {
        throw new InvalidArgumentException('La valeur « ' . $key . ' » est invalide.');
    }

    return mb_substr(str_replace("\0", '', (string) $value), 0, $maximumLength);
}

/**
 * Schéma pour une collection clé → valeur, par exemple les quantités d'un panier.
 *
 * @return array<string, string>
 */
function postScalarMap(string $key, int $maximumEntries, int $maximumValueLength = 100): array
{
    $value = $_POST[$key] ?? null;
    if (!is_array($value) || $maximumEntries < 1 || count($value) > $maximumEntries) {
        throw new InvalidArgumentException('La collection « ' . $key . ' » est invalide.');
    }

    $result = [];
    foreach ($value as $mapKey => $mapValue) {
        if (!is_int($mapKey) && !is_string($mapKey) || !is_scalar($mapValue)) {
            throw new InvalidArgumentException('La collection « ' . $key . ' » est invalide.');
        }
        $normalizedKey = (string) $mapKey;
        if ($normalizedKey === '' || array_key_exists($normalizedKey, $result)) {
            throw new InvalidArgumentException('La collection « ' . $key . ' » est invalide.');
        }
        $result[$normalizedKey] = mb_substr(str_replace("\0", '', (string) $mapValue), 0, $maximumValueLength);
    }

    return $result;
}

/**
 * Schéma pour une liste HTML `champ[]` : une liste compacte, bornée et scalaire.
 *
 * @return list<string>
 */
function postScalarList(string $key, int $maximumEntries, int $maximumValueLength = 100): array
{
    $value = $_POST[$key] ?? null;
    if (!is_array($value) || !array_is_list($value) || count($value) > $maximumEntries) {
        throw new InvalidArgumentException('La liste « ' . $key . ' » est invalide.');
    }

    $result = [];
    foreach ($value as $item) {
        if (!is_scalar($item)) {
            throw new InvalidArgumentException('La liste « ' . $key . ' » est invalide.');
        }
        $result[] = mb_substr(str_replace("\0", '', (string) $item), 0, $maximumValueLength);
    }

    return $result;
}

function postString(string $key, int $maxLength = 0): string
{
    $value = $_POST[$key] ?? '';
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim(str_replace("\0", '', (string) $value));

    return $maxLength > 0 ? mb_substr($value, 0, $maxLength) : $value;
}

function queryString(string $key, int $maxLength = 0): string
{
    $value = $_GET[$key] ?? '';
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim(str_replace("\0", '', (string) $value));

    return $maxLength > 0 ? mb_substr($value, 0, $maxLength) : $value;
}

/** @param list<string> $allowedValues */
function queryEnum(string $key, array $allowedValues, string $default = ''): string
{
    $value = queryString($key, 100);

    return in_array($value, $allowedValues, true) ? $value : $default;
}

function queryPositiveInt(string $key, ?int $default = null, int $maximum = PHP_INT_MAX): ?int
{
    $value = $_GET[$key] ?? null;
    if (!is_scalar($value) || preg_match('/^[0-9]+$/', (string) $value) !== 1) {
        return $default;
    }

    $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $integer === false || $integer === null || $integer > $maximum ? $default : (int) $integer;
}

function postPassword(string $key = 'mot_de_passe', int $maxLength = 4096): string
{
    $value = $_POST[$key] ?? '';
    if (!is_scalar($value)) {
        return '';
    }

    return mb_substr(str_replace("\0", '', (string) $value), 0, $maxLength);
}

function postPositiveInt(string $key): int
{
    $value = $_POST[$key] ?? null;
    if (!is_scalar($value) || preg_match('/^[0-9]+$/', (string) $value) !== 1) {
        throw new InvalidArgumentException('La valeur « ' . $key . ' » est invalide.');
    }

    $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($integer === false || $integer === null) {
        throw new InvalidArgumentException('La valeur « ' . $key . ' » est invalide.');
    }

    return (int) $integer;
}

function postOptionalPositiveInt(string $key): ?int
{
    $value = $_POST[$key] ?? null;

    return $value === null || $value === '' ? null : postPositiveInt($key);
}

function postDecimal(string $key, float $minimum, float $maximum, int $maximumFractionDigits = 2): float
{
    $value = $_POST[$key] ?? null;
    if (!is_scalar($value) || $maximumFractionDigits < 0 || $maximum < $minimum) {
        throw new InvalidArgumentException('La valeur « ' . $key . ' » est invalide.');
    }

    $normalized = str_replace(',', '.', trim((string) $value));
    $pattern = '/^[0-9]+(?:\\.[0-9]{1,' . $maximumFractionDigits . '})?$/';
    if ($normalized === '' || preg_match($pattern, $normalized) !== 1) {
        throw new InvalidArgumentException('La valeur « ' . $key . ' » est invalide.');
    }

    $number = (float) $normalized;
    if (!is_finite($number) || $number < $minimum || $number > $maximum) {
        throw new InvalidArgumentException('La valeur « ' . $key . ' » est invalide.');
    }

    return round($number, $maximumFractionDigits);
}

/** @param list<string> $allowedValues */
function postEnum(string $key, array $allowedValues): string
{
    $value = postString($key, 100);
    if (!in_array($value, $allowedValues, true)) {
        throw new InvalidArgumentException('La valeur « ' . $key . ' » est invalide.');
    }

    return $value;
}

function postEmail(string $key): string
{
    $email = mb_strtolower(postString($key, 191));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('L’adresse e-mail est invalide.');
    }

    return $email;
}

function postOrderNumber(string $key = 'numero_commande'): string
{
    $number = mb_strtoupper(postString($key, 40));
    if (preg_match('/^PSM-[0-9]{8}-[A-F0-9]{6,24}$/', $number) !== 1) {
        throw new InvalidArgumentException('Le numéro de commande est invalide.');
    }

    return $number;
}

function queryOrderNumber(string $key = 'numero'): string
{
    $number = mb_strtoupper(queryString($key, 40));
    if (preg_match('/^PSM-[0-9]{8}-[A-F0-9]{6,24}$/', $number) !== 1) {
        throw new InvalidArgumentException('Le numéro de commande est invalide.');
    }

    return $number;
}

function assertStrongPassword(string $password): void
{
    if (mb_strlen($password) < 12 || mb_strlen($password) > 4096) {
        throw new InvalidArgumentException('Le mot de passe doit contenir au moins 12 caractères.');
    }

    $classes = 0;
    $classes += preg_match('/\p{Ll}/u', $password) === 1 ? 1 : 0;
    $classes += preg_match('/\p{Lu}/u', $password) === 1 ? 1 : 0;
    $classes += preg_match('/\p{N}/u', $password) === 1 ? 1 : 0;
    $classes += preg_match('/[^\p{L}\p{N}\s]/u', $password) === 1 ? 1 : 0;
    if ($classes < 3) {
        throw new InvalidArgumentException('Le mot de passe doit combiner au moins trois types de caractères (majuscules, minuscules, chiffres ou symboles).');
    }
}

final class SecurityRateLimitException extends RuntimeException
{
}

function securityClientIp(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'inconnue';
}

function securityRateLimitKey(string $scope, string $subject = ''): string
{
    return hash('sha256', $scope . "\n" . securityClientIp() . "\n" . mb_strtolower(trim($subject)));
}

function enforceRateLimit(PDO $pdo, string $scope, string $subject, int $maximumAttempts, int $windowSeconds): void
{
    if ($scope === '' || $maximumAttempts < 1 || $windowSeconds < 1) {
        throw new InvalidArgumentException('Configuration de limite de sécurité invalide.');
    }

    $key = securityRateLimitKey($scope, $subject);
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $pdo->prepare(
            'INSERT INTO limites_securite (
                cle_rate_limite, type_limite, debut_fenetre, tentatives, derniere_tentative
             ) VALUES (:cle, :type, NOW(), 0, NOW())
             ON DUPLICATE KEY UPDATE cle_rate_limite = VALUES(cle_rate_limite)'
        )->execute(['cle' => $key, 'type' => $scope]);

        $statement = $pdo->prepare(
            'SELECT debut_fenetre, tentatives, bloque_jusqua
               FROM limites_securite
              WHERE cle_rate_limite = :cle
              FOR UPDATE'
        );
        $statement->execute(['cle' => $key]);
        $entry = $statement->fetch();
        if (!$entry) {
            throw new RuntimeException('La protection contre les tentatives répétées est indisponible.');
        }

        $now = new DateTimeImmutable('now');
        $blockedUntil = !empty($entry['bloque_jusqua']) ? new DateTimeImmutable((string) $entry['bloque_jusqua']) : null;
        if ($blockedUntil !== null && $blockedUntil > $now) {
            if ($ownsTransaction) {
                $pdo->commit();
            }
            throw new SecurityRateLimitException('Trop de tentatives ont été effectuées. Réessayez dans quelques minutes.');
        }

        $windowStart = new DateTimeImmutable((string) $entry['debut_fenetre']);
        $windowIsCurrent = $windowStart > $now->modify('-' . $windowSeconds . ' seconds');
        $attempts = $windowIsCurrent ? (int) $entry['tentatives'] : 0;
        if ($attempts >= $maximumAttempts) {
            $retryAt = $now->modify('+' . $windowSeconds . ' seconds');
            $pdo->prepare(
                'UPDATE limites_securite
                    SET bloque_jusqua = :bloque_jusqua,
                        derniere_tentative = NOW()
                  WHERE cle_rate_limite = :cle'
            )->execute(['bloque_jusqua' => $retryAt->format('Y-m-d H:i:s'), 'cle' => $key]);
            if ($ownsTransaction) {
                $pdo->commit();
            }
            throw new SecurityRateLimitException('Trop de tentatives ont été effectuées. Réessayez dans quelques minutes.');
        }

        $pdo->prepare(
            'UPDATE limites_securite
                SET debut_fenetre = :debut,
                    tentatives = :tentatives,
                    bloque_jusqua = NULL,
                    derniere_tentative = NOW()
              WHERE cle_rate_limite = :cle'
        )->execute([
            'debut' => $windowIsCurrent ? $windowStart->format('Y-m-d H:i:s') : $now->format('Y-m-d H:i:s'),
            'tentatives' => $attempts + 1,
            'cle' => $key,
        ]);
        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function clearRateLimit(PDO $pdo, string $scope, string $subject): void
{
    $pdo->prepare('DELETE FROM limites_securite WHERE cle_rate_limite = :cle')
        ->execute(['cle' => securityRateLimitKey($scope, $subject)]);
}

function back(string $fallback): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    if (is_string($referer) && str_starts_with($referer, BASE_URL)) {
        header('Location: ' . $referer);
        exit;
    }

    redirect($fallback);
}

/**
 * Téléverse une image produit et renvoie son chemin relatif public.
 * Les extensions PHP ne sont jamais acceptées, même si leur nom est maquillé.
 */
function uploadProductImage(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement de la photo a échoué.');
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('La photo ne doit pas dépasser 5 Mo.');
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Le fichier téléversé est invalide.');
    }

    $image = @getimagesize($temporaryPath);
    if ($image === false || !isset($image[0], $image[1], $image['mime'])
        || (int) $image[0] < 1 || (int) $image[1] < 1
        || (int) $image[0] * (int) $image[1] > MAX_UPLOAD_PIXELS) {
        throw new RuntimeException('La photo est invalide ou ses dimensions sont trop importantes.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime]) || $mime !== $image['mime']) {
        throw new RuntimeException('Seules les images JPG, PNG et WEBP sont acceptées.');
    }

    $directory = UPLOADS_PATH . '/produits';

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Le dossier des images produits est inaccessible.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $target = $directory . '/' . $filename;

    if (!move_uploaded_file($temporaryPath, $target)) {
        throw new RuntimeException('Impossible d’enregistrer la photo du produit.');
    }

    return 'assets/uploads/produits/' . $filename;
}

function deleteProductImage(?string $relativePath): void
{
    if ($relativePath === null || !str_starts_with($relativePath, 'assets/uploads/produits/')) {
        return;
    }

    $filename = basename($relativePath);
    if ($relativePath !== 'assets/uploads/produits/' . $filename
        || preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/', $filename) !== 1) {
        return;
    }

    $path = PROJECT_ROOT . '/' . $relativePath;

    if (is_file($path)) {
        unlink($path);
    }
}

/**
 * Enregistre une action métier sensible dans le journal d'audit.
 *
 * Cette fonction est appelée dans la même transaction que l'action concernée
 * afin qu'un événement ne puisse jamais pointer vers une modification annulée.
 *
 * @param array<string, mixed>|null $before
 * @param array<string, mixed>|null $after
 */
function recordAuditEvent(
    PDO $pdo,
    string $eventType,
    string $targetType,
    int $targetId,
    ?array $before = null,
    ?array $after = null
): void {
    if ($targetId <= 0) {
        throw new InvalidArgumentException('La cible du journal d’audit est invalide.');
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;

    $statement = $pdo->prepare(
        'INSERT INTO journal_audit (
            id_utilisateur, type_evenement, cible_type, cible_id,
            donnees_avant, donnees_apres, adresse_ip
         ) VALUES (
            :utilisateur, :type, :cible_type, :cible_id,
            :avant, :apres, :ip
         )'
    );
    $statement->execute([
        'utilisateur' => isset($_SESSION['utilisateur']['id']) ? (int) $_SESSION['utilisateur']['id'] : null,
        'type' => mb_substr($eventType, 0, 100),
        'cible_type' => $targetType,
        'cible_id' => $targetId,
        'avant' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        'apres' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        'ip' => $ip,
    ]);
}

/**
 * @return array{from: DateTimeImmutable, to: DateTimeImmutable}
 */
function adminDateRange(int $defaultDays = 30, int $maximumDays = 366): array
{
    $today = new DateTimeImmutable('today');
    $defaultDays = max(1, $defaultDays);
    $maximumDays = max($defaultDays, $maximumDays);

    $parse = static function (string $value): ?DateTimeImmutable {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    };

    $from = $parse(queryString('date_debut', 10)) ?? $today->modify('-' . ($defaultDays - 1) . ' days');
    $to = $parse(queryString('date_fin', 10)) ?? $today;

    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }

    if ($to > $today) {
        $to = $today;
    }

    if ($from > $to) {
        $from = $to;
    }

    if ($from->diff($to)->days >= $maximumDays) {
        $from = $to->modify('-' . ($maximumDays - 1) . ' days');
    }

    return ['from' => $from, 'to' => $to];
}

function adminDateTimeEnd(DateTimeImmutable $date): string
{
    return $date->modify('+1 day')->format('Y-m-d');
}

function moneyFcfa(float|int|string|null $amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
}

function paymentModeLabel(?string $mode): string
{
    return match ($mode) {
        'MTN_MOMO' => 'MTN Mobile Money',
        'MOOV_MONEY' => 'Moov Money',
        'CELTIS_CASH' => 'Celtis Cash',
        'ESPECES' => 'Espèces',
        default => 'Non initié',
    };
}

function orderStatusLabel(string $status): string
{
    return match ($status) {
        'EN_ATTENTE' => 'En attente',
        'EN_COURS_TRAITEMENT' => 'En cours de traitement',
        'TRAITEE' => 'Traitée',
        'EN_COURS_LIVRAISON' => 'En cours de livraison',
        'LIVREE' => 'Livrée',
        default => str_replace('_', ' ', $status),
    };
}

function orderStatusBadgeClass(string $status): string
{
    return match ($status) {
        'EN_ATTENTE' => 'badge-warning',
        'EN_COURS_TRAITEMENT', 'EN_COURS_LIVRAISON' => 'badge-info',
        'LIVREE' => 'badge-success',
        default => '',
    };
}

function paymentStatusLabel(string $status): string
{
    return match ($status) {
        'EN_ATTENTE' => 'En attente',
        'REUSSI' => 'Réussi',
        'ECHOUE' => 'Échoué',
        'ANNULE' => 'Annulé',
        default => str_replace('_', ' ', $status),
    };
}

function deliveryStatusLabel(string $status): string
{
    return match ($status) {
        'AFFECTEE' => 'Affectée',
        'ACCEPTEE' => 'Acceptée',
        'REFUSEE' => 'Refusée',
        'EN_COURS' => 'En cours',
        'LIVREE' => 'Livrée',
        'ANNULEE' => 'Annulée',
        default => str_replace('_', ' ', $status),
    };
}

function deliveryStatusBadgeClass(string $status): string
{
    return match ($status) {
        'AFFECTEE', 'ACCEPTEE' => 'badge-warning',
        'EN_COURS' => 'badge-info',
        'LIVREE' => 'badge-success',
        'REFUSEE', 'ANNULEE' => 'badge-danger',
        default => 'badge-muted',
    };
}

function auditTargetLabel(string $targetType): string
{
    return match ($targetType) {
        'COMMANDE' => 'Commande',
        'PAIEMENT' => 'Paiement',
        'PRODUIT' => 'Produit',
        'LIVRAISON' => 'Livraison',
        'STOCK' => 'Stock',
        'PARAMETRAGE' => 'Paramétrage',
        'UTILISATEUR' => 'Utilisateur',
        default => str_replace('_', ' ', $targetType),
    };
}

function auditEventTargetLabel(string $eventType, string $targetType): string
{
    if (str_contains($eventType, 'CATEGORIE')) {
        return 'Catégorie';
    }

    return auditTargetLabel($targetType);
}

/**
 * Crée une notification à destination d'un client ou d'un utilisateur interne.
 * Un seul destinataire est autorisé afin d'éviter les notifications ambiguës.
 */
function createNotification(
    PDO $pdo,
    int $orderId,
    string $type,
    string $message,
    ?int $clientId = null,
    ?int $userId = null
): void {
    if ($orderId <= 0 || ($clientId === null) === ($userId === null)) {
        throw new InvalidArgumentException('Le destinataire de la notification est invalide.');
    }

    $pdo->prepare(
        'INSERT INTO notifications (
            id_commande, id_client, id_utilisateur, type_notification, canal, message, statut_envoi
         ) VALUES (
            :commande, :client, :utilisateur, :type, \'WEB\', :message, \'A_ENVOYER\'
         )'
    )->execute([
        'commande' => $orderId,
        'client' => $clientId,
        'utilisateur' => $userId,
        'type' => mb_substr($type, 0, 60),
        'message' => mb_substr($message, 0, 5000),
    ]);
}

/**
 * Passe par la procédure SQL afin que les transitions et leur historique
 * restent validés par la base de données, quel que soit l'écran appelant.
 */
function changeOrderStatus(
    PDO $pdo,
    int $orderId,
    string $newStatus,
    string $origin,
    ?int $userId,
    string $comment = ''
): void {
    $statement = $pdo->prepare(
        'CALL sp_changer_statut_commande(:commande, :statut, :origine, :utilisateur, :commentaire)'
    );
    $statement->execute([
        'commande' => $orderId,
        'statut' => $newStatus,
        'origine' => $origin,
        'utilisateur' => $userId,
        'commentaire' => $comment !== '' ? mb_substr($comment, 0, 500) : null,
    ]);
    $statement->closeCursor();
}
