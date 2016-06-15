<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Slackbot\Http\Controllers;


use Seat\Slackbot\Models\SlackRelations;

class SlackbotAdminController
{

    public function listRelations()
    {
        $slack_relations = SlackRelations::all();
        
        return view('slackbot::list', compact('slack_relations'));
    }

    public function getRelation()
    {
        return view('slackbot::create');
    }

    public function postRelation()
    {
        return redirect()->back()
            ->with('success', 'New slack relation has been created');
    }

}