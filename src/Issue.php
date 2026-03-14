<?php

declare(strict_types=1);

namespace Raoh;

final readonly class Issue
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly Path $path,
        public readonly string $code,
        public readonly string $message,
        public readonly array $meta = [],
        public readonly bool $customMessage = false,
    ) {
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function of(
        Path $path,
        string $code,
        string $message,
        array $meta = [],
    ): self {
        return new self($path, $code, $message, $meta, false);
    }

    public function withCustomMessage(string $message): self
    {
        return new self($this->path, $this->code, $message, $this->meta, true);
    }

    public function rebase(Path $prefix): self
    {
        return new self(
            $prefix->appendPath($this->path),
            $this->code,
            $this->message,
            $this->meta,
            $this->customMessage,
        );
    }

    public function resolve(callable $resolver): self
    {
        if ($this->customMessage) {
            return $this;
        }
        return new self(
            $this->path,
            $this->code,
            $resolver($this->code, $this->meta),
            $this->meta,
            true,
        );
    }
}
