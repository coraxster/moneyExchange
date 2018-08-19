<?php

namespace App\Models;

use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Money\Money;

/**
 * Class Wallet
 * @package App\Models
 * @property Money $money
 */
class Wallet extends Model
{
	protected $appends = [
		'amount'
	];

	protected $visible = [
		'id',
		'amount',
		'currency'
	];

    public static function rules() : array
    {
        return [
            'currency' => 'required|string|max:255|in:' . implode(',', Config::get('app.currencies')),
            'amount' => 'numeric'
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawHistory()
    {
        return $this->hasMany(WalletOperation::class, 'from_wallet_id');
    }

	public function depositHistory()
	{
		return $this->hasMany(WalletOperation::class, 'to_wallet_id');
	}



    public function getMoneyAttribute()
    {
	    /* @var MoneyService $moneyService */
    	$moneyService = resolve(MoneyService::class);
	    return $moneyService->amountToMoney($this->raw_amount, $this->currency);
    }

    public function setMoneyAttribute(Money $money)
    {
	    $this->raw_amount = $money->getAmount();
	    $this->currency = $money->getCurrency();
    }

    public function getAmountAttribute()
    {
	    /* @var MoneyService $moneyService */
	    $moneyService = resolve(MoneyService::class);
	    return $moneyService->formatMoney($this->money);
    }
}
