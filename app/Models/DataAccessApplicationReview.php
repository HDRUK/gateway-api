<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="DataAccessApplicationReview",
 *   description="A review/comment thread against a single question of a Data Access Application",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="application_id", type="integer", example=10),
 *   @OA\Property(property="question_id", type="integer", nullable=true, example=55),
 *   @OA\Property(property="resolved", type="integer", enum={0,1}, example=0),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 * )
 */
class DataAccessApplicationReview extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_application_reviews';

    protected $fillable = [
        'application_id',
        'question_id',
        'resolved',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(DataAccessApplicationComment::class, 'review_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DataAccessApplicationReviewHasFile::class, 'review_id');
    }
}
