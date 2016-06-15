<?php

namespace Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\Acl\Role;

class SlackRelations extends Model
{
    protected $table = 'slack_relations';

    protected $fillable = [
        'role_id', 'channel_id', 'status'
    ];

    protected $primaryKey = [
        'role_id', 'channel_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channels()
    {
        return $this->hasMany(SlackChannel::class, 'id', 'channel_id');
    }
}
