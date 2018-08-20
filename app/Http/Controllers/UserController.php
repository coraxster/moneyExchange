<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Money\Currency;
use Money\Money;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends Controller
{
	/**
	 * @param Request $request
	 * @return array
	 */
	public function create(Request $request)
    {
        $validatedData = $request->validate(array_merge(User::rules(), Wallet::rules()));

        DB::beginTransaction();

        try {
	        /* @var User $user */
	        $user = User::query()->create($validatedData);
	        $wallet = $user->wallet()->make();
	        $wallet->money = new Money(0, new Currency($validatedData['currency']));
	        $wallet->save();
	        DB::commit();
	        return [
	        	'status' => true,
		        'user' => $user->load('wallet')
	        ];
        } catch (\Exception $e) {
	        DB::rollBack();
	        Log::warning('error with user creating', ['err' => $e->getMessage()]);
	        return [
		        'status' => false,
		        'errors' => ['Creating failed. :(']
	        ];
        }
    }
}
