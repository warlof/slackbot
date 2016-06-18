<?php

namespace Seat\Slackbot;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Seat\Slackbot\Commands\Corp\SlackInvite;
use Seat\Slackbot\Commands\Corp\SlackKick;

class SlackbotServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->add_commands();
        $this->add_routes();
        $this->add_views();
        $this->add_publications();
        $this->add_translations();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/package.sidebar.php', 'slackbot.config');

        $this->mergeConfigFrom(
            __DIR__ . '/Config/slackbot.permissions.php', 'web.permissions');
    }

    public function add_commands()
    {
        $this->commands([
            SlackInvite::class,
            SlackKick::class
        ]);
    }
    
    public function add_translations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'slackbot');
    }
    
    public function add_routes()
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }
    
    public function add_views()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'slackbot');
    }
    
    public function add_publications()
    {
        $this->publishes([
            __DIR__ . '/database/migrations/' => database_path('migrations')
        ]);
    }
}
