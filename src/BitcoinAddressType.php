<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

enum BitcoinAddressType: string
{
    case P2pkh         = 'p2pkh';
    case P2sh          = 'p2sh';
    case P2wpkh        = 'p2wpkh';
    case P2wsh         = 'p2wsh';
    case P2tr          = 'p2tr';
    case WitnessFuture = 'witness_future';

    public function isSegwit(): bool
    {
        return self::P2pkh !== $this && self::P2sh !== $this;
    }
}
