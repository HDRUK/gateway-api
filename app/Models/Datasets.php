<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Datasets extends Model
{
    use HasFactory;

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'datasets';

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'active' => 'boolean',
        'comercialUse' => 'boolean',
        'is5Safes' => 'boolean',
        'isCohortDiscovery' => 'boolean',
    ];

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Dictates the mongo imported id associated with this model
     * 
     * @var string
     */
    private $mongoId = '';

    /**
     * Indicates if this model is active or draft
     * 
     * @var bool
     */
    private $active = false;

    /**
     * Author id associated with this model
     * 
     * @var int
     */
    private $applicationStatusAuthor = 0;

    /**
     * Description identifier associated with this model's application status
     * 
     * @var int
     */
    private $applicationStatusDesc = 0;

    /**
     * Indicates whether this model is used commercially
     * 
     * @var bool
     */
    private $commercialUse = false;

    /**
     * Legacy identifier to track dataset ids, but we'll organically
     * have the models primary key supersed this
     * 
     * @var string
     */
    private $datasetId = '';

    /**
     * Indicates if this model uses 5 safes framework
     * 
     * @var bool
     */
    private $is5Safes = false;

    /**
     * Indicates if this model is for cohort discovery
     * 
     * @var bool
     */
    private $isCohortDiscovery = false;

    /**
     * License associated with this model
     * 
     * @var string
     */
    private $license = '';

    /**
     * Identifier associated with this model - legacy
     * 
     * @var string
     */
    private $pid = '';

    /**
     * Question Answers associated with this model
     * 
     * @var string
     */
    private $questionAnswers = '';

    /**
     * Source associated with this model
     * 
     * @var string
     */
    private $source = '';
}
