<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *   schema="Experiment",
 *   description="A feedback prompt or design review shown to targeted visitors",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="Search feedback"),
 *   @OA\Property(property="status", type="string", enum={"draft","active","paused","archived"}, example="active"),
 *   @OA\Property(property="definition", type="object", description="Steps and blocks rendered by @hdruk/experiments",
 *     @OA\Property(property="steps", type="array", @OA\Items(type="object",
 *       @OA\Property(property="id", type="string", example="step-1"),
 *       @OA\Property(property="blocks", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="string", example="score"),
 *         @OA\Property(property="type", type="string", enum={"heading","richText","image","rating","choice","textInput","abChoice","consentNotice"}),
 *       )),
 *     )),
 *     @OA\Property(property="thankYou", type="string", nullable=true),
 *   ),
 *   @OA\Property(property="targeting", type="object",
 *     @OA\Property(property="paths", type="array", @OA\Items(type="string", example="/en/search")),
 *     @OA\Property(property="audience", type="string", enum={"all","logged_in","logged_out","user_ids"}, example="all"),
 *     @OA\Property(property="user_ids", type="array", @OA\Items(type="integer")),
 *     @OA\Property(property="percentage", type="integer", minimum=1, maximum=100, example=100),
 *   ),
 *   @OA\Property(property="trigger", type="object",
 *     @OA\Property(property="on", type="string", enum={"load","delay","exitIntent"}, example="load"),
 *     @OA\Property(property="delaySeconds", type="integer", example=10),
 *     @OA\Property(property="frequency", type="string", enum={"once","untilAnswered","always"}, example="untilAnswered"),
 *   ),
 *   @OA\Property(property="starts_at", type="string", format="date", nullable=true, example="2026-11-01"),
 *   @OA\Property(property="ends_at", type="string", format="date", nullable=true, example="2026-12-01"),
 *   @OA\Property(property="created_by", type="integer", nullable=true, example=1),
 *   @OA\Property(property="responses_count", type="integer", example=0),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
 * )
 */
class Experiment extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_ARCHIVED,
    ];

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_LOGGED_IN = 'logged_in';
    public const AUDIENCE_LOGGED_OUT = 'logged_out';
    public const AUDIENCE_USER_IDS = 'user_ids';

    public const AUDIENCES = [
        self::AUDIENCE_ALL,
        self::AUDIENCE_LOGGED_IN,
        self::AUDIENCE_LOGGED_OUT,
        self::AUDIENCE_USER_IDS,
    ];

    public const TRIGGER_LOAD = 'load';
    public const TRIGGER_DELAY = 'delay';
    public const TRIGGER_EXIT_INTENT = 'exitIntent';

    public const TRIGGERS = [self::TRIGGER_LOAD, self::TRIGGER_DELAY, self::TRIGGER_EXIT_INTENT];

    public const FREQUENCIES = ['once', 'untilAnswered', 'always'];

    public const QUESTION_TYPES = ['rating', 'choice', 'textInput', 'abChoice'];

    public const BLOCK_TYPES = ['heading', 'richText', 'image', 'consentNotice', ...self::QUESTION_TYPES];

    protected $fillable = [
        'name',
        'status',
        'definition',
        'targeting',
        'trigger',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $table = 'experiments';

    protected $casts = [
        'definition' => 'array',
        'targeting' => 'array',
        'trigger' => 'array',
        'starts_at' => 'date:Y-m-d',
        'ends_at' => 'date:Y-m-d',
        'responses_count' => 'integer',
    ];

    public $timestamps = true;

    public function scopeRunning(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    public function blocks(): array
    {
        return collect($this->definition['steps'] ?? [])
            ->flatMap(fn ($step) => $step['blocks'] ?? [])
            ->all();
    }

    public function knownAnswers(array $answers): array
    {
        $questionIds = collect($this->blocks())
            ->filter(fn ($block) => in_array($block['type'] ?? null, self::QUESTION_TYPES, true))
            ->pluck('id')
            ->all();

        return array_intersect_key($answers, array_flip($questionIds));
    }
}
