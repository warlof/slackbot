<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Eve\AllianceList;

class SlackChannelPublic extends Model
{
    protected $table = 'slack_channel_public';

    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }
}
