<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

interface ChainAddressInterface
{
    /**
     * @return bool true when both addresses identify the same on-chain location; cross-chain comparisons always return false
     */
    public function eq(self $other): bool;

    public function toString(): string;

    public function __toString(): string;
}
