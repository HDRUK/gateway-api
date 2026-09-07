<?php

namespace App\Models;

use App\Observers\PublicationHasDatasetVersionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * Populated by the join select in Gwdm2xHandler::afterRead() (publications.paper_doi):
 *
 * @property-read string|null $paper_doi
 */
#[ObservedBy([PublicationHasDatasetVersionObserver::class])]
class PublicationHasDatasetVersion extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'publication_id',
        'dataset_version_id',
        'link_type',
        'description',
        'raw_doi',
    ];

    protected $dates = ['deleted_at'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'publication_has_dataset_version';
}
