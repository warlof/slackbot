# slackbot
This plugin provide an extension to the standard SeAT character Job which handle automatic invitation to an existing Slack Team

[![Code Climate](https://codeclimate.com/github/warlof/slackbot/badges/gpa.svg)](https://codeclimate.com/github/warlof/slackbot)
[![License](https://poser.pugx.org/warlof/slackbot/license)](https://packagist.org/packages/warlof/slackbot)
[![Latest Unstable Version](https://poser.pugx.org/warlof/slackbot/v/unstable)](https://packagist.org/packages/warlof/slackbot)
[![Latest Stable Version](https://poser.pugx.org/warlof/slackbot/v/stable)](https://packagist.org/packages/warlof/slackbot)

# Setup

## Slack side
1. go on Slack Application page (https://api.slack.com/apps)
2. create a new application
  - bind it to your Slack team
  - set the redirect URI to `https://yourseat/slackbot/callback` where `yourseat` is your seat domain

## SeAT side
1. put your SeAT instance offline by running `php artisan down`
2. download the plugin by running `composer require warlof/slackbot`
3. add the plugin to your SeAT instance by editing the file in `config/app.php`
and append this line `Seat\Slackbot\SlackbotServiceProvider::class,` after `// Example\Pakage\ServiceProvider::class`
4. publish the plugin by running `php artisan vendor:publish --force`
5. update your database by running `php artisan migrate`
6. passing your SeAT instance online by running `php artisan up`
7. sign to the instance as superuser and go into `Slackbot > Slackbot Settings` in order to setup the plugin
9. go into `Slackbot > Slack Access Management` in order to configure user access

## Supervisor side
In order to keep your slack data up to date (like channels and groups), you need to run the slack:daemon:run
command with supervisor.

1. create a new supervisor configuration file or edit your seat supervisor configuration file
(located at /etc/supervisor/conf.d/seat.conf by default)
2. append the following text
```
[program:seat-slackbot]
command=/usr/bin/php /var/www/seat/artisan slack:daemon:run
process_name = %(program_name)s-80%(process_num)02d
stdout_logfile = /var/log/seat-80%(process_num)02d.log
stdout_logfile_maxbytes=100MB
stdout_logfile_backups=10
numprocs=1
directory=/var/www/seat
stopwaitsecs=600
user=www-data
```
3. restart the service by running `service supervisor restart`

# Available Commands
The slackbot is provided with some CLI command which enable you to get informations from an existing Slack Team.
- `slack:update:channels` this will pull all public and private channels into SeAT
- `slack:update:users` this will try to aggregate existing slack user to existing SeAT user based on user email.