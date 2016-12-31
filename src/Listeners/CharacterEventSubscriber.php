<?php

namespace Warlof\Seat\Slackbot\Listeners;

class CharacterEventSubscriber
{
    public function call($event)
    {
        logger()->debug('event', $event);
    }

    public function subscribe($events)
    {
        $events->listen(
            'Seat\Eveapi\Api\Character\CharacterSheet@call'
        );
    }
}