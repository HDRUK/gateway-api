<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAccessSection extends Model
{
    use HasFactory;

    /**
     * The table associated with this model
     *
     * @var string
     */
    protected $table = 'dar_sections';

    protected $fillable = [
        'name',
        'description',
        'parent_section',
        'order',
    ];
}
