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
        'as' => 'slackbot.users.remove',
        'uses' => 'SlackbotController@getRemoveUser'
    ]);

    Route::get('/roles/{role_id}/{channel_id}/remove', [
        'as' => 'slackbot.role.remove',
        'uses' => 'SlackbotController@getRemoveRole'
    ]);

    Route::get('/corporations/{corporation_id}/{channel_id}/remove', [
        'as' => 'slackbot.corporations.remove',
        'uses' => 'SlackbotController@getRemoveCorporation'
    ]);

    Route::get('/alliances/{alliance_id}/{channel_id}/remove', [
        'as' => 'slackbot.alliances.remove',
        'uses' => 'SlackbotController@getRemoveAlliance'
    ]);

    Route::post('/', [
        'as' => 'slackbot.add',
        'uses' => 'SlackbotController@postRelation'
    ]);
});
