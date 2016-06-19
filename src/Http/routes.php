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
        'uses' => 'SlackbotController@list']);
});
