<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuthorisationCode extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authorisation_codes';
    
    /**
     * Whether or not this model supports timestamps
     * 
     * @var boolean
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'jwt',
        'created_at',
        'expired_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $cast = [];

    /**
     * Create a new row with the given array.
     */
    public static function createRow($data)
    {
        self::deleteRowByUserId((int) $data['user_id']);

        $array = [
            'user_id' => $data['user_id'],
            'jwt' => $data['jwt'],
            'created_at' => $data['created_at'],
            'expired_at' => $data['expired_at'],
        ];

        return self::create($array);
    }

    /**
     * Delete the row with the given user id.
     */
    public static function deleteRowByUserId($userId)
    {
        $model = static::where(['user_id' => $userId]);

        if ($model) {
            return $model->delete();
        }

        return false;
    }

    /**
     * Delete the row with the given jwt.
     */
    public static function findRowByJwt($jwt)
    {
        $model = static::where(['jwt' => $jwt])->first();

        if ($model) {
            return true;
        } else {
            return false;
        }
    }
}
