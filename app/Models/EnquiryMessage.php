<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnquiryMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'from',
        'message_body',
        'thread_id',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'enquiry_messages';

    public $timestamps = true;

    public function getFromAttribute()
    {
        return $this->attributes['from'];
    }

    public function getBodyAttribute()
    {
        return $this->attributes['body'];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(EnquiryThread::class, 'thread_id');
    }

}
