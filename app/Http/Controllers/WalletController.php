<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\MoneyService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;


/**
 * Class WalletController
 * @package App\Http\Controllers
 */
class WalletController extends Controller
{
	/**
	 * @var WalletService
	 */
	protected $walletService;
	/**
	 * @var MoneyService
	 */
	protected $moneyService;

	/**
	 * WalletController constructor.
	 * @param WalletService $walletService
	 * @param MoneyService $moneyService
	 */
	public function __construct(WalletService $walletService, MoneyService $moneyService)
	{
		$this->walletService = $walletService;
		$this->moneyService = $moneyService;
	}

	/**
	 * @param int $walletId
	 * @param Request $request
	 * @return array
	 * @throws \Throwable
	 */
	public function refill(int $walletId, Request $request) : array
    {
	    /* @var Wallet $wallet */
	    $wallet = Wallet::query()->findOrFail($walletId);
        $validatedData = $request->validate(
        	[
        		'amount' => 'required|numeric|min:0',
		        'currency' => 'required|string|max:255|in:' . $wallet->money->getCurrency()
	        ]
        );

	    try{
		    $money = $this->moneyService->parseMoney($validatedData['amount'], $wallet->money->getCurrency());
		    $this->walletService->refill($wallet, $money);
	    } catch (\Exception $e) {
		    return [
			    'status' => false,
			    'error' => $e->getMessage(),
			    'wallet' => $wallet->refresh()
		    ];
	    }

        return [
        	'status' => true,
	        'wallet' => $wallet->refresh()
        ];
    }

	/**
	 * @param int $fromWalletId
	 * @param Request $request
	 * @return array
	 * @throws \Exception
	 */
	public function transfer(int $fromWalletId, Request $request) : array
    {
	    $request->validate([
		    'currency' => 'required|string|max:255|in:' . implode(',', Config::get('app.currencies')),
		    'to_wallet_id' => 'required|int|not_in:' . $fromWalletId,
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
	    	['currency' => 'required|string|max:255|in:' . implode(',', $currencies)],
		    ['currency.in' => 'Provided currency is not supported by this wallets. Choose one of ' . implode(',', $currencies)]
	    );

	    try {
		    $money = $this->moneyService->parseMoney($request->get('amount'), $request->get('currency'));
		    $this->walletService->transfer($fromWallet, $toWallet, $money);
	    } catch (\Exception $e) {
		    return [
			    'status' => false,
			    'error' => $e->getMessage(),
			    'fromWallet' => $fromWallet->refresh(),
			    'toWallet' => $toWallet->refresh()
		    ];
	    }

	    return [
	    	'status' => true,
		    'fromWallet' => $fromWallet->refresh(),
		    'toWallet' => $toWallet->refresh()
	    ];
    }
}
