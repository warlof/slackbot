<?php

namespace Warlof\Seat\Slackbot;

use Illuminate\Support\ServiceProvider;
use Warlof\Seat\Slackbot\Commands\SlackDaemon;
use Warlof\Seat\Slackbot\Commands\SlackLogsClear;
use Warlof\Seat\Slackbot\Commands\SlackUpdate;
use Warlof\Seat\Slackbot\Commands\SlackChannelsUpdate;
use Warlof\Seat\Slackbot\Commands\SlackUsersUpdate;
use Warlof\Seat\Slackbot\Helpers\SlackApi;

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
        $this->registerServices();
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
            SlackUpdate::class,
            SlackChannelsUpdate::class,
            SlackUsersUpdate::class,
            SlackLogsClear::class,
            SlackDaemon::class
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

    private function registerServices()
    {
        $slackToken = setting('slack_token', true);

        // Ensure slack has been set
        if ($slackToken == null) {
            return;
        }

        // Load the Slack Api on boot time
        $this->app->singleton('warlof.slackbot.slack', function() use ($slackToken) {
            return new SlackApi($slackToken);
        });
    }
}
