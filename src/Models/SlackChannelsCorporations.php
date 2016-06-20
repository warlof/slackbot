<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

class SlackChannelsCorporations extends Model
{
    protected $table = 'slack_channels_corporations';

    protected $fillable = [
        'corporation_id', 'channel_id', 'enable'
    ];

    protected $primaryKey = [
        'corporation_id', 'channel_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }
}
