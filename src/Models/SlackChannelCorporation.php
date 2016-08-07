<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationSheet;

class SlackChannelCorporation extends Model
{
    protected $fillable = ['corporation_id', 'channel_id', 'enable'];

    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(CorporationSheet::class, 'corporation_id', 'corporationID');
    }
}
