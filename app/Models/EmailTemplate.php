<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'identifier',
        'enabled',
        'body',
        'subject',
        'buttons'
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'email_templates';

    /**
     * Specifically requests that laravel casts these values
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Whether or not this model implements timestamps
     *
     * @var boolean
     */
    public $timestamps = true;

    /**
     * Represents the descriptive text to describe this email object
     *
     * @var string
     */
    public $identifier = '';

    /**
     * Whether or not this model is enabled or disabled
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * Represents the body of the entire message of this email object
     *
     * @var string
     */
    public $body = '';

    /**
     * Represents the subject for this message of this email object
     *
     * @var string
     */
    public $subject = '';
}
