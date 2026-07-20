<?php

declare(strict_types=1);

namespace Amashukov\BlockchainAddress;

final readonly class Erc20Address implements ChainAddressInterface
{
    private function __construct(
        private EvmAddress $contract,
        private EvmAddress $token,
    ) {}

    public static function fromStrings(string $contract, string $token): self
    {
        return new self(EvmAddress::fromString($contract), EvmAddress::fromString($token));
    }

    public function contract(): EvmAddress
    {
        return $this->contract;
    }

    public function token(): EvmAddress
    {
        return $this->token;
    }

    public function eq(ChainAddressInterface $other): bool
    {
        return $other instanceof self
            && $this->contract->eq($other->contract)
            && $this->token->eq($other->token);
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
