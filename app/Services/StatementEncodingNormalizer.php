<?php

namespace App\Services;

class StatementEncodingNormalizer
{
    /**
     * Converts any bank statement raw content (CSV, OFX, TXT) into clean, standard UTF-8.
     * Automatically handles Windows-1252 (ANSI), ISO-8859-1 (Latin1), UTF-8 BOM, UTF-16, etc.
     */
    public static function normalize(string $content): string
    {
        if ($content === '') {
            return '';
        }

        // 1. Strip UTF-8 BOM (\xEF\xBB\xBF) if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // 2. Strip UTF-16 LE / BE BOM if present and convert
        if (str_starts_with($content, "\xFF\xFE")) {
            $converted = @mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
            if ($converted !== false) {
                return $converted;
            }
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $converted = @mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
            if ($converted !== false) {
                return $converted;
            }
        }

        // 3. If already valid UTF-8, verify and return
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        // 4. In Brazilian banking exports (BB, Caixa, Itaú, Bradesco, Santander),
        // Windows-1252 (CP1252) / ISO-8859-1 is the standard non-UTF-8 encoding.
        // Windows-1252 is a superset of ISO-8859-1 that includes curly quotes, dashes, euro, etc.
        $detected = mb_detect_encoding($content, ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15', 'ASCII'], true);
        $fromEncoding = $detected ?: 'Windows-1252';

        $converted = @mb_convert_encoding($content, 'UTF-8', $fromEncoding);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        // 5. Fallback via iconv with //IGNORE to safely discard any rogue byte
        $iconvConverted = @iconv('Windows-1252', 'UTF-8//IGNORE', $content);
        if ($iconvConverted !== false && mb_check_encoding($iconvConverted, 'UTF-8')) {
            return $iconvConverted;
        }

        // 6. Last resort: sanitize invalid UTF-8 bytes
        return mb_convert_encoding($content, 'UTF-8', 'UTF-8');
    }

    /**
     * Guarantees a string attribute is valid UTF-8 without corrupting characters.
     */
    public static function sanitizeString(?string $string): string
    {
        if ($string === null || $string === '') {
            return '';
        }

        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = self::normalize($string);
        }

        // Remove non-printable control characters except line feed and tab
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        return $clean !== null ? $clean : $string;
    }
}
