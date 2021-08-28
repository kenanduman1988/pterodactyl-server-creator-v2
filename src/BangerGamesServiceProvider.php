<?php

namespace BangerGames\ServerCreator;

use BangerGames\ServerCreator\Commands\PanelSyncCommand;
use BangerGames\ServerCreator\Commands\ServerCreateCommand;
use BangerGames\ServerCreator\Console\Kernel;
use BangerGames\ServerCreator\Models\PanelServer;
use BangerGames\ServerCreator\Observers\PanelServerObserver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class BangerGamesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/Migrations');
            $this->commands([
                ServerCreateCommand::class,
                PanelSyncCommand::class,
            ]);

            // Cron jobs
            $this->app->booted(function () {
                $schedule = app(Schedule::class);
                $schedule->command('bangergames:panel-sync')->everyFiveMinutes();
            });
        }

        PanelServer::observe(PanelServerObserver::class);
    }
}
