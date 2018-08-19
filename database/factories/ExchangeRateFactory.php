<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Config;

$factory->define(App\Models\ExchangeRate::class, function (Faker $faker) {
    return [
	    'date' => $faker->date('Y-M-D'),
	    'rate' => $faker->randomFloat(6, 0.00374941, 100.99999999),
        'source_currency' => $faker->randomElement(
        	array_diff(Config::get('app.currencies'), [Config::get('app.mediate_currency')])
        ),
        //'target_currency' => $faker->currencyCode, // now we have always USD default
    ];
});
