<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

final readonly class JettonAddress implements ChainAddressInterface
{
    private function __construct(
        private TonAddress $contract,
        private TonAddress $master,
        private TonAddress $wallet,
    ) {}

    public static function fromStrings(string $contract, string $master, string $wallet): self
    {
        return new self(
            TonAddress::fromString($contract),
            TonAddress::fromString($master),
            TonAddress::fromString($wallet),
        );
    }

    public function contract(): TonAddress
    {
        return $this->contract;
    }

    public function master(): TonAddress
    {
        return $this->master;
    }

    public function wallet(): TonAddress
    {
        return $this->wallet;
    }

    public function eq(ChainAddressInterface $other): bool
    {
        return $other instanceof self
            && $this->contract->eq($other->contract)
            && $this->master->eq($other->master)
            && $this->wallet->eq($other->wallet);
    }

    public function toString(): string
    {
        return $this->contract->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
