<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExchangeRateTest extends TestCase
{
	use RefreshDatabase;


    public function testAdd()
    {
	    $data = [
		    'date' => '2018-01-02',
		    'source_currency' => 'RUB',
		    'rate' => '1.234567'
	    ];
	    $this->post('/api/rates', $data)
		    ->assertStatus(200)
		    ->assertJson([
			    'status' => true
		    ]);

	    $rate = ExchangeRate::query()->where($data)->first();
	    $this->assertNotNull($rate);
    }

	public function testAddWrongCurrency()
	{
		$data = [
			'date' => '2018-01-02',
			'source_currency' => 'DOLLAR',
			'rate' => '1.234567'
		];
		$this->post('/api/rates', $data)
			->assertStatus(302);

		$rate = ExchangeRate::query()->where($data)->first();
		$this->assertNull($rate);
	}
}
