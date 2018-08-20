<?php


use App\Models\ExchangeRate;
use App\Models\User;
use App\Models\Wallet;
use \App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class DatabaseSeeder extends Seeder
{

	protected $defaultCurrencies = [
		'USD' => [
			'source_currency' => 'RUB',
			'target_currency' => 'USD',
			'rate' => 67.543223
		],
		'EUR' => [
			'source_currency' => 'EUR',
			'target_currency' => 'USD',
			'rate' => 0.823423
		]
	];

	/**
	 * Seed the application's database.
	 *
	 * @return void
	 * @throws Exception
	 */
    public function run() : void
    {
        $users = factory(User::class, 50)->create()->each(function (User $u) {
            $u->wallet()->save(factory(Wallet::class)->make());
        });
	    $this->seedCurrencies($users);
	    $this->seedTransfers($users);
    }

	/**
	 * @param $users
	 */
	protected function seedCurrencies($users): void
	{
		foreach ($this->defaultCurrencies as $currencyData) {
			$currencyData['date'] = today();
			factory(ExchangeRate::class)->create($currencyData);
		}

		$currencies = $users->pluck('wallet.currency')->unique();
		foreach ($currencies as $currency) {
			$exists = ExchangeRate::query()->where([
				'date' => today(),
				'source_currency' => $currency
			])->exists();
			if ($exists || $currency === Config::get('app.mediate_currency')) {
				continue;
			}
			factory(ExchangeRate::class)->create([
				'date' => today(),
				'source_currency' => $currency
			]);
		}
	}

	/**
	 * @param $users
	 * @throws Exception
	 */
	protected function seedTransfers($users): void
	{
		/* @var WalletService $walletService */
		$walletService = resolve(WalletService::class);

		foreach ($users as $user) {
			$walletA = $user->wallet;
			for ($i = random_int(5, 10); $i > 0; $i--) {
				$walletB = $users[rand(0, 49)]->wallet;
				if ($walletA->id === $walletB->id) {
					continue;
				}
				$money = $walletA->money->divide(rand(2, 8));
				[$wa, $wb] = rand(0, 1) ? [$walletA, $walletB] : [$walletB, $walletA];
				$walletService->transfer($wa, $wb, $money);
			}
		}
	}
}
