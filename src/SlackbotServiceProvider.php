<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot <loic.leuilliot@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Slackbot;

use Illuminate\Support\ServiceProvider;
use Warlof\Seat\Slackbot\Commands\SlackConversationSync;
use Warlof\Seat\Slackbot\Commands\SlackLogsClear;
use Warlof\Seat\Slackbot\Commands\SlackUserPolicy;
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
        	SlackLogsClear::class,
            SlackUserSync::class,
	        SlackUserPolicy::class,
	        SlackConversationSync::class,
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
            __DIR__ . '/database/migrations/' => database_path('migrations'),
	        __DIR__ . '/resources/assets/css/' => public_path('web/css'),
        ]);
    }
}
