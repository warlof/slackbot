<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\Acl\Role;

class SlackChannelRole extends Model
{
    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
