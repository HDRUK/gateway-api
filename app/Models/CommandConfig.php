<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandConfig extends Model
{
    use HasFactory;

    public $table = 'command_config';

    protected $fillable = [
        'ident',
        'url',
        'username',
        'password',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Whether or not this model supports timestamps
     *
     * @var boolean
     */
    public $timestamps = true;

    /**
     * Represents a SiteScraper command indentifier
     *
     * @var string
     */
    private $ident = '';

    /**
     * Represents a SiteScraper command url to pull from
     *
     * @var string
     */
    private $url = '';

    /**
     * Represents a SiteScraper command username to use for auth
     *
     * @var string
     */
    private $username = '';

    /**
     * Represents a SiteScraper command password to use for auth
     *
     * @var string
     */
    private $password = '';

    /**
     * Whether or not this command config is enabled or not
     *
     * @var int
     */
    private $enabled = 0;
}
