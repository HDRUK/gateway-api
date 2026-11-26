<?php

namespace App\Modules\Closer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A CLOSER QuestionItem or QuestionGrid, reduced to its label and text.
 * For anything more (grid rows, answer options, routing) link to CLOSER
 * Discovery: {discovery_url}/item/{wave agency}/{identifier}.
 */
class CloserQuestion extends Model
{
    public const TYPE_ITEM = 'item';
    public const TYPE_GRID = 'grid';

    protected $table = 'closer_questions';

    protected $fillable = [
        'closer_wave_id',
        'identifier',
        'version',
        'type',
        'label',
        'text',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    public function wave(): BelongsTo
    {
        return $this->belongsTo(CloserWave::class, 'closer_wave_id');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(CloserVariable::class);
    }
}
