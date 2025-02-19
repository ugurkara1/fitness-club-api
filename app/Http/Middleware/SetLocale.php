<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        if (Request::has('lang')) {
            App::setLocale(Request::get('lang'));
        }
        return $next($request);
    }
}