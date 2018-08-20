<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletOperation;
use App\Services\MoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Money\Currency;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

	/**
	 * ReportController constructor.
	 * @param MoneyService $moneyService
	 */
	public function __construct(MoneyService $moneyService)
	{
		$this->moneyService = $moneyService;
	}

	/**
	 * @param int $walletId
	 * @param Request $request
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 * @throws \Exception
	 */
	public function getHtml(int $walletId, Request $request)
    {
	    /* @var Wallet $wallet */
    	$wallet = Wallet::query()->findOrFail($walletId);
    	$query = WalletOperation::query();
    	if ($request->filled('from-date')) {
		    $query->where('created_at', '>=', $request->get('from-date'));
	    }
	    if ($request->filled('to-date')) {
		    $query->where('created_at', '<=', $request->get('to-date'));
	    }

	    $overall = $this->getOverall($query, $wallet);

	    $query->withWallet($wallet)
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
	 * @param int $walletId
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 * @throws \Exception
	 */
	public function getCsv(int $walletId, Request $request) : StreamedResponse
    {
	    /* @var Wallet $wallet */
	    $wallet = Wallet::query()->findOrFail($walletId);
	    $query = WalletOperation::query();
	    if ($request->filled('from-date')) {
		    $query->where('created_at', '>=', $request->get('from-date'));
	    }
	    if ($request->filled('to-date')) {
		    $query->where('created_at', '<=', $request->get('to-date'));
	    }

	    $overall = $this->getOverall($query, $wallet);

	    $query->withWallet($wallet)
		    ->orderBy('id', 'desc')
		    ->with(['fromWallet.user', 'toWallet.user']);

	    $closure = $this->makeCsvClosure($query, $wallet, $overall);
	    $headers = $this->getCsvHeaders($wallet);

	    return Response::stream($closure, 200, $headers);
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

	/**
	 * @param $query
	 * @param Wallet $wallet
	 * @param array $overall
	 * @return \Closure
	 */
	protected function makeCsvClosure($query, Wallet $wallet, array $overall): \Closure
	{
		return function () use ($query, $wallet, $overall) {
			$file = fopen('php://output', 'w');
			fputcsv($file, ['opId', 'from', 'amount', 'currency', 'operation', 'to', 'date']);

			$query->orderBy('id', 'desc'); // make sure the ordering is right
			$lastId = null;
			do {
				if ($lastId) {
					$query->where('id', '<', $lastId); // '<' because desc ordering
				}
				$ops = $query->limit(50)->get();
				foreach ($ops as $op) {
					$lastId = $op->id;
					fputcsv($file, [
							$op->id,
							$op->fromWallet->user->name ?? '-',
							($wallet->id === $op->toWallet->id) ? $op->deposit : $op->withdraw,
							($wallet->id === $op->toWallet->id) ? $op->deposit_money->getCurrency() : $op->withdraw_money->getCurrency(),
							$op->operation,
							$op->toWallet->user->name ?? '-',
							$op->created_at
						]
					);
				}
			} while ($ops->count());

			foreach ($overall as $currency_code => $data) {
				fputcsv($file, [
						"overall({$currency_code}): ",
						-$data['withdraw'],
						$data['deposit']
					]
				);
			}
			fclose($file);
		};
	}

	protected function getCsvHeaders(Wallet $wallet) : array
	{
		$today = today()->format('Y-m-d');
		$filename = "{$wallet->user->name} - {$today}.csv";
		return [
			'Content-type' => 'text/csv',
			'Content-Disposition' => "attachment; filename={$filename}",
			'Pragma' => 'no-cache',
			'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
			'Expires' => '0'
		];
	}
}
