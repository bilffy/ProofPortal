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
use App\Helpers\AppSettingsHelper;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Services\Storage\StorageServiceInterface;
use App\Services\Storage\StorageFactory;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use App\Auth\ImpersonateSessionGuard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the storage implementation
        $this->app->singleton(StorageServiceInterface::class, function ($app) {
            return StorageFactory::make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $versionFile = base_path('VERSION');
        $version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : (@exec('git describe --tags --abbrev=0 2>/dev/null') ?: 'dev');
        config(['app.version' => $version]);
        
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
        view()->share('AppSettingsHelper', new AppSettingsHelper());

        // Allow letters (incl. accents), numbers, spaces, hyphens, apostrophes, and periods
        Validator::extend('no_special_chars', function ($attribute, $value, $parameters, $validator) {
            return preg_match("/^[\\p{L}\\p{N}\\s\\-'‘’\\.]+$/u", (string) $value) === 1;
        });

        Validator::replacer('no_special_chars', function ($message, $attribute, $rule, $parameters) {
            return "The $attribute may only contain letters, numbers, spaces, hyphens, apostrophes, and periods.";
        });
        
        JsonResource::withoutWrapping();

        $this->registerImpersonateSessionGuard();
    }

    /**
     * Override lab404's session guard so impersonation does not rotate the session ID.
     */
    protected function registerImpersonateSessionGuard(): void
    {
        $this->app->make(AuthManager::class)->extend('session', function (Application $app, string $name, array $config) {
            $auth = $app->make(AuthManager::class);
            $provider = $auth->createUserProvider($config['provider']);

            $guard = new ImpersonateSessionGuard($name, $provider, $app['session.store']);

            if (method_exists($guard, 'setCookieJar')) {
                $guard->setCookieJar($app['cookie']);
            }

            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($app['events']);
            }

            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            }

            return $guard;
        });
    }
}
