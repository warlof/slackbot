<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

class SlackChannelsAlliances extends Model
{
    protected $table = 'slack_channels_alliances';

    protected $fillable = [
        'alliance_id', 'channel_id', 'enable'
    ];

    protected $primaryKey = [
        'alliance_id', 'channel_id'
    ];
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }
}
