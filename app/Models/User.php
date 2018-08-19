<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class User
 * @package App\Models
 */
class User extends Model
{

	protected $fillable = [
        'name',
        'country',
        'city'
    ];

	public static function rules() : array
    {
        return [
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255'
        ];
    }

	public function wallet() : HasOne
    {
        return $this->hasOne(Wallet::class);
    }
}
