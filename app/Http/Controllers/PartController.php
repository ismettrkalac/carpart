<?php

namespace App\Http\Controllers;

use App\Enums\PartStatus;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Part;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PartController extends Controller
{
    /**
     * Browse the catalog, optionally scoped to a category (see
     * CategoryController), filtered by manufacturer/search, and sorted.
     */
    public function index(Request $request, ?Category $category = null): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'exists:manufacturers,slug'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,name'],
        ]);

        $manufacturer = isset($validated['manufacturer'])
            ? Manufacturer::where('slug', $validated['manufacturer'])->first()
            : null;

        $searchTerm = isset($validated['q']) && trim($validated['q']) !== '' ? $validated['q'] : null;

        if ($searchTerm !== null) {
            // Relevance-ranked full-text search (Laravel Scout, see
            // config/scout.php) — a distinct query path from plain
            // browsing below, since Scout's Builder talks to the search
            // engine rather than the database directly. shouldBeSearchable()
            // on Part already keeps non-Active parts out of the index; the
            // explicit status filter here is belt-and-suspenders (and what
            // makes the `database` test-driver — which has no separate
            // index to have excluded them from — behave the same way).
            $parts = Part::search($searchTerm)
                ->where('status', PartStatus::Active->value)
                ->when($category, fn ($search) => $search->where('category_id', $category->id))
                ->when($manufacturer, fn ($search) => $search->where('manufacturer_id', $manufacturer->id))
                ->when(
                    $validated['sort'] ?? null,
                    fn ($search, string $sort) => match ($sort) {
                        'price_asc' => $search->orderBy('base_price_cents', 'asc'),
                        'price_desc' => $search->orderBy('base_price_cents', 'desc'),
                        'name' => $search->orderBy('name', 'asc'),
                        // No explicit sort chosen: leave the engine's own
                        // relevance ranking in place rather than forcing
                        // "newest first" like the plain-browse default.
                        default => $search,
                    },
                )
                ->query(fn ($query) => $query->with(['manufacturer', 'category', 'images']))
                ->paginate(12)
                ->withQueryString();
        } else {
            $parts = Part::published()
                ->with(['manufacturer', 'category', 'images'])
                ->when($category, fn ($query) => $query->where('category_id', $category->id))
                ->when($manufacturer, fn ($query) => $query->where('manufacturer_id', $manufacturer->id))
                ->when(
                    $validated['sort'] ?? null,
                    fn ($query, string $sort) => match ($sort) {
                        'price_asc' => $query->orderBy('base_price_cents'),
                        'price_desc' => $query->orderByDesc('base_price_cents'),
                        'name' => $query->orderBy('name'),
                        default => $query->latest(),
                    },
                    fn ($query) => $query->latest(),
                )
                ->paginate(12)
                ->withQueryString();
        }

        return view('parts.index', [
            'parts' => $parts,
            'category' => $category,
            'manufacturers' => Manufacturer::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'activeManufacturer' => $manufacturer,
            'query' => $validated['q'] ?? '',
            'sort' => $validated['sort'] ?? 'newest',
        ]);
    }

    public function show(Part $part): View
    {
        abort_unless($part->status === PartStatus::Active, 404);

        $part->load(['manufacturer', 'category', 'fitments', 'images']);

        $related = Part::published()
            ->with(['manufacturer', 'images'])
            ->where('category_id', $part->category_id)
            ->where('id', '!=', $part->id)
            ->take(4)
            ->get();

        return view('parts.show', [
            'part' => $part,
            'related' => $related,
        ]);
    }
}
