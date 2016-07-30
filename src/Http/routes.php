<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:45
 */

Route::group([
    'namespace' => 'Seat\Slackbot\Http\Controllers',
    'prefix' => 'slackbot'
], function(){
    Route::get('/', [
        'as' => 'slackbot.list',
        'uses' => 'SlackbotController@getRelations',
        'middleware' => 'bouncer:slackbot.view'
    ]);

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

    Route::get('/configuration', [
        'as' => 'slackbot.configuration',
        'uses' => 'SlackbotController@getConfiguration',
        'middleware' => 'bouncer:slackbot.setup'
    ]);
    
    Route::get('/logs', [
        'as' => 'slackbot.logs',
        'uses' => 'SlackbotController@getLogs',
        'middleware' => 'bouncer:slackbot.setup'
    ]);

    Route::get('/run/{command_name}', [
        'as' => 'slackbot.command.run',
        'uses' => 'SlackbotController@getSubmitJob',
        'middleware' => 'bouncer:slackbot.setup'
    ]);

    Route::get('/callback', [
        'as' => 'slackbot.callback',
        'uses' => 'SlackbotController@getOAuthToken'
    ]);

    Route::post('/configuration', [
        'as' => 'slackbot.configuration.post',
        'uses' => 'SlackbotController@postConfiguration',
        'middleware' => 'bouncer:slackbot.setup'
    ]);
});
