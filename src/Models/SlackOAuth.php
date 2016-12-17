<?php

namespace Warlof\Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;

class SlackOAuth extends Model
{
    protected $table = 'slack_oauth';

    protected $fillable = [
        'client_id', 'client_secret', 'verification_token', 'state', 'access_token'
    ];

    protected $primaryKey = 'client_id';
}
