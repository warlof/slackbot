<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationSheet;

class SlackChannelCorporation extends Model
{
    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(CorporationSheet::class, 'corporation_id', 'corporationID');
    }
}
