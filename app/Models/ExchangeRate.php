<?php

namespace App\Models;

use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Money\Converter;

class ExchangeRate extends Model
{
	public const DEFAULT_TARGET = 'USD';

	public $fillable = [
		'date',
		'rate',
		'source_currency',
		'target_currency'
	];

    public static function rules() : array
    {
	    return [
		    'date' => 'required|date|date_format:Y-m-d',
		    'rate' => 'required|numeric|regex:' . MoneyService::RATE_REGEX,
    		'source_currency' => 'required|string|max:255|in:' . implode(',', Config::get('app.currencies')),
    		'target_currency' => 'required_if:source_currency,' . Config::get('app.mediate_currency') .
			    '|string|max:255|different:source_currency|in:' . implode(',', Config::get('app.currencies'))
	    ];
    }

	public static function getTableName()
	{
		return (new self)->getTable();
	}

	public function scopeWithCurrencyPair(Builder $query, $c1, $c2) : Builder
	{
		return $query->where([
				'source_currency' => $c1,
				'target_currency' => $c2
			])
			->orWhere(function ($query) use ($c1, $c2) {
				$query->where([
					'source_currency' => $c2,
					'target_currency' => $c1
				]);
			});
	}

	public function getConverter() : Converter
	{
		/* @var MoneyService $moneyService */
		$moneyService = resolve(MoneyService::class);
		return $moneyService->createConverter($this->source_currency, $this->target_currency, $this->rate);
	}
}
