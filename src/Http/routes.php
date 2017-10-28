<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:45
 */

Route::group([
    'namespace' => 'Warlof\Seat\Slackbot\Http\Controllers',
    'prefix' => 'slackbot'
], function(){

    Route::group([
        'middleware' => 'web'
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
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/users/{user_id}/{channel_id}/remove', [
                'as' => 'slackbot.user.remove',
                'uses' => 'SlackbotJsonController@getRemoveUser',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/roles/{role_id}/{channel_id}/remove', [
                'as' => 'slackbot.role.remove',
                'uses' => 'SlackbotJsonController@getRemoveRole',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/corporations/{corporation_id}/{channel_id}/remove', [
                'as' => 'slackbot.corporation.remove',
                'uses' => 'SlackbotJsonController@getRemoveCorporation',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/corporation/{corporation_id}/{title_id}/{channel_id}/remove', [
                'as' => 'slackbot.title.remove',
                'uses' => 'SlackbotJsonController@getRemoveTitle',
                'middleware' => 'bouncer:slackbot:create'
            ]);

            Route::get('/alliances/{alliance_id}/{channel_id}/remove', [
                'as' => 'slackbot.alliance.remove',
                'uses' => 'SlackbotJsonController@getRemoveAlliance',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::post('/', [
                'as' => 'slackbot.add',
                'uses' => 'SlackbotJsonController@postRelation',
                'middleware' => 'bouncer:slackbot.create'
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
                'uses' => 'SlackbotLogsController@getLogData',
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
