<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use App\Socialite\TwitterOAuth2Provider;

class TwitterOAuth2ServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->extend(SocialiteFactory::class, function ($socialite) {
            $socialite->extend('twitter-oauth2', function ($app) use ($socialite) {
                $config = $app['config']['services.twitter-oauth2'];
                return $socialite->buildProvider(TwitterOAuth2Provider::class, $config);
            });

            return $socialite;
        });
    }
}
