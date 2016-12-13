<?php

namespace Warlof\Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;

class SlackChannelPublic extends Model
{
    protected $primaryKey = 'channel_id';

    protected $fillable = ['channel_id', 'enable'];

    protected $table = 'slack_channel_public';

    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }
}
