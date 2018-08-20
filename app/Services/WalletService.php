<?php
/**
 * Created by PhpStorm.
 * User: dmitrykuzmin
 * Date: 19/08/2018
 * Time: 09:23
 */

namespace App\Services;

use App\Exceptions\ConversionNotAllowed;
use App\Models\Wallet;
use App\Models\WalletOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Money\Money;

/**
 * Class WalletService
 * @package App\Services
 */
class WalletService
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
	 * Refills wallet balance
	 *
	 * @param Wallet $wallet
	 * @param Money $addMoney
	 * @throws ConversionNotAllowed
	 * @throws \Throwable
	 */
	public function refill(Wallet $wallet, Money $addMoney) : void
	{
		if (! $wallet->money->isSameCurrency($addMoney)) {
			throw new ConversionNotAllowed('can\'t convert while refill');
		}

		DB::beginTransaction();

		try {
			/* @var Wallet $wallet */
			$wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
			$wallet->money = $wallet->money->add($addMoney);
			$wallet->save();

			$op = $wallet->depositHistory()->create(
				[
					'operation_code' => WalletOperation::OP_CODES['REFILL'],
					'raw_deposit' => $addMoney->getAmount()
				]
			);

			DB::commit();

		} catch (\Exception $e) {
			DB::rollBack();
			Log::warning('error while refilling', ['wId' => $wallet->id ?? null, 'err' => $e->getMessage()]);
			throw $e;
		}

		Log::info('balance refilled', ['wId' => $wallet->id, 'opId' => $op->id]);
	}


	/**
	 * Transfers money between wallets. Supports auto-conversion with moneyService.
	 *
	 * @param Wallet $fromWallet
	 * @param Wallet $toWallet
	 * @param Money $money
	 * @throws \Exception
	 */
	public function transfer(Wallet $fromWallet, Wallet $toWallet, Money $money) : void
	{
		DB::beginTransaction();

		try {
			/* @var Wallet $fromWallet */
			/* @var Wallet $toWallet */
			$fromWallet = Wallet::query()->whereKey($fromWallet->id)->lockForUpdate()->firstOrFail();
			$toWallet = Wallet::query()->whereKey($toWallet->id)->lockForUpdate()->firstOrFail();
			$fromCurrency = $fromWallet->money->getCurrency();
			$toCurrency = $toWallet->money->getCurrency();
			$mediateCurrency = $this->moneyService->mediateCurrency;

			$withdrawMoney = $this->moneyService->convert($money, $fromCurrency, true, $mediateCurrency);
			$depositMoney = $this->moneyService->convert($money, $toCurrency, false, $mediateCurrency);
			if ($depositMoney->isZero() || $withdrawMoney->isZero()) {
				throw new \Exception('no diff after conversion');
			}

			$fromWallet->money = $fromWallet->money->subtract($withdrawMoney);
			$toWallet->money = $toWallet->money->add($depositMoney);

			$fromWallet->save();
			$toWallet->save();

			$op = $toWallet->depositHistory()->create([
					'operation_code' => WalletOperation::OP_CODES['TRANSFER'],
					'from_wallet_id' => $fromWallet->id,
					'raw_withdraw' => $withdrawMoney->getAmount(),
					'raw_deposit' => $depositMoney->getAmount()
				]);

			DB::commit();

		} catch (\Exception $e) {
			DB::rollBack();
			Log::warning(
				'error while money transferring',
				[
					'err' => $e->getMessage(),
					'fromId' => $fromWallet->id ?? null,
					'toId' => $toWallet->id ?? null
				]
			);
			throw $e;
		}

		Log::info(
			'money transferred',
			[
				'fromId' => $fromWallet->id,
				'toId' => $toWallet->id,
				'opId' => $op->id
			]
		);
	}
}