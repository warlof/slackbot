<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018, 2019  Loïc Leuilliot <loic.leuilliot@gmail.com>
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

namespace Warlof\Seat\Connector\Drivers\Slack;

use Seat\Services\AbstractSeatPlugin;
use Seat\Services\Exceptions\SettingException;
use SocialiteProviders\Slack\Provider;

/**
 * Class SlackConnectorServiceProvider.
 *
 * @package Warlof\Seat\Connector\Drivers\Slack
 */
class SlackConnectorServiceProvider extends AbstractSeatPlugin
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addRoutes();
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
            __DIR__ . '/Config/seat-connector.config.php', 'seat-connector.drivers.slack');

        $slack = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $slack->extend('slack', function() use ($slack) {
            $config = [
                'client_id'     => '',
                'client_secret' => '',
                'redirect'      => '',
            ];

            try {
                $settings = setting('seat-connector.drivers.slack', true);

                if (! is_null($settings)) {
                    $config['client_id']     = $settings->client_id;
                    $config['client_secret'] = $settings->client_secret;
                    $config['redirect']      = route('seat-connector.drivers.slack.callback');
                }
            } catch (SettingException $e) { }

            return $slack->buildProvider(Provider::class, $config);
        });
    }

    private function addRoutes()
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    private function addTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'seat-connector-slack');
    }

    /**
     * Return the plugin public name as it should be displayed into settings.
     *
     * @example SeAT Web
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Slackbot Connector';
    }

    /**
     * Return the plugin repository address.
     *
     * @example https://github.com/eveseat/web
     *
     * @return string
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/warlof/slackbot';
    }

    /**
     * Return the plugin technical name as published on package manager.
     *
     * @example web
     *
     * @return string
     */
    public function getPackagistPackageName(): string
    {
        return 'slackbot';
    }

    /**
     * Return the plugin vendor tag as published on package manager.
     *
     * @example eveseat
     *
     * @return string
     */
    public function getPackagistVendorName(): string
    {
        return 'warlof';
    }

    /**
     * Return the plugin installed version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return config('slackbot.config.version');
    }
}
