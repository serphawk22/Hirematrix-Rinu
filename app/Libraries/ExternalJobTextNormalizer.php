<?php

namespace App\Libraries;

class ExternalJobTextNormalizer
{
    public static function normalize(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Repair UTF-8 text that was previously decoded as Windows-1252
        // (for example, "Youâ€™ve" instead of "You’ve").
        if (preg_match('/(?:â|Ã|Â|ðŸ)/u', $value) === 1) {
            $repaired = @mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
            if (
                is_string($repaired)
                && mb_check_encoding($repaired, 'UTF-8')
                && self::mojibakeScore($repaired) < self::mojibakeScore($value)
            ) {
                $value = $repaired;
            }
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        return trim($value);
    }

    private static function mojibakeScore(string $value): int
    {
        return preg_match_all('/(?:â|Ã|Â|ðŸ)/u', $value) ?: 0;
    }
}
