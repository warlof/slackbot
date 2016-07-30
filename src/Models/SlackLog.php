<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;

class SlackLog extends Model
{
    protected $fillable = [
        'event', 'message'
    ];

    protected $primaryKey = 'id';
}
