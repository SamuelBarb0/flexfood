<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Restaurante;

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
        View::composer('*', function ($view) {
            // 1) Si viene por la ruta /r/{restaurante...}, úsalo
            $routeRest = request()->route('restaurante');
            if ($routeRest instanceof Restaurante) {
                $view->with('restaurante', $routeRest);
                return;
            }

            // 2) Si el usuario tiene restaurante_id úsalo; si no, 1
            $id = auth()->check() && auth()->user()->restaurante_id
                ? auth()->user()->restaurante_id
                : 1;

            // 3) Instancia y compártelo (si no existe, no rompas)
            $rest = Restaurante::find($id);
            $view->with('restaurante', $rest);
        });
    }
}
