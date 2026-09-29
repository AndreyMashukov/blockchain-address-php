<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

use InvalidArgumentException;

enum BitcoinNetwork: string
{
    case Mainnet = 'mainnet';
    case Testnet = 'testnet';
    case Signet  = 'signet';
    case Regtest = 'regtest';

    public static function fromName(string $name): self
    {
        return match (strtolower(trim($name))) {
            'mainnet', 'main', 'bitcoin' => self::Mainnet,
            'testnet', 'testnet3', 'testnet4', 'test' => self::Testnet,
            'signet' => self::Signet,
            'regtest' => self::Regtest,
            default => throw new InvalidArgumentException(sprintf('BitcoinNetwork: unknown network "%s".', $name)),
        };
    }

    public function hrp(): string
    {
        return match ($this) {
            self::Mainnet => 'bc',
            self::Testnet, self::Signet => 'tb',
            self::Regtest => 'bcrt',
        };
    }
}
