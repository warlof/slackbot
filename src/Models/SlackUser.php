<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

class SlackUser extends Model
{
    protected $table = 'slack_user';

    protected $fillable = [
        'user_id', 'slack_id', 'invited'
    ];

    protected $primaryKey = [
        'user_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
