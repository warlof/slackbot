<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

class SlackChannelUser extends Model
{
    protected $fillable = ['user_id', 'channel_id', 'enable'];

    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
