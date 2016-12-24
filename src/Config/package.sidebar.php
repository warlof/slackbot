<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 20:01
 */

return [
    'slackbot' => [
        'name'          => 'Slackbot',
        'icon'          => 'fa-slack',
        'route_segment' => 'slackbot',
        'entries' => [
            [
                'name'  => 'Slack Access Management',
                'icon'  => 'fa-shield',
                'route' => 'slackbot.list',
            ],
            [
                'name' => 'Slack User Mapping',
                'icon' => 'fa-exchange',
                'route' => 'slackbot.users'
            ],
            [
                'name'  => 'Slackbot Settings',
                'icon'  => 'fa-cogs',
                'route' => 'slackbot.configuration',
                'permission' => 'slackbot.setup'
            ],
            [
                'name'  => 'Slackbot Logs',
                'icon'  => 'fa-list',
                'route' => 'slackbot.logs'
            ]
        ],
        'permission' => 'slackbot.view'
    ]
];
