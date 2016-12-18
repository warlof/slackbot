<?php

namespace Warlof\Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

class SlackUser extends Model
{
    protected $fillable = [
        'user_id', 'slack_id'
    ];

    protected $primaryKey = 'user_id';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
