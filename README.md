# amashukov/blockchain-address-php

Typed on-chain address Value Objects for EVM, TON and Lightning — parse once, compare structurally, never pass a raw string again.

[![CI](https://img.shields.io/github/actions/workflow/status/AndreyMashukov/blockchain-address-php/ci.yml?branch=main&label=CI)](https://github.com/AndreyMashukov/blockchain-address-php/actions)
[![PHPStan L9](https://img.shields.io/github/actions/workflow/status/AndreyMashukov/blockchain-address-php/stan.yml?branch=main&label=PHPStan%20L9)](https://github.com/AndreyMashukov/blockchain-address-php/actions)
[![Latest Version](https://img.shields.io/packagist/v/amashukov/blockchain-address-php)](https://packagist.org/packages/amashukov/blockchain-address-php)
[![Downloads](https://img.shields.io/packagist/dt/amashukov/blockchain-address-php)](https://packagist.org/packages/amashukov/blockchain-address-php)
[![PHP](https://img.shields.io/packagist/dependency-v/amashukov/blockchain-address-php/php)](https://packagist.org/packages/amashukov/blockchain-address-php)
[![License](https://img.shields.io/packagist/l/amashukov/blockchain-address-php)](LICENSE)
[![Stars](https://img.shields.io/github/stars/AndreyMashukov/blockchain-address-php?style=social)](https://github.com/AndreyMashukov/blockchain-address-php)

A **address Value Object layer for multi-chain PHP applications**. Every address is parsed and validated at construction, so an invalid one cannot exist as an instance, and comparison is structural rather than textual — which matters because the same address has several legitimate spellings on both TON and EVM.

## Features

- **Parse-at-the-boundary** — `fromString()` throws on anything malformed, `tryFromString()` returns `null`. A constructed instance is always a valid address.
- **Structural `eq()`** — comparison is by identity, not spelling. A TON address is equal to itself across bounceable / non-bounceable / url-safe forms; an EVM address is equal across EIP-55 checksum casing. `strtolower($a) === strtolower($b)` is wrong on TON and this exists to stop you writing it.
- **Cross-chain safe** — `eq()` between two different chain types is `false`, never a coincidental match.
- **Composite addresses** — `Erc20Address` (contract + token) and `JettonAddress` (contract + master + wallet) carry the several identifiers those standards actually need, instead of passing three loose strings alongside each other.
- **`Bolt11Invoice`** — a BOLT11 Lightning invoice as an address: payment hash, amount in millisatoshi as a decimal string, network from the human-readable prefix, and `isExpiredAt(ClockInterface)` for deterministic, testable expiry.
- **Bech32 (BIP-173)** — a from-scratch decoder with checksum verification, used by the BOLT11 parser.

## Why amashukov/blockchain-address-php

Raw address strings are the classic source of silent cross-chain bugs: a TON address compared case-insensitively matches the wrong account, an EIP-55 checksummed address fails a `===` against its lowercase form, and an ERC-20 transfer built from "the address" sends to the token contract instead of the recipient. Making the address a type moves all of that to construction time, where it fails loudly.

## Installation

```bash
composer require amashukov/blockchain-address-php
```

## Usage

```php
use Amashukov\BlockchainAddress\Bolt11Invoice;
use Amashukov\BlockchainAddress\EvmAddress;
use Amashukov\BlockchainAddress\TonAddress;

$evm = EvmAddress::fromString('0xDAC17F958D2ee523a2206206994597C13D831ec7');
$evm->eq(EvmAddress::fromString('0xdac17f958d2ee523a2206206994597c13d831ec7'));  // true — checksum casing

$ton = TonAddress::fromString('UQAht13a44YMjGClyRbYCFi9sEPaQbfP6RZJhy_2RGv4Wi1D');
$ton->eq($evm);                                                                  // false — different chains

$invoice = Bolt11Invoice::fromString('lnbc2500u1pvjluez...');
$invoice->paymentHash;      // '0001020304050607...'
$invoice->amountMsat;       // '250000000' — decimal string, never a float
$invoice->network;          // Bolt11Network::Mainnet
$invoice->isExpiredAt($clock);
```

`tryFromString()` is the non-throwing form, for validating untrusted input:

```php
if (null === Bolt11Invoice::tryFromString($userSuppliedInvoice)) {
    // reject at the boundary
}
```

## Amounts are decimal strings

`Bolt11Invoice::$amountMsat` is a numeric string, not an `int` or `float`. A millisatoshi amount is well within `PHP_INT_MAX`, but the surrounding money code is bcmath, and handing it a float is how rounding errors enter a payment path. The type keeps the boundary honest.

## Testing

```bash
composer install
composer test
composer stan
```

## License

MIT — see [LICENSE](LICENSE).
