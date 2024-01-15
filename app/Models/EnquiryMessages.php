<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnquiryMessages extends Model
{
    use HasFactory;

    protected $fillable = [
        'from',
        'message_body'
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'enquiry_messages';

    public $timestamps = false;

    /**
     * Email address from
     * 
     * @var string
     */
    public $from = '';

    /**
     * Email body content
     * 
     * @var string
     */
    public $message_body = '';
}
