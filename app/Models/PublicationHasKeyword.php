<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicationHasKeyword extends Model
{
    use HasFactory;


    protected $fillable = [
        'publication_id',
        'keyword_id',
    ];

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'publication_has_keywords';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
