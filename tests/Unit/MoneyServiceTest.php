<?php

namespace Tests\Unit;

use App\Services\CurrencyService;
use App\Services\MoneyService;
use Money\Currency;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MoneyServiceTest extends TestCase
{
	use RefreshDatabase;

	protected $simpleConvertCases = [
		[
			'from' => '10.00',
			'rate' => '2',
			'toRoundUp' => '5.00',
			'toRoundDown' => '5.00'
		],
		[
			'from' => '5.00',
			'rate' => '0.5',
			'toRoundUp' => '10.00',
			'toRoundDown' => '10.00'
		],
		[
			'from' => '1.00',
			'rate' => '3',
			'toRoundUp' => '0.34',
			'toRoundDown' => '0.33'
		],
		[
			'from' => '3.00',
			'rate' => '0.33333',
			'toRoundUp' => '9.01',
			'toRoundDown' => '9.00'
		]
	];

    public function testSimpleConversion()
    {
	    foreach ($this->simpleConvertCases as $case) {
		    $this->simpleCase($case);
	    }
    }

    protected function simpleCase(array $case)
    {
	    $mockedCurrencyService = $this->createMock(CurrencyService::class);
	    /* @var MoneyService $moneyService */
	    $moneyService = new MoneyService($mockedCurrencyService);
	    $converter = $moneyService->createConverter('RUB', 'USD', $case['rate']);
	    $mockedCurrencyService->method('findConverter')->willReturn($converter);

	    $money = $moneyService->parseMoney($case['from'], new Currency('RUB'));

	    $newMoneyRoundUp = $moneyService->convert($money, new Currency('USD'), true);
	    $newMoneyRoundDown = $moneyService->convert($money, new Currency('USD'), false);
	    $this->assertEquals(
		    $case['toRoundUp'],
		    $moneyService->formatMoney($newMoneyRoundUp)
	    );
	    $this->assertEquals(
		    $case['toRoundDown'],
		    $moneyService->formatMoney($newMoneyRoundDown)
	    );
    }
}
