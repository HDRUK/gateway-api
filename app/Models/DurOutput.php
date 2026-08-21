<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DurOutput extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dur_id',
        'type',
        'title',
        'status',
        'detail',
        'url',
    ];

    /**
     * @var string
     */
    protected $table = 'dur_outputs';

    public function dur(): BelongsTo
    {
        return $this->belongsTo(Dur::class);
    }
}
