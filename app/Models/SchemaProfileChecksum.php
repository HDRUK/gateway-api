<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchemaProfileChecksum extends Model
{
    use HasFactory;

    protected $fillable = [
        'checksum',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'schema_profile_checksums';

    /**
     * Whether this model uses timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Represents the calculated checksum of the previous schema check
     *
     * @var string
     */
    private $checksum = '';
}
