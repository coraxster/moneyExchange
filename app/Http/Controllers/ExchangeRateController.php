<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * Class ExchangeRateController
 * @package App\Http\Controllers
 */
class ExchangeRateController extends Controller
{
	/**
	 * @param Request $request
	 * @return array
	 */
	public function create(Request $request)
    {
    	$validatedData = $request->validate(ExchangeRate::rules());

    	// validate for unique
    	$request->validate(
    		[
    			'date' => Rule::unique(ExchangeRate::getTableName())
				    ->where(function ($query) use ($request) {
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
    	$rate = ExchangeRate::query()->create($validatedData);
    	return [
    		'status' => true,
		    'rate' => $rate
	    ];
    }
}
