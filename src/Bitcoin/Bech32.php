<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress\Bitcoin;

use InvalidArgumentException;

final class Bech32
{
    public const int BECH32 = 1;

    public const int BECH32M = 0x2BC830A3;

    private const string CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

    private const array GENERATOR = [0x3B6A57B2, 0x26508E6D, 0x1EA119FA, 0x3D4233DD, 0x2A1462B3];

    private const int MAX_LENGTH = 90;

    /**
     * @return array{string, list<int>, int}
     */
    public static function decode(string $encoded): array
    {
        if (strlen($encoded) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Bech32: string is too long.');
        }
        if (strtolower($encoded) !== $encoded && strtoupper($encoded) !== $encoded) {
            throw new InvalidArgumentException('Bech32: mixed case.');
        }

        $lower     = strtolower($encoded);
        $separator = strrpos($lower, '1');
        if (false === $separator || $separator < 1 || $separator + 7 > strlen($lower)) {
            throw new InvalidArgumentException('Bech32: separator is missing or misplaced.');
        }

        $hrp = substr($lower, 0, $separator);
        foreach (str_split($hrp) as $char) {
            $code = ord($char);
            if ($code < 33 || $code > 126) {
                throw new InvalidArgumentException('Bech32: invalid human-readable part.');
            }
        }

        $data = [];
        foreach (str_split(substr($lower, $separator + 1)) as $char) {
            $value = strpos(self::CHARSET, $char);
            if (false === $value) {
                throw new InvalidArgumentException(sprintf('Bech32: invalid character "%s".', $char));
            }
            $data[] = $value;
        }

        $constant = self::polymod([...self::expandHrp($hrp), ...$data]);
        if (self::BECH32 !== $constant && self::BECH32M !== $constant) {
            throw new InvalidArgumentException('Bech32: checksum mismatch.');
        }

        return [$hrp, array_slice($data, 0, -6), $constant];
    }

    /**
     * @param list<int> $data
     *
     * @return list<int>
     */
    public static function convertBits(array $data, int $from, int $to, bool $pad): array
    {
        $accumulator = 0;
        $bits        = 0;
        $result      = [];
        $mask        = (1 << $to) - 1;

        foreach ($data as $value) {
            if ($value < 0 || $value >> $from !== 0) {
                throw new InvalidArgumentException('Bech32: value out of range.');
            }
            $accumulator = ($accumulator << $from) | $value;
            $bits += $from;
            while ($bits >= $to) {
                $bits -= $to;
                $result[] = ($accumulator >> $bits) & $mask;
            }
        }

        if ($pad) {
            if ($bits > 0) {
                $result[] = ($accumulator << ($to - $bits)) & $mask;
            }
        } elseif ($bits >= $from || (($accumulator << ($to - $bits)) & $mask) !== 0) {
            throw new InvalidArgumentException('Bech32: invalid padding.');
        }

        return $result;
    }

    /**
     * @return list<int>
     */
    private static function expandHrp(string $hrp): array
    {
        $high = [];
        $low  = [];
        foreach (str_split($hrp) as $char) {
            $high[] = ord($char) >> 5;
            $low[]  = ord($char) & 31;
        }

        return [...$high, 0, ...$low];
    }

    /**
     * @param list<int> $values
     */
    private static function polymod(array $values): int
    {
        $checksum = 1;
        foreach ($values as $value) {
            $top      = $checksum >> 25;
            $checksum = (($checksum & 0x1FFFFFF) << 5) ^ $value;
            foreach (self::GENERATOR as $index => $generator) {
                if ((($top >> $index) & 1) === 1) {
                    $checksum ^= $generator;
                }
            }
        }

        return $checksum;
    }
}
