<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Observers\PublicationHasDatasetVersionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([PublicationHasDatasetVersionObserver::class])]
class PublicationHasDatasetVersion extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    public $timestamps = false;

    protected $fillable = [
        'publication_id', 'dataset_version_id', 'link_type', 'description'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'publication_has_dataset_version';

}
