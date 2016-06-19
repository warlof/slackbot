<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Slackbot\Http\Controllers;


use Seat\Slackbot\Models\SlackChannelsAlliances;
use Seat\Slackbot\Models\SlackChannelsCorporations;
use Seat\Slackbot\Models\SlackChannelsRoles;
use Seat\Slackbot\Models\SlackChannelsUsers;

class SlackbotAdminController
{

    public function list()
    {
        $channels_users = SlackChannelsUsers::all();
        $channels_roles = SlackChannelsRoles::all();
        $channels_corporations = SlackChannelsCorporations::all();
        $channels_alliances = SlackChannelsAlliances::all();
        
        return view('slackbot::list',
            compact('channels_users', 'channels_roles', 'channels_corporations', 'channels_alliances'));
    }

    public function postRelation()
    {
        return redirect()->back()
            ->with('success', 'New slack relation has been created');
    }

}
