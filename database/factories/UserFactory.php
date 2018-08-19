<?php

use Faker\Generator as Faker;


$factory->define(App\Models\User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'country' => $faker->country,
        'city' => $faker->city,
    ];
});
