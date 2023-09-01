<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DarIntegration extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'created_at',
        'updated_at',
        'deleted_at',
        'enabled',
        'notification_email',
        'outbound_auth_type',
        'outbound_auth_key',
        'outbound_endpoints_base_url',
        'outbound_endpoints_enquiry',
        'outbound_endpoints_5safes',
        'outbound_endpoints_5safes_files',
        'inbound_service_account_id',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'dar_integrations';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Indicates whether this model is enabled or disabled
     * 
     * @var bool
     */
    private $enabled = false;

    /**
     * Represents the notification email to use for this dar integration
     * 
     * @var string
     */
    private $notificationEmail = '';

    /**
     * Represents this dar integration outbound auth type
     * 
     * @var string
     */
    private $outboundAuthType = '';
    
    /**
     * Represents this dar integration outbound auth key
     * 
     * @var string
     */
    private $outboundAuthKey = '';

    /**
     * Represents this dar integration outbound endpoint base url
     * 
     * @var string
     */
    private $outboundEndpointsBaseUrl = '';

    /**
     * Represents this dar integration outbound endpoint enquiry
     * 
     * @var string
     */
    private $outboundEndpointsEnquiry = '';

    /**
     * Represents this dar integration outbound endpoint for 5 safes
     * 
     * @var string
     */
    private $outboundEndpoints5Safes = '';

    /** 
     * Represents this dar integration outbound endpoint for 5 safes files
     * 
     * @var string
     */
    private $outboundEndpoints5SafesFiles = '';

    /**
     * Represents this dar integration inbound service account id
     * 
     * @var string
     */
    private $inboundServiceAccountId = '';


}
