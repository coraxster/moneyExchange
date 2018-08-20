<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletOperation;
use App\Services\MoneyService;
use Illuminate\Http\Request;
use Money\Currency;

/**
 * Class ReportController
 * @package App\Http\Controllers
 */
class ReportController extends Controller
{
	/**
	 * @var MoneyService
	 */
	protected $moneyService;

	public function __construct(MoneyService $moneyService)
	{
		$this->moneyService = $moneyService;
	}

	/**
	 * @param int $walletId
	 * @param Request $request
	 * @param MoneyService $moneyService
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 * @throws \Exception
	 */
	public function getHtml(int $walletId, Request $request)
    {
    	$wallet = Wallet::query()->findOrFail($walletId);
    	$query = WalletOperation::query();
    	if ($request->filled('from-date')) {
		    $query->where('created_at', '>=', $request->get('from-date'));
	    }
	    if ($request->filled('to-date')) {
		    $query->where('created_at', '<=', $request->get('to-date'));
	    }

	    $overall = $this->getOverall($query, $wallet);

	    $query->where(function($query) use ($wallet) {
			    $query->where('from_wallet_id', $wallet->id)
				    ->orWhere('to_wallet_id', $wallet->id);
		         })
		    ->orderBy('id', 'desc')
		    ->with(['fromWallet.user', 'toWallet.user']);

    	return view(
    		'report',
		    [
		    	'ops' => $query->paginate(),
			    'wallet' => $wallet,
			    'overall' => $overall
		    ]
	    );
    }

	/**
	 * @param $query
	 * @param $wallet
	 * @return array
	 * @throws \Exception
	 */
	protected function getOverall($query, $wallet): array
	{
		$withdrawSumRaw = (clone $query)->where('from_wallet_id', $wallet->id)->sum('raw_withdraw');
		$depositSumRaw = (clone $query)->where('to_wallet_id', $wallet->id)->sum('raw_deposit');
		$withdrawSumMoney = $this->moneyService->amountToMoney($withdrawSumRaw, $wallet->currency);
		$depositSumMoney = $this->moneyService->amountToMoney($depositSumRaw, $wallet->currency);
		$withdrawSum = $this->moneyService->formatMoney($withdrawSumMoney);
		$depositSum = $this->moneyService->formatMoney($depositSumMoney);

		$USDCurrency = new Currency('USD');
		$withdrawSumUSD = $this->moneyService->formatMoney(
			$this->moneyService->convert($withdrawSumMoney, $USDCurrency)
		);
		$depositSumUSD = $this->moneyService->formatMoney(
			$this->moneyService->convert($depositSumMoney, $USDCurrency)
		);
		return [
			$wallet->currency => [
				'withdraw' => $withdrawSum,
				'deposit' => $depositSum
			],
			'USD' => [
				'withdraw' => $withdrawSumUSD,
				'deposit' => $depositSumUSD
			]
		];
	}

	//todo: csv export

}
