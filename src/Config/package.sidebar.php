<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 20:01
 */

return [
    'slackbot' => [
        'permission'    => 'Superuser',
        'name'          => 'Slackbot',
        'icon'          => 'fa-slack',
        'route_segment' => 'slack-admin',
        'route'         => 'slack-admin.relations',
        'entries'       => [
            'name'  => 'Relations',
            'icon'  => 'fa-exchange',
            'route' => 'slack-admin.relations'
        ]
    ]
];
