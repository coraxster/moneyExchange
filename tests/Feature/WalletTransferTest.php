<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MoneyService;
use Money\Currency;
use Money\Money;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletTransferTest extends TestCase
{
	use RefreshDatabase;

	/* @var MoneyService $moneyService */
	protected $moneyService;

	public function setUp()
	{
		parent::setUp();
		$this->moneyService = resolve(MoneyService::class);
	}


	public function testTransferSameCurrency()
    {
    	$u1 = $this->makeUserWithCurrency('USD');
    	$u2 = $this->makeUserWithCurrency('USD');
    	$w1 = $u1->wallet;
    	$w2 = $u2->wallet;

	    /* @var Money $beforeMoney1 */
	    /* @var Money $beforeMoney2 */
	    $beforeMoney1 = $w1->money;
	    $beforeMoney2 = $w2->money;

	    $amount = '1000';
	    $money = $this->moneyService->parseMoney($amount, $w1->currency);

	    $this->post("/api/wallet/{$w1->id}/transfer", [
	    	'amount' => $amount,
		    'currency' => $w1->currency,
		    'to_wallet_id' => $w2->id
	    ])
		    ->assertStatus(200)
		    ->assertJson(['status' => true]);

	    /* @var Money $afterMoney1 */
	    /* @var Money $afterMoney2 */
	    $afterMoney1 = $w1->refresh()->money;
	    $afterMoney2 = $w2->refresh()->money;

	    $this->assertTrue($money->equals($beforeMoney1->subtract($afterMoney1)));
	    $this->assertTrue($money->equals($afterMoney2->subtract($beforeMoney2)));

	    $this->assertTrue($beforeMoney1->greaterThan($afterMoney1));
	    $this->assertTrue($afterMoney2->greaterThan($beforeMoney2));
    }

	public function testTransferWithSingleConversion()
	{
		$this->makeExchengeRate('RUB', 'USD', 67.543223);
		$u1 = $this->makeUserWithCurrency('USD');
		$u2 = $this->makeUserWithCurrency('RUB');
		$w1 = $u1->wallet;
		$w2 = $u2->wallet;

		/* @var Money $beforeMoney1 */
		/* @var Money $beforeMoney2 */
		$beforeMoney1 = $w1->money;
		$beforeMoney2 = $w2->money;

		$amount = '1000';
		$money = $this->moneyService->parseMoney($amount, $w1->currency);
		$exceptingMoney1 = $beforeMoney1->subtract($money);
		$exceptingMoney2 = $beforeMoney2->add($this->moneyService->convert($money, $beforeMoney2->getCurrency()));

		$this->post("/api/wallet/{$w1->id}/transfer", [
			'amount' => $amount,
			'currency' => $w1->currency,
			'to_wallet_id' => $w2->id
		])
			->assertStatus(200)
			->assertJson(['status' => true]);

		/* @var Money $afterMoney1 */
		/* @var Money $afterMoney2 */
		$afterMoney1 = $w1->refresh()->money;
		$afterMoney2 = $w2->refresh()->money;

		$this->assertTrue($afterMoney1->equals($exceptingMoney1));
		$this->assertTrue($afterMoney2->equals($exceptingMoney2));
	}

	public function testTransferWithDoubleConversion()
	{
		$this->makeExchengeRate('RUB', 'USD', 67.543223);
		$this->makeExchengeRate('EUR', 'USD', 0.823423);
		$u1 = $this->makeUserWithCurrency('RUB');
		$u2 = $this->makeUserWithCurrency('EUR');
		$w1 = $u1->wallet;
		$w2 = $u2->wallet;

		/* @var Money $beforeMoney1 */
		/* @var Money $beforeMoney2 */
		$beforeMoney1 = $w1->money;
		$beforeMoney2 = $w2->money;

		$amount = '1000';
		$money = $this->moneyService->parseMoney($amount, $w1->currency);
		$exceptingMoney1 = $beforeMoney1->subtract($money);
		$exceptingMoney2 = $beforeMoney2->add(
			$this->moneyService->convert(
				$this->moneyService->convert($money, new Currency('USD')),
				new Currency('EUR')
			)
		);

		$this->post("/api/wallet/{$w1->id}/transfer", [
			'amount' => 1000,
			'currency' => $w1->currency,
			'to_wallet_id' => $w2->id
		])
			->assertStatus(200)
			->assertJson(['status' => true]);

		/* @var Money $afterMoney1 */
		/* @var Money $afterMoney2 */
		$afterMoney1 = $w1->refresh()->money;
		$afterMoney2 = $w2->refresh()->money;

		$this->assertTrue($afterMoney1->equals($exceptingMoney1));
		$this->assertTrue($afterMoney2->equals($exceptingMoney2));
	}

	public function testTransferRounding()
	{
		$this->makeExchengeRate('RUB', 'USD', 67.543223);
		$this->makeExchengeRate('EUR', 'USD', 0.823423);

		$u1 = $this->makeUserWithCurrency('EUR');
		$u2 = $this->makeUserWithCurrency('RUB');
		$w1 = $u1->wallet;
		$w2 = $u2->wallet;

		/* @var Money $beforeMoney1 */
		/* @var Money $beforeMoney2 */
		$beforeMoney1 = $w1->money;
		$beforeMoney2 = $w2->money;

		for ($i=0;$i<10;$i++) {
			$this->post("/api/wallet/{$w1->id}/transfer", [
				'amount' => 1000,
				'currency' => $w1->currency,
				'to_wallet_id' => $w2->id
			])
				->assertStatus(200)
				->assertJson(['status' => true]);
		}

		for ($i=0;$i<10;$i++) {
			$this->post("/api/wallet/{$w2->id}/transfer", [
				'amount' => 1000,
				'currency' => $w1->currency,
				'to_wallet_id' => $w1->id
			])
				->assertStatus(200)
				->assertJson(['status' => true]);
		}

		/* @var Money $afterMoney1 */
		/* @var Money $afterMoney2 */
		$afterMoney1 = $w1->refresh()->money;
		$afterMoney2 = $w2->refresh()->money;

		$this->assertTrue($beforeMoney1->greaterThanOrEqual($afterMoney1));
		$this->assertTrue($beforeMoney2->greaterThanOrEqual($afterMoney2));
	}



	protected function makeExchengeRate($from, $to, $rate) : ExchangeRate
	{
		$data = [
			'date' => today(),
			'source_currency' => $from,
			'target_currency' => $to,
			'rate' => $rate
		];
		return factory(ExchangeRate::class)->create($data)->first();
	}

	protected function makeUserWithCurrency(string $currencyCode) : User
	{
		return factory(User::class, 1)->create()->each(function (User $u) use ($currencyCode) {
			$u->wallet()->save(factory(Wallet::class)->make(['currency' => $currencyCode]));
		})->first();
	}

}