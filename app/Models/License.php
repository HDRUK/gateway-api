<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *   schema="License",
 *   description="A license record describing terms under which a dataset/tool may be accessed",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="code", type="string", example="HDR_CATEGORY_AVAILABLE_UPON_REQUEST"),
 *   @OA\Property(property="label", type="string", nullable=true, example="Available upon request"),
 *   @OA\Property(property="valid_since", type="string", format="date-time", nullable=true, example="2024-04-15T00:00:00Z"),
 *   @OA\Property(property="valid_until", type="string", format="date-time", nullable=true, example="2024-04-15T00:00:00Z"),
 *   @OA\Property(property="definition", type="string", nullable=true, example="Access to the software is available upon request."),
 *   @OA\Property(property="verified", type="boolean", example=true),
 *   @OA\Property(property="origin", type="string", example="HDR"),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 * )
 */
class License extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $table = 'licenses';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'label',
        'valid_since',
        'valid_until',
        'definition',
        'deleted_at',
        'verified',
        'origin',
    ];
}
