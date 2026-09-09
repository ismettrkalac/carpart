<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * The account landing page — the customer's own info plus a category
     * for each account area (addresses, orders), each linking through to
     * its own full page (SavedAddressController / AccountOrderController)
     * rather than listing items inline here.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.profile', [
            'user' => $user,
            'addressCount' => $user->savedAddresses()->count(),
            'orderCount' => $user->orders()->count(),
        ]);
    }
}
