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
    'namespace' => 'Warlof\Seat\Slackbot\Http\Controllers',
    'prefix' => 'slackbot'
], function(){

    Route::group([
        'middleware' => ['web', 'auth', 'locale'],
    ], function() {

        // Endpoints with Configuration Permission
        Route::group([
            'middleware' => 'bouncer:slackbot.setup'
        ], function(){

            Route::get('/configuration', [
                'as' => 'slackbot.configuration',
                'uses' => 'SlackbotSettingsController@getConfiguration'
            ]);

            Route::get('/run/{commandName}', [
                'as' => 'slackbot.command.run',
                'uses' => 'SlackbotSettingsController@getSubmitJob'
            ]);

            // OAuth
            Route::group([
                'namespace' => 'Services',
                'prefix' => 'oauth'
            ], function(){

                Route::get('/callback', [
                    'as' => 'slack.oauth.callback',
                    'uses' => 'OAuthController@callback'
                ]);

                Route::post('/configuration', [
                    'as' => 'slack.oauth.configuration.post',
                    'uses' => 'OAuthController@postConfiguration'
                ]);

            });

        });

        Route::group([
            'middleware' => 'bouncer:slackbot.create'
        ], function(){

            Route::get('/public/{channel_id}/remove', [
                'as' => 'slackbot.public.remove',
                'uses' => 'SlackbotJsonController@getRemovePublic',
            ]);

            Route::get('/users/{group_id}/{channel_id}/remove', [
                'as' => 'slackbot.user.remove',
                'uses' => 'SlackbotJsonController@getRemoveUser',
            ]);

            Route::get('/roles/{role_id}/{channel_id}/remove', [
                'as' => 'slackbot.role.remove',
                'uses' => 'SlackbotJsonController@getRemoveRole',
            ]);

            Route::get('/corporations/{corporation_id}/{channel_id}/remove', [
                'as' => 'slackbot.corporation.remove',
                'uses' => 'SlackbotJsonController@getRemoveCorporation',
            ]);

            Route::get('/corporation/{corporation_id}/{title_id}/{channel_id}/remove', [
                'as' => 'slackbot.title.remove',
                'uses' => 'SlackbotJsonController@getRemoveTitle',
            ]);

            Route::get('/alliances/{alliance_id}/{channel_id}/remove', [
                'as' => 'slackbot.alliance.remove',
                'uses' => 'SlackbotJsonController@getRemoveAlliance',
            ]);

            Route::post('/', [
                'as' => 'slackbot.add',
                'uses' => 'SlackbotJsonController@postRelation',
            ]);

        });

        Route::get('/', [
            'as' => 'slackbot.list',
            'uses' => 'SlackbotJsonController@getRelations',
            'middleware' => 'bouncer:slackbot.view'
        ]);

        Route::get('/logs', [
            'as' => 'slackbot.logs',
            'uses' => 'SlackbotLogsController@getLogs',
            'middleware' => 'bouncer:slackbot.security'
        ]);

        Route::get('/users', [
            'as' => 'slackbot.users',
            'uses' => 'SlackbotController@getUsers',
            'middleware' => 'bouncer:slackbot.view'
        ]);

        Route::group([
            'prefix' => 'json'
        ], function(){

            Route::get('/logs', [
                'as' => 'slackbot.json.logs',
                'uses' => 'SlackbotLogsController@getJsonLogData',
                'middleware' => 'bouncer:slackbot.security'
            ]);

            Route::get('/users', [
                'as' => 'slackbot.json.users',
                'uses' => 'SlackbotController@getUsersData',
                'middleware' => 'bouncer:slackbot.view'
            ]);

            Route::post('/user/remove', [
                'as' => 'slackbot.json.user.remove',
                'uses' => 'SlackbotController@postRemoveUserMapping',
                'middleware' => 'bouncer:slackbot.security'
            ]);

            Route::get('/users/channels', [
                'as' => 'slackbot.json.user.channels',
                'uses' => 'SlackbotJsonController@getJsonUserChannelsData',
                'middleware' => 'bouncer:slackbot.security'
            ]);

            Route::get('/titles', [
                'as' => 'slackbot.json.titles',
                'uses' => 'SlackbotJsonController@getJsonTitle',
                'middleware' => 'bouncer:slackbot.create'
            ]);

        });

    });

    Route::group([
        'prefix' => 'event',
        'namespace' => 'Services'
    ], function(){

        Route::post('/callback', [
            'as' => 'slack.event.callback',
            'uses' => 'EventController@callback'
        ]);

    });

});
