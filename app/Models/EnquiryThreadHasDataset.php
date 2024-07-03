<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnquiryThreadHasDataset extends Model
{
    use HasFactory;

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'enquiry_thread_has_datasets';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'enquiry_thread_id',
        'dataset_id',
        'interest_type',
    ];
}
