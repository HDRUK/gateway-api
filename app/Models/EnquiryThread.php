<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnquiryThread extends Model
{
    use HasFactory;

    protected $fillable = [
       'user_id',
    //    'team_id',
       'team_ids',
       'project_title',
       'unique_key',
       'enabled',
       'is_dar_dialogue',
       'is_dar_status',
       'is_general_enquiry',
       'is_feasibility_enquiry',
       'is_dar_review',
    ];

    // protected $casts = [
    //     'team_ids' => 'array',
    // ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'enquiry_threads';

    public $timestamps = false;

    /**
     * Get and Set the team IDs.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function teamIds(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    }

    public function getProjectTitleAttribute()
    {
        return $this->attributes['project_title'];
    }

    /**
     * Define the one-to-many relationship with EnquiryMessage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
    */
    public function messages(): HasMany
    {
        return $this->hasMany(EnquiryMessage::class, 'thread_id');
    }
}
