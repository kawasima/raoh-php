# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
