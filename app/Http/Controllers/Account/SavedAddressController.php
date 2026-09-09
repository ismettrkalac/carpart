<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\SavedAddressRequest;
use App\Models\SavedAddress;
use App\Services\Account\SavedAddressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedAddressController extends Controller
{
    public function __construct(private readonly SavedAddressService $addresses) {}

    public function index(Request $request): View
    {
        $addresses = $request->user()->savedAddresses()
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return view('account.addresses.index', ['addresses' => $addresses]);
    }

    public function create(): View
    {
        return view('account.addresses.create');
    }

    public function store(SavedAddressRequest $request): RedirectResponse
    {
        $this->addresses->create($request->user(), $request->validated());

        return redirect()->route('account.addresses.index')->with('status', 'Address saved.');
    }

    public function edit(Request $request, SavedAddress $address): View
    {
        $this->authorizeOwner($request, $address);

        return view('account.addresses.edit', ['address' => $address]);
    }

    public function update(SavedAddressRequest $request, SavedAddress $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $this->addresses->update($address, $request->validated());

        return redirect()->route('account.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(Request $request, SavedAddress $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $this->addresses->delete($address);

        return redirect()->route('account.addresses.index')->with('status', 'Address removed.');
    }

    /**
     * Strictly owner-only, same convention as AccountOrderController — a
     * mismatch 404s rather than 403s so it doesn't confirm the address
     * even exists.
     */
    private function authorizeOwner(Request $request, SavedAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 404);
    }
}
