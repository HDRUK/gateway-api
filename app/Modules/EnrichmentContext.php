<?php

namespace App\Modules;

use Illuminate\Database\Eloquent\Model;

/**
 * Everything a module needs to know about one enrichment: the subject, the
 * partners involved, any params (e.g. a wave) and who asked.
 *
 * Two partners are recorded, both captured when the context is made:
 *   - $partner: the requesting partner (x-partner-context, or the default),
 *   - $subjectPartner: the subject's own `partner_context`, if it has one.
 * The subject's partner owns the data; see owningPartner().
 *
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
        public readonly ?string $subjectPartner = null,
    ) {
    }

    public static function forModel(Model $subject, string $partner, array $params = [], string $origin = 'unknown'): self
    {
        $subjectPartner = $subject->getAttribute('partner_context');

        $context = new self(
            $subject::class,
            $subject->getKey(),
            $partner,
            $params,
            $origin,
            is_string($subjectPartner) && $subjectPartner !== '' ? $subjectPartner : null,
        );
        $context->subject = $subject;

        return $context;
    }

    /** The partner the data belongs to: the subject's own, else the requester's. */
    public function owningPartner(): string
    {
        return $this->subjectPartner ?? $this->partner;
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
            'subjectPartner' => $this->subjectPartner,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->subjectType = $data['subjectType'];
        $this->subjectId = $data['subjectId'];
        $this->partner = $data['partner'];
        $this->params = $data['params'];
        $this->origin = $data['origin'];
        $this->subjectPartner = $data['subjectPartner'] ?? null;
    }

    private static function sortKeys(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($v) => is_array($v) ? self::sortKeys($v) : $v, $value);
    }
}
