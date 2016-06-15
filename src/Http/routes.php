<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:45
 */

Route::group([
    'namespace' => 'Seat\Slackbot\Http\Controllers',
], function(){
    Route::get('/relations', [
        'as' => 'slack-admin.relations',
        'uses' => 'SlackbotAdminController@listRelations']);
    Route::get('/relation', [
        'as' => 'slack-admin.relation.create',
        'uses' => 'SlackbotAdminController@getRelation']);
    Route::post('/relation', [
        'as' => 'slack-admin.relation.create',
        'uses' => 'SlackbotAdminController@postRelation']);
});