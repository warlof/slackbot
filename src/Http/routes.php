<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:45
 */

Route::group([
    'namespace' => 'Seat\Slackbot\Http\Controllers',
    'middleware' => 'bouncer:superuser',
    'prefix' => 'slackbot'
], function(){
    Route::get('/', [
        'as' => 'slackbot.list',
        'uses' => 'SlackbotController@getRelations']);

    Route::get('/users/{user_id}/{channel_id}/remove', [
        'as' => 'slackbot.user.remove',
        'uses' => 'SlackbotController@getRemoveUser'
    ]);

    Route::get('/roles/{role_id}/{channel_id}/remove', [
        'as' => 'slackbot.role.remove',
        'uses' => 'SlackbotController@getRemoveRole'
    ]);

    Route::get('/corporations/{corporation_id}/{channel_id}/remove', [
        'as' => 'slackbot.corporation.remove',
        'uses' => 'SlackbotController@getRemoveCorporation'
    ]);

    Route::get('/alliances/{alliance_id}/{channel_id}/remove', [
        'as' => 'slackbot.alliance.remove',
        'uses' => 'SlackbotController@getRemoveAlliance'
    ]);

    Route::post('/', [
        'as' => 'slackbot.add',
        'uses' => 'SlackbotController@postRelation'
    ]);

    Route::get('/configuration', [
        'as' => 'slackbot.configuration',
        'uses' => 'SlackbotController@getConfiguration'
    ]);

    Route::post('/configuration', [
        'as' => 'slackbot.configuration.post',
        'uses' => 'SlackbotController@postConfiguration'
    ]);
});
