<?php

namespace Webkul\Security\Support;

/**
 * RFC 6238 time-based one-time passwords (Google Authenticator, Authy,
 * Microsoft Authenticator…): 30-second steps, 6 digits, HMAC-SHA1.
 */
class Totp
{
    const PERIOD = 30;

    const DIGITS = 6;

    /**
     * Steps accepted either side of "now" to absorb clock drift.
     */
    const WINDOW = 1;

    const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * A new random base32 secret (160 bits).
     */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /**
     * Verify a code and return the matched time step, or null.
     *
     * Passing the last step already used rejects replays of the same code.
     */
    public static function verify(string $secret, string $code, ?int $lastUsedStep = null, ?int $time = null): ?int
    {
        $code = preg_replace('/\D/', '', $code);

        if (strlen($code) !== self::DIGITS) {
            return null;
        }

        $current = intdiv($time ?? time(), self::PERIOD);

        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            $step = $current + $offset;

            if ($lastUsedStep !== null && $step <= $lastUsedStep) {
                continue;
            }

            if (hash_equals(self::codeAt($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * The code for a given time step.
     */
    public static function codeAt(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);

        $offset = ord($hash[19]) & 0x0F;

        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * otpauth:// URI that authenticator apps read from the QR code.
     */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account)
            .'?secret='.$secret
            .'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1&digits='.self::DIGITS.'&period='.self::PERIOD;
    }

    /**
     * Secret grouped in blocks of four for manual entry.
     */
    public static function formatSecret(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    protected static function base32Encode(string $bytes): string
    {
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $output;
    }

    protected static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret));

        $bits = '';

        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos(self::BASE32_ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}
