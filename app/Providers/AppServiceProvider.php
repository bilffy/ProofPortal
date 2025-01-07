<?php

namespace App\Providers;

use App\Helpers\PermissionHelper;
use App\Helpers\PhotographyHelper;
use App\Helpers\RoleHelper;
use App\Helpers\SchoolContextHelper;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use App\Helpers\UserStatusHelper;
use App\Helpers\AvatarHelper;
use App\Helpers\UiSettingHelper;
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
        view()->share('PermissionHelper', new PermissionHelper());
        view()->share('RoleHelper', new RoleHelper());
        view()->share('AvatarHelper', new AvatarHelper());
        view()->share('UiSettingHelper', new UiSettingHelper());
        view()->share('SchoolContextHelper', new SchoolContextHelper());
        view()->share('PhotographyHelper', new PhotographyHelper());
        JsonResource::withoutWrapping();
    }
}
