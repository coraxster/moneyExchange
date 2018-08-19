<?php
/**
 * Created by PhpStorm.
 * User: dmitrykuzmin
 * Date: 19/08/2018
 * Time: 13:41
 */

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Exchange\ReversedCurrenciesExchange;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

class MoneyService
{
	public const RATE_REGEX = '/^\d{1,8}(\.\d{1,8})?$/';

	public $mediateCurrency;

	public function __construct(Currency $mediateCurrency = null)
	{
		$this->mediateCurrency = $mediateCurrency ?? new Currency(Config::get('app.mediate_currency'));
	}

	public function amountToMoney(int $amount, string $currencyCode) : Money
	{
		return new Money($amount, new Currency($currencyCode));
	}

	public function parseMoney(string $sum, $currencyCode) : Money
	{
		$currencies = new ISOCurrencies();
		$moneyParser = new DecimalMoneyParser($currencies);
		return $moneyParser->parse($sum, $currencyCode);
	}

	public function formatMoney(Money $money) : string
	{
		$currencies = new ISOCurrencies();
		$moneyFormatter = new DecimalMoneyFormatter($currencies);
		return $moneyFormatter->format($money);
	}

	public function createConverter(string $sourceCurrencyCode, string $targetCurrencyCode, $rate) : Converter
	{
		$exchange = new ReversedCurrenciesExchange(new FixedExchange([
			$targetCurrencyCode => [
				$sourceCurrencyCode => $rate
			]
		]));
		return new Converter(new ISOCurrencies(), $exchange);
	}

	public function convertIfNeed(
		Money $money,
		Currency $targetCurrency,
		$roundUp = null,
		Currency $mediateCurrency = null,
		Carbon $date = null
	) : Money
	{
		if ($money->getCurrency()->equals($targetCurrency)) {
			return $money;
		}
		$round = $roundUp ? Money::ROUND_UP : Money::ROUND_DOWN;
		$date = $date ?? today();
		$sourceCurrency = $money->getCurrency();

		/* @var ExchangeRate $rate */
		$rate = ExchangeRate::query()
			->where('date', $date)
			->withCurrencyPair($sourceCurrency->getCode(), $targetCurrency->getCode())
			->first();

		if ($rate) {
			return $rate->getConverter()->convert($money, $targetCurrency, $round);
		}

		if ($mediateCurrency === null || $targetCurrency->equals($mediateCurrency)) {
			Log::warning('cant\'t convert :(', ['target' => $targetCurrency]);
			throw new \Exception('cant\'t convert :(', ['target' => $targetCurrency]);
		}

		return $this->convertIfNeed(
			$this->convertIfNeed($money, $mediateCurrency, $roundUp, $mediateCurrency, $date),
			$targetCurrency,
			$roundUp,
			$mediateCurrency,
			$date
		);
	}



}
