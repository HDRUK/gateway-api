<?php

namespace App\Models;

use App\Models\Team;
use App\Models\User;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataUseRegister extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'counter',
        'keywords',
        'dataset_ids',
        'gateway_dataset_ids',
        'non_gateway_dataset_ids',
        'gateway_applicants',
        'non_gateway_applicants',
        'funders_and_sponsors',
        'other_approval_committees',
        'gateway_output_tools',
        'gateway_output_papers',
        'non_gateway_outputs',
        'project_title',
        'project_id_text',
        'organisation_name',
        'organisation_sector',
        'lay_summary',
        'latest_approval_date',
        'enabled',
        'team_id',
        'user_id',
        'last_activity',
        'manual_upload',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'keywords' => 'array',
        'dataset_ids' => 'array',
        'gateway_dataset_ids' => 'array',
        'non_gateway_dataset_ids' => 'array',
        'gateway_applicants' => 'array',
        'non_gateway_applicants' => 'array',
        'funders_and_sponsors' => 'array',
        'other_approval_committees' => 'array',
        'gateway_output_tools' => 'array',
        'gateway_output_papers' => 'array',
        'non_gateway_outputs' => 'array',
        'latest_approval_date' => 'datetime',
        'enabled' => 'boolean',
        'last_activity' => 'datetime',
        'manual_upload' => 'boolean',
    ];

    /**
     * Get the user that owns the data use register
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Get the team that owns the data use register
     */
    public function team(): HasOne
    {
        return $this->hasOne(Team::class, 'id', 'team_id');
    }
}
