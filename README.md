# amashukov/blockchain-address-php

Typed on-chain address Value Objects for EVM, TON and Bitcoin — parse once, compare structurally, never pass a raw string again.

[![CI](https://img.shields.io/github/actions/workflow/status/AndreyMashukov/blockchain-address-php/ci.yml?branch=main&label=CI)](https://github.com/AndreyMashukov/blockchain-address-php/actions)
[![PHPStan L9](https://img.shields.io/github/actions/workflow/status/AndreyMashukov/blockchain-address-php/stan.yml?branch=main&label=PHPStan%20L9)](https://github.com/AndreyMashukov/blockchain-address-php/actions)
[![Latest Version](https://img.shields.io/packagist/v/amashukov/blockchain-address-php)](https://packagist.org/packages/amashukov/blockchain-address-php)
[![Downloads](https://img.shields.io/packagist/dt/amashukov/blockchain-address-php)](https://packagist.org/packages/amashukov/blockchain-address-php)
[![PHP](https://img.shields.io/packagist/dependency-v/amashukov/blockchain-address-php/php)](https://packagist.org/packages/amashukov/blockchain-address-php)
[![License](https://img.shields.io/packagist/l/amashukov/blockchain-address-php)](LICENSE)
[![Stars](https://img.shields.io/github/stars/AndreyMashukov/blockchain-address-php?style=social)](https://github.com/AndreyMashukov/blockchain-address-php)

An **address Value Object layer for multi-chain PHP applications**. Every address is parsed and validated at construction, so an invalid one cannot exist as an instance, and comparison is structural rather than textual — which matters because the same address has several legitimate spellings on both TON and EVM.

## Features

- **Parse-at-the-boundary** — `fromString()` throws on anything malformed, `tryFromString()` returns `null`. A constructed instance is always a valid address.
- **Structural `eq()`** — comparison is by identity, not spelling. A TON address is equal to itself across bounceable / non-bounceable / url-safe forms; an EVM address is equal across EIP-55 checksum casing. `strtolower($a) === strtolower($b)` is wrong on TON and this exists to stop you writing it.
- **Cross-chain safe** — `eq()` between two different chain types is `false`, never a coincidental match.
- **Every Bitcoin address format** — `BitcoinAddress::fromString($raw, BitcoinNetwork::Mainnet)` accepts legacy base58check P2PKH (`1…`, `m…`/`n…`) and P2SH (`3…`, `2…`) with their double-SHA-256 checksum, and SegWit bech32 / bech32m (BIP-173, BIP-350: P2WPKH, P2WSH, P2TR and future witness versions) with the checksum variant checked against the witness version and the witness program length. The network is part of the check — version byte for base58, prefix (`bc`, `tb`, `bcrt`) for SegWit — so an address from another network never parses. `type()` reports which format it is.
- **Composite addresses** — `Erc20Address` (contract + token) and `JettonAddress` (contract + master + wallet) carry the several identifiers those standards actually need, instead of passing three loose strings alongside each other.

## Why amashukov/blockchain-address-php

Raw address strings are the classic source of silent cross-chain bugs: a TON address compared case-insensitively matches the wrong account, an EIP-55 checksummed address fails a `===` against its lowercase form, and an ERC-20 transfer built from "the address" sends to the token contract instead of the recipient. Making the address a type moves all of that to construction time, where it fails loudly.

## Installation

```bash
composer require amashukov/blockchain-address-php
```

## Usage

```php
use Amashukov\BlockchainAddress\EvmAddress;
use Amashukov\BlockchainAddress\TonAddress;

$evm = EvmAddress::fromString('0xDAC17F958D2ee523a2206206994597C13D831ec7');
$evm->eq(EvmAddress::fromString('0xdac17f958d2ee523a2206206994597c13d831ec7'));  // true — checksum casing

$ton = TonAddress::fromString('UQAht13a44YMjGClyRbYCFi9sEPaQbfP6RZJhy_2RGv4Wi1D');
$ton->eq($evm);                                                                  // false — different chains

use Amashukov\BlockchainAddress\BitcoinAddress;
use Amashukov\BlockchainAddress\BitcoinNetwork;

$btc = BitcoinAddress::fromString('BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4', BitcoinNetwork::fromName('mainnet'));
$btc->type();                                                                    // BitcoinAddressType::P2wpkh
$btc->witnessVersion();                                                          // 0
BitcoinAddress::fromString('3J98t1WpEZ73CNmQviecrnyiWrnqRhWNLy', BitcoinNetwork::Mainnet)->type(); // BitcoinAddressType::P2sh
$btc->toString();                                                                // 'bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4'
BitcoinAddress::tryFromString((string) $btc, BitcoinNetwork::Testnet);           // null — wrong network

```

## Testing

```bash
composer install
composer test
composer stan
```

## License

MIT — see [LICENSE](LICENSE).
