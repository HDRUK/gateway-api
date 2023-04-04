<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    use HasFactory;

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'name',
        'enabled',
        'allows_messaging',
        'workflow_enabled',
        'access_requests_management',
        'uses_5_safes',
        'member_of',
        'contact_point',
        'application_form_updated_by',
        'application_form_updated_on',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'publishers';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Indicates the name of the publisher
     * 
     * @var string
     */
    private $name = '';

    /**
     * Indicates whether this model is enabled or disabled
     * 
     * @var bool
     */
    private $enabled = false;

    /**
     * Indicates whether the publisher allows messaging or not
     * 
     * @var bool
     */
    private $allows_messaging = false;

    /**
     * Indicates whether the publisher has workflows enabled
     * 
     * @var bool
     */
    private $workflow_enabled = false;

    /**
     * Indicates whether the publisher has acces requst management enabled
     * 
     * @var bool
     */
    private $access_requests_management = false;

    /**
     * Indicates whether the publisher uses 5 safes
     * 
     * @var bool
     */
    private $uses_5_safes = false;

    /**
     * Indicates the organisation the publisher is a member of
     * 
     * @var string
     */
    private $member_of = '';

    /**
     * Represents the contact point for the publisher
     * 
     * @var string
     */
    private $contact_point = '';

    /**
     * Represents the person to last update the application
     * 
     * @var string
     */
    private $application_form_updated_by = '';

    /**
     * Indicates the datetime when the application was last updated
     * 
     * @var string
     */
    private $application_form_updated_on = '';
}
