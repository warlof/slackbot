<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationSheet;

class SlackChannelAlliance extends Model
{
    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }

    public function alliance()
    {
        return $this->belongsTo(CorporationSheet::class, 'alliance_id', 'allianceID');
    }
}
