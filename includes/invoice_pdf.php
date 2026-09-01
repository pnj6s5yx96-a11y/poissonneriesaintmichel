<?php
declare(strict_types=1);

/**
 * Génère une facture PDF sans dépendance externe afin qu'elle reste
 * téléchargeable sur l'installation MAMP de la boutique.
 *
 * @param array<string, mixed> $invoice
 * @param list<array<string, mixed>> $lines
 * @param array<string, mixed> $settings
 */
function buildInvoicePdf(array $invoice, array $lines, array $settings): string
{
    $firstPageLines = array_splice($lines, 0, 7);
    $pages = [$firstPageLines];
    while ($lines !== []) {
        // Laisse l'espace nécessaire au bloc total sur la dernière page.
        $pages[] = array_splice($lines, 0, 11);
    }

    $pageContents = [];
    foreach ($pages as $pageIndex => $pageLines) {
        $pageContents[] = invoicePdfPage(
            $invoice,
            $pageLines,
            $settings,
            $pageIndex === 0,
            $pageIndex === array_key_last($pages),
            $pageIndex + 1,
            count($pages)
        );
    }

    return invoicePdfDocument($pageContents);
}

/** @param array<string, mixed> $invoice @param list<array<string, mixed>> $lines @param array<string, mixed> $settings */
function invoicePdfPage(
    array $invoice,
    array $lines,
    array $settings,
    bool $isFirstPage,
    bool $isLastPage,
    int $pageNumber,
    int $pageCount
): string {
    $content = "q\n";
    $content .= invoicePdfFillRect(0, 680, 595, 162, [0.035, 0.29, 0.22]);
    $content .= invoicePdfFillRect(0, 680, 595, 6, [0.96, 0.75, 0.20]);
    $content .= invoicePdfText(42, 787, 21, (string) ($settings['nom_boutique'] ?? 'Poissonnerie Saint-Michel'), 'F2', [1, 1, 1]);
    $content .= invoicePdfText(42, 762, 9, 'PRODUITS DE LA MER - COTONOU', 'F1', [0.78, 0.91, 0.85]);
    $content .= invoicePdfText(42, 734, 9, invoicePdfOneLine((string) ($settings['adresse_boutique'] ?? '')), 'F1', [0.88, 0.95, 0.92]);
    if (!empty($settings['telephone_boutique'])) {
        $content .= invoicePdfText(42, 717, 9, 'Tel. ' . (string) $settings['telephone_boutique'], 'F1', [0.88, 0.95, 0.92]);
    }

    $content .= invoicePdfText(413, 790, 11, 'FACTURE', 'F2', [1, 1, 1]);
    $content .= invoicePdfText(413, 765, 15, (string) ($invoice['numero_document'] ?? ''), 'F2', [1, 1, 1]);
    $content .= invoicePdfText(413, 740, 9, 'Commande ' . (string) ($invoice['numero_commande'] ?? ''), 'F1', [0.88, 0.95, 0.92]);
    $content .= invoicePdfText(413, 721, 8, date('d/m/Y - H:i', strtotime((string) ($invoice['date_emission'] ?? 'now'))), 'F1', [0.88, 0.95, 0.92]);

    if ($isFirstPage) {
        $content .= invoicePdfFillRect(42, 586, 244, 64, [0.95, 0.98, 0.96]);
        $content .= invoicePdfFillRect(309, 586, 244, 64, [0.95, 0.98, 0.96]);
        $content .= invoicePdfText(56, 632, 8, 'CLIENT', 'F2', [0.04, 0.36, 0.27]);
        $content .= invoicePdfText(56, 613, 11, invoicePdfOneLine((string) ($invoice['client'] ?? 'Client')), 'F2', [0.06, 0.13, 0.11]);
        $content .= invoicePdfText(56, 596, 9, invoicePdfOneLine((string) ($invoice['telephone'] ?? '')), 'F1', [0.25, 0.34, 0.30]);
        $content .= invoicePdfText(323, 632, 8, 'REGLEMENT', 'F2', [0.04, 0.36, 0.27]);
        $content .= invoicePdfText(323, 613, 10, paymentModeLabel((string) ($invoice['mode_paiement'] ?? '')), 'F2', [0.06, 0.13, 0.11]);
        $content .= invoicePdfText(323, 596, 9, 'Reference : ' . invoicePdfOneLine((string) ($invoice['reference_transaction'] ?? '')), 'F1', [0.25, 0.34, 0.30]);
        $tableTop = 554;
    } else {
        $content .= invoicePdfText(42, 649, 10, 'DETAIL DES ARTICLES - suite', 'F2', [0.04, 0.36, 0.27]);
        $tableTop = 626;
    }

    $content .= invoicePdfFillRect(42, $tableTop - 25, 511, 25, [0.04, 0.36, 0.27]);
    $content .= invoicePdfText(54, $tableTop - 17, 8, 'ARTICLE', 'F2', [1, 1, 1]);
    $content .= invoicePdfText(316, $tableTop - 17, 8, 'QTE', 'F2', [1, 1, 1]);
    $content .= invoicePdfText(381, $tableTop - 17, 8, 'PRIX UNIT.', 'F2', [1, 1, 1]);
    $content .= invoicePdfText(488, $tableTop - 17, 8, 'TOTAL', 'F2', [1, 1, 1]);

    $rowY = $tableTop - 53;
    foreach ($lines as $lineIndex => $line) {
        if ($lineIndex % 2 === 0) {
            $content .= invoicePdfFillRect(42, $rowY - 14, 511, 33, [0.975, 0.985, 0.98]);
        }
        $content .= invoicePdfText(54, $rowY + 3, 10, invoicePdfOneLine((string) ($line['libelle'] ?? ''), 44), 'F2', [0.07, 0.16, 0.13]);
        $quantity = number_format((float) ($line['quantite'] ?? 0), 3, ',', ' ');
        $unit = mb_strtolower((string) ($line['unite_vente'] ?? ''));
        $content .= invoicePdfText(316, $rowY + 3, 8, invoicePdfOneLine($quantity . ' ' . $unit, 12), 'F1', [0.25, 0.34, 0.30]);
        $content .= invoicePdfText(381, $rowY + 3, 8, moneyFcfa($line['prix_unitaire_applique'] ?? 0), 'F1', [0.25, 0.34, 0.30]);
        $content .= invoicePdfText(488, $rowY + 3, 8, moneyFcfa($line['sous_total'] ?? 0), 'F2', [0.07, 0.16, 0.13]);
        $rowY -= 36;
    }

    if ($isLastPage) {
        $summaryY = max(170, $rowY - 28);
        $content .= invoicePdfFillRect(320, $summaryY - 82, 233, 96, [0.95, 0.98, 0.96]);
        $content .= invoicePdfText(335, $summaryY - 8, 9, 'Sous-total articles', 'F1', [0.25, 0.34, 0.30]);
        $content .= invoicePdfText(464, $summaryY - 8, 9, moneyFcfa($invoice['total_produits'] ?? 0), 'F2', [0.07, 0.16, 0.13]);
        $deliveryLabel = $invoice['mode_retrait'] === 'RETRAIT_BOUTIQUE'
            ? 'Retrait en boutique'
            : 'Livraison - ' . (string) ($invoice['zone_livraison'] ?? 'à confirmer')
                . (!empty($invoice['quartier_livraison']) ? ' - ' . (string) $invoice['quartier_livraison'] : '');
        $content .= invoicePdfText(335, $summaryY - 31, 9, invoicePdfOneLine($deliveryLabel, 26), 'F1', [0.25, 0.34, 0.30]);
        $content .= invoicePdfText(464, $summaryY - 31, 9, moneyFcfa($invoice['frais_livraison'] ?? 0), 'F2', [0.07, 0.16, 0.13]);
        $content .= invoicePdfFillRect(320, $summaryY - 75, 233, 30, [0.04, 0.36, 0.27]);
        $content .= invoicePdfText(335, $summaryY - 64, 10, 'TOTAL REGLE', 'F2', [1, 1, 1]);
        $content .= invoicePdfText(464, $summaryY - 64, 11, moneyFcfa($invoice['montant'] ?? 0), 'F2', [1, 1, 1]);
    }

    $content .= invoicePdfStrokeLine(42, 68, 553, 68, [0.78, 0.86, 0.82], 0.5);
    $footer = 'Merci pour votre confiance.';
    if ($pageCount > 1) {
        $footer .= '  Page ' . $pageNumber . '/' . $pageCount;
    }
    $content .= invoicePdfText(42, 51, 8, $footer, 'F1', [0.33, 0.43, 0.39]);
    $content .= invoicePdfText(383, 51, 8, 'Poissonnerie Saint-Michel', 'F2', [0.04, 0.36, 0.27]);

    return $content . "Q\n";
}

/** @param list<string> $pageContents */
function invoicePdfDocument(array $pageContents): string
{
    $pageCount = count($pageContents);
    $fontRegularId = 3 + ($pageCount * 2);
    $fontBoldId = $fontRegularId + 1;
    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        2 => '<< /Type /Pages /Kids [' . implode(' ', array_map(
            static fn (int $index): string => (3 + ($index * 2)) . ' 0 R',
            array_keys($pageContents)
        )) . '] /Count ' . $pageCount . ' >>',
    ];

    foreach ($pageContents as $index => $content) {
        $pageId = 3 + ($index * 2);
        $contentId = $pageId + 1;
        $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
            . '/Resources << /Font << /F1 ' . $fontRegularId . ' 0 R /F2 ' . $fontBoldId . ' 0 R >> >> '
            . '/Contents ' . $contentId . ' 0 R >>';
        $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream';
    }
    $objects[$fontRegularId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($objects as $id => $_object) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$id]) . "\n";
    }
    $pdf .= 'trailer' . "\n<< /Size " . (count($objects) + 1) . ' /Root 1 0 R >>'
        . "\nstartxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

/** @param list<float> $color */
function invoicePdfText(float $x, float $y, float $size, string $text, string $font, array $color): string
{
    return sprintf(
        "%.3F %.3F %.3F rg\nBT /%s %.1F Tf %.1F %.1F Td (%s) Tj ET\n",
        $color[0],
        $color[1],
        $color[2],
        $font,
        $size,
        $x,
        $y,
        invoicePdfEscape($text)
    );
}

/** @param list<float> $color */
function invoicePdfFillRect(float $x, float $y, float $width, float $height, array $color): string
{
    return sprintf("%.3F %.3F %.3F rg\n%.1F %.1F %.1F %.1F re f\n", $color[0], $color[1], $color[2], $x, $y, $width, $height);
}

/** @param list<float> $color */
function invoicePdfStrokeLine(float $x1, float $y1, float $x2, float $y2, array $color, float $width): string
{
    return sprintf(
        "%.3F %.3F %.3F RG\n%.1F w\n%.1F %.1F m %.1F %.1F l S\n",
        $color[0],
        $color[1],
        $color[2],
        $width,
        $x1,
        $y1,
        $x2,
        $y2
    );
}

function invoicePdfEscape(string $value): string
{
    $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
    $encoded = $encoded === false ? '' : $encoded;

    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $encoded);
}

function invoicePdfOneLine(string $value, int $maximumLength = 60): string
{
    $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

    return mb_strimwidth($value, 0, $maximumLength, '…');
}
