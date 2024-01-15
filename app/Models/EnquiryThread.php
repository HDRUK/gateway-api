<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnquiryThread extends Model
{
    use HasFactory;

     protected $fillable = [
        'project_title',
        'unique_key'
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'enquiry_thread';

    public $timestamps = false;


    /**
     * The associated project title
     * 
     * @var string
     */
    public $project_title = '';

    /**
     * Unique key (hashed 64 string) for the enquiry thread
     * 
     * @var string
     */
    public $unique_key = '';

}
