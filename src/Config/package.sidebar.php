<?php
/**
 * This file is part of seat-slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
