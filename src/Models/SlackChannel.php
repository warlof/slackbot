<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;

class SlackChannel extends Model
{
    protected $fillable = [
        'id', 'name', 'is_group'
    ];

    protected $primaryKey = 'id';
}
