<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Part;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = Category::withCount(['parts' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get();

        $featuredParts = Part::published()
            ->with(['manufacturer', 'category', 'images'])
            ->where('stock_quantity', '>', 0)
            ->latest()
            ->take(8)
            ->get();

        return view('home', [
            'categories' => $categories,
            'featuredParts' => $featuredParts,
        ]);
    }
}
