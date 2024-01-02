<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DatasetVersion extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'dataset_versions';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'dataset_id',
        'metadata',
        'version',
    ];

    /**
     * Accessor for the metadata field to convert json string to 
     * php array for inclusion in json response object. Weirdly
     * the $casts of metadata to object was failing. Possibly due
     * to the encoding of the string being added to the db field.
     * Needs further investigation as this is just a workaround.
     * 
     * @param $value The original value prior to pre-processing
     * 
     * @return array The json metadata string as an array
     */
    public function getMetadataAttribute($value): array
    {
        return json_decode(json_decode($value, true), true);
    }
}
