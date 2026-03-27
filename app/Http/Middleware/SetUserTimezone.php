<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SetUserTimezone
{
    
    public function handle(Request $request, Closure $next): mixed
    {

        $timezone = $this->getUserTimezone($request);
        
        if ($timezone) {
            Session::put('user_timezone', $timezone);
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
        
        return $next($request);
    }

    private function getUserTimezone(Request $request): ?string
    {

        if (Session::has('user_timezone')) {
            return Session::get('user_timezone');
        }

        if (auth()->check() && auth()->user()->timezone) {
            return auth()->user()->timezone;
        }

        $timezoneHeader = $request->header('X-Timezone');
        if ($timezoneHeader && in_array($timezoneHeader, timezone_identifiers_list())) {
            Session::put('user_timezone', $timezoneHeader);
            return $timezoneHeader;
        }

        return 'Asia/Karachi';
    }
}