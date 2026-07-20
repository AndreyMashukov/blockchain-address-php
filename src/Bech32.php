<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

use InvalidArgumentException;

final class Bech32
{
    public const string CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

    private const int CHECKSUM_LENGTH = 6;

    private const array GENERATOR = [0x3B6A57B2, 0x26508E6D, 0x1EA119FA, 0x3D4233DD, 0x2A1462B3];

    /**
     * @return array{hrp: string, words: list<int>}
     */
    public static function decode(string $encoded): array
    {
        $lower = strtolower($encoded);

        if ($encoded !== $lower && $encoded !== strtoupper($encoded)) {
            throw new InvalidArgumentException(sprintf('Bech32: mixed-case string "%s".', $encoded));
        }

        $separator = strrpos($lower, '1');

        if (false === $separator || 0 === $separator) {
            throw new InvalidArgumentException(sprintf('Bech32: missing human-readable part in "%s".', $encoded));
        }

        $hrp     = substr($lower, 0, $separator);
        $dataRaw = substr($lower, $separator + 1);

        if (\strlen($dataRaw) < self::CHECKSUM_LENGTH) {
            throw new InvalidArgumentException(sprintf('Bech32: data part shorter than the checksum in "%s".', $encoded));
        }

        $words = [];

        foreach (str_split($dataRaw) as $char) {
            $index = strpos(self::CHARSET, $char);

            if (false === $index) {
                throw new InvalidArgumentException(sprintf('Bech32: character "%s" is outside the charset.', $char));
            }

            $words[] = $index;
        }

        if (1 !== self::polymod(array_merge(self::expandHrp($hrp), $words))) {
            throw new InvalidArgumentException(sprintf('Bech32: checksum mismatch in "%s".', $encoded));
        }

        return ['hrp' => $hrp, 'words' => \array_slice($words, 0, -self::CHECKSUM_LENGTH)];
    }

    /**
     * @param list<int> $words
     *
     * @return list<int>
     */
    public static function wordsToBytes(array $words): array
    {
        return self::convertBits($words, 5, 8, false);
    }

    /**
     * @param list<int> $words
     */
    public static function wordsToInt(array $words): int
    {
        $value = 0;

        foreach ($words as $word) {
            $value = $value * 32 + $word;
        }

        return $value;
    }

    /**
     * @param list<int> $values
     *
     * @return list<int>
     */
    private static function convertBits(array $values, int $fromBits, int $toBits, bool $pad): array
    {
        $accumulator = 0;
        $bits        = 0;
        $maximum     = (1 << $toBits) - 1;
        $out         = [];

        foreach ($values as $value) {
            $accumulator = ($accumulator << $fromBits) | $value;
            $bits += $fromBits;

            while ($bits >= $toBits) {
                $bits -= $toBits;
                $out[] = ($accumulator >> $bits) & $maximum;
            }
        }

        if ($pad && $bits > 0) {
            $out[] = ($accumulator << ($toBits - $bits)) & $maximum;
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    private static function expandHrp(string $hrp): array
    {
        $high = [];
        $low  = [];

        foreach (str_split($hrp) as $char) {
            $code   = \ord($char);
            $high[] = $code >> 5;
            $low[]  = $code & 31;
        }

        return array_merge($high, [0], $low);
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

            for ($i = 0; $i < 5; ++$i) {
                if (0 !== (($top >> $i) & 1)) {
                    $checksum ^= self::GENERATOR[$i];
                }
            }
        }

        return $checksum;
    }
}
