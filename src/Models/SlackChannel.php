<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\Acl\Role;

class SlackChannel extends Model
{
    protected $table = 'slack_channel';

    protected $fillable = [
        'id', 'name'
    ];

    protected $primaryKey = [
        'id'
    ];
}
