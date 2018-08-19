<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}
