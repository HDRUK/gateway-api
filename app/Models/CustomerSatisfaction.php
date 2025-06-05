<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSatisfaction extends Model
{
    use HasFactory;

    protected $table = 'customer_satisfactions';

    protected $fillable = ['score', 'reason'];
}
