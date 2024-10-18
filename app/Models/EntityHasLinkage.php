<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityHasLinkage extends Model
{
    use HasFactory;

    public $table = 'entity_has_linkages';

    protected $fillable = [
        'entity_id',
        'linked_entity_id',
        'entity_type',
        'direct_linkage',
        'linkage_type',
    ];
}
