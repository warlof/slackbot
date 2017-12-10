<?php

namespace Warlof\Seat\Slackbot;

use Illuminate\Support\ServiceProvider;
use Warlof\Seat\Slackbot\Commands\SlackUserInvite;
use Warlof\Seat\Slackbot\Commands\SlackUserKick;
use Warlof\Seat\Slackbot\Commands\SlackUserSync;

class SlackbotServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addCommands();
        $this->addRoutes();
        $this->addViews();
        $this->addPublications();
        $this->addTranslations();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/slackbot.config.php', 'slackbot.config');

        $this->mergeConfigFrom(
            __DIR__ . '/Config/slackbot.permissions.php', 'web.permissions');
        
        $this->mergeConfigFrom(
            __DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
    }

    private function addCommands()
    {
        $this->commands([
            SlackUserSync::class,
	        SlackUserInvite::class,
	        SlackUserKick::class,
        ]);
    }
    
    private function addTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'slackbot');
    }
    
    private function addRoutes()
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }
    
    private function addViews()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'slackbot');
    }
    
    private function addPublications()
    {
        $this->publishes([
            __DIR__ . '/database/migrations/' => database_path('migrations')
        ]);
    }
}
