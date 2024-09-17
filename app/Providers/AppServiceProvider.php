<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use App\Helpers\UserStatusHelper;
use App\Models\User;

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
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        
        view()->share('User', User::class);
        view()->share('UserStatusHelper', new UserStatusHelper());
        JsonResource::withoutWrapping();
    }
}
