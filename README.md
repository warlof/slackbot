# slackbot
This plugin provide an extension to the standard SeAT character Job which handle automatic invitation to an existing Slack Team

[![Code Climate](https://codeclimate.com/github/warlof/slackbot/badges/gpa.svg)](https://codeclimate.com/github/warlof/slackbot)
[![License](https://poser.pugx.org/warlof/slackbot/license)](https://packagist.org/packages/warlof/slackbot)
[![Latest Unstable Version](https://poser.pugx.org/warlof/slackbot/v/unstable)](https://packagist.org/packages/warlof/slackbot)
[![Latest Stable Version](https://poser.pugx.org/warlof/slackbot/v/stable)](https://packagist.org/packages/warlof/slackbot)

# Setup

1. put your SeAT instance offline by running `php artisan down`
2. download the plugin by running `composer require warlof/slackbot`
3. add the plugin to your SeAT instance by editing the file in `config/app.php`
and append this line `Seat\Slackbot\SlackbotServiceProvider::class,` after `// Example\Pakage\ServiceProvider::class`
4. publish the plugin by running `php artisan vendor:publish --force`
5. update your database by running `php artisan migrate`
6. passing your SeAT instance online by running `php artisan up`
7. sign to the instance as superuser and go into `Slackbot > Slackbot Settings` in order to setup the plugin
8. Run slackbot service by running `php artisan slack:daemon:run` (it will run a permanent job)
9. go into `Slackbot > Slack Access Management` in order to configure user access