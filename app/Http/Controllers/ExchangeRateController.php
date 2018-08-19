<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class ExchangeRateController extends Controller
{
    public function create(Request $request)
    {
    	$validatedData = $request->validate(ExchangeRate::rules());

    	// validate for unique
    	$request->validate(
    		[
    			'date' => Rule::unique(ExchangeRate::getTableName())->where(function ($query) use ($request) {
				    return $query
					    ->where([
					    	'source_currency' => $request->get('source_currency'),
						    'target_currency' => $request->get('target_currency', Config::get('app.mediate_currency'))
					    ]);
			    })
		    ],
		    [
		    	'date' => 'ExchangeRate on this date already exists.'
		    ]
	    );

        return ExchangeRate::query()->create($validatedData);
    }
}
