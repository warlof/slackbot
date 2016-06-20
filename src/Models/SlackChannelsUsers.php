<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

class SlackChannelsUsers extends Model
{
    protected $table = 'slack_channels_users';

    protected $fillable = [
        'user_id', 'channel_id', 'enable'
    ];

    protected $primaryKey = [
        'user_id', 'channel_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }
}
