# raoh-php

[![License](https://img.shields.io/github/license/kawasima/raoh)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)

PHP port of [Raoh](https://github.com/kawasima/raoh) — a decoder library for turning untyped boundary input into typed domain values.

It is built around a parse-don't-validate approach:

- decode at the boundary
- keep invalid states out of the domain model
- return failures as values instead of throwing
- attach structured errors to precise paths

raoh-php is closer to a parser/decoder library than to a traditional validation library.

If you are coming from a validator-oriented library, the main difference in feel is this:

- you do not validate an already-constructed domain object
- you decode raw input into a domain object
- object construction happens only after decoding succeeds

## Requirements

- PHP 8.2+
- Composer

Install and run tests:

```bash
composer install
./vendor/bin/phpunit
```

## Package Layout

```
src/
├── Result.php              # abstract readonly class (Ok / Err parent)
├── Ok.php                  # final readonly class Ok
├── Err.php                 # final readonly class Err
├── Path.php                # JSON Pointer path (immutable cons-list)
├── Issue.php               # single error (path, code, message, meta)
├── Issues.php              # error collection (accumulation)
├── Decoder.php             # interface Decoder
├── DecoderTrait.php        # map / flatMap / pipe / asList defaults
├── CallableDecoder.php     # closure → Decoder adapter
├── ErrorCodes.php          # enum ErrorCodes: string
├── Decoders.php            # utility: lazy / withDefault / recover / oneOf
├── StaticConstructor.php   # trait for first-class callable constructors
├── Presence.php            # tri-state presence base
├── Absent.php              # field not present
├── PresentNull.php         # field explicitly null
├── Present.php             # field present with a value
│
├── Builtin/
│   ├── StringDecoder.php
│   ├── IntDecoder.php
│   ├── FloatDecoder.php
│   └── BoolDecoder.php
│
├── Combinator/
│   └── Combiner.php        # variadic applicative combinator
│
└── Boundary/
    ├── Array_/
    │   ├── functions.php       # use function imports
    │   └── ArrayDecoders.php
    └── Json/
        ├── functions.php
        └── JsonDecoders.php
```

## Core Model

### `Result`

Decoding returns a value instead of throwing:

- `Ok` for success
- `Err` for failure

`Result` supports:

- `map(...)`
- `flatMap(...)`
- `fold(...)`
- `getOrThrow()`
- `orElseThrow(...)`
- `Result::map2(...)` — applicative combination of two results
- `Result::traverse(...)` — list traversal with full error accumulation

### `Issue`, `Issues`, and `Path`

Each error includes:

- `path`
- `code`
- `message`
- `meta`

Paths use JSON Pointer notation (RFC 6901), for example:

- `/email`
- `/address/city`
- `/items/0/name`

`Issues` can be merged, rebased, flattened, formatted, or converted to JSON-like data.

### `Decoder`

The core abstraction is:

```php
interface Decoder {
    public function decode(mixed $in, ?Path $path = null): Result;
}
```

A decoder reads an input value and produces either:

- a typed value wrapped in `Ok`
- structured issues wrapped in `Err`

Two boundary implementations are included:

- `Raoh\Boundary\Array_` — PHP arrays and form data
- `Raoh\Boundary\Json` — raw JSON strings

## What It Feels Like

The normal raoh-php workflow looks like this:

1. Start from raw input such as a JSON string or PHP array.
2. Define small decoders for domain primitives such as `Email`, `Age`, or `UserId`.
3. Combine them into object decoders.
4. If decoding succeeds, you get a fully-typed value.
5. If decoding fails, you get structured issues with paths.

That means the "happy path" looks like object construction, while the failure path looks like machine-readable diagnostics.

## Quick Start

### Decode an array into a domain object

```php
<?php

use function Raoh\Boundary\Array_\{field, string_, int_, combine};

class Email
{
    public function __construct(public readonly string $value) {}
}

class Age
{
    public function __construct(public readonly int $value) {}
}

class User
{
    use \Raoh\StaticConstructor;

    public function __construct(
        public readonly Email $email,
        public readonly Age   $age,
    ) {}
}

function emailDecoder(): \Raoh\Decoder {
    return string_()->trim()->toLowerCase()->email()
        ->map(fn($v) => new Email($v));
}

function ageDecoder(): \Raoh\Decoder {
    return int_()->range(0, 150)
        ->map(fn($v) => new Age($v));
}

function userDecoder(): \Raoh\Decoder {
    return combine(
        field('email', emailDecoder()),
        field('age',   ageDecoder()),
    )->map(User::of(...));
}
```

Use it like this:

```php
$result = userDecoder()->decode($_POST);
```

Success case:

```php
$result->fold(
    fn(User $user)        => saveUser($user),
    fn(\Raoh\Issues $errs) => respond(422, $errs->toJsonList()),
);
```

Example failure shape:

```json
[
  { "path": "/email", "code": "invalid_format", "message": "not a valid email address", "meta": {} }
]
```

### Decode a JSON string

```php
<?php

use function Raoh\Boundary\Json\{field, string_, int_, combine, from_json};

$dec = from_json(combine(
    field('host', string_()->nonBlank()),
    field('port', int_()->range(1, 65535)),
)->map(fn($host, $port) => new Config($host, $port)));

$result = $dec->decode('{"host":"localhost","port":5432}');
```

This is useful for:

- HTTP request bodies
- webhook payloads
- configuration files

## Built-in Decoders

### String Capabilities

`StringDecoder` supports:

- `nonBlank()`
- `allowBlank()`
- `minLength(...)`
- `maxLength(...)`
- `fixedLength(...)`
- `pattern(...)`
- `startsWith(...)`
- `endsWith(...)`
- `includes(...)`
- `oneOf(...)`
- `email()`
- `url()`
- `ip()`
- `ipv4()`
- `ipv6()`
- `uuid()`
- `ulid()`
- `trim()`
- `toLowerCase()`
- `toUpperCase()`
- `toInt()`
- `toFloat()`
- `toBool()`
- `toDate(...)`

### Numeric Capabilities

`IntDecoder` supports:

- `min(...)`
- `max(...)`
- `range(...)`
- `positive()`
- `negative()`
- `nonNegative()`
- `nonPositive()`
- `multipleOf(...)`
- `oneOf(...)`

`FloatDecoder` supports:

- `min(...)`
- `max(...)`
- `range(...)`
- `positive()`
- `scale(...)`

### Boolean Capabilities

`BoolDecoder` supports:

- `isTrue()`
- `isFalse()`

## Object Decoding

raoh-php distinguishes these cases:

- `field($name, $dec)` — required field
- `optional_field($name, $dec)` — missing field is allowed, returns `null`
- `nullable($dec)` — `null` value is allowed
- `optional_nullable_field($name, $dec)` — tri-state presence

Tri-state presence returns one of:

- `Absent` — field not present in the input
- `PresentNull` — field explicitly set to null
- `Present` — field present with a value

This distinction matters when "missing" and "explicitly null" have different meanings, which often comes up in PATCH-style APIs:

```php
$dec = optional_nullable_field('nickname', string_());
// Absent:       don't update
// PresentNull:  clear the existing value
// Present:      set to the new value
```

## A More Realistic Example

```php
<?php

use Raoh\Issues;
use Raoh\Path;
use Raoh\Result;
use function Raoh\Boundary\Array_\{field, string_, float_, combine, enum_of, nested};

enum Currency: string
{
    case JPY = 'JPY';
    case USD = 'USD';
}

class Money
{
    private function __construct(
        public readonly float    $amount,
        public readonly Currency $currency,
    ) {}

    public static function parse(float $amount, Currency $currency): Result
    {
        if ($amount <= 0) {
            return Result::fail(Path::root(), 'out_of_range', 'amount must be positive');
        }
        return Result::ok(new self($amount, $currency));
    }
}

class User
{
    use \Raoh\StaticConstructor;

    public function __construct(
        public readonly string $email,
        public readonly Money  $balance,
    ) {}
}

function moneyDecoder(): \Raoh\Decoder {
    return combine(
        field('amount',   float_()->positive()),
        field('currency', enum_of(Currency::class)),
    )->flatMap(Money::parse(...));
}

function userDecoder(): \Raoh\Decoder {
    return combine(
        field('email',   string_()->trim()->toLowerCase()->email()),
        field('balance', nested(moneyDecoder())),
    )->map(User::of(...));
}

$result = userDecoder()->decode($input);

$result->fold(
    fn(User $user)   => saveUser($user),
    fn(Issues $errs) => respond(422, $errs->toJsonList()),
);
```

This reads naturally as:

- "read `email` as a trimmed lowercased email"
- "read `balance` structurally, then apply domain rules"
- "construct `User` only if everything succeeded"

## Composition Patterns

raoh-php offers four distinct composition patterns.

### `combine(...)->map(...)`

All fields decoded independently; errors accumulate:

```php
combine(
    field('email', string_()->email()),
    field('age',   int_()->range(0, 150)),
)->map(fn($email, $age) => new User($email, $age));
```

### `combine(...)->flatMap(...)`

All fields decoded first, then a second step runs that can also fail — useful for cross-field validation:

```php
combine(
    field('password',        string_()->minLength(8)),
    field('passwordConfirm', string_()),
)->flatMap(function ($pw, $confirm) {
    if ($pw !== $confirm) {
        return Result::fail(Path::of('passwordConfirm'), 'invalid_value', 'passwords do not match');
    }
    return Result::ok(['password' => $pw]);
});
```

### `Result::map2(...)`

Applicative combination of exactly two results:

```php
$result = Result::map2(
    $emailResult,
    $ageResult,
    fn($email, $age) => new User($email, $age),
);
```

### `Result::traverse(...)`

Decode every element in a list and accumulate all errors:

```php
$result = Result::traverse($items, fn($item) => itemDecoder()->decode($item));
```

or use `list_of(...)` which wraps this:

```php
field('tags', list_of(string_()->nonBlank()))
```

## Error Accumulation

Given this decoder:

```php
$dec = combine(
    field('email', string_()->email()),
    field('age',   int_()->range(0, 150)),
)->map(fn($email, $age) => ['email' => $email, 'age' => $age]);
```

And this input:

```php
['email' => 'not-an-email', 'age' => 300]
```

raoh-php returns both issues:

```php
$err->issues->flatten();
// [
//   '/email' => ['not a valid email address'],
//   '/age'   => ['must be between 0 and 150'],
// ]
```

## Utility Combinators

The `Decoders` class and boundary functions provide reusable combinators.

- `Decoders::lazy(callable $fn)` — for recursive decoders
- `Decoders::withDefault(Decoder $dec, mixed $default)` — fallback for missing/null-like failures
- `Decoders::recover(Decoder $dec, mixed $fallback)` — fallback for any decoding failure
- `Decoders::oneOf(Decoder ...$candidates)` — tries multiple candidates; returns `one_of_failed` if all fail
- `enum_of(string $enumClass)` — matches backed enum values (case-sensitive)
- `literal(mixed $value)` — matches one exact value

### `Decoders::strict(...)`

Reject unknown fields:

```php
combine(
    field('name', string_()),
    field('age',  int_()),
)->strict(fn($name, $age) => new Person($name, $age));
```

### `lazy(...)`

For recursive structures:

```php
use Raoh\Decoders;

$commentDecoder = null;
$commentDecoder = combine(
    field('body',    string_()->nonBlank()),
    Decoders::withDefault(field('replies', list_of(Decoders::lazy(fn() => $commentDecoder))), []),
)->map(fn($body, $replies) => new Comment($body, $replies));
```

### `one_of(...)`

For discriminated union decoding:

```php
use Raoh\Decoders;

$contactDecoder = Decoders::oneOf(
    combine(
        field('kind',  literal('email')),
        field('value', string_()->email()),
    )->map(fn($kind, $value) => new EmailContact($value)),
    combine(
        field('kind',  literal('phone')),
        field('value', string_()->pattern('/^\d+$/')),
    )->map(fn($kind, $value) => new PhoneContact($value)),
);
```

If all candidates fail, `one_of_failed` is returned with candidate-specific errors in `meta.candidates`.

### `Decoders::withDefault(...)` vs `Decoders::recover(...)`

Use `Decoders::withDefault(...)` when a value is conceptually optional and you want a fallback for missing/null-like cases:

```php
field('role', Decoders::withDefault(enum_of(Role::class), Role::Member))
```

Use `Decoders::recover(...)` when you want to tolerate any decoding failure:

```php
Decoders::recover(field('pageSize', int_()->range(1, 100)), 20)
```

`Decoders::recover(...)` is more permissive. `Decoders::withDefault(...)` is stricter.

## `StaticConstructor` Trait

PHP does not support `new ClassName(...)` as a first-class callable. The `StaticConstructor` trait bridges this gap:

```php
class User
{
    use \Raoh\StaticConstructor;

    public function __construct(
        public readonly string $email,
        public readonly int    $age,
    ) {}
}

// enables this syntax:
combine(
    field('email', string_()->email()),
    field('age',   int_()->range(0, 150)),
)->map(User::of(...));
```

Without the trait, use a closure:

```php
)->map(fn($email, $age) => new User($email, $age));
```

## Boundary Modules

raoh-php ships two boundary modules for different input types.

### `Raoh\Boundary\Array_`

For PHP arrays (form data, deserialized YAML, framework request objects, etc.):

```php
use function Raoh\Boundary\Array_\{field, string_, int_, float_, bool_, combine,
    optional_field, optional_nullable_field, nullable, nested, list_of,
    enum_of, literal};
```

### `Raoh\Boundary\Json`

For raw JSON strings (HTTP bodies, webhook payloads, config files):

```php
use function Raoh\Boundary\Json\{field, string_, int_, float_, bool_, combine,
    optional_field, optional_nullable_field, nullable, nested, list_of,
    enum_of, literal, from_json};
```

`from_json($dec)` wraps any decoder to accept a raw JSON string as input.

Each module provides the same helper set (`string_()`, `field(...)`, `combine(...)`, etc.) adapted to its input type.

## Error Handling

Use `fold()`:

```php
$result->fold(
    fn(User $user)     => saveUser($user),
    fn(Issues $issues) => respond(422, $issues->toJsonList()),
);
```

Or use `instanceof`:

```php
if ($result instanceof \Raoh\Ok) {
    $user = $result->value;
} else {
    $issues = $result->issues;
}
```

Useful helpers on `Issues`:

- `flatten()` — path-keyed list of messages, convenient for form-like UIs
- `format()` — nested structure with `_errors` keys
- `toJsonList()` — flat list of `{path, code, message, meta}` objects, convenient for APIs
- `toArray()` — access the raw list of `Issue` objects

## Supported Usage Patterns

The current implementation covers:

- decoding nested objects
- decoding lists
- optional, nullable, and tri-state fields
- custom constraints via `flatMap`
- cross-field validation
- defaults and recovery
- strict mode
- recursive decoders
- discriminated variants
- single-value decoding
- constructor shorthand via `StaticConstructor`

Examples:

**Nested object decoding:**

```php
combine(
    field('name',    string_()),
    field('address', nested(addressDecoder())),
)->map(fn($name, $address) => new User($name, $address));
```

**Cross-field validation:**

```php
combine(
    field('start', int_()),
    field('end',   int_()),
)->flatMap(function ($start, $end) {
    if ($start >= $end) {
        return Result::fail(Path::of('end'), 'invalid_value', 'end must be after start');
    }
    return Result::ok(new Period($start, $end));
});
```

**Defaults:**

```php
field('role', Decoders::withDefault(enum_of(Role::class), Role::Member))
```

**Strict mode:**

```php
combine(
    field('id',    string_()->uuid()),
    field('email', string_()->email()),
    field('age',   int_()->range(0, 150)),
)->strict(fn($id, $email, $age) => new User($id, $email, $age));
```

**Single value decoding:**

```php
$result = string_()->email()->decode($input);
```

## Design Direction

The intended workflow is:

1. Read dirty external input at the boundary.
2. Decode it into domain values.
3. Either get a fully-typed object or a structured error value.

This avoids passing partially-valid data deeper into the application and keeps the domain model focused on valid states.
