<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\App;

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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
        /*if (Request::has('lang')) {
            App::setLocale(Request::get('lang')); // Örneğin: ?lang=en veya ?lang=tr
        }*/
        if (Request::hasHeader('Accept-Language')) {
            $locale = Request::getPreferredLanguage(['en', 'tr']); // Desteklenen diller
            App::setLocale($locale);
        } else {
            App::setLocale(config('app.fallback_locale')); // Varsayılan dil
        }
    }
}