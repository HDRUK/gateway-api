<?php

namespace App\Modules;

use Illuminate\Database\Eloquent\Model;

/**
 * Everything a module needs to know about one enrichment: the subject, the
 * partner it is for, any params (e.g. a wave) and who asked.
 *
 * The partner is captured when the context is made, so a queued job runs for
 * the partner of the original request rather than whatever is current.
 * Only the subject's type and id are serialised; the model is reloaded lazily.
 */
final class EnrichmentContext
{
    private ?Model $subject = null;

    public function __construct(
        public readonly string $subjectType,
        public readonly int|string $subjectId,
        public readonly string $partner,
        public readonly array $params = [],
        public readonly string $origin = 'unknown',
    ) {
    }

    public static function forModel(Model $subject, string $partner, array $params = [], string $origin = 'unknown'): self
    {
        $context = new self($subject::class, $subject->getKey(), $partner, $params, $origin);
        $context->subject = $subject;

        return $context;
    }

    public function subject(): ?Model
    {
        return $this->subject ??= $this->subjectType::find($this->subjectId);
    }

    /** Stable regardless of key order, so the same params always match. */
    public function paramsHash(): string
    {
        return sha1(json_encode(self::sortKeys($this->params)));
    }

    public function __serialize(): array
    {
        return [
            'subjectType' => $this->subjectType,
            'subjectId' => $this->subjectId,
            'partner' => $this->partner,
            'params' => $this->params,
            'origin' => $this->origin,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->subjectType = $data['subjectType'];
        $this->subjectId = $data['subjectId'];
        $this->partner = $data['partner'];
        $this->params = $data['params'];
        $this->origin = $data['origin'];
    }

    private static function sortKeys(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($v) => is_array($v) ? self::sortKeys($v) : $v, $value);
    }
}
