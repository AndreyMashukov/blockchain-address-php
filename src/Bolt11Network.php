<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

enum Bolt11Network: string
{
    case Mainnet = 'bc';
    case Testnet = 'tb';
    case Regtest = 'bcrt';
    case Signet  = 'tbs';

    public function isMainnet(): bool
    {
        return self::Mainnet === $this;
    }
}
