<?php

namespace App\Services\Account;

use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates, updates, and deletes a customer's saved addresses, and is the
 * single place the "exactly one default address per customer" rule is
 * enforced — never set SavedAddress::is_default directly.
 */
class SavedAddressService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): SavedAddress
    {
        return DB::transaction(function () use ($user, $data): SavedAddress {
            // A customer's first address has nothing to be a fallback to
            // yet, so it becomes the default regardless of what was
            // submitted — otherwise checkout would have no default to
            // prefill from until they thought to check the box.
            $isDefault = ($data['is_default'] ?? false) || $user->savedAddresses()->doesntExist();

            if ($isDefault) {
                $this->clearDefault($user);
            }

            return $user->savedAddresses()->create([...$data, 'is_default' => $isDefault]);
        });
    }

    /**
     * Same as create(), but skips it if the customer already has an
     * address with these exact fields — used when saving is automatic
     * (checkout's "save this address" checkbox is checked by default),
     * so repeat orders to the same place don't pile up duplicate entries.
     * A deliberate manual save via the account page always goes through
     * create() instead, since a customer adding the same address twice
     * on purpose is their call.
     *
     * @param  array<string, mixed>  $data
     */
    public function createIfNew(User $user, array $data): ?SavedAddress
    {
        $alreadySaved = $user->savedAddresses()
            ->where('name', $data['name'])
            ->where('line1', $data['line1'])
            ->where('line2', $data['line2'] ?? null)
            ->where('city', $data['city'])
            ->where('state', $data['state'])
            ->where('postal_code', $data['postal_code'])
            ->where('country', $data['country'])
            ->exists();

        if ($alreadySaved) {
            return null;
        }

        return $this->create($user, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SavedAddress $address, array $data): SavedAddress
    {
        return DB::transaction(function () use ($address, $data): SavedAddress {
            if ($data['is_default'] ?? false) {
                $this->clearDefault($address->user);
            }

            $address->update($data);

            return $address;
        });
    }

    public function delete(SavedAddress $address): void
    {
        DB::transaction(function () use ($address): void {
            $wasDefault = $address->is_default;
            $user = $address->user;

            $address->delete();

            // Promote the next-oldest remaining address so there's always
            // an obvious default to prefill checkout from, as long as one
            // exists.
            if ($wasDefault) {
                $user->savedAddresses()->oldest()->first()?->update(['is_default' => true]);
            }
        });
    }

    private function clearDefault(User $user): void
    {
        $user->savedAddresses()->where('is_default', true)->update(['is_default' => false]);
    }
}
