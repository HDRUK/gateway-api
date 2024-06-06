<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnquiryThread extends Model
{
    use HasFactory;

     protected $fillable = [
        'title',
        'unique_key'
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
     * Define the one-to-many relationship with EnquiryMessages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
    */
    public function messages(): HasMany
    {
        return $this->hasMany(EnquiryMessages::class, 'thread_id');
    }
}