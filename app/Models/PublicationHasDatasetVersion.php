<?php

namespace App\Models;

use App\Models\Dataset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PublicationHasDatasetVersion extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    public $timestamps = false;

    protected $fillable = [
        'publication_id', 'dataset_version_id', 'link_type'
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'publication_has_dataset_version';

}
