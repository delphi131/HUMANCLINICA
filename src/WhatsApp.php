<?php

declare(strict_types=1);

final class WhatsApp
{
    /**
     * Build a wa.me deep link that opens a chat with a prefilled message.
     * Assumes Italian numbers when no country code is present.
     */
    public static function chatLink(string $phone, string $message = ''): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        // Strip a leading trunk "0" only for plausible Italian mobile-length numbers
        // that lack a country code; leave already-international numbers untouched.
        if (!str_starts_with($digits, '39') && strlen($digits) <= 11) {
            $digits = '39' . ltrim($digits, '0');
        }

        $url = 'https://wa.me/' . $digits;
        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }

        return $url;
    }
}
