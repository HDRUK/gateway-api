<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnquiryThreadHasDatasetVersion extends Model
{
    use HasFactory;

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'enquiry_thread_has_dataset_version';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'enquiry_thread_id',
        'dataset_version_id',
        'interest_type',
    ];
}
