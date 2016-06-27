<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;

class SlackChannel extends Model
{
    protected $fillable = [
        'id', 'name', 'is_group'
    ];

    protected $primaryKey = 'id';
}
