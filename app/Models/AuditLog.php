<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use SoftDeletes;
    use HasFactory;

    /**
     * 
     */
    public $table = 'audit_logs';

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'user_id',
        'team_id',
        'action_type',
        'action_service',
        'description',
    ];

    /**
     * Whether or not this model supports timestamps
     * 
     * @var boolean
     */
    public $timestamps = true;

    /**
     * Represents the user id associated with this audit log
     * 
     * @var int
     */
    private $user_id = 0;

    /**
     * Represents the description for this audit log
     * 
     * @var string
     */
    private $description = '';

    /**
     * Represents the function being run for this audit log
     * 
     * @var string
     */
    private $function = '';
}
