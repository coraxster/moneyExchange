<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletOperation;
use App\Services\MoneyService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function getHtml(int $walletId, Request $request, MoneyService $moneyService)
    {
    	$wallet = Wallet::query()->findOrFail($walletId);
    	$query = WalletOperation::query();
    	if ($request->filled('from-date')) {
		    $query->where('created_at', '>=', $request->get('from-date'));
	    }
	    if ($request->filled('to-date')) {
		    $query->where('created_at', '<=', $request->get('to-date'));
	    }

	    $withdrawSumRaw = (clone $query)->where('from_wallet_id', $wallet->id)->sum('raw_withdraw');
	    $depositSumRaw = (clone $query)->where('to_wallet_id', $wallet->id)->sum('raw_deposit');
	    $withdrawSumMoney = $moneyService->amountToMoney($withdrawSumRaw, $wallet->currency);
	    $depositSumMoney = $moneyService->amountToMoney($depositSumRaw, $wallet->currency);
	    $withdrawSum = $moneyService->formatMoney($withdrawSumMoney);
	    $depositSum = $moneyService->formatMoney($depositSumMoney);

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
			    'withdrawSum' => $withdrawSum,
			    'depositSum' => $depositSum,
			    'wallet' => $wallet
		    ]
	    );
    }

	//todo: csv export

}
