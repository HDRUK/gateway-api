<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankHoliday extends Model
{
    protected $table = 'bank_holidays';
    public $timestamps = false;

    protected $fillable = [
        'country',
        'region',
        'user_id',
        'holiday_date',
        'title',
    ];
}
