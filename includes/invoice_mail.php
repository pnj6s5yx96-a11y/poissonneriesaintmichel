<?php
declare(strict_types=1);

require_once __DIR__ . '/invoice_pdf.php';

/**
 * Prépare le message contenant les factures PDF à renvoyer à un client.
 * La préparation est séparée de l'envoi afin de pouvoir être vérifiée sans
 * solliciter un serveur de messagerie.
 *
 * @param list<array{invoice: array<string, mixed>, lines: list<array<string, mixed>>}> $invoices
 * @param array<string, mixed> $settings
 * @return array{to: string, subject: string, body: string, headers: string}
 */
function buildInvoiceRecoveryEmail(string $recipient, array $invoices, array $settings): array
{
    $recipient = mb_strtolower(trim($recipient));
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('L’adresse e-mail destinataire est invalide.');
    }
    if ($invoices === []) {
        throw new InvalidArgumentException('Aucune facture ne peut être jointe au message.');
    }

    $shopName = invoiceMailHeaderText((string) ($settings['nom_boutique'] ?? 'Poissonnerie Saint-Michel'));
    $fromName = invoiceMailHeaderText((string) ($settings['mail_from_name'] ?? $shopName));
    $fromAddress = invoiceMailFromAddress($settings);
    $boundary = '=_PSM_' . bin2hex(random_bytes(16));
    $count = count($invoices);
    $subject = invoiceMailEncodedHeader(
        $count === 1 ? 'Votre facture Poissonnerie Saint-Michel' : 'Vos factures Poissonnerie Saint-Michel'
    );
    $message = [
        'Bonjour,',
        '',
        $count === 1
            ? 'Votre facture demandée est jointe à ce message au format PDF.'
            : 'Vos factures Mobile Money confirmées sont jointes à ce message au format PDF.',
        '',
        'Merci pour votre confiance.',
        $shopName,
    ];
    $body = '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= implode("\r\n", $message) . "\r\n";

    foreach ($invoices as $item) {
        $invoice = $item['invoice'];
        $pdf = buildInvoicePdf($invoice, $item['lines'], $settings);
        $filename = 'facture-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $invoice['numero_document']) . '.pdf';

        $body .= "\r\n--" . $boundary . "\r\n";
        $body .= "Content-Type: application/pdf; name=\"" . $filename . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
        $body .= chunk_split(base64_encode($pdf), 76, "\r\n");
    }
    $body .= '--' . $boundary . "--\r\n";

    $headers = [
        'MIME-Version: 1.0',
        'From: ' . invoiceMailEncodedHeader($fromName) . ' <' . $fromAddress . '>',
        'Reply-To: ' . $fromAddress,
        'X-Mailer: Poissonnerie Saint-Michel',
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
    ];

    return [
        'to' => $recipient,
        'subject' => $subject,
        'body' => $body,
        'headers' => implode("\r\n", $headers),
    ];
}

/**
 * @param list<array{invoice: array<string, mixed>, lines: list<array<string, mixed>>}> $invoices
 * @param array<string, mixed> $settings
 */
function sendInvoiceRecoveryEmail(string $recipient, array $invoices, array $settings): bool
{
    try {
        $transport = invoiceMailTransportConfig($settings);
        $settings['email_boutique'] = $transport['from_address'];
        $settings['mail_from_name'] = $transport['from_name'];
        $message = buildInvoiceRecoveryEmail($recipient, $invoices, $settings);

        if ($transport['transport'] === 'smtp') {
            return invoiceMailSendViaSmtp($message, $transport);
        }

        return @mail($message['to'], $message['subject'], $message['body'], $message['headers']);
    } catch (Throwable $exception) {
        error_log('Échec d’envoi de facture par e-mail : ' . $exception->getMessage());

        return false;
    }
}

/**
 * Lit les identifiants d’envoi locaux. Sans configuration SMTP, l’ancien
 * transport PHP mail() reste disponible pour les hébergements qui le gèrent.
 *
 * @param array<string, mixed> $settings
 * @return array{transport: 'native'|'smtp', from_address: string, from_name: string, host?: string, port?: int, encryption?: 'tls'|'ssl', username?: string, password?: string, timeout?: int}
 */
function invoiceMailTransportConfig(array $settings): array
{
    $default = [
        'transport' => 'native',
        'from_address' => invoiceMailFromAddress($settings),
        'from_name' => invoiceMailHeaderText((string) ($settings['nom_boutique'] ?? 'Poissonnerie Saint-Michel')),
    ];
    $configPath = dirname(__DIR__) . '/config/mail.local.php';
    if (!is_file($configPath)) {
        return $default;
    }

    $configured = require $configPath;
    if (!is_array($configured) || ($configured['transport'] ?? '') !== 'smtp') {
        throw new RuntimeException('La configuration SMTP locale est invalide.');
    }

    $host = trim((string) ($configured['host'] ?? ''));
    $port = filter_var($configured['port'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    $encryption = (string) ($configured['encryption'] ?? 'tls');
    $username = mb_strtolower(trim((string) ($configured['username'] ?? '')));
    $password = (string) ($configured['password'] ?? '');
    $fromAddress = mb_strtolower(trim((string) ($configured['from_address'] ?? $username)));
    $fromName = invoiceMailHeaderText((string) ($configured['from_name'] ?? $default['from_name']));
    $timeout = filter_var($configured['timeout'] ?? 15, FILTER_VALIDATE_INT, ['options' => ['min_range' => 5, 'max_range' => 60]]);

    $validHost = filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
        || filter_var($host, FILTER_VALIDATE_IP) !== false
        || $host === 'localhost';
    if (!$validHost || $port === false || !in_array($encryption, ['tls', 'ssl'], true)
        || !filter_var($username, FILTER_VALIDATE_EMAIL) || $password === ''
        || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || $timeout === false) {
        throw new RuntimeException('Les paramètres SMTP sont incomplets ou invalides.');
    }

    return [
        'transport' => 'smtp',
        'from_address' => $fromAddress,
        'from_name' => $fromName,
        'host' => $host,
        'port' => (int) $port,
        'encryption' => $encryption,
        'username' => $username,
        'password' => $password,
        'timeout' => (int) $timeout,
    ];
}

/**
 * Envoie le message à un serveur SMTP authentifié (Gmail ou équivalent),
 * sans dépendance externe. Les identifiants ne quittent jamais le fichier
 * local ignoré par Git.
 *
 * @param array{to: string, subject: string, body: string, headers: string} $message
 * @param array{transport: 'native'|'smtp', from_address: string, from_name: string, host?: string, port?: int, encryption?: 'tls'|'ssl', username?: string, password?: string, timeout?: int} $transport
 */
function invoiceMailSendViaSmtp(array $message, array $transport): bool
{
    $scheme = ($transport['encryption'] ?? 'tls') === 'ssl' ? 'ssl://' : 'tcp://';
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => (string) $transport['host'],
        ],
    ]);
    $socket = @stream_socket_client(
        $scheme . $transport['host'] . ':' . $transport['port'],
        $errorNumber,
        $errorMessage,
        (float) $transport['timeout'],
        STREAM_CLIENT_CONNECT,
        $context
    );
    if (!is_resource($socket)) {
        throw new RuntimeException('Connexion SMTP impossible (' . $errorNumber . ').');
    }

    try {
        stream_set_timeout($socket, (int) $transport['timeout']);
        invoiceSmtpExpect($socket, [220]);
        invoiceSmtpCommand($socket, 'EHLO localhost', [250]);

        if (($transport['encryption'] ?? 'tls') === 'tls') {
            invoiceSmtpCommand($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Activation TLS SMTP impossible.');
            }
            invoiceSmtpCommand($socket, 'EHLO localhost', [250]);
        }

        invoiceSmtpCommand($socket, 'AUTH LOGIN', [334]);
        invoiceSmtpCommand($socket, base64_encode((string) $transport['username']), [334]);
        invoiceSmtpCommand($socket, base64_encode((string) $transport['password']), [235]);
        invoiceSmtpCommand($socket, 'MAIL FROM:<' . $transport['from_address'] . '>', [250]);
        invoiceSmtpCommand($socket, 'RCPT TO:<' . $message['to'] . '>', [250, 251]);
        invoiceSmtpCommand($socket, 'DATA', [354]);

        $payload = 'To: <' . $message['to'] . ">\r\n"
            . 'Subject: ' . $message['subject'] . "\r\n"
            . $message['headers'] . "\r\n\r\n"
            . preg_replace('/(?m)^\./', '..', $message['body']) . "\r\n.\r\n";
        invoiceSmtpWriteAll($socket, $payload);
        invoiceSmtpExpect($socket, [250]);
        invoiceSmtpCommand($socket, 'QUIT', [221]);

        return true;
    } finally {
        fclose($socket);
    }
}

/** @param resource $socket @param list<int> $acceptedCodes */
function invoiceSmtpCommand($socket, string $command, array $acceptedCodes): void
{
    invoiceSmtpWriteAll($socket, $command . "\r\n");
    invoiceSmtpExpect($socket, $acceptedCodes);
}

/** @param resource $socket @param list<int> $acceptedCodes */
function invoiceSmtpExpect($socket, array $acceptedCodes): void
{
    $response = '';
    do {
        $line = fgets($socket, 1024);
        if ($line === false) {
            throw new RuntimeException('Réponse SMTP absente.');
        }
        $response = $line;
    } while (isset($line[3]) && $line[3] === '-');

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $acceptedCodes, true)) {
        throw new RuntimeException('Le serveur SMTP a refusé la demande (' . $code . ').');
    }
}

/** @param resource $socket */
function invoiceSmtpWriteAll($socket, string $data): void
{
    $length = strlen($data);
    $written = 0;
    while ($written < $length) {
        $result = fwrite($socket, substr($data, $written));
        if ($result === false || $result === 0) {
            throw new RuntimeException('Écriture SMTP impossible.');
        }
        $written += $result;
    }
}

/** @param array<string, mixed> $settings */
function invoiceMailFromAddress(array $settings): string
{
    $candidates = [
        (string) ($settings['email_boutique'] ?? ''),
        (string) (getenv('MAIL_FROM_ADDRESS') ?: ''),
        'no-reply@poissonnerie-saint-michel.local',
    ];
    foreach ($candidates as $candidate) {
        $candidate = mb_strtolower(trim($candidate));
        if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return $candidate;
        }
    }

    throw new RuntimeException('Aucune adresse d’expédition valide n’est configurée.');
}

function invoiceMailHeaderText(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function invoiceMailEncodedHeader(string $value): string
{
    return '=?UTF-8?B?' . base64_encode(invoiceMailHeaderText($value)) . '?=';
}
