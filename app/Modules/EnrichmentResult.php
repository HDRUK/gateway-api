<?php

namespace App\Modules;

use Carbon\CarbonImmutable;

/**
 * The outcome of a module for one subject. Failures are results too, so a
 * caller never has to catch anything.
 */
final class EnrichmentResult
{
    public function __construct(
        public readonly EnrichmentStatus $status,
        public readonly array $payload = [],
        public readonly ?string $sourceVersion = null,
        public readonly ?string $reason = null,
        public readonly string $module = '',
        public readonly ?CarbonImmutable $fetchedAt = null,
    ) {
    }

    public static function ok(array $payload, ?string $sourceVersion = null): self
    {
        return new self(EnrichmentStatus::Ok, $payload, $sourceVersion, fetchedAt: CarbonImmutable::now());
    }

    public static function empty(?string $sourceVersion = null): self
    {
        return new self(EnrichmentStatus::Empty, [], $sourceVersion, fetchedAt: CarbonImmutable::now());
    }

    public static function failed(string $reason): self
    {
        return new self(EnrichmentStatus::Failed, reason: $reason);
    }

    public static function skipped(string $reason): self
    {
        return new self(EnrichmentStatus::Skipped, reason: $reason);
    }

    public static function missing(): self
    {
        return new self(EnrichmentStatus::Missing);
    }

    /** Set by the manager from the config key; modules don't need to know their name. */
    public function forModule(string $module): self
    {
        return new self($this->status, $this->payload, $this->sourceVersion, $this->reason, $module, $this->fetchedAt);
    }

    /** Keep this result's data, but record that a later refresh failed. */
    public function stale(string $reason): self
    {
        return new self(EnrichmentStatus::Stale, $this->payload, $this->sourceVersion, $reason, $this->module, $this->fetchedAt);
    }

    /** Whether there is fetched data worth keeping. */
    public function hasData(): bool
    {
        return in_array($this->status, [EnrichmentStatus::Ok, EnrichmentStatus::Empty, EnrichmentStatus::Stale], true);
    }

    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'status' => $this->status->value,
            'payload' => $this->payload,
            'source_version' => $this->sourceVersion,
            'reason' => $this->reason,
            'fetched_at' => $this->fetchedAt?->toIso8601String(),
        ];
    }
}
