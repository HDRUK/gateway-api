<?php

namespace App\Models;

use App\Models\Dataset;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;


class PublicationHasDataset extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'publication_id', 'dataset_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'publication_has_dataset';

}
