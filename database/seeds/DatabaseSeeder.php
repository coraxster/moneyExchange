<?php


use App\Models\ExchangeRate;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() : void
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
    }
}
