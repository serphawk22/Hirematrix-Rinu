<?php

namespace App\Libraries;

class ExternalJobTextNormalizer
{
    public static function normalize(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Repair common UTF-8 text that was previously interpreted as a
        // Windows-1252 byte string (for example curly quotes shown as mojibake).
        for ($pass = 0; $pass < 2 && preg_match('/(?:\x{00C3}|\x{00C2}|\x{00E2}\x{20AC}|\x{00F0}\x{0178})/u', $value) === 1; $pass++) {
            $repaired = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            if (
                is_string($repaired)
                && mb_check_encoding($repaired, 'UTF-8')
                && self::mojibakeScore($repaired) < self::mojibakeScore($value)
            ) {
                $value = $repaired;
            } else {
                break;
            }
        }

        // Old MySQL utf8 imports sometimes replaced an unsupported emoji with
        // question marks. The original glyph cannot be restored, but its
        // replacement run must not be displayed as part of a job title.
        $value = preg_replace('/^(?:(?:\?|\x{FFFD})\s*){2,}(?=[\p{L}\p{N}])/u', '', $value) ?? $value;
        $value = preg_replace('/(?<=\s)(?:(?:\?|\x{FFFD})\s*){3,}(?=[\p{L}\p{N}])/u', '', $value) ?? $value;

        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        return trim($value);
    }

    private static function mojibakeScore(string $value): int
    {
        return preg_match_all('/(?:\x{00C3}|\x{00C2}|\x{00E2}\x{20AC}|\x{00F0}\x{0178})/u', $value) ?: 0;
    }
}
