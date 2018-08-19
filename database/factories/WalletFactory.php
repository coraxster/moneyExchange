<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Config;

$factory->define(App\Models\Wallet::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(App\Models\User::class)->create()->id;
        },
        'currency' => $faker->randomElement(Config::get('app.currencies')),
        'raw_amount' => random_int(0, 9999999)
    ];
});
