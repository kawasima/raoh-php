# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Targets version parity with [kawasima/raoh](https://github.com/kawasima/raoh) 0.8.0. So far this
covers error-reporting semantics only (`code` taxonomy, `messageKey`, resolver fallback — closing
[#7](https://github.com/kawasima/raoh-php/issues/7)); the rest of 0.8.0 (Unicode whitespace
folding, URL/IP grammar, temporal parsing) is tracked separately. Available ahead of a tagged
`0.8.0` release via `composer require raoh/raoh:0.8.x-dev`, aliased from the `develop` branch.

### Added

- `Issue::$messageKey`, refining `code` into the specific constraint that failed (defaults to `code`, preserved by `rebase()` and `withCustomMessage()`)
- `MessageKeys` enum with the refined keys for numeric bounds and string formats, matching kawasima/raoh's `MessageKeys` string-for-string
- `Result::failWith()`, distinguishing an explicit `?string $message` (kept as-is, protected from resolver overwrite) from a constraint's builtin default (subject to `messageKey` resolution)

### Changed

- `Issue::resolve(callable $resolver)`: the resolver may now return `null` to decline (no template for this issue, including one it cannot fully interpolate), in which case resolution falls back from `messageKey` to `code`, and finally to the issue's existing message. A resolver that always returns a `string` still behaves as before.
- All builtin constraints that expose `?string $message = null` (`IntDecoder`, `FloatDecoder`, `StringDecoder`, `BoolDecoder`) now go through `Result::failWith()`, so an explicit custom message is no longer silently replaced when `resolve()` runs.

### Not changed

- `StringDecoder::toDate($format)` intentionally keeps the generic `invalid_format` message key. It accepts arbitrary PHP date formats, unlike Raoh's ISO-8601-only `invalid_format.date`, so the two do not share a meaning a message catalog could rely on.

### Breaking

- Numeric bound constraints report `out_of_range` instead of `too_small`/`too_big`: `IntDecoder::{min,max,positive,negative,nonNegative,nonPositive}` and `FloatDecoder::{min,max,positive}`. `too_small`/`too_big` remain reserved for collection-size constraints. This aligns the `code` taxonomy with Raoh 0.8.0 and raoh-rust.

## [0.1.0] - 2026-03-14

### Added

- Core types: `Result<T>`, `Ok<T>`, `Err<T>`, `Path`, `Issue`, `Issues`
- `Decoder` interface with `DecoderTrait` providing `map`, `flatMap`, `pipe`, `asList`
- `CallableDecoder` — closure-to-Decoder adapter
- `StaticConstructor` trait — first-class callable shorthand for constructors
- Built-in decoders: `StringDecoder`, `IntDecoder`, `FloatDecoder`, `BoolDecoder` with fluent constraint chains
- `Combiner` — applicative combinator with full error accumulation
- `Decoders` utility: `combine`, `lazy`, `withDefault`, `recover`, `oneOf`, `strict`
- `Presence` tri-state (`Absent`, `PresentNull`, `Present<T>`) for PATCH semantics
- `ErrorCodes` enum with 20 standard error codes
- `Boundary\Array_` module with `use function` API: `field`, `optional_field`, `optional_nullable_field`, `nested`, `list_of`, `nullable`, `combine`, `enum_of`, `literal`
- `Boundary\Json` module: `from_json` wrapping any array decoder to accept raw JSON strings
- Laravel example application demonstrating the library in a real HTTP context
