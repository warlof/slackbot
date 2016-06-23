<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;

class SlackChannelCorporation extends Model
{
    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }
}
