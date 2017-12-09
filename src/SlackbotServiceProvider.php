<?php

namespace Warlof\Seat\Slackbot;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Warlof\Seat\Slackbot\Commands\SlackUserInvite;
use Warlof\Seat\Slackbot\Commands\SlackUserKick;
use Warlof\Seat\Slackbot\Commands\SlackUserSync;
use Warlof\Seat\Slackbot\Repositories\SlackApi;

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

    private function registerServices()
    {
        $slackToken = '';
        if (Schema::hasTable('global_settings')) {
            $slackToken = setting('warlof.slackbot.credentials.access_token', true);
        }

        // Ensure slack has been set
        if ($slackToken == null) {
            return;
        }

        // Load the Slack Api on boot time
        $this->app->singleton(SlackApi::class, function() use ($slackToken) {
            return new SlackApi($slackToken);
        });
    }
}
