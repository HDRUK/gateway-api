<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Alias",
 *   description="An alias used to label a team or dataset",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="Also known as XYZ"),
 * )
 */
class Alias extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $table = 'aliases';

    protected $fillable = [
        'name',
    ];
}
