<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\MoneyService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;


class WalletController extends Controller
{
	protected $walletService;
	protected $moneyService;
	public function __construct(WalletService $walletService, MoneyService $moneyService)
	{
		$this->walletService = $walletService;
		$this->moneyService = $moneyService;
	}

	public function refill(int $walletId, Request $request)
    {
        $validatedData = $request->validate(
        	[
        		'amount' => 'required|numeric|min:0',
		        'currency' => 'string|max:255|in:' . implode(',', Config::get('app.currencies'))
	        ]
        );

	    /* @var Wallet $wallet */
        $wallet = Wallet::query()->findOrFail($walletId);
	    $validatedData['currency'] = $validatedData['currency'] ?? $wallet->money->getCurrency();
	    $money = $this->moneyService->parseMoney($validatedData['amount'], $validatedData['currency']);

	    $this->walletService->refill($wallet, $money);

        return [
        	'status' => true,
	        'wallet' => $wallet
        ];
    }

    public function transfer(int $fromWalletId, Request $request)
    {
	    $request->validate([
		    'currency' => 'required|string|max:255|in:' . implode(',', Config::get('app.currencies')),
		    'to_wallet_id' => 'required|int',
		    'amount' => 'required'
	    ]);

	    /* @var Wallet $fromWallet */
	    /* @var Wallet $toWallet */
	    $fromWallet = Wallet::query()->findOrFail($fromWalletId);
	    $toWallet = Wallet::query()->findOrFail($request->get('to_wallet_id'));

	    $currencies = [
		    $fromWallet->money->getCurrency(),
		    $toWallet->money->getCurrency()
	    ];

	    $request->validate(
	    	[
            'currency' => 'required|string|max:255|in:' . implode(',', $currencies)
		    ],
		    [
		    	'currency.in' => 'Provided currency is not supported by this wallets. Choose one of ' . implode(',', $currencies)
		    ]);

	    $money = $this->moneyService->parseMoney($request->get('amount'), $request->get('currency'));

	    $this->walletService->transfer($fromWallet, $toWallet, $money);

	    return [
	    	'status' => true,
		    'fromWallet' => $fromWallet->refresh(),
		    'toWallet' => $toWallet->refresh()
	    ];
    }


}
