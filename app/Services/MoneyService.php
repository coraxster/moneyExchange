<?php
/**
 * Created by PhpStorm.
 * User: dmitrykuzmin
 * Date: 19/08/2018
 * Time: 13:41
 */

namespace App\Services;

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

/**
 * Class MoneyService
 * @package App\Services
 */
class MoneyService
{
	/**
	 * @var CurrencyService
	 */
	protected $currencyService;

	/**
	 * @var Currency
	 */
	public $mediateCurrency;

	public function __construct(CurrencyService $currencyService, Currency $mediateCurrency = null)
	{
		$this->currencyService = $currencyService;
		$this->mediateCurrency = $mediateCurrency ?? new Currency(Config::get('app.mediate_currency'));
	}

	public function amountToMoney(int $rawAmount, string $currencyCode) : Money
	{
		return new Money($rawAmount, new Currency($currencyCode));
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

	/**
	 * Converts Money if needed.
	 * Firstly tries to find direct ExchangeRate.
	 * If not found tries to convert to $mediateCurrency then $targetCurrency.
	 * If it does not work again, throws Exception
	 *
	 * @param Money $money
	 * @param Currency $targetCurrency
	 * @param bool $roundUp
	 * @param Currency|null $mediateCurrency
	 * @param Carbon|null $date
	 * @return Money
	 * @throws \Exception
	 */
	public function convert(
		Money $money,
		Currency $targetCurrency,
		bool $roundUp = null,
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

		$converter = $this->currencyService->findConverter($sourceCurrency, $targetCurrency, $date);
		if ($converter) {
			return $converter->convert($money, $targetCurrency, $round);
		}

		if ($mediateCurrency === null || $targetCurrency->equals($mediateCurrency)) {
			Log::warning('cant\'t convert :(', ['target' => $targetCurrency]);
			throw new \Exception('cant\'t convert :( ' . $sourceCurrency . ' > ' . $targetCurrency);
		}

		return $this->convert(
			$this->convert($money, $mediateCurrency, $roundUp, null, $date),
			$targetCurrency,
			$roundUp,
			null,
			$date
		);
	}
}
