<?php
/**
 * Created by PhpStorm.
 * User: dmitrykuzmin
 * Date: 21/08/2018
 * Time: 09:53
 */

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Money\Converter;
use Money\Currency;

/**
 * Class CurrencyService
 * @package App\Services
 */
class CurrencyService
{

	/**
	 * @param Currency $from
	 * @param Currency $to
	 * @param Carbon $date
	 * @return Converter|null
	 */
	public function findConverter(Currency $from, Currency $to, Carbon $date) : ?Converter
	{
		/* @var ExchangeRate $rate */
		$rate = ExchangeRate::query()
			->where('date', $date)
			->withCurrencyPair($from->getCode(), $to->getCode())
			->first();

		return $rate ? $rate->getConverter() : null;
	}

}