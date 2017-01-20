<?php

namespace Warlof\Seat\Slackbot\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Corporation\Title;

class SlackChannelTitle extends Model
{
    protected $fillable = ['corporation_id', 'title_id', 'title_surrogate_key', 'channel_id', 'enable'];

    public static function create(array $attributes = [])
    {
        // search for primary key assigned to the surrogate key
        $title = Title::where('corporationID', $attributes['corporation_id'])
            ->where('titleID', $attributes['title_id'])
            ->first();

        $attributes['title_surrogate_key'] = $title->id;

        parent::create($attributes);
    }

    public function channel()
    {
        return $this->belongsTo(SlackChannel::class, 'channel_id', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(CorporationSheet::class, 'corporation_id', 'corporationID');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'title_surrogate_key', 'id');
    }
}
