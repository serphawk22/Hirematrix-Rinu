<?php

namespace App\Libraries;

final class JobDescriptionSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><em><ul><ol><li><h2><h3>';

    public static function sanitize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        // Remove active or invisible content together with its body.
        $value = preg_replace('#<(script|style|iframe|object|embed|svg|math)[^>]*>.*?</\1\s*>#is', '', $value) ?? '';
        $value = preg_replace('/<!--.*?-->/s', '', $value) ?? $value;

        if (!preg_match('/<(?:p|br|strong|em|ul|ol|li|h2|h3)\b/i', $value)) {
            return '<p>' . nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false) . '</p>';
        }

        $value = strip_tags($value, self::ALLOWED_TAGS);
        // Formatting tags do not need attributes. Removing every attribute also
        // removes event handlers, inline styles, URLs and tracking metadata.
        $value = preg_replace('/<(p|br|strong|em|ul|ol|li|h2|h3)\b[^>]*>/i', '<$1>', $value) ?? '';
        $value = preg_replace('/<(p|h2|h3)>\s*<\/\1>/i', '', $value) ?? $value;

        return trim($value);
    }

    public static function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags(self::sanitize($value)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
