<?php

namespace App\Models;

use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WalletOperation extends Model
{
    public const OP_CODES = [
    	'REFILL' => 1,
	    'TRANSFER' => 2
    ];

	protected $fillable = [
    	'operation_code',
	    'from_wallet_id',
	    'to_wallet_id',
	    'withdraw',
	    'deposit'
    ];

	protected $appends = [
		'operation',
    	'withdraw',
	    'deposit'
    ];

    protected $visible = [
    	'operation',
    	'withdraw',
	    'deposit'
    ];

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function scopeRefills(Builder $query) : Builder
    {
        return $query->where(['operation_code' => self::OP_CODES['REFILL']]);
    }

    public function scopeTransfers(Builder $query) : Builder
    {
        return $query->where(['operation_code' => self::OP_CODES['TRANSFER']]);
    }



    public function getOperationAttribute()
    {
    	$operations = [
    		self::OP_CODES['REFILL'] => 'Refill',
    		self::OP_CODES['TRANSFER'] => 'Transfer'
	    ];
    	return $operations[$this->operation_code];
    }

	public function getWithdrawMoneyAttribute()
	{
		/* @var MoneyService $moneyService */
		$moneyService = resolve(MoneyService::class);
		return $moneyService->amountToMoney($this->raw_withdraw, $this->fromWallet->currency);
	}

	public function getDepositMoneyAttribute()
	{
		/* @var MoneyService $moneyService */
		$moneyService = resolve(MoneyService::class);
		return $moneyService->amountToMoney($this->raw_deposit, $this->toWallet->currency);
	}

	public function getWithdrawAttribute()
	{
		/* @var MoneyService $moneyService */
		$moneyService = resolve(MoneyService::class);
		return $moneyService->formatMoney($this->withdraw_money);
	}

	public function getDepositAttribute()
	{
		/* @var MoneyService $moneyService */
		$moneyService = resolve(MoneyService::class);
		return $moneyService->formatMoney($this->deposit_money);
	}
}
