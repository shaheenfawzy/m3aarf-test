<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\YoutubeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(YoutubeService::class, fn (): YoutubeService => new YoutubeService(
            apiKey: (string) config('services.youtube.key'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
    }
}
