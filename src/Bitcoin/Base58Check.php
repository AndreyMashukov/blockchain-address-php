<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Bitcoin;

use InvalidArgumentException;

final class Base58Check
{
    private const string ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    public static function decode(string $encoded): string
    {
        if ('' === $encoded) {
            throw new InvalidArgumentException('Base58Check: empty string.');
        }

        $bytes = [];
        foreach (str_split($encoded) as $char) {
            $carry = strpos(self::ALPHABET, $char);
            if (false === $carry) {
                throw new InvalidArgumentException(sprintf('Base58Check: invalid character "%s".', $char));
            }
            foreach ($bytes as $index => $byte) {
                $carry += $byte * 58;
                $bytes[$index] = $carry & 0xFF;
                $carry >>= 8;
            }
            while ($carry > 0) {
                $bytes[] = $carry & 0xFF;
                $carry >>= 8;
            }
        }

        $leadingZeros = strlen($encoded) - strlen(ltrim($encoded, '1'));
        $decoded      = str_repeat("\x00", $leadingZeros) . pack('C*', ...array_reverse($bytes));

        if (strlen($decoded) < 5) {
            throw new InvalidArgumentException('Base58Check: payload is too short.');
        }

        $payload  = substr($decoded, 0, -4);
        $checksum = substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);
        if (!hash_equals($checksum, substr($decoded, -4))) {
            throw new InvalidArgumentException('Base58Check: checksum mismatch.');
        }

        return $payload;
    }
}
