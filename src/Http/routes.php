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

Route::group([
    'namespace'  => 'Warlof\Seat\Connector\Drivers\Slack\Http\Controllers',
    'prefix'     => 'seat-connector',
    'middleware' => ['web', 'auth', 'locale'],
], function () {

    Route::group([
        'prefix' => 'registration',
    ], function () {

        Route::get('/slack', [
            'as'   => 'seat-connector.drivers.slack.registration',
            'uses' => 'RegistrationController@redirectToProvider',
        ]);

        Route::get('/slack/callback', [
            'as'   => 'seat-connector.drivers.slack.registration.callback',
            'uses' => 'RegistrationController@handleProviderCallback',
        ]);

    });

    Route::group([
        'prefix' => 'settings',
        'middleware' => 'bouncer:superuser',
    ], function () {

        Route::post('/slack', [
            'as' => 'seat-connector.drivers.slack.setup',
            'uses' => 'SettingsController@store',
        ]);

    });

});
