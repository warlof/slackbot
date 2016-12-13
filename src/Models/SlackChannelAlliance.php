<?php

namespace Warlof\Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Eve\AllianceList;

class SlackChannelAlliance extends Model
{
    protected $fillable = ['alliance_id', 'channel_id', 'enable'];

    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }

    public function alliance()
    {
        return $this->belongsTo(AllianceList::class, 'alliance_id', 'allianceID');
    }
}
