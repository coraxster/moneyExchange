<?php


use App\Models\ExchangeRate;
use App\Models\User;
use App\Models\Wallet;
use \App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 *
	 * @param WalletService $walletService
	 * @return void
	 * @throws Exception
	 */
    public function run(WalletService $walletService) : void
    {
        $users = factory(User::class, 50)->create()->each(function (User $u) {
            $u->wallet()->save(factory(Wallet::class)->make());
        });

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

        foreach ($users as $user) {
        	$walletA = $user->wallet;
        	for ($i=random_int(10, 100) ; $i>0 ; $i--) {
		        $walletB = $users[rand(0, 50)]->wallet;
		        if ($walletA->id === $walletB->id) {
		        	continue;
		        }
		        $money = $walletA->money->divide(10);
		        $walletService->transfer($walletA, $walletB, $money);
	        }
        }
    }
}
