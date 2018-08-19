<?php

namespace App\Providers;

use App\Services\MoneyService;
use App\Services\WalletService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

	public $singletons = [
		MoneyService::class => MoneyService::class,
		WalletService::class => WalletService::class
	];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    	//
    }
}
