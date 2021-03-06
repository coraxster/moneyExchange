<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Money\Money;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletRefillTest extends TestCase
{
	use RefreshDatabase;

	/* @var User $u */
	protected $u;
	/* @var Wallet $w */
	protected $w;

	public function setUp() : void
	{
		parent::setUp();

		$this->u = factory(User::class, 1)->create()->each(function (User $u) {
			$u->wallet()->save(factory(Wallet::class)->make());
		})->first();
		$this->w = $this->u->wallet;
	}

	public function testRefill()
    {
	    /* @var Money $beforeMoney */
	    $beforeMoney = $this->w->money;

	    $this->post("/api/wallet/{$this->w->id}/refill", [
	    	'amount' => 100,
		    'currency' => $this->w->currency
	    ])
		    ->assertStatus(200)
		    ->assertJson([
		    	'status' => true
		    ]);

	    $afterMoney = $this->w->refresh()->money;
	    $this->assertFalse($beforeMoney->equals($afterMoney));
    }

	public function testRefillNegative()
	{
		/* @var Money $beforeMoney */
		$beforeMoney = $this->w->money;

		$this->post("/api/wallet/{$this->w->id}/refill", [
			'amount' => -100,
			'currency' => $this->w->currency
		])
			->assertStatus(302);

		$afterMoney = $this->w->refresh()->money;
		$this->assertTrue($beforeMoney->equals($afterMoney));
	}

	public function testRefillNotExists()
	{
		$this->post('/api/wallet/100/refill', [
			'amount' => 100,
			'currency' => $this->w->currency
		])
			->assertStatus(404);
	}

	public function testRefillWrongCurrency()
	{
		/* @var Money $beforeMoney */
		$beforeMoney = $this->w->money;

		$this->post("/api/wallet/{$this->w->id}/refill", [
			'amount' => 100,
			'currency' => 'BAD'
		])
			->assertStatus(302);

		$afterMoney = $this->w->refresh()->money;
		$this->assertTrue($beforeMoney->equals($afterMoney));
	}

	public function testRefillWithoutCurrency()
	{
		/* @var Money $beforeMoney */
		$beforeMoney = $this->w->money;

		$this->post("/api/wallet/{$this->w->id}/refill", [
			'amount' => 100
		])
			->assertStatus(302);

		$afterMoney = $this->w->refresh()->money;
		$this->assertTrue($beforeMoney->equals($afterMoney));
	}
}