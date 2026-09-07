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

        $parts = Part::published()
            ->with(['manufacturer', 'category'])
            ->search($validated['q'] ?? null)
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

        $part->load(['manufacturer', 'category', 'fitments']);

        $related = Part::published()
            ->with('manufacturer')
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
