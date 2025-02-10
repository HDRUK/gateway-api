<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnquiryThread extends Model
{
    use HasFactory;

    protected $fillable = [
       'user_id',
       'team_id',
       'project_title',
       'unique_key',
       'enabled',
       'is_dar_dialogue',
       'is_dar_status',
       'is_general_enquiry',
       'is_feasibility_enquiry',
       'is_dar_review',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'enquiry_thread';

    public $timestamps = false;


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
