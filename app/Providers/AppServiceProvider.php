<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layout', function (\Illuminate\View\View $view): void {
            $view->with('cartCount', app(CartService::class)->count());
        });
    }
}
