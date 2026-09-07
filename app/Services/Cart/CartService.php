<?php

namespace App\Services\Cart;

use App\Enums\PartStatus;
use App\Models\Part;
use App\Services\Inventory\StockChecker;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Session-based cart shared by guests and logged-in users alike (it's not
 * tied to a user record at all). Only {part_id => quantity} is stored in
 * the session — price and availability are always resolved fresh from the
 * database, never cached here, so the cart can't go stale or be tampered
 * with client-side.
 *
 * Adding or updating a line never touches Part::stock_quantity — see
 * App\Services\Inventory\StockChecker for why, and where real reservation
 * will connect once payments exist.
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly Session $session,
        private readonly StockChecker $stock,
    ) {}

    /**
     * @throws CartException if the quantity is invalid or the product
     *                       isn't available for that quantity
     */
    public function add(Part $part, int $quantity): void
    {
        $this->assertValidQuantity($quantity);

        $items = $this->raw();
        $newQuantity = ($items[$part->id] ?? 0) + $quantity;

        $this->assertAvailable($part, $newQuantity);

        $items[$part->id] = $newQuantity;
        $this->save($items);
    }

    /**
     * @throws CartException
     */
    public function update(int $partId, int $quantity): void
    {
        $this->assertValidQuantity($quantity);

        $part = Part::find($partId);
        $this->assertAvailable($part, $quantity);

        $items = $this->raw();
        $items[$partId] = $quantity;
        $this->save($items);
    }

    public function remove(int $partId): void
    {
        $items = $this->raw();
        unset($items[$partId]);
        $this->save($items);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function items(): Collection
    {
        $raw = $this->raw();

        if ($raw === []) {
            return collect();
        }

        $parts = Part::whereIn('id', array_keys($raw))->get()->keyBy('id');

        return collect($raw)
            ->map(fn (int $quantity, int $partId) => CartItem::fromPart($parts->get($partId), $quantity))
            ->values();
    }

    /**
     * Total quantity across all lines (for a header badge), including
     * lines that currently need attention.
     */
    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /**
     * Subtotal across only the lines that are actually purchasable right
     * now. Lines needing attention (unavailable, or over current stock)
     * are excluded until the shopper resolves them.
     */
    public function subtotalCents(): int
    {
        return $this->items()
            ->reject(fn (CartItem $item) => $item->needsAttention())
            ->sum('lineTotalCents');
    }

    public function hasItemsNeedingAttention(): bool
    {
        return $this->items()->contains(fn (CartItem $item) => $item->needsAttention());
    }

    /**
     * @throws CartException
     */
    private function assertValidQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new CartException('Quantity must be a positive number.');
        }
    }

    /**
     * @throws CartException
     */
    private function assertAvailable(?Part $part, int $requestedQuantity): void
    {
        if ($part === null || $part->status !== PartStatus::Active) {
            throw new CartException('That product is no longer available.');
        }

        if ($part->stock_quantity < 1) {
            throw new CartException("{$part->name} is currently out of stock.");
        }

        if (! $this->stock->isAvailable($part, $requestedQuantity)) {
            throw new CartException("Only {$part->stock_quantity} of \"{$part->name}\" available.");
        }
    }

    /**
     * @return array<int, int>
     */
    private function raw(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    /**
     * @param  array<int, int>  $items
     */
    private function save(array $items): void
    {
        $this->session->put(self::SESSION_KEY, $items);
    }
}
