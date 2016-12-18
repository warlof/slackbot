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
                'uses' => 'SlackbotController@getConfiguration'
            ]);

            Route::get('/run/{commandName}', [
                'as' => 'slackbot.command.run',
                'uses' => 'SlackbotController@getSubmitJob'
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
                'uses' => 'SlackbotController@getRemovePublic',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/users/{user_id}/{channel_id}/remove', [
                'as' => 'slackbot.user.remove',
                'uses' => 'SlackbotController@getRemoveUser',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/roles/{role_id}/{channel_id}/remove', [
                'as' => 'slackbot.role.remove',
                'uses' => 'SlackbotController@getRemoveRole',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/corporations/{corporation_id}/{channel_id}/remove', [
                'as' => 'slackbot.corporation.remove',
                'uses' => 'SlackbotController@getRemoveCorporation',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::get('/alliances/{alliance_id}/{channel_id}/remove', [
                'as' => 'slackbot.alliance.remove',
                'uses' => 'SlackbotController@getRemoveAlliance',
                'middleware' => 'bouncer:slackbot.create'
            ]);

            Route::post('/', [
                'as' => 'slackbot.add',
                'uses' => 'SlackbotController@postRelation',
                'middleware' => 'bouncer:slackbot.create'
            ]);

        });

        Route::get('/', [
            'as' => 'slackbot.list',
            'uses' => 'SlackbotController@getRelations',
            'middleware' => 'bouncer:slackbot.view'
        ]);

        Route::get('/logs', [
            'as' => 'slackbot.logs',
            'uses' => 'SlackbotController@getLogs',
            'middleware' => 'bouncer:slackbot.security'
        ]);

        Route::get('/json/logs', [
            'as' => 'slackbot.json.logs',
            'uses' => 'SlackbotController@getLogData',
            'middleware' => 'bouncer:slackbot.security'
        ]);

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
